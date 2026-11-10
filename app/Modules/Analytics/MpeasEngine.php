<?php
namespace App\Modules\Analytics;

use InvalidArgumentException;

/** Advisory only, deterministic calculations. No persistence or writes to live financials. */
final class MpeasEngine
{
    public static function breakeven(int $fixedCostsCents,int $unitPriceCents,int $unitVariableCents): array
    {
        if ($fixedCostsCents<0 || $unitPriceCents<0 || $unitVariableCents<0)
            throw new InvalidArgumentException('Entradas negativas.');
        $contribution=$unitPriceCents-$unitVariableCents;
        return ['contribution_cents'=>$contribution,
            'units_to_break_even'=>$contribution<=0?null:intdiv($fixedCostsCents+$contribution-1,$contribution),
            'feasible'=>$contribution>0];
    }
    /** Scenarios defined explicitly by administrator; neither values nor weights are invented. */
    public static function scenarios(array $scenarios): array
    {
        if (!$scenarios || count($scenarios)>200) throw new InvalidArgumentException('Escenarios inválidos.');
        $output=[];
        foreach ($scenarios as $s) {
            foreach (['name','units','price_cents','variable_cost_cents','fixed_costs_cents'] as $key)
                if (!array_key_exists($key,$s)) throw new InvalidArgumentException('Falta '.$key);
            if (!is_string($s['name']) || $s['name']==='' || strlen($s['name'])>100) throw new InvalidArgumentException('Nombre inválido.');
            foreach (['units','price_cents','variable_cost_cents','fixed_costs_cents'] as $k)
                if (!is_int($s[$k]) || $s[$k]<0 || $s[$k]>1000000000) throw new InvalidArgumentException('Monto/cantidad inválido.');
            $output[]=['name'=>$s['name'], 'profit_cents'=>$s['units']*($s['price_cents']-$s['variable_cost_cents'])-$s['fixed_costs_cents']];
        }
        return $output;
    }
    /** Ordered scenarios for parameter sensitivity: returns deltas vs first scenario. */
    public static function sensitivity(array $scenarios): array
    {
        $results=self::scenarios($scenarios); $base=$results[0]['profit_cents'];
        return array_map(static fn($r)=>$r+['delta_vs_baseline_cents'=>$r['profit_cents']-$base],$results);
    }
    /** Reproducible advisory Monte Carlo from explicitly provided discrete outcomes.
     * Validates integer weights; no guesses about market distributions.
     */
    public static function monteCarlo(array $outcomes,int $draws,string $seed): array
    {
        if ($draws<1 || $draws>100000 || $seed==='' || !$outcomes || count($outcomes)>200)
            throw new InvalidArgumentException('Configuración Monte Carlo inválida.');
        $total=0;
        foreach ($outcomes as $o) {
            if (!isset($o['value_cents'],$o['weight']) || !is_int($o['value_cents']) || !is_int($o['weight']) || $o['weight']<1 || $o['weight']>1000000)
                throw new InvalidArgumentException('Distribución inválida.');
            $total+=$o['weight'];
        }
        $values=[]; $sum=0;
        for ($i=0;$i<$draws;$i++) {
            $bits=hexdec(substr(hash('sha256',$seed.'|'.$i),0,12));
            $target=$bits%$total; $acc=0; $value=null;
            foreach ($outcomes as $o) { $acc+=$o['weight']; if ($target<$acc) {$value=$o['value_cents']; break;} }
            $values[]=$value; $sum+=$value;
        }
        sort($values,SORT_NUMERIC);
        return ['draws'=>$draws,'mean_cents'=>$sum/$draws,
          'min_cents'=>$values[0],'median_cents'=>$values[intdiv($draws-1,2)],
          'max_cents'=>$values[$draws-1], 'seed_hash'=>hash('sha256',$seed)];
    }
}
