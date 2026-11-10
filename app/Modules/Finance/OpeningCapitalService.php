<?php
namespace App\Modules\Finance;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Model A is 100,000 Bs initial cash. Model B requires separate approved opening assets. */
final class OpeningCapitalService
{
    public function __construct(private LedgerService $ledger,private CashService $cash) {}

    public function initializeModelA(string $gameId,string $companyId): string
    {
        return DB::transaction(function () use($gameId,$companyId) {
            $game=DB::table('game.games')->where('id',$gameId)->lockForUpdate()->first();
            if (!$game || $game->initial_model!=='A' || $game->status!=='draft')
                throw ValidationException::withMessages(['capital'=>'La capitalización en caja solo procede para modalidad A y partida en borrador.']);
            if (!DB::table('game.companies')->where('game_id',$gameId)->where('id',$companyId)->exists())
                throw ValidationException::withMessages(['company'=>'Empresa ajena a la partida.']);
            $this->cash->initialize($gameId,$companyId);
            $journal=$this->ledger->post($gameId,$companyId,null,'opening-capital-A','Aporte inicial Bs 100.000',[
                ['account'=>'1000','debit'=>10000000,'credit'=>0],
                ['account'=>'3000','debit'=>0,'credit'=>10000000]
            ]);
            $this->cash->move($gameId,$companyId,$journal,10000000);
            return $journal;
        },3);
    }
}
