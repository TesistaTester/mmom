<?php
namespace App\Modules\Simulation;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Strictly admin-only, sandboxed from official round state. Do not expose to players. */
final class SimulationPreviewService
{
    public function __construct(private MarketRoundEngine $engine) {}

    public function preview(string $gameId, array $input, string $actorId): array
    {
        $admin=DB::table('identity.users')->where('id',$actorId)->where('is_active',true)->exists()
          && DB::table('identity.user_roles')->where('user_id',$actorId)->where('role','admin')->exists()
          && DB::table('identity.users')->where('id',$actorId)->whereNotNull('two_factor_confirmed_at')->exists();
        if (!$admin) throw ValidationException::withMessages(['actor'=>'Solo administrador con MFA confirmado.']);
        $game=DB::table('game.games')->where('id',$gameId)->first();
        if (!$game) throw ValidationException::withMessages(['game'=>'Partida inexistente.']);
        $catalog=DB::table('game.companies as c')
           ->join('catalog.discotheques as d','d.id','=','c.discotheque_id')
           ->where('c.game_id',$gameId)->pluck('d.code')->all();
        sort($catalog,SORT_STRING);
        $given=$input['clubs']??[];
        if (!is_array($given)) throw ValidationException::withMessages(['clubs'=>'Catálogo inválido.']);
        sort($given,SORT_STRING);
        if ($catalog!==$given) throw ValidationException::withMessages(['clubs'=>'Las siete discotecas no coinciden con la partida.']);
        $output=$this->engine->simulate($input);
        $canonical=json_encode($input,JSON_THROW_ON_ERROR|JSON_PRESERVE_ZERO_FRACTION);
        $hash=hash('sha256',$canonical);$seedHash=hash('sha256',$input['seed']);
        // No official market, cash, stock, ranking or round status writes.
        return DB::transaction(function () use ($gameId,$input,$output,$hash,$seedHash) {
            $existing=DB::table('simulation.engine_runs')->where('game_id',$gameId)
              ->where('round_number',$input['round'])->where('engine_version','MMOM-E4-V0.1')
              ->where('config_hash',$hash)->where('seed_hash',$seedHash)->first();
            if ($existing) return ['id'=>$existing->id,'output'=>json_decode($existing->output_data,true,512,JSON_THROW_ON_ERROR),'reused'=>true];
            $id=(string)Str::uuid();
            DB::table('simulation.engine_runs')->insert(['id'=>$id,'game_id'=>$gameId,'round_number'=>$input['round'],
              'engine_version'=>'MMOM-E4-V0.1','config_hash'=>$hash,'seed_hash'=>$seedHash,
              'input_data'=>$canonical,'output_data'=>json_encode($output,JSON_THROW_ON_ERROR),'status'=>'preview','created_at'=>now()]);
            return ['id'=>$id,'output'=>$output,'reused'=>false];
        },3);
    }
}
