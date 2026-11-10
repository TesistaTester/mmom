<?php
namespace App\Modules\Simulation;

use InvalidArgumentException;

final class SoftmaxEngine
{
    /** @param array<string,float|int> $utilities includes exactly seven clubs + abstention */
    public function probabilities(array $utilities): array
    {
        if (count($utilities) !== 8 || !array_key_exists('ABSTAIN', $utilities))
            throw new InvalidArgumentException('Se necesitan siete discotecas y ABSTAIN.');
        foreach ($utilities as $key => $value) {
            if (!is_string($key) || $key === '' || !is_numeric($value) || !is_finite((float)$value))
                throw new InvalidArgumentException('Utilidades no finitas o identificadores inválidos.');
        }
        $max = max(array_map('floatval', array_values($utilities)));
        $exp=[];$total=0.0;
        foreach ($utilities as $key=>$value) { $exp[$key]=exp((float)$value-$max); $total += $exp[$key]; }
        if (!is_finite($total) || $total <= 0) throw new InvalidArgumentException('Normalización Softmax inválida.');
        return array_map(static fn($v) => $v/$total, $exp);
    }

    /** Fully specified utility: 11 numeric factor values and 11 corresponding weights. */
    public function weightedUtility(array $factors, array $weights): float
    {
        if (count($factors)!==11 || count($weights)!==11 || array_keys($factors)!==array_keys($weights))
            throw new InvalidArgumentException('Se necesitan exactamente once factores con pesos explícitos y claves idénticas.');
        $u=0.0;
        foreach ($factors as $k=>$v) {
            if (!is_string($k) || !is_numeric($v) || !is_numeric($weights[$k]) ||
                !is_finite((float)$v) || !is_finite((float)$weights[$k]))
                throw new InvalidArgumentException('Factor o peso inválido.');
            $u += (float)$v*(float)$weights[$k];
        }
        if (!is_finite($u)) throw new InvalidArgumentException('Utilidad fuera de rango.');
        return $u;
    }
}
