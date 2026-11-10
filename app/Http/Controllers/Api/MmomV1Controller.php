<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MmomUser;
use App\Modules\Game\MmomReadService;
use App\Modules\Game\MmomDecisionService;
use Illuminate\Http\Request;

/** Session-authenticated JSON version 1: calls EXACT same application services as Livewire. */
final class MmomV1Controller extends Controller
{
    private function actor(Request $request): MmomUser
    {
        $actor=$request->user();
        abort_unless($actor instanceof MmomUser,403);
        return $actor;
    }
    public function index(Request $request,MmomReadService $reads)
    {
        return response()->json(['data'=>$reads->listGames($this->actor($request))]);
    }
    public function show(Request $request,string $gameId,MmomReadService $reads)
    {
        return response()->json(['data'=>$reads->snapshot($this->actor($request),$gameId)]);
    }
    public function draft(Request $request,string $gameId,MmomDecisionService $decisions)
    {
        $input=$request->validate(['stage'=>'required|integer|between:1,7','payload'=>'required|array']);
        $id=$decisions->draft($this->actor($request),$gameId,(int)$input['stage'],$input['payload']);
        return response()->json(['data'=>['draft_id'=>$id,'status'=>'draft']],201);
    }
    public function confirm(Request $request,string $gameId,MmomDecisionService $decisions)
    {
        $input=$request->validate(['stage'=>'required|integer|between:1,7',
           'idempotency_key'=>'required|string|min:12|max:120']);
        $id=$decisions->confirm($this->actor($request),$gameId,(int)$input['stage'],$input['idempotency_key']);
        return response()->json(['data'=>['decision_id'=>$id,'status'=>'confirmed','effects_status'=>'deferred_E3_E6']]);
    }
}
