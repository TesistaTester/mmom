<?php
namespace App\Modules\Game;

use App\Models\MmomUser;
use App\Modules\Identity\MmomAuthorization;
use App\Modules\Audit\MmomAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon;

final class MmomGameService
{
    public function __construct(private MmomAuthorization $auth,private MmomAudit $audit) {}

    public function create(MmomUser $actor,string $name,string $initialModel): string
    {
        $this->auth->requireAdmin($actor);
        if (!in_array($initialModel,['A','B'],true)) throw ValidationException::withMessages(['initial_model'=>'Modalidad inicial inválida.']);
        return DB::transaction(function () use($actor,$name,$initialModel) {
            $id=(string)Str::uuid();
            DB::table('game.games')->insert(['id'=>$id,'name'=>$name,'initial_model'=>$initialModel,
              'created_by'=>$actor->id,'status'=>'draft','reference_capital'=>100000,
              'round_count'=>8,'round_minutes'=>20,'config_version'=>1,'state_version'=>1,
              'created_at'=>now(),'updated_at'=>now()]);
            $catalog=DB::table('catalog.discotheques')->orderBy('code')->get(['id']);
            if ($catalog->count()!==7) throw ValidationException::withMessages(['catalog'=>'El catálogo debe tener exactamente siete discotecas.']);
            foreach ($catalog as $club) DB::table('game.companies')->insert([
              'id'=>(string)Str::uuid(),'game_id'=>$id,'discotheque_id'=>$club->id]);
            $this->audit->record($id,$actor->id,'game.created',['initial_model'=>$initialModel]);
            return $id;
        },3);
    }

    public function assign(MmomUser $actor,string $gameId,string $companyId,string $playerId): void
    {
        $this->auth->requireAdmin($actor);
        DB::transaction(function () use($actor,$gameId,$companyId,$playerId) {
            $game=DB::table('game.games')->where('id',$gameId)->lockForUpdate()->first();
            if (!$game || $game->status!=='draft') throw ValidationException::withMessages(['game'=>'Las asignaciones ordinarias solo se modifican en borrador.']);
            if (!DB::table('game.companies')->where('game_id',$gameId)->where('id',$companyId)->exists())
              throw ValidationException::withMessages(['company'=>'Empresa ajena a la partida.']);
            if (!DB::table('identity.users')->where('id',$playerId)->where('is_active',true)->exists() ||
                !DB::table('identity.user_roles')->where('user_id',$playerId)->where('role','player')->exists())
              throw ValidationException::withMessages(['player'=>'Jugador inexistente, inactivo o sin rol player.']);
            if (DB::table('game.assignments')->where('game_id',$gameId)->where('is_current',true)
               ->where(fn($q)=>$q->where('company_id',$companyId)->orWhere('user_id',$playerId))->exists())
               throw ValidationException::withMessages(['assignment'=>'Empresa o jugador ya asignado en esta partida.']);
            DB::table('game.assignments')->insert(['id'=>(string)Str::uuid(),'game_id'=>$gameId,
              'company_id'=>$companyId,'user_id'=>$playerId,'is_current'=>true,'assigned_at'=>now()]);
            $this->audit->record($gameId,$actor->id,'assignment.created',['company_id'=>$companyId,'user_id'=>$playerId]);
        },3);
    }

    public function prepare(MmomUser $actor,string $gameId): void
    {
        $this->auth->requireAdmin($actor);
        DB::transaction(function () use($actor,$gameId) {
            $game=DB::table('game.games')->where('id',$gameId)->lockForUpdate()->first();
            if (!$game || $game->status!=='draft') throw ValidationException::withMessages(['game'=>'Solo se prepara una partida en borrador.']);
            $companies=DB::table('game.companies')->where('game_id',$gameId)->count();
            $assignments=DB::table('game.assignments')->where('game_id',$gameId)->where('is_current',true)->count();
            if ($companies!==7 || $assignments!==7) throw ValidationException::withMessages(['assignments'=>'Se requieren siete empresas y siete asignaciones activas.']);
            $segments=DB::table('catalog.consumer_segments')->get(['share_percent']);
            if ($segments->count()!==4 || round((float)$segments->sum('share_percent'),2)!==100.00)
              throw ValidationException::withMessages(['segments'=>'Los cuatro segmentos deben sumar 100 %.']);
            DB::table('game.games')->where('id',$gameId)->update(['status'=>'prepared','config_frozen_at'=>now(),
              'state_version'=>$game->state_version+1,'updated_at'=>now()]);
            for($r=1;$r<=8;$r++) DB::table('game.rounds')->insert(['id'=>(string)Str::uuid(),
              'game_id'=>$gameId,'round_number'=>$r,'status'=>'pending','created_at'=>now()]);
            $this->audit->record($gameId,$actor->id,'game.prepared',['assignments'=>7,'rounds'=>8]);
        },3);
    }

