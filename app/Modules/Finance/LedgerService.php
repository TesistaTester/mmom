<?php
namespace App\Modules\Finance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Internal server-side posting only. Never expose arbitrary journal lines to players. */
final class LedgerService
{
    /** @param array<int,array{account:string,debit:int,credit:int}> $lines */
    public function post(string $gameId, string $companyId, ?int $round, string $key, string $description, array $lines): string
    {
        if (count($lines)<2 || strlen($key)>160 || $key==='' || strlen($description)>240) {
            throw ValidationException::withMessages(['journal'=>'Póliza incompleta o identificador inválido.']);
        }
        $debits=0; $credits=0;
        foreach ($lines as $line) {
            if (!isset($line['account'],$line['debit'],$line['credit']) || !is_string($line['account']) ||
                !is_int($line['debit']) || !is_int($line['credit']) ||
                $line['debit']<0 || $line['credit']<0 || (($line['debit']>0) === ($line['credit']>0))) {
                throw ValidationException::withMessages(['journal'=>'Debe existir exactamente un débito o crédito positivo por línea.']);
            }
            $debits += $line['debit']; $credits += $line['credit'];
        }
        if ($debits!==$credits || $debits===0) throw ValidationException::withMessages(['journal'=>'Póliza no balanceada.']);
        // Call inside the transaction that owns the cash/stock locks.
        $existing=DB::table('finance.journals')->where('game_id',$gameId)->where('company_id',$companyId)->where('source_key',$key)->first();
        if ($existing) throw ValidationException::withMessages(['journal'=>'Operación contable ya registrada.']);
        $id=(string)Str::uuid();
        DB::table('finance.journals')->insert(['id'=>$id,'game_id'=>$gameId,'company_id'=>$companyId,
            'round_number'=>$round,'source_key'=>$key,'description'=>$description,'posted_at'=>now()]);
        foreach ($lines as $line) DB::table('finance.entries')->insert([
            'id'=>(string)Str::uuid(),'journal_id'=>$id,'account_code'=>$line['account'],
            'debit_cents'=>$line['debit'],'credit_cents'=>$line['credit']]);
        return $id;
    }
}
