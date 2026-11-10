<?php
namespace App\Modules\Commerce;

use App\Modules\Finance\CashService;
use App\Modules\Finance\LedgerService;
use App\Modules\Inventory\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** No player route. Intended for trusted E4 simulation events after server-side calculations. */
final class SaleSettlementService
{
    public function __construct(private StockService $stock,private LedgerService $ledger,private CashService $cash) {}

    /** $components: [{item_id:string, quantity:int}], 1 drink + 1 mixer for a combo. */
    public function settle(string $gameId,string $companyId,int $round,string $category,int $grossCents,array $components,string $sourceKey): string
    {
        return DB::transaction(function () use($gameId,$companyId,$round,$category,$grossCents,$components,$sourceKey) {
            $game=DB::table('game.games')->where('id',$gameId)->lockForUpdate()->first();
            $roundRow=DB::table('game.rounds')->where('game_id',$gameId)->where('round_number',$round)->first();
            if (!$game || !$roundRow || $game->status!=='processing' || $roundRow->status!=='locked'
                || (int)$game->current_round!==$round)
                throw ValidationException::withMessages(['round'=>'Ventas oficiales solo durante procesamiento E4 de ronda bloqueada.']);
            if (!DB::table('game.companies')->where('game_id',$gameId)->where('id',$companyId)->exists())
                throw ValidationException::withMessages(['company'=>'Empresa ajena a la partida.']);
            if (!in_array($category,['ticket','individual','combo','vip_extra'],true) || $grossCents<=0 || $grossCents>1000000000000)
                throw ValidationException::withMessages(['sale'=>'Categoría o monto inválido.']);
            if ($sourceKey==='' || strlen($sourceKey)>140)
                throw ValidationException::withMessages(['source'=>'Clave de evento inválida.']);
            // Event component signature prevents a reused key from changing the simulated goods.
            $fingerprint=$components;
            usort($fingerprint,fn($a,$b)=>strcmp((string)($a['item_id']??''),(string)($b['item_id']??'')));
            $componentHash=hash('sha256',json_encode($fingerprint,JSON_THROW_ON_ERROR));
            $key='sale:'.$sourceKey;
            $existing=DB::table('commerce.sales')->where('game_id',$gameId)->where('company_id',$companyId)
                ->where('source_key',$key)->first();
            if ($existing) {
                if ($existing->category!==$category || (int)$existing->gross_cents!==$grossCents ||
                    (int)$existing->round_number!==$round || !hash_equals($existing->components_hash,$componentHash)) throw ValidationException::withMessages(['sale'=>'Evento repetido con atributos diferentes.']);
                return $existing->id;
            }
            if ($category==='combo' && count($components)!==2)
                throw ValidationException::withMessages(['combo'=>'El combo debe tener exactamente dos componentes.']);
            if (in_array($category,['ticket','vip_extra'],true) && count($components)!==0)
                throw ValidationException::withMessages(['sale'=>'Categoría sin componentes físicos.']);
            if ($category==='individual' && count($components)!==1)
                throw ValidationException::withMessages(['sale'=>'Venta individual requiere un producto.']);
            $kinds=[]; $seen=[]; $validated=[];
            foreach ($components as $part) {
                $id=$part['item_id']??null; $qty=$part['quantity']??null;
                if (!is_string($id) || !is_int($qty) || $qty<=0 || $qty>100000 || isset($seen[$id]))
                    throw ValidationException::withMessages(['components'=>'Producto duplicado o componente inválido.']);
                $seen[$id]=true;
                $item=DB::table('catalog.stock_items')->where('id',$id)->first();
                if (!$item) throw ValidationException::withMessages(['components'=>'Producto inexistente.']);
                $kinds[]=$item->kind;
                $validated[]=['id'=>$id,'qty'=>$qty];
            }
            if ($category==='combo' && (count(array_filter($kinds,fn($k)=>$k==='drink'))!==1 ||
                    count(array_filter($kinds,fn($k)=>$k==='mixer'))!==1 ||
                    $validated[0]['qty']!==1 || $validated[1]['qty']!==1))
                throw ValidationException::withMessages(['combo'=>'Combo = exactamente 1 bebida + 1 mixer.']);
            // Deterministic ordering prevents deadlocks when many consumers use the same products.
            usort($validated,fn($a,$b)=>strcmp($a['id'],$b['id']));
            $saleId=(string)Str::uuid(); $cogs=0; $costs=[];
            foreach ($validated as $part) {
                $cost=$this->stock->consume($gameId,$companyId,$round,$part['id'],$part['qty'],
                    'sale-usage:'.$sourceKey.':'.$part['id']);
                $costs[]=['id'=>$part['id'],'qty'=>$part['qty'],'cost'=>$cost]; $cogs+=$cost;
            }
            $revenueAccount=match($category) {'ticket'=>'4000','individual'=>'4010','combo'=>'4020','vip_extra'=>'4030'};
            $lines=[['account'=>'1000','debit'=>$grossCents,'credit'=>0],
                ['account'=>$revenueAccount,'debit'=>0,'credit'=>$grossCents]];
            if ($cogs>0) {
                $lines[]=['account'=>'5000','debit'=>$cogs,'credit'=>0];
                $lines[]=['account'=>'1100','debit'=>0,'credit'=>$cogs];
            }
            $journal=$this->ledger->post($gameId,$companyId,$round,$key,'Venta '.$category,$lines);
            $this->cash->move($gameId,$companyId,$journal,$grossCents);
            DB::table('commerce.sales')->insert(['id'=>$saleId,'game_id'=>$gameId,'company_id'=>$companyId,
                'round_number'=>$round,'category'=>$category,'gross_cents'=>$grossCents,'components_hash'=>$componentHash,'source_key'=>$key,'journal_id'=>$journal]);
            foreach ($costs as $part) DB::table('commerce.sale_components')->insert(['sale_id'=>$saleId,
                'item_id'=>$part['id'],'quantity'=>$part['qty'],'cogs_cents'=>$part['cost']]);
            return $saleId;
        },3);
    }
}
