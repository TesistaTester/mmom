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
            $games=DB::table('game.games')->orderByDesc('created_at')->limit(100)->get(['id','name','status','current_round']);
        } else {
            $games=DB::table('game.games as g')
                ->join('game.assignments as a','a.game_id','=','g.id')
                ->where('a.user_id',$actor->id)->where('a.is_current',true)
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
        $snapshot=['game'=>(array)$game,'round'=>$round?(array)$round:null,
            'server_now'=>now('UTC')->toIso8601String(),'seconds_remaining'=>(int)$remaining,
            'can_manage'=>$isAdmin,'company_id'=>$companyId];
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
