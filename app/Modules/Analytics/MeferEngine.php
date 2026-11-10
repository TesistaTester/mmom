<?php
namespace App\Modules\Analytics;

use InvalidArgumentException;

/** Informational only. Assumes a user-supplied discount rate per ROUND. */
final class MeferEngine
{
    public static function evaluate(int $initialCostCents,array $futureCashFlowsCents,float $discountRatePerRound,?int $remainingRounds=null): array
    {
        if ($initialCostCents<=0 || count($futureCashFlowsCents)<1 || count($futureCashFlowsCents)>8 ||
            !is_finite($discountRatePerRound) || $discountRatePerRound<0 || $discountRatePerRound>1 ||
            ($remainingRounds!==null && ($remainingRounds<0 || $remainingRounds>8)))
            throw new InvalidArgumentException('Entradas MEFER inválidas.');
        if ($remainingRounds!==null && count($futureCashFlowsCents)>$remainingRounds)
            throw new InvalidArgumentException('Flujos exceden las rondas restantes.');
        $npv=-$initialCostCents; $cumulative=-$initialCostCents; $payback=null;
        foreach ($futureCashFlowsCents as $i=>$cf) {
            if (!is_int($cf)) throw new InvalidArgumentException('Cada flujo debe ser centavos enteros.');
            $npv += $cf / pow(1+$discountRatePerRound,$i+1);
            $cumulative+=$cf;
            if ($payback===null && $cumulative>=0) $payback=$i+1;
        }
        return ['npv_cents'=>(int)round($npv,0,PHP_ROUND_HALF_UP),'simple_payback_round'=>$payback,
            'total_net_cash_cents'=>$cumulative,'informational_only'=>true];
    }
    /** Investment authorization requires confirmed free company liquidity, NEVER PALO. */
    public static function canFund(int $confirmedFreeOwnCashCents,int $investmentCostCents): bool
    {
        if ($confirmedFreeOwnCashCents<0 || $investmentCostCents<=0) throw new InvalidArgumentException('Montos inválidos.');
        return $confirmedFreeOwnCashCents >= $investmentCostCents;
    }
}
