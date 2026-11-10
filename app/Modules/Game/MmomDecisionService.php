<?php
namespace App\Modules\Game;

use App\Models\MmomUser;
use App\Modules\Audit\MmomAudit;
use App\Modules\Identity\MmomAuthorization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

final class MmomDecisionService
{
    public function __construct(private MmomAuthorization $auth,private MmomAudit $audit) {}

    private function authorizeStage(MmomUser $actor,string $gameId,int $stage): array
    {
        $companyId=$this->auth->requireAssignedCompany($actor,$gameId);
        $game=DB::table('game.games')->where('id',$gameId)->lockForUpdate()->first();
        if (!$game || $game->status!=='active' || !$game->current_round) throw ValidationException::withMessages(['game'=>'La partida no recibe decisiones.']);
        $round=DB::table('game.rounds')->where('game_id',$gameId)->where('round_number',$game->current_round)->lockForUpdate()->first();
        if (!$round || $round->status!=='active' || $round->current_stage!==$stage ||
           now('UTC')->greaterThanOrEqualTo(Carbon::parse($round->deadline_at)))
           throw ValidationException::withMessages(['stage'=>'La etapa no está activa o el plazo venció.']);
        if ($stage===7 && $game->current_round===1) throw ValidationException::withMessages(['stage'=>'Artistas no disponibles en ronda 1.']);
        return [$companyId,(int)$game->current_round];
    }
    public function draft(MmomUser $actor,string $gameId,int $stage,array $payload): string
    {
        return DB::transaction(function () use($actor,$gameId,$stage,$payload) {
            [$companyId,$round]=$this->authorizeStage($actor,$gameId,$stage);
            if (DB::table('game.confirmed_decisions')->where('game_id',$gameId)->where('company_id',$companyId)
              ->where('round_number',$round)->where('stage',$stage)->exists())
                throw ValidationException::withMessages(['decision'=>'Esta etapa ya fue confirmada.']);
            $existing=DB::table('game.decision_drafts')->where('game_id',$gameId)->where('company_id',$companyId)
              ->where('round_number',$round)->where('stage',$stage)->first();
            $encoded=json_encode($payload,JSON_THROW_ON_ERROR);
            if ($existing) {
              DB::table('game.decision_drafts')->where('id',$existing->id)->update(['payload'=>$encoded,'version'=>$existing->version+1,'updated_at'=>now(),'user_id'=>$actor->id]);
              $id=$existing->id;
            } else {
              $id=(string)Str::uuid();
              DB::table('game.decision_drafts')->insert(['id'=>$id,'game_id'=>$gameId,'company_id'=>$companyId,
                'round_number'=>$round,'stage'=>$stage,'user_id'=>$actor->id,'payload'=>$encoded]);
            }
            $this->audit->record($gameId,$actor->id,'decision.draft_saved',['stage'=>$stage,'round'=>$round]);
            return $id;
        },3);
    }
    public function confirm(MmomUser $actor,string $gameId,int $stage,string $key): string
    {
        return DB::transaction(function () use($actor,$gameId,$stage,$key) {
            [$companyId,$round]=$this->authorizeStage($actor,$gameId,$stage);
            $draft=DB::table('game.decision_drafts')->where('game_id',$gameId)->where('company_id',$companyId)
                ->where('round_number',$round)->where('stage',$stage)->lockForUpdate()->first();
            if (!$draft) throw ValidationException::withMessages(['draft'=>'Debe guardar y revisar un borrador antes de confirmar.']);
            $hash=hash('sha256',(string)$draft->payload);
            $existing=DB::table('game.confirmed_decisions')->where('game_id',$gameId)->where('company_id',$companyId)
                ->where('round_number',$round)->where('stage',$stage)->first();
            if ($existing) {
                if ($existing->idempotency_key===$key && hash_equals($existing->payload_hash,$hash)) return $existing->id;
                throw ValidationException::withMessages(['decision'=>'Decisión ya confirmada de forma inmutable.']);
            }
            $id=(string)Str::uuid();
            DB::table('game.confirmed_decisions')->insert(['id'=>$id,'game_id'=>$gameId,'company_id'=>$companyId,
              'round_number'=>$round,'stage'=>$stage,'user_id'=>$actor->id,'payload'=>$draft->payload,
              'payload_hash'=>$hash,'idempotency_key'=>$key,'confirmed_at'=>now()]);
            $this->audit->record($gameId,$actor->id,'decision.confirmed',['decision_id'=>$id,'round'=>$round,'stage'=>$stage]);
            return $id;
        },3);
    }
}
