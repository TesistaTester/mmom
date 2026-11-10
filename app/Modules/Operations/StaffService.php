<?php
namespace App\Modules\Operations;

use App\Modules\Finance\LedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** E3 staffing skeleton: six initial staff positions; wages require an approved administrator value. */
final class StaffService
{
    public function __construct(private LedgerService $ledger) {}

    public function bootstrapSix(string $gameId,string $companyId): void
    {
        DB::transaction(function () use($gameId,$companyId) {
            $game=DB::table('game.games')->where('id',$gameId)->lockForUpdate()->first();
            if (!$game || $game->status!=='draft')
                throw ValidationException::withMessages(['game'=>'Plantilla inicial solo antes de preparar partida.']);
            if (!DB::table('game.companies')->where('game_id',$gameId)->where('id',$companyId)->exists())
                throw ValidationException::withMessages(['company'=>'Empresa ajena a la partida.']);
            for($slot=1;$slot<=6;$slot++) DB::table('operations.staff_positions')->insertOrIgnore([
                'id'=>(string)Str::uuid(),'game_id'=>$gameId,'company_id'=>$companyId,
                'slot_number'=>$slot,'title'=>'Trabajador inicial '.$slot,'active'=>true]);
        },3);
    }

    /** Explicit pre-game admin configuration; no salary is inferred or hardcoded. */
    public function configureWage(string $gameId,string $companyId,int $slot,int $wageCents): void
    {
        if ($wageCents<=0) throw ValidationException::withMessages(['wage'=>'Salario debe ser positivo.']);
        DB::transaction(function () use($gameId,$companyId,$slot,$wageCents) {
            $game=DB::table('game.games')->where('id',$gameId)->lockForUpdate()->first();
            if (!$game || $game->status!=='draft') throw ValidationException::withMessages(['game'=>'Salarios solo configurables antes de preparar.']);
            $changed=DB::table('operations.staff_positions')->where('game_id',$gameId)->where('company_id',$companyId)
                ->where('slot_number',$slot)->update(['wage_per_round_cents'=>$wageCents]);
            if (!$changed) throw ValidationException::withMessages(['staff'=>'Puesto inexistente.']);
        },3);
    }

    /** Accrue configured PER-ROUND wage: Debits salary expense; credits salary payable, not cash. */
    public function accruePayroll(string $gameId,string $companyId,int $round): int
    {
        return DB::transaction(function () use($gameId,$companyId,$round) {
            $game=DB::table('game.games')->where('id',$gameId)->lockForUpdate()->first();
            $roundRow=DB::table('game.rounds')->where('game_id',$gameId)->where('round_number',$round)->first();
            if (!$game || !$roundRow || $game->status!=='processing' || $roundRow->status!=='locked' ||
                (int)$game->current_round!==$round)
                throw ValidationException::withMessages(['round'=>'Devengo salarial solo en ronda bloqueada.']);
            $positions=DB::table('operations.staff_positions')->where('game_id',$gameId)
                ->where('company_id',$companyId)->where('active',true)->orderBy('slot_number')->get();
            if ($positions->count()<6) throw ValidationException::withMessages(['staff'=>'Plantilla inicial incompleta.']);
            $sum=0;
            foreach ($positions as $position) {
                if ($position->wage_per_round_cents===null || (int)$position->wage_per_round_cents<=0)
                    throw ValidationException::withMessages(['staff'=>'Salario por ronda sin configurar en puesto '.$position->slot_number]);
                $already=DB::table('operations.payroll_accruals')->where('game_id',$gameId)->where('company_id',$companyId)
                    ->where('round_number',$round)->where('staff_position_id',$position->id)->first();
                if ($already) {$sum+=(int)$already->amount_cents; continue;}
                $amount=(int)$position->wage_per_round_cents;
                $journal=$this->ledger->post($gameId,$companyId,$round,
                    'payroll:'.$round.':'.$position->id,'Devengo salarial por ronda',[
                        ['account'=>'5100','debit'=>$amount,'credit'=>0],
                        ['account'=>'2000','debit'=>0,'credit'=>$amount]]);
                DB::table('operations.payroll_accruals')->insert(['id'=>(string)Str::uuid(),'game_id'=>$gameId,
                    'company_id'=>$companyId,'round_number'=>$round,'staff_position_id'=>$position->id,
                    'amount_cents'=>$amount,'journal_id'=>$journal]);
                $sum+=$amount;
            }
            return $sum;
        },3);
    }
}
