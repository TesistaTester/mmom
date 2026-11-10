<?php
namespace App\Modules\Inventory;

use App\Modules\Finance\CashService;
use App\Modules\Finance\LedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Internal E4 integration seam. Settles a stage-2 immutable decision once, after lock. */
final class PurchaseSettlementService
{
    public function __construct(private StockService $stock,private LedgerService $ledger,private CashService $cash) {}

    public function settle(string $decisionId): string
    {
        return DB::transaction(function () use($decisionId) {
            $decision=DB::table('game.confirmed_decisions')->where('id',$decisionId)->first();
            if (!$decision || (int)$decision->stage!==2) throw ValidationException::withMessages(['decision'=>'Debe indicar una decisión de inventario confirmada.']);
            $game=DB::table('game.games')->where('id',$decision->game_id)->lockForUpdate()->first();
            $round=DB::table('game.rounds')->where('game_id',$decision->game_id)->where('round_number',$decision->round_number)->first();
            if (!$game || !$round || $game->status!=='processing' || $round->status!=='locked' ||
                (int)$game->current_round!==(int)$decision->round_number)
                throw ValidationException::withMessages(['round'=>'Las compras solo pueden liquidarse durante el procesamiento de una ronda bloqueada.']);
            $key='decision-purchase:'.$decisionId;
            $existing=DB::table('finance.journals')->where('game_id',$decision->game_id)
                ->where('company_id',$decision->company_id)->where('source_key',$key)->first();
            if ($existing) return $existing->id;
            $payload=json_decode($decision->payload,true,512,JSON_THROW_ON_ERROR);
            if (!isset($payload['lines']) || !is_array($payload['lines']) || !count($payload['lines']))
                throw ValidationException::withMessages(['payload'=>'Se requieren líneas de compra.']);
            if (count($payload['lines'])>100) throw ValidationException::withMessages(['payload'=>'Demasiadas líneas.']);
            $seen=[]; $total=0; $quoted=[];
            foreach ($payload['lines'] as $line) {
                $code=$line['product_code']??null; $qty=$line['quantity']??null;
                if (!is_string($code) || !is_int($qty) || $qty<1 || $qty>100000 || isset($seen[$code]))
                    throw ValidationException::withMessages(['payload'=>'Producto duplicado, cantidad no entera o línea inválida.']);
                $seen[$code]=true;
                $item=DB::table('catalog.stock_items')->where('code',$code)->where('active',true)->first();
                if (!$item) throw ValidationException::withMessages(['item'=>'Producto no habilitado: '.$code]);
                $quote=DB::table('inventory.supplier_quotes')->where('game_id',$decision->game_id)
                    ->where('round_number',$decision->round_number)->where('item_id',$item->id)->first();
                if (!$quote) throw ValidationException::withMessages(['quote'=>'No hay precio de proveedor aprobado para '.$code]);
                $cost=(int)$quote->unit_cost_cents*$qty;
                if ($cost>1000000000000 || $total>1000000000000-$cost)
                    throw ValidationException::withMessages(['amount'=>'Importe fuera de límite.']);
                $total+=$cost; $quoted[]=['item_id'=>$item->id,'qty'=>$qty,'unit'=>(int)$quote->unit_cost_cents];
            }
            if ($total<1) throw ValidationException::withMessages(['amount'=>'La compra debe tener importe positivo.']);
            if ($this->cash->freeCents($decision->game_id,$decision->company_id)<$total)
                throw ValidationException::withMessages(['cash'=>'No hay liquidez libre para compra discrecional; PALO no está permitido.']);
            foreach ($quoted as $line) $this->stock->purchase($decision->game_id,$decision->company_id,
                (int)$decision->round_number,$line['item_id'],$line['qty'],$line['unit'],
                'purchase:'.$decisionId.':'.$line['item_id']);
            $journal=$this->ledger->post($decision->game_id,$decision->company_id,(int)$decision->round_number,$key,
                'Compra de inventario confirmada',[
                    ['account'=>'1100','debit'=>$total,'credit'=>0],
                    ['account'=>'1000','debit'=>0,'credit'=>$total]
                ]);
            $this->cash->move($decision->game_id,$decision->company_id,$journal,-$total);
            return $journal;
        },3);
    }
}
