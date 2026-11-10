<?php
namespace App\Modules\Game;

use App\Models\MmomUser;
use App\Modules\Identity\MmomAuthorization;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/** Shared read-only application service: used by HTTP JSON and Livewire. */
final class MmomReadService
{
    public function __construct(private MmomAuthorization $authorization) {}

    public function listGames(MmomUser $actor): array
    {
        if (!$actor->is_active) throw new AuthorizationException();
        $admin = DB::table('identity.user_roles')->where('user_id',$actor->id)->where('role','admin')->exists();
        if ($admin) {
            $this->authorization->requireAdmin($actor);
            $games=DB::table('game.games')
                ->where('status', '!=', 'archived')
                ->orderByDesc('created_at')
                ->limit(100)
                ->get(['id','name','status','current_round']);
        } else {
            $games=DB::table('game.games as g')
                ->join('game.assignments as a','a.game_id','=','g.id')
                ->where('a.user_id',$actor->id)->where('a.is_current',true)
                ->where('g.status', '!=', 'archived')
                ->orderByDesc('g.created_at')->limit(100)->get(['g.id','g.name','g.status','g.current_round']);
        }
        return $games->map(fn($g)=>(array)$g)->all();
    }

    public function snapshot(MmomUser $actor,string $gameId): array
    {
        if (!$actor->is_active) throw new AuthorizationException();
        $isAdmin=DB::table('identity.user_roles')->where('user_id',$actor->id)->where('role','admin')->exists();
        $companyId=null;
        if ($isAdmin) {
            $this->authorization->requireAdmin($actor);
        } else {
            $companyId=$this->authorization->requireAssignedCompany($actor,$gameId);
        }
        $game=DB::table('game.games')->where('id',$gameId)->first(['id','name','status','current_round','round_count','round_minutes']);
        if (!$game) abort(404);
        $round=null;
        if ($game->current_round) {
            $round=DB::table('game.rounds')->where('game_id',$gameId)->where('round_number',$game->current_round)
                ->first(['round_number','status','current_stage','deadline_at']);
        }
        $remaining=0;
        if ($round && $round->status==='active' && $round->deadline_at) {
            $remaining=max(0,now('UTC')->diffInSeconds(Carbon::parse($round->deadline_at),false));
        }
        // Companies progress in current round
        $currentRoundNum = (int)($game->current_round ?: 1);
        $confirmedCounts = DB::table('game.confirmed_decisions')
            ->where('game_id', $gameId)
            ->where('round_number', $currentRoundNum)
            ->groupBy('company_id')
            ->select('company_id', DB::raw('count(distinct stage) as confirmed_count'))
            ->pluck('confirmed_count', 'company_id');

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
            ])
            ->map(function ($c) use ($confirmedCounts) {
                $cArr = (array)$c;
                $cArr['confirmed_stages_count'] = (int)($confirmedCounts[$c->company_id] ?? 0);
                return $cArr;
            })
            ->all();

        // Latest MIFIV candidate and MIEC ranking scores
        $latestCandidate = DB::table('analytics.mifiv_candidates')
            ->where('game_id', $gameId)
            ->orderByDesc('round_number')
            ->first();

        $miecRanking = [];
        $lastRoundResults = [];
        if ($latestCandidate) {
            $scores = DB::table('analytics.miec_candidate_scores as s')
                ->join('game.companies as c', 'c.id', '=', 's.company_id')
                ->join('catalog.discotheques as d', 'd.id', '=', 'c.discotheque_id')
                ->where('s.candidate_id', $latestCandidate->id)
                ->orderBy('s.rank')
                ->get([
                    's.rank',
                    'd.name as club_name',
                    's.weighted_score',
                    's.dimension_scores',
                    's.verified_metrics',
                    'c.id as company_id'
                ]);

            foreach ($scores as $s) {
                $dims = json_decode((string)$s->dimension_scores, true) ?: [];
                $metrics = json_decode((string)$s->verified_metrics, true) ?: [];
                $miecRanking[] = [
                    'rank' => (int)$s->rank,
                    'club_name' => $s->club_name,
                    'company_id' => $s->company_id,
                    'weighted_score' => round((float)$s->weighted_score, 2),
                    'profit_score' => round((float)($dims['profitability'] ?? 0), 1),
                    'market_score' => round((float)($dims['market'] ?? 0), 1),
                    'logistics_score' => round((float)($dims['logistics'] ?? 0), 1),
                    'satisfaction_score' => round((float)($dims['satisfaction'] ?? 0), 1),
                    'artist_score' => round((float)($dims['artists'] ?? 0), 1),
                ];
            }
        }

        // Load operational & financial summaries from simulation.engine_runs if available
        $latestRun = DB::table('simulation.engine_runs')
            ->where('game_id', $gameId)
            ->orderByDesc('round_number')
            ->first();

        if ($latestRun) {
            $parsedRun = json_decode((string)$latestRun->output_data, true);
            if (is_array($parsedRun) && !empty($parsedRun)) {
                $lastRoundResults = $parsedRun;
            }
        }

        $allRounds = DB::table('game.rounds')
            ->where('game_id', $gameId)
            ->orderBy('round_number')
            ->get(['round_number', 'status', 'starts_at', 'closed_at'])
            ->map(fn($r) => (array)$r)
            ->all();

        $snapshot = [
            'game' => (array)$game,
            'round' => $round ? (array)$round : null,
            'all_rounds' => $allRounds,
            'companies' => $companies,
            'miec_ranking' => $miecRanking,
            'last_round_results' => $lastRoundResults,
            'candidate_round' => $latestCandidate ? (int)$latestCandidate->round_number : null,
            'server_now' => now('UTC')->toIso8601String(),
            'seconds_remaining' => (int)$remaining,
            'can_manage' => $isAdmin,
            'company_id' => $companyId
        ];
        if ($companyId && $round) {
            $stage=(int)($round->current_stage ?? 0);
            $snapshot['can_decide']=$round->status==='active' && $remaining>0 && $stage>=1 && $stage<=7
               && !($game->current_round===1 && $stage===7);
            $snapshot['confirmed_stages']=DB::table('game.confirmed_decisions')
                ->where('game_id',$gameId)->where('company_id',$companyId)
                ->where('round_number',$game->current_round)->pluck('stage')->map(fn($v)=>(int)$v)->all();
            if ($stage>0) {
                $draft=DB::table('game.decision_drafts')->where('game_id',$gameId)->where('company_id',$companyId)
                    ->where('round_number',$game->current_round)->where('stage',$stage)->first(['payload','version']);
                $snapshot['current_draft']=$draft?['payload'=>json_decode((string)$draft->payload,true,512,JSON_THROW_ON_ERROR),'version'=>$draft->version]:null;
            } else $snapshot['current_draft']=null;
        } else {
            $snapshot['can_decide']=false;
            $snapshot['confirmed_stages']=[];
            $snapshot['current_draft']=null;
        }
        return $snapshot;
    }
}