    public function startRound(MmomUser $actor,string $gameId,int $roundNumber): void
    {
        $this->auth->requireAdmin($actor);
        DB::transaction(function () use($actor,$gameId,$roundNumber) {
            $game=DB::table('game.games')->where('id',$gameId)->lockForUpdate()->first();
            if (!$game || $game->status==='draft' || !($roundNumber>=1 && $roundNumber<=8))
              throw ValidationException::withMessages(['round'=>'Partida o ronda inválida.']);
            if ($roundNumber!==1) throw ValidationException::withMessages(['round'=>'E2 solo habilita la apertura de ronda 1; las posteriores dependen del cierre MIFIV (E6).']);
            if ($game->status!=='prepared') throw ValidationException::withMessages(['game'=>'Solo se inicia una partida preparada.']);
            $round=DB::table('game.rounds')->where('game_id',$gameId)->where('round_number',1)->lockForUpdate()->first();
            if (!$round || $round->status!=='pending') throw ValidationException::withMessages(['round'=>'Ronda no disponible.']);
            $start=now('UTC'); $deadline=$start->copy()->addMinutes(20);
            DB::table('game.rounds')->where('id',$round->id)->update(['status'=>'active','starts_at'=>$start,
              'deadline_at'=>$deadline,'current_stage'=>1,'state_version'=>$round->state_version+1]);
            DB::table('game.games')->where('id',$gameId)->update(['status'=>'active','current_round'=>1,
              'state_version'=>$game->state_version+1,'updated_at'=>now()]);
            $this->audit->record($gameId,$actor->id,'round.started',['round'=>1,'deadline'=>$deadline->toIso8601String()]);
        },3);
    }

    public function advanceStage(MmomUser $actor,string $gameId): int
    {
        $this->auth->requireAdmin($actor);
        return DB::transaction(function () use($actor,$gameId) {
            $game=DB::table('game.games')->where('id',$gameId)->lockForUpdate()->first();
            if (!$game || $game->status!=='active' || !$game->current_round) throw ValidationException::withMessages(['game'=>'No hay ronda activa.']);
            $round=DB::table('game.rounds')->where('game_id',$gameId)->where('round_number',$game->current_round)->lockForUpdate()->first();
            if (!$round || $round->status!=='active' || now('UTC')->greaterThanOrEqualTo(Carbon::parse($round->deadline_at)))
              throw ValidationException::withMessages(['round'=>'Plazo de decisiones finalizado.']);
            $limit=$game->current_round===1 ? 6 : 7;
            if ($round->current_stage >= $limit) throw ValidationException::withMessages(['stage'=>'Última etapa permitida alcanzada.']);
            $next=$round->current_stage+1;
            DB::table('game.rounds')->where('id',$round->id)->update(['current_stage'=>$next,'state_version'=>$round->state_version+1]);
            DB::table('game.stage_transitions')->insert(['id'=>(string)Str::uuid(),'game_id'=>$gameId,
              'round_number'=>$game->current_round,'from_stage'=>$round->current_stage,'to_stage'=>$next,
              'actor_id'=>$actor->id,'reason'=>'admin_advance','happened_at'=>now()]);
            $this->audit->record($gameId,$actor->id,'round.stage_advanced',['round'=>$game->current_round,'stage'=>$next]);
            return $next;
        },3);
    }

    public function lockExpired(MmomUser $actor,string $gameId): bool
    {
        $this->auth->requireAdmin($actor);
        return DB::transaction(function () use($actor,$gameId) {
            $game=DB::table('game.games')->where('id',$gameId)->lockForUpdate()->first();
            if (!$game || $game->status!=='active' || !$game->current_round) return false;
            $round=DB::table('game.rounds')->where('game_id',$gameId)->where('round_number',$game->current_round)->lockForUpdate()->first();
            if (!$round || $round->status!=='active' || now('UTC')->lessThan(Carbon::parse($round->deadline_at))) return false;
            DB::table('game.rounds')->where('id',$round->id)->update(['status'=>'locked','current_stage'=>null,
              'state_version'=>$round->state_version+1]);
            DB::table('game.games')->where('id',$gameId)->update(['status'=>'processing','state_version'=>$game->state_version+1,'updated_at'=>now()]);
            $this->audit->record($gameId,$actor->id,'round.locked',['round'=>$game->current_round]);
            return true;
        },3);
    }
}
