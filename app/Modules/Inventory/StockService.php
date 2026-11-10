<?php
namespace App\Modules\Inventory;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Moving-average cost: integer centavos, whole product units; no floating-point money. */
final class StockService
{
    public function purchase(string $gameId,string $companyId,int $round,string $itemId,int $quantity,int $unitCostCents,string $sourceKey): int
    {
        if ($quantity<=0 || $unitCostCents<0 || $quantity>1000000)
            throw ValidationException::withMessages(['stock'=>'Cantidad o costo no válido.']);
        $this->ensureBalance($gameId,$companyId,$itemId);
        $row=$this->getLocked($gameId,$companyId,$itemId);
        $total=$quantity*$unitCostCents;
        $afterQty=(int)$row->quantity+$quantity; $afterCost=(int)$row->total_cost_cents+$total;
        $this->updateBalance($gameId,$companyId,$itemId,$row,$afterQty,$afterCost);
        $this->movement($gameId,$companyId,$round,$itemId,'purchase',$quantity,$total,$afterQty,$afterCost,$sourceKey);
        return $total;
    }

    public function consume(string $gameId,string $companyId,int $round,string $itemId,int $quantity,string $sourceKey): int
    {
        if ($quantity<=0) throw ValidationException::withMessages(['stock'=>'Cantidad inválida.']);
        $row=$this->getLocked($gameId,$companyId,$itemId);
        if ((int)$row->quantity<$quantity) throw ValidationException::withMessages(['stock'=>'Inventario insuficiente.']);
        // Moving-average with deterministic half-up centavo rounding; last units absorb residual.
        $cost=CppCalculator::costToConsume((int)$row->quantity,(int)$row->total_cost_cents,$quantity);
        $afterQty=(int)$row->quantity-$quantity;
        $afterCost=$afterQty===0 ? 0 : (int)$row->total_cost_cents-$cost;
        $this->updateBalance($gameId,$companyId,$itemId,$row,$afterQty,$afterCost);
        $this->movement($gameId,$companyId,$round,$itemId,'sale_usage',-$quantity,-$cost,$afterQty,$afterCost,$sourceKey);
        return $cost;
    }

    private function ensureBalance(string $gameId,string $companyId,string $itemId): void
    {
        DB::table('inventory.stock_balances')->insertOrIgnore(['game_id'=>$gameId,
            'company_id'=>$companyId,'item_id'=>$itemId,'quantity'=>0,'total_cost_cents'=>0,'version'=>1]);
    }
    private function getLocked(string $gameId,string $companyId,string $itemId): object
    {
        $row=DB::table('inventory.stock_balances')->where('game_id',$gameId)
            ->where('company_id',$companyId)->where('item_id',$itemId)->lockForUpdate()->first();
        if (!$row) throw ValidationException::withMessages(['stock'=>'No existe inventario del producto.']);
        return $row;
    }
    private function updateBalance(string $gameId,string $companyId,string $itemId,object $old,int $qty,int $cost): void
    {
        DB::table('inventory.stock_balances')->where('game_id',$gameId)->where('company_id',$companyId)
            ->where('item_id',$itemId)->update(['quantity'=>$qty,'total_cost_cents'=>$cost,'version'=>$old->version+1]);
    }
    private function movement(string $gameId,string $companyId,int $round,string $itemId,string $type,int $qty,int $cost,int $afterQty,int $afterCost,string $key): void
    {
        DB::table('inventory.stock_movements')->insert(['id'=>(string)Str::uuid(),'game_id'=>$gameId,
            'company_id'=>$companyId,'round_number'=>$round,'item_id'=>$itemId,
            'movement_type'=>$type,'quantity_delta'=>$qty,'value_delta_cents'=>$cost,
            'quantity_after'=>$afterQty,'value_after_cents'=>$afterCost,'source_key'=>$key]);
    }
}
