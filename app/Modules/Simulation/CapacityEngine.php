<?php
namespace App\Modules\Simulation;

use InvalidArgumentException;

final class CapacityEngine
{
    public const RESOURCES = ['infrastructure','staff','bar','cashier','security'];

    public function effective(array $resources): int
    {
        if (array_keys($resources) !== self::RESOURCES)
            throw new InvalidArgumentException('Capacidad requiere cinco recursos en el orden contractual.');
        foreach ($resources as $value)
            if (!is_int($value) || $value<0) throw new InvalidArgumentException('Cada capacidad debe ser un entero no negativo.');
        return min($resources);
    }

    /** VIP tables occupy the effective capacity, NEVER extend it. */
    public function allocate(int $vipDemand, int $otherDemand, int $effectiveCapacity, int $vipTables=5, int $seatsPerTable=5): array
    {
        foreach ([$vipDemand,$otherDemand,$effectiveCapacity,$vipTables,$seatsPerTable] as $n)
            if ($n<0) throw new InvalidArgumentException('Los conteos deben ser no negativos.');
        if ($vipTables>0 && $seatsPerTable===0) throw new InvalidArgumentException('Mesa VIP sin asientos.');
        $vipSeats=$vipTables*$seatsPerTable;
        $vipServed=min($vipDemand,$vipSeats,$effectiveCapacity);
        $otherServed=min($otherDemand,$effectiveCapacity-$vipServed);
        return ['effective_capacity'=>$effectiveCapacity,'vip_seats'=>$vipSeats,
            'vip_served'=>$vipServed,'other_served'=>$otherServed,
            'unused_capacity'=>$effectiveCapacity-$vipServed-$otherServed,
            'vip_unserved'=>$vipDemand-$vipServed,'other_unserved'=>$otherDemand-$otherServed];
    }
}
