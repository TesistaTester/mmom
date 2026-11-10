<?php
namespace App\Modules\Simulation;

use InvalidArgumentException;

/** Stable independent draws. Engine version required for long-term cross-version replay. */
final class DeterministicRandom
{
    public static function unit(string $seed, string $drawKey): float
    {
        if ($seed === '' || $drawKey === '') throw new InvalidArgumentException('Se requieren semilla y clave de sorteo.');
        // 52 hexadecimal bits: exactly representable as an integer in IEEE 754 doubles.
        $hash = hash('sha256', 'MMOM-E4-V0.1|' . strlen($seed) . ':' . $seed . '|' . $drawKey);
        return hexdec(substr($hash, 0, 13)) / 4503599627370496.0;
    }
}
