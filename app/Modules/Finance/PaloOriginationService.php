<?php
namespace App\Modules\Finance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** INTERNAL ONLY. Must be invoked by a trusted automatic-obligation settlement workflow.
 * Not a player action. Loan proceeds are financing, never revenues.
 */
final class PaloOriginationService
{
    public function __construct(private LedgerService $ledger, private CashService $cash) {}

    public function cover(string $gameId,string $companyId,string $obligationId,string $sourceKey): array
    {
        if ($sourceKey==='' || strlen($sourceKey)>130)
            throw ValidationException::withMessages(['source_key'=>'Clave inválida.']);
        return DB::transaction(function () use ($gameId,$companyId,$obligationId,$sourceKey) {
            // Lock cash FIRST to serialize competing loans; all PALO issuing paths use this order.
            $cash=DB::table('finance.cash_balances')->where('game_id',$gameId)->where('company_id',$companyId)->lockForUpdate()->first();
            if (!$cash) throw ValidationException::withMessages(['cash'=>'Caja no inicializada.']);
            $ob=DB::table('finance.palo_obligations')->where('id',$obligationId)->where('game_id',$gameId)
               ->where('company_id',$companyId)->lockForUpdate()->first();
            if (!$ob || $ob->status!=='due' || !in_array($ob->kind,['maintenance','payroll','approved_admin'],true))
                throw ValidationException::withMessages(['obligation'=>'No es obligación automática elegible y vencida.']);
            $r=(int)$ob->round_number;
            $round=DB::table('game.rounds')->where('game_id',$gameId)->where('round_number',$r)->first();
            if (!$round || $round->status!=='active')
                throw ValidationException::withMessages(['round'=>'La ronda no admite operaciones oficiales.']);
            $existing=DB::table('finance.palo_loans')->where('game_id',$gameId)->where('company_id',$companyId)
                ->where('source_key',$sourceKey)->first();
            if ($existing) return ['loan_id'=>$existing->id,'amount_cents'=>(int)$existing->principal_cents,'reused'=>true];
            if (DB::table('finance.palo_loans')->where('obligation_id',$obligationId)->exists())
                throw ValidationException::withMessages(['obligation'=>'La obligación ya originó un PALO.']);
            // Cross-loan outstanding principal includes previously issued loans not yet repaid.
            $outstanding=(int)DB::table('finance.palo_loans')->where('game_id',$gameId)->where('company_id',$companyId)
                ->sum('outstanding_cents');
            $free=max(0,(int)$cash->cash_cents-(int)$cash->reserved_cents);
            $due=(int)$ob->amount_cents-(int)$ob->paid_cents;
            $amount=PaloSchedule::allowed($outstanding,$free,$due);
            if ($amount===0) return ['loan_id'=>null,'amount_cents'=>0,'reused'=>false,'uncovered_cents'=>max(0,$due-$free)];
            $id=(string)Str::uuid();
            $journal=$this->ledger->post($gameId,$companyId,$r,'palo:issue:'.$sourceKey,
                'PALO: financiación automática de obligación elegible',[
                 ['account'=>'1000','debit'=>$amount,'credit'=>0],
                 ['account'=>'2200','debit'=>0,'credit'=>$amount]]);
            $this->cash->move($gameId,$companyId,$journal,$amount);
            DB::table('finance.palo_loans')->insert(['id'=>$id,'game_id'=>$gameId,'company_id'=>$companyId,
                'obligation_id'=>$obligationId,'origin_round'=>$r,'principal_cents'=>$amount,
                'outstanding_cents'=>$amount,'source_key'=>$sourceKey,'issued_journal_id'=>$journal,'created_at'=>now()]);
            foreach (PaloSchedule::plan($amount,$r) as $line)
                DB::table('finance.palo_schedule')->insert(['loan_id'=>$id,'due_round'=>$line['round'],
                   'principal_due_cents'=>$line['principal_cents'],'interest_due_cents'=>$line['interest_cents'],
                   'principal_paid_cents'=>0,'interest_paid_cents'=>0]);
            return ['loan_id'=>$id,'amount_cents'=>$amount,'reused'=>false,
                'uncovered_cents'=>max(0,$due-$free-$amount)];
        },3);
    }
}
