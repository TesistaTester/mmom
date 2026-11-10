<?php
namespace App\Http\Controllers;

use App\Models\MmomUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

final class MmomExportController extends Controller
{
    public function export(Request $request, string $gameId)
    {
        $actor = $request->user();
        abort_unless($actor instanceof MmomUser, 403);

        $game = DB::table('game.games')->where('id', $gameId)->first();
        abort_unless($game !== null, 404);

        // Fetch catalog discotheques for this game
        $companies = DB::table('game.companies as c')
            ->join('catalog.discotheques as d', 'd.id', '=', 'c.discotheque_id')
            ->leftJoin('game.assignments as a', fn($j) => $j->on('a.company_id', '=', 'c.id')->where('a.is_current', true))
            ->leftJoin('identity.users as u', 'u.id', '=', 'a.user_id')
            ->where('c.game_id', $gameId)
            ->orderBy('d.code')
            ->get([
                'c.id as company_id',
                'd.code as club_code',
                'd.name as club_name',
                'u.display_name as player_name',
                'u.email as player_email'
            ]);

        // Fetch rounds
        $rounds = DB::table('game.rounds')
            ->where('game_id', $gameId)
            ->orderBy('round_number')
            ->get();

        // Fetch confirmed decisions
        $decisions = DB::table('game.confirmed_decisions as cd')
            ->join('game.companies as c', 'c.id', '=', 'cd.company_id')
            ->join('catalog.discotheques as d', 'd.id', '=', 'c.discotheque_id')
            ->where('cd.game_id', $gameId)
            ->orderBy('cd.round_number')
            ->orderBy('cd.stage')
            ->get([
                'cd.id',
                'cd.round_number',
                'cd.stage',
                'd.name as club_name',
                'cd.payload',
                'cd.payload_hash',
                'cd.confirmed_at'
            ]);

        // Fetch candidates and MIEC rankings
        $candidates = DB::table('analytics.mifiv_candidates')
            ->where('game_id', $gameId)
            ->orderBy('round_number')
            ->get();

        $scores = DB::table('analytics.miec_candidate_scores as s')
            ->join('game.companies as c', 'c.id', '=', 's.company_id')
            ->join('catalog.discotheques as d', 'd.id', '=', 'c.discotheque_id')
            ->where('s.game_id', $gameId)
            ->orderBy('s.rank')
            ->get([
                's.candidate_id',
                's.rank',
                'd.name as club_name',
                's.weighted_score',
                's.dimension_scores',
                's.verified_metrics'
            ]);

        // Fetch audit change events
        $audit = DB::table('audit.change_events')
            ->where('game_id', $gameId)
            ->orderBy('happened_at')
            ->get();

        $exportData = [
            'application' => 'MMOM - Strategic Business Simulator',
            'exported_at' => now('UTC')->toIso8601String(),
            'exported_by' => $actor->email,
            'game' => (array)$game,
            'companies' => $companies,
            'rounds' => $rounds,
            'decisions' => $decisions->map(function ($d) {
                $arr = (array)$d;
                $arr['payload'] = json_decode((string)$d->payload, true);
                return $arr;
            }),
            'candidates' => $candidates->map(function ($c) {
                $arr = (array)$c;
                $arr['metric_payload'] = json_decode((string)$c->metric_payload, true);
                $arr['validation_report'] = json_decode((string)$c->validation_report, true);
                return $arr;
            }),
            'miec_scores' => $scores->map(function ($s) {
                $arr = (array)$s;
                $arr['dimension_scores'] = json_decode((string)$s->dimension_scores, true);
                $arr['verified_metrics'] = json_decode((string)$s->verified_metrics, true);
                return $arr;
            }),
            'audit_events' => $audit->map(function ($a) {
                $arr = (array)$a;
                $arr['payload'] = json_decode((string)$a->payload, true);
                return $arr;
            }),
        ];

        $filename = 'mmom_partida_' . substr($gameId, 0, 8) . '_' . date('Ymd_His') . '.json';

        return response()->json($exportData, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store'
        ]);
    }
}
