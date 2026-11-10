<?php
namespace App\Modules\Inventory;

/** Integer centavos and whole units; independent of PDO/Laravel for deterministic tests. */
final class CppCalculator
{
    public static function costToConsume(int $availableUnits,int $totalCents,int $consumeUnits): int
    {
        if ($availableUnits<=0 || $totalCents<0 || $consumeUnits<=0 || $consumeUnits>$availableUnits)
            throw new \InvalidArgumentException('Invalid inventory quantity or cost.');
        if ($consumeUnits===$availableUnits) return $totalCents;
        if ($totalCents>intdiv(PHP_INT_MAX-$availableUnits,$consumeUnits))
            throw new \OverflowException('Inventory valuation exceeds integer limit.');
        return min($totalCents,intdiv($totalCents*$consumeUnits + intdiv($availableUnits,2),$availableUnits));
    }
}
