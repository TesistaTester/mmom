<?php
namespace App\Livewire\Mmom;

use App\Models\MmomUser;
use App\Modules\Game\MmomReadService;
use App\Modules\Game\MmomDecisionService;
use App\Modules\Game\MmomGameService;
use App\Modules\Simulation\MmomRoundSimulationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/** UI is an adapter; mutations go through E2 services and their DB locks/authorization. */
final class GameConsole extends Component
{
    public string $gameId;
    public string $draftJson = '{"observacion":""}';
    public bool $confirmAcknowledged = false;
    public string $notice = '';
    public ?int $editedStage = null;
    public bool $isBusy = false;

    // Campos visuales interactivos para los participantes
    public int $formHire = 0;
    public int $formFire = 0;
    public int $formDrinks = 130;
    public int $formMixers = 100;
    public float $formTicket = 30.0;
    public float $formDrink = 35.0;
    public float $formCombo = 45.0;
    public float $formVip = 120.0;
    public float $formAdBudget = 800.0;
    public int $formCapacity = 0;
    public int $formVipTables = 0;
    public float $formResearchBudget = 200.0;
    public float $formArtistBudget = 800.0;

    public function mount(string $gameId, MmomReadService $reads): void
    {
        $this->gameId = $gameId;
        $snapshot = $reads->snapshot($this->actor(), $gameId);
        $this->loadDraft($snapshot);
    }

    private function actor(): MmomUser
    {
        $actor = Auth::user();
        abort_unless($actor instanceof MmomUser, 403);
        return $actor;
    }

    public function getStageTemplatePayload(int $stage): array
    {
        return match ($stage) {
            1 => [
                'hire' => 0,
                'fire' => 0,
                'observacion' => 'Personal base: 6 colaboradores. Nómina fija: Bs 550 c/u'
            ],
            2 => [
                'drinks' => 130,
                'mixers' => 100,
                'observacion' => 'Bebidas a Bs 12/u, Mixers a Bs 5/u. Consumo por CPP'
            ],
            3 => [
                'ticket' => 30.00,
                'drink' => 35.00,
                'combo' => 45.00,
                'vip' => 120.00,
                'observacion' => 'Precios de venta al público en barra, entrada y VIP'
            ],
            4 => [
                'budget' => 800.00,
                'observacion' => 'Presupuesto de publicidad y visibilidad de marca'
            ],
            5 => [
                'capacity' => 0,
                'vipTables' => 0,
                'observacion' => 'Bs 250 por plaza nueva, Bs 1.800 por mesa VIP (5 plazas)'
            ],
            6 => [
                'budget' => 200.00,
                'observacion' => 'Presupuesto de investigación e inteligencia de mercado'
            ],
            7 => [
                'budget' => 800.00,
                'observacion' => 'Contratación artística (habilitado desde ronda 2)'
            ],
            default => ['observacion' => 'Decisión de ronda']
        };
    }

    private function syncFormFromPayload(int $stage, array $payload): void
    {
        if (isset($payload['hire'])) $this->formHire = (int)$payload['hire'];
        if (isset($payload['fire'])) $this->formFire = (int)$payload['fire'];
        if (isset($payload['drinks'])) $this->formDrinks = (int)$payload['drinks'];
        if (isset($payload['mixers'])) $this->formMixers = (int)$payload['mixers'];
        if (isset($payload['ticket'])) $this->formTicket = (float)$payload['ticket'];
        if (isset($payload['drink'])) $this->formDrink = (float)$payload['drink'];
        if (isset($payload['combo'])) $this->formCombo = (float)$payload['combo'];
        if (isset($payload['vip'])) $this->formVip = (float)$payload['vip'];
        if (isset($payload['budget'])) {
            if ($stage === 4) $this->formAdBudget = (float)$payload['budget'];
            elseif ($stage === 6) $this->formResearchBudget = (float)$payload['budget'];
            elseif ($stage === 7) $this->formArtistBudget = (float)$payload['budget'];
        }
        if (isset($payload['capacity'])) $this->formCapacity = (int)$payload['capacity'];
        if (isset($payload['vipTables'])) $this->formVipTables = (int)$payload['vipTables'];
    }

