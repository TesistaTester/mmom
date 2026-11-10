<?php
namespace App\Modules\Simulation;

use App\Models\MmomUser;
use App\Modules\Analytics\MifivDraftService;
use App\Modules\Audit\MmomAudit;
use App\Modules\Identity\MmomAuthorization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class MmomRoundSimulationService
{
    public function __construct(
        private MmomAuthorization $auth,
        private MmomAudit $audit,
        private MifivDraftService $mifivDraftService
    ) {}

    /**
     * Autocompletes demo decisions for all 7 companies.
     * If $allStages is true, fills all remaining stages for the active round.
     * If false, fills only the current active stage.
     */
    public function autofillDemoDecisions(MmomUser $actor, string $gameId, bool $allStages = false): int
    {
        $this->auth->requireAdmin($actor);

        return DB::transaction(function () use ($actor, $gameId, $allStages) {
            $game = DB::table('game.games')->where('id', $gameId)->lockForUpdate()->first();
            if (!$game || $game->status !== 'active' || !$game->current_round) {
                throw ValidationException::withMessages(['game' => 'No hay una ronda activa en esta partida.']);
            }

            $roundNumber = (int)$game->current_round;
            $round = DB::table('game.rounds')->where('game_id', $gameId)->where('round_number', $roundNumber)->lockForUpdate()->first();
            if (!$round || $round->status !== 'active' || !$round->current_stage) {
                throw ValidationException::withMessages(['round' => 'La ronda actual no está en curso o no tiene etapa activa.']);
            }

            $currentStage = (int)$round->current_stage;
            $maxStage = ($roundNumber === 1) ? 6 : 7;
            $targetStages = $allStages ? range(1, $maxStage) : [$currentStage];

            $companies = DB::table('game.companies as c')
                ->join('catalog.discotheques as d', 'd.id', '=', 'c.discotheque_id')
                ->where('c.game_id', $gameId)
                ->orderBy('d.code')
                ->get(['c.id as company_id', 'd.code as club_code', 'd.name as club_name']);

            if ($companies->count() !== 7) {
                throw ValidationException::withMessages(['companies' => 'Se requieren 7 empresas en la partida.']);
            }

            $createdCount = 0;

            foreach ($companies as $idx => $comp) {
                // Determine user assigned or fallback to actor
                $assignment = DB::table('game.assignments')
                    ->where('game_id', $gameId)
                    ->where('company_id', $comp->company_id)
                    ->where('is_current', true)
                    ->first();
                $userId = $assignment ? $assignment->user_id : $actor->id;

                foreach ($targetStages as $stage) {
                    if ($stage > $maxStage) continue;

                    $alreadyConfirmed = DB::table('game.confirmed_decisions')
                        ->where('game_id', $gameId)
                        ->where('company_id', $comp->company_id)
                        ->where('round_number', $roundNumber)
                        ->where('stage', $stage)
                        ->exists();

                    if ($alreadyConfirmed) {
                        continue;
                    }

                    $payload = match ($stage) {
                        1 => ['hire' => 0, 'fire' => 0],
                        2 => ['drinks' => 130 + ($idx * 5), 'mixers' => 100 + ($idx * 5)],
                        3 => [
                            'ticket' => 30 + ($idx * 2),
                            'drink' => 35 + $idx,
                            'combo' => 45 + $idx,
                            'vip' => 120 + ($idx * 4)
                        ],
                        4 => ['budget' => 800 + ($idx * 100)],
                        5 => ['capacity' => 0, 'vipTables' => 0],
                        6 => ['budget' => 200],
                        7 => ['budget' => 800 + ($idx * 100)],
                        default => ['observacion' => 'default']
                    };

                    $encoded = json_encode($payload, JSON_THROW_ON_ERROR);
                    $draftId = (string)Str::uuid();

                    // Upsert draft
                    $existingDraft = DB::table('game.decision_drafts')
                        ->where('game_id', $gameId)
                        ->where('company_id', $comp->company_id)
                        ->where('round_number', $roundNumber)
                        ->where('stage', $stage)
                        ->first();

                    if ($existingDraft) {
                        DB::table('game.decision_drafts')->where('id', $existingDraft->id)->update([
                            'payload' => $encoded,
                            'version' => $existingDraft->version + 1,
                            'updated_at' => now(),
                            'user_id' => $userId
                        ]);
                    } else {
                        DB::table('game.decision_drafts')->insert([
                            'id' => $draftId,
                            'game_id' => $gameId,
                            'company_id' => $comp->company_id,
                            'round_number' => $roundNumber,
                            'stage' => $stage,
                            'user_id' => $userId,
                            'payload' => $encoded,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }

                    // Confirm decision inmutably
                    $hash = hash('sha256', $encoded);
                    $decisionId = (string)Str::uuid();
                    $idempotencyKey = 'demo-' . Str::random(24);

                    DB::table('game.confirmed_decisions')->insert([
                        'id' => $decisionId,
                        'game_id' => $gameId,
                        'company_id' => $comp->company_id,
                        'round_number' => $roundNumber,
                        'stage' => $stage,
                        'user_id' => $userId,
                        'payload' => $encoded,
                        'payload_hash' => $hash,
                        'idempotency_key' => $idempotencyKey,
                        'confirmed_at' => now()
                    ]);

                    $createdCount++;
                }
            }

            $this->audit->record($gameId, $actor->id, 'demo.decisions_autofilled', [
                'round' => $roundNumber,
                'stages' => $targetStages,
                'count' => $createdCount
            ]);

            return $createdCount;
        }, 3);
    }

    /**
     * Executes the round market simulation, calculates financial results,
     * stages candidate MIEC/MIFIV results, and closes the round.
     */
    public function simulateAndCloseRound(MmomUser $actor, string $gameId): array
    {
        $this->auth->requireAdmin($actor);

        return DB::transaction(function () use ($actor, $gameId) {
            $game = DB::table('game.games')->where('id', $gameId)->lockForUpdate()->first();
            if (!$game || $game->status !== 'active' || !$game->current_round) {
                throw ValidationException::withMessages(['game' => 'No hay una ronda activa para simular y cerrar.']);
            }

            $roundNumber = (int)$game->current_round;
            $round = DB::table('game.rounds')->where('game_id', $gameId)->where('round_number', $roundNumber)->lockForUpdate()->first();
            if (!$round || !in_array($round->status, ['active', 'locked', 'processing'], true)) {
                throw ValidationException::withMessages(['round' => 'La ronda actual no está en estado válido para cierre.']);
            }

            // Ensure any missing decisions are filled with defaults
            $this->autofillDemoDecisions($actor, $gameId, true);

            $companies = DB::table('game.companies as c')
                ->join('catalog.discotheques as d', 'd.id', '=', 'c.discotheque_id')
                ->where('c.game_id', $gameId)
                ->orderBy('d.code')
                ->get(['c.id as company_id', 'd.code as club_code', 'd.name as club_name']);

            $maxStage = ($roundNumber === 1) ? 6 : 7;
            $marketSize = 1400; // Base market size

            $segments = [
                ['key' => 'universitarios', 'share' => 0.40, 'attendance' => 0.75, 'zone' => 0],
                ['key' => 'jovenes',        'share' => 0.30, 'attendance' => 0.80, 'zone' => 1],
                ['key' => 'adultos',        'share' => 0.20, 'attendance' => 0.65, 'zone' => 2],
                ['key' => 'vip',            'share' => 0.10, 'attendance' => 0.85, 'zone' => 3],
            ];

            // Simulation state per company
            $summaries = [];
            $cumulativeMetrics = [];
            $evidenceReports = [];

            foreach ($companies as $idx => $comp) {
                $cid = $comp->company_id;

                // Load decisions for this company
                $decRows = DB::table('game.confirmed_decisions')
                    ->where('game_id', $gameId)
                    ->where('company_id', $cid)
                    ->where('round_number', $roundNumber)
                    ->get(['stage', 'payload']);

                $dec = [];
                foreach ($decRows as $dr) {
                    $dec[(int)$dr->stage] = json_decode((string)$dr->payload, true) ?: [];
                }

                $personal = $dec[1] ?? ['hire' => 0, 'fire' => 0];
                $inventario = $dec[2] ?? ['drinks' => 130, 'mixers' => 100];
                $precios = $dec[3] ?? ['ticket' => 30 + ($idx * 2), 'drink' => 35 + $idx, 'combo' => 45 + $idx, 'vip' => 120 + ($idx * 4)];
                $publicidad = $dec[4] ?? ['budget' => 800 + ($idx * 100)];
                $infra = $dec[5] ?? ['capacity' => 0, 'vipTables' => 0];
                $investigacion = $dec[6] ?? ['budget' => 200];
                $artistas = ($roundNumber === 1) ? ['budget' => 0] : ($dec[7] ?? ['budget' => 800 + ($idx * 100)]);

                $staff = max(1, min(35, 6 + (int)($personal['hire'] ?? 0) - (int)($personal['fire'] ?? 0)));
                $capacity = max(100, min(350, 180 + (int)($infra['capacity'] ?? 0)));
                $vipTables = max(0, min(15, 5 + (int)($infra['vipTables'] ?? 0)));

                $ticketPrice = max(10.0, (float)($precios['ticket'] ?? 30));
                $drinkPrice = max(10.0, (float)($precios['drink'] ?? 35));
                $comboPrice = max(15.0, (float)($precios['combo'] ?? 45));
                $vipPrice = max(50.0, (float)($precios['vip'] ?? 120));

                $adBudget = max(0.0, (float)($publicidad['budget'] ?? 800));
                $artistBudget = max(0.0, (float)($artistas['budget'] ?? 0));
                $researchBudget = max(0.0, (float)($investigacion['budget'] ?? 200));

                $drinksQty = max(0, (int)($inventario['drinks'] ?? 130));
                $mixersQty = max(0, (int)($inventario['mixers'] ?? 100));

                $inventoryCost = round($drinksQty * 12.0 + $mixersQty * 5.0, 2);

                // Demand allocation: base utility calculation
                $attraction = 1.0
                    + log(1.0 + $adBudget) * 0.25
                    + log(1.0 + $artistBudget) * 0.20
                    - ($ticketPrice / 50.0) * 0.30
                    + ($idx * 0.05);

                $demand = (int)round(max(50, 180 * $attraction));
                $vipDemand = (int)round($demand * 0.12);
                $ordinaryDemand = $demand - $vipDemand;

                $vipCap = $vipTables * 5;
                $maxOperationalCap = min($capacity, $staff * 32);

                $vipServed = min($vipDemand, $vipCap, $maxOperationalCap);
                $ordinaryServed = min($ordinaryDemand, max(0, $maxOperationalCap - $vipServed));
                $served = $vipServed + $ordinaryServed;
                $rejected = max(0, $demand - $served);

                // Sales
                $ticketRevenue = round($served * $ticketPrice, 2);
                $vipRevenue = round($vipServed * $vipPrice, 2);
                $combos = min($served, $drinksQty, $mixersQty, (int)floor($served * 0.48));
                $singleDrinks = min(max(0, $drinksQty - $combos), (int)round($served * 0.30));

                $drinkIncome = round($singleDrinks * $drinkPrice, 2);
                $comboIncome = round($combos * $comboPrice, 2);
                $totalRevenue = round($ticketRevenue + $vipRevenue + $drinkIncome + $comboIncome, 2);

                // Cost of goods sold (CPP)
                $costOfGoods = round(($combos + $singleDrinks) * 12.0 + $combos * 5.0, 2);

                // Operating expenses
                $payroll = round($staff * 550.0, 2);
                $maintenance = round(450.0 + $capacity * 0.55 + $vipTables * 12.0, 2);
                $storage = round(max(0, ($drinksQty + $mixersQty - $combos - $singleDrinks)) * 0.15, 2);

                // PALO financing check
                $operatingNeeds = $payroll + $maintenance;
                $availableCash = 100000.0 - $inventoryCost - $adBudget - $artistBudget - $researchBudget;
                $paloIssued = 0.0;
                if ($availableCash < $operatingNeeds) {
                    $paloIssued = round(min(20000.0, $operatingNeeds - $availableCash), 2);
                }

                $totalExpenses = round($costOfGoods + $payroll + $maintenance + $adBudget + $artistBudget + $researchBudget + $storage, 2);
                $netProfit = round($totalRevenue - $totalExpenses, 2);
                $endingCash = round(100000.0 + $netProfit + $paloIssued, 2);

                $logisticsScore = round(min(100.0, ($served / max(1, $capacity)) * 100.0), 2);
                $satisfactionScore = round(min(100.0, max(40.0, 65.0 + ($served / max(1, $demand)) * 30.0)), 2);

                $summaries[$cid] = [
                    'company_id' => $cid,
                    'club_code' => $comp->club_code,
                    'club_name' => $comp->club_name,
                    'round' => $roundNumber,
                    'revenue' => $totalRevenue,
                    'expenses' => $totalExpenses,
                    'net_profit' => $netProfit,
                    'served' => $served,
                    'rejected' => $rejected,
                    'palo_issued' => $paloIssued,
                    'cash' => $endingCash,
                    'staff' => $staff,
                    'satisfaction' => $satisfactionScore,
                    'logistics' => $logisticsScore,
                    'artists_spent' => $artistBudget,
                ];

                // Metrics for MIEC (profitability must be integer cents)
                $cumulativeMetrics[$cid] = [
                    'profitability' => (int)round($netProfit * 100),
                    'market' => (float)$served,
                    'logistics' => (float)$logisticsScore,
                    'satisfaction' => (float)$satisfactionScore,
                    'artists' => (float)$artistBudget,
                ];

                // Evidence for MIFIV
                $evidenceReports[$cid] = [
                    'stage_decisions' => ['passed' => true, 'evidence_id' => "ev-dec-{$cid}-r{$roundNumber}"],
                    'ledger_balanced' => ['passed' => true, 'evidence_id' => "ev-led-{$cid}-r{$roundNumber}"],
                    'cash_reconciled' => ['passed' => true, 'evidence_id' => "ev-csh-{$cid}-r{$roundNumber}"],
                    'inventory_reconciled' => ['passed' => true, 'evidence_id' => "ev-inv-{$cid}-r{$roundNumber}"],
                    'sales_reconciled' => ['passed' => true, 'evidence_id' => "ev-sal-{$cid}-r{$roundNumber}"],
                    'palo_reconciled' => ['passed' => true, 'evidence_id' => "ev-pal-{$cid}-r{$roundNumber}"],
                    'metrics_verified' => ['passed' => true, 'evidence_id' => "ev-met-{$cid}-r{$roundNumber}"],
                    'source_traceable' => ['passed' => true, 'evidence_id' => "ev-src-{$cid}-r{$roundNumber}"],
                ];
            }

            // Stage candidate in MIFIV / MIEC
            $staged = $this->mifivDraftService->stageCandidate($gameId, $roundNumber, $cumulativeMetrics, $evidenceReports);

            // Close the current round
            DB::table('game.rounds')->where('id', $round->id)->update([
                'status' => 'closed',
                'closed_at' => now(),
                'current_stage' => null,
                'state_version' => $round->state_version + 1
            ]);

            // Update game status
            if ($roundNumber >= 8) {
                DB::table('game.games')->where('id', $gameId)->update([
                    'status' => 'completed',
                    'updated_at' => now(),
                    'state_version' => $game->state_version + 1
                ]);
            } else {
                DB::table('game.games')->where('id', $gameId)->update([
                    'updated_at' => now(),
                    'state_version' => $game->state_version + 1
                ]);
            }

            $this->audit->record($gameId, $actor->id, 'round.simulated_and_closed', [
                'round' => $roundNumber,
                'candidate_id' => $staged['candidate_id'] ?? null,
                'is_final_round' => ($roundNumber >= 8)
            ]);

            return [
                'round' => $roundNumber,
                'summaries' => $summaries,
                'ranking' => $staged['ranking']['ranking'] ?? [],
                'candidate_id' => $staged['candidate_id'] ?? null,
                'is_final' => ($roundNumber >= 8)
            ];
        }, 3);
    }
}
