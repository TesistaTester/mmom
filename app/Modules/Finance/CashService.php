<?php
namespace App\Modules\Finance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Internal accounting subledger. Caller MUST wrap mutation in DB::transaction. */
final class CashService
{
    public function initialize(string $gameId,string $companyId): void
    {
        DB::table('finance.cash_balances')->insertOrIgnore([
            'game_id'=>$gameId,'company_id'=>$companyId,'cash_cents'=>0,'reserved_cents'=>0,'version'=>1]);
    }

    public function freeCents(string $gameId,string $companyId): int
    {
        $row=DB::table('finance.cash_balances')->where('game_id',$gameId)
            ->where('company_id',$companyId)->lockForUpdate()->first();
        if (!$row) throw ValidationException::withMessages(['cash'=>'Primero inicialice la caja de la empresa.']);
        return max(0,(int)$row->cash_cents-(int)$row->reserved_cents);
    }

    public function move(string $gameId,string $companyId,string $journalId,int $deltaCents): void
    {
        if ($deltaCents===0) throw ValidationException::withMessages(['cash'=>'Movimiento de caja cero.']);
        $row=DB::table('finance.cash_balances')->where('game_id',$gameId)
            ->where('company_id',$companyId)->lockForUpdate()->first();
        if (!$row) throw ValidationException::withMessages(['cash'=>'Caja no inicializada.']);
        $after=(int)$row->cash_cents+$deltaCents;
        if ($after<0 || $after<(int)$row->reserved_cents)
            throw ValidationException::withMessages(['cash'=>'Liquidez libre insuficiente.']);
        DB::table('finance.cash_balances')->where('game_id',$gameId)->where('company_id',$companyId)
            ->update(['cash_cents'=>$after,'version'=>$row->version+1]);
        DB::table('finance.cash_movements')->insert(['id'=>(string)Str::uuid(),'game_id'=>$gameId,
            'company_id'=>$companyId,'journal_id'=>$journalId,'delta_cents'=>$deltaCents,
            'cash_after_cents'=>$after]);
    }
}