    public function syncPayloadFromForm(): void
    {
        $stage = $this->editedStage ?: 1;
        $payload = match ($stage) {
            1 => ['hire' => (int)$this->formHire, 'fire' => (int)$this->formFire],
            2 => ['drinks' => (int)$this->formDrinks, 'mixers' => (int)$this->formMixers],
            3 => [
                'ticket' => (float)$this->formTicket,
                'drink' => (float)$this->formDrink,
                'combo' => (float)$this->formCombo,
                'vip' => (float)$this->formVip,
            ],
            4 => ['budget' => (float)$this->formAdBudget],
            5 => ['capacity' => (int)$this->formCapacity, 'vipTables' => (int)$this->formVipTables],
            6 => ['budget' => (float)$this->formResearchBudget],
            7 => ['budget' => (float)$this->formArtistBudget],
            default => ['observacion' => '']
        };
        $this->draftJson = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function updated($propertyName): void
    {
        if (str_starts_with($propertyName, 'form')) {
            $this->syncPayloadFromForm();
        }
    }

    private function loadDraft(array $snapshot): void
    {
        $stage = $snapshot['round']['current_stage'] ?? null;
        $this->editedStage = $stage ? (int)$stage : null;
        $currentPayload = $snapshot['current_draft']['payload'] ?? null;

        // Si no hay borrador previo, precargar automáticamente los valores recomendados
        if (!$currentPayload && $this->editedStage) {
            $currentPayload = $this->getStageTemplatePayload($this->editedStage);
        }

        if ($currentPayload && $this->editedStage) {
            $this->syncFormFromPayload($this->editedStage, $currentPayload);
            $this->draftJson = json_encode($currentPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            $this->draftJson = "{}";
        }

        $this->confirmAcknowledged = false;
    }

    public function refreshSnapshot(MmomReadService $reads): void
    {
        $snapshot = $reads->snapshot($this->actor(), $this->gameId);
        if ($this->editedStage !== ($snapshot['round']['current_stage'] ?? null)) {
            $this->loadDraft($snapshot);
        }
    }

    public function loadStageTemplate(int $stage): void
    {
        $payload = $this->getStageTemplatePayload($stage);
        $this->syncFormFromPayload($stage, $payload);
        $this->draftJson = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $this->notice = "📋 Parámetros de la Etapa {$stage} cargados con éxito.";
    }

    public function saveDraft(MmomReadService $reads, MmomDecisionService $decisions): void
    {
        if ($this->isBusy) return;
        $this->isBusy = true;
        $this->notice = '';

        try {
            $snapshot = $reads->snapshot($this->actor(), $this->gameId);
            $stage = $snapshot['round']['current_stage'] ?? null;
            if (!$snapshot['can_decide'] || !$stage || in_array($stage, $snapshot['confirmed_stages'], true)) {
                throw ValidationException::withMessages(['draftJson' => 'No está habilitada la edición de esta etapa.']);
            }
            if ($stage !== $this->editedStage) {
                throw ValidationException::withMessages(['draftJson' => 'La etapa cambió; actualice la pantalla.']);
            }

            // Sincronizar desde los campos si fuera necesario
            $this->syncPayloadFromForm();

            if (strlen($this->draftJson) > 32768) {
                throw ValidationException::withMessages(['draftJson' => 'El borrador supera el límite permitido de 32 KB.']);
            }
            try { 
                $decoded = json_decode($this->draftJson, true, 512, JSON_THROW_ON_ERROR); 
            } catch (\JsonException $exception) { 
                throw ValidationException::withMessages(['draftJson' => 'El formato no es válido.']); 
            }
            if (!is_array($decoded) || array_is_list($decoded)) {
                throw ValidationException::withMessages(['draftJson' => 'Se requiere un objeto estructurado con parámetros válidos.']);
            }

            $decisions->draft($this->actor(), $this->gameId, (int)$stage, $decoded);
            $this->notice = '✓ Borrador guardado correctamente. Marca la casilla y presiona Confirmar cuando estés listo.';
            $this->confirmAcknowledged = false;
        } catch (ValidationException $e) {
            $this->notice = '⚠️ ' . collect($e->errors())->flatten()->first();
        } catch (\Throwable $e) {
            $this->notice = '⚠️ Error al guardar borrador: ' . $e->getMessage();
        } finally {
            $this->isBusy = false;
        }
    }

    public function confirmDecision(MmomReadService $reads, MmomDecisionService $decisions): void
    {
        if ($this->isBusy) return;
        $this->isBusy = true;
        $this->notice = '';

        try {
            $snapshot = $reads->snapshot($this->actor(), $this->gameId);
            $stage = $snapshot['round']['current_stage'] ?? null;
            if (!$this->confirmAcknowledged || !$snapshot['can_decide'] || !$stage || $this->editedStage !== $stage
                || in_array($stage, $snapshot['confirmed_stages'], true)) {
                throw ValidationException::withMessages(['draftJson' => 'Debes marcar la casilla comprendiendo que la decisión es inmutable.']);
            }
            if (!$snapshot['current_draft']) {
                // Auto guardar el borrador antes de confirmar si no existía
                $this->syncPayloadFromForm();
                $decoded = json_decode($this->draftJson, true, 512, JSON_THROW_ON_ERROR);
                $decisions->draft($this->actor(), $this->gameId, (int)$stage, $decoded);
            }

            $decisions->confirm($this->actor(), $this->gameId, (int)$stage, (string)\Illuminate\Support\Str::uuid());
            $this->confirmAcknowledged = false;
            $this->notice = '🔒 ¡Decisión confirmada e inmutable! Ha quedado registrada formalmente para esta etapa.';
        } catch (ValidationException $e) {
            $this->notice = '⚠️ ' . collect($e->errors())->flatten()->first();
        } catch (\Throwable $e) {
            $this->notice = '⚠️ Error al confirmar la decisión: ' . $e->getMessage();
        } finally {
            $this->isBusy = false;
        }
    }

    public function prepareGame(MmomReadService $reads, MmomGameService $games): void
    {
        if ($this->isBusy) return;
        $this->isBusy = true;
        $this->notice = '';

        try {
            $snapshot = $reads->snapshot($this->actor(), $this->gameId);
            abort_unless($snapshot['can_manage'], 403);
            $games->prepare($this->actor(), $this->gameId);
            $this->notice = '✓ ¡Partida preparada con éxito! Las 7 discotecas están listas para competir.';
        } catch (\Throwable $e) {
            $this->notice = '⚠️ Error al preparar la partida: ' . $e->getMessage();
        } finally {
            $this->isBusy = false;
        }
    }

    public function startRound(int $roundNumber, MmomGameService $games): void
    {
        if ($this->isBusy) return;
        $this->isBusy = true;
        $this->notice = '';

        try {
            $games->startRound($this->actor(), $this->gameId, $roundNumber);
            $this->notice = "✓ ¡Ronda {$roundNumber} iniciada con éxito! El cronómetro de 20 minutos está activo.";
        } catch (\Throwable $e) {
            $this->notice = "⚠️ Error al iniciar la Ronda {$roundNumber}: " . $e->getMessage();
        } finally {
            $this->isBusy = false;
        }
    }

    public function startFirstRound(MmomGameService $games): void
    {
        $this->startRound(1, $games);
    }

    public function nextStage(MmomReadService $reads, MmomGameService $games): void
    {
        if ($this->isBusy) return;
        $this->isBusy = true;
        $this->notice = '';

        try {
            $snapshot = $reads->snapshot($this->actor(), $this->gameId);
            abort_unless($snapshot['can_manage'], 403);
            $games->advanceStage($this->actor(), $this->gameId);
            $this->notice = '✓ ¡Etapa avanzada exitosamente! Los participantes ya pueden formular la siguiente decisión.';
        } catch (\Throwable $e) {
            $this->notice = '⚠️ No se pudo avanzar la etapa: ' . $e->getMessage();
        } finally {
            $this->isBusy = false;
        }
    }

    public function autofillDemo(bool $allStages, MmomRoundSimulationService $sim): void
    {
        if ($this->isBusy) return;
        $this->isBusy = true;
        $this->notice = '';

        try {
            $count = $sim->autofillDemoDecisions($this->actor(), $this->gameId, $allStages);
            $this->notice = $allStages
                ? "⚡ ¡Decisiones de todas las etapas completadas y confirmadas para los 7 equipos ({$count} registros)! Ahora puedes simular y cerrar la ronda."
                : "⚡ ¡Decisiones de la etapa actual autocompletadas para los 7 equipos ({$count} registros)! Ya puedes avanzar de etapa.";
        } catch (\Throwable $e) {
            $this->notice = '⚠️ Error en autocompletado demo: ' . $e->getMessage();
        } finally {
            $this->isBusy = false;
        }
    }

    public function simulateRound(MmomRoundSimulationService $sim): void
    {
        if ($this->isBusy) return;
        $this->isBusy = true;
        $this->notice = '';

        try {
            $result = $sim->simulateAndCloseRound($this->actor(), $this->gameId);
            $rNum = $result['round'];
            $isFinal = $result['is_final'] ?? false;
            $this->notice = $isFinal
                ? "🏁 ¡Ronda {$rNum} procesada con éxito y Partida Finalizada! Revisa la pestaña de Resultados y la Clasificación Final Definitiva."
                : "✓ ¡Ronda {$rNum} simulada y cerrada exitosamente con MIFIV! Se actualizaron ingresos, gastos, préstamos PALO y ranking MIEC. Puedes iniciar la Ronda " . ($rNum + 1) . ".";
        } catch (\Throwable $e) {
            $this->notice = '⚠️ Error al simular la ronda: ' . $e->getMessage();
        } finally {
            $this->isBusy = false;
        }
    }

    public function render(MmomReadService $reads)
    {
        $snapshot = $reads->snapshot($this->actor(), $this->gameId);
        return view('livewire.mmom.game-console', ['snapshot' => $snapshot])->layout('mmom.layout');
    }
}
