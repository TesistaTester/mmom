<?php
namespace App\Http\Controllers;

use App\Models\MmomUser;
use App\Modules\Game\MmomGameService;
use App\Modules\Game\MmomDecisionService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

final class MmomE2Controller extends Controller
{
    private function user(Request $request): MmomUser
    {
        $user=$request->user();
        if (!$user instanceof MmomUser) abort(403,'El proveedor auth debe utilizar App\\Models\\MmomUser.');
        return $user;
    }
    public function create(Request $request,MmomGameService $service)
    {
        $v=$request->validate(['name'=>'required|string|max:150','initial_model'=>'required|in:A,B']);
        return response()->json(['game_id'=>$service->create($this->user($request),$v['name'],$v['initial_model'])],201);
    }
    public function assign(Request $request,string $gameId,MmomGameService $service)
    {
        $v=$request->validate(['company_id'=>'required|uuid','player_id'=>'required|uuid']);
        $service->assign($this->user($request),$gameId,$v['company_id'],$v['player_id']);
        return response()->json(['status'=>'assigned'],201);
    }
    public function prepare(Request $request,string $gameId,MmomGameService $service)
    {
        $service->prepare($this->user($request),$gameId);
        return response()->json(['status'=>'prepared']);
    }
    public function startRound(Request $request,string $gameId,MmomGameService $service)
    {
        $v=$request->validate(['round'=>'required|integer|min:1|max:8']);
        $service->startRound($this->user($request),$gameId,(int)$v['round']);
        return response()->json(['status'=>'active','round'=>$v['round']]);
    }
    public function nextStage(Request $request,string $gameId,MmomGameService $service)
    {
        return response()->json(['stage'=>$service->advanceStage($this->user($request),$gameId)]);
    }
    public function lockExpired(Request $request,string $gameId,MmomGameService $service)
    {
        return response()->json(['locked'=>$service->lockExpired($this->user($request),$gameId)]);
    }
    public function draft(Request $request,string $gameId,MmomDecisionService $service)
    {
        $v=$request->validate(['stage'=>'required|integer|min:1|max:7','payload'=>'required|array']);
        $id=$service->draft($this->user($request),$gameId,(int)$v['stage'],$v['payload']);
        return response()->json(['draft_id'=>$id,'status'=>'draft']);
    }
    public function confirm(Request $request,string $gameId,MmomDecisionService $service)
    {
        $v=$request->validate(['stage'=>'required|integer|min:1|max:7',
          'idempotency_key'=>'required|string|min:12|max:120']);
        $id=$service->confirm($this->user($request),$gameId,(int)$v['stage'],$v['idempotency_key']);
        return response()->json(['decision_id'=>$id,'status'=>'confirmed','effects_status'=>'deferred_E3_E6']);
    }
    public function clock(Request $request,string $gameId)
    {
        $user=$this->user($request);
        $authorized=DB::table('identity.user_roles')->where('user_id',$user->id)->where('role','admin')->exists()
          || DB::table('game.assignments')->where('game_id',$gameId)->where('user_id',$user->id)->where('is_current',true)->exists();
        if (!$user->is_active || !$authorized) abort(403);
        $game=DB::table('game.games')->where('id',$gameId)->first();
        if (!$game || !$game->current_round) abort(404);
        $round=DB::table('game.rounds')->where('game_id',$gameId)->where('round_number',$game->current_round)->first();
        if (!$round) abort(404);
        $remaining=($round->status==='active' && $round->deadline_at)
          ? max(0,now('UTC')->diffInSeconds(\Illuminate\Support\Carbon::parse($round->deadline_at),false)) : 0;
        return response()->json(['round'=>$game->current_round,'round_status'=>$round->status,
          'stage'=>$round->current_stage,'deadline_at'=>$round->deadline_at,
          'server_now'=>now('UTC')->toIso8601String(),'seconds_remaining'=>(int)$remaining]);
    }
}
