<?php
namespace App\Modules\Analytics;

use InvalidArgumentException;

/** Pure MIEC calculation. Inputs are reconciled accumulated indicators, NEVER clients' claims. */
final class MiecEngine
{
    public const WEIGHTS = ['profitability'=>30,'market'=>25,'logistics'=>20,'satisfaction'=>15,'artists'=>10];
    public const REFERENCE_CAPITAL_CENTS = 10000000;

    /**
     * @param array<string,array<string,int|float>> $companies exactly 7 indexed by canonical company UUID.
     * profitability = accumulated net income in CENTS (normalized against Bs100000);
     * other dimensions = accumulated verified comparable indicators, higher is better.
     * @return array{ranking:array,dimensions:array,round:int,official:bool}
     */
    public function rank(array $companies, int $round): array
    {
        if ($round < 1 || $round > 8 || count($companies) !== 7) {
            throw new InvalidArgumentException('MIEC requiere siete compañías y ronda 1..8.');
        }
        $dimensions = array_fill_keys(array_keys(self::WEIGHTS), []);
        foreach ($companies as $id=>$metrics) {
            if (!is_string($id) || !preg_match('/^[0-9a-f]{8}-[0-9a-f-]{27,}$/i',$id) ||
                !is_array($metrics) || array_keys(array_intersect_key($metrics,self::WEIGHTS)) === [] ||
                count($metrics)!==5 || array_diff(array_keys(self::WEIGHTS),array_keys($metrics)) ||
                array_diff(array_keys($metrics),array_keys(self::WEIGHTS))) {
                throw new InvalidArgumentException('Identificador o cinco métricas MIEC inválidos.');
            }
            foreach(self::WEIGHTS as $metric=>$weight) {
                $v=$metrics[$metric];
                if (!is_int($v) && !is_float($v)) throw new InvalidArgumentException('Métrica no numérica.');
                if (!is_finite((float)$v) || abs((float)$v) > 1.0e15) throw new InvalidArgumentException('Métrica no finita/fuera de rango.');
                if ($metric==='profitability' && !is_int($v)) throw new InvalidArgumentException('RN debe ser centavos enteros.');
                if ($metric!=='profitability' && $v<0) throw new InvalidArgumentException('Indicador operativo negativo.');
                $dimensions[$metric][$id]=(float)$v;
            }
        }
        $scores=[];
        foreach($dimensions as $dimension=>$values) {
            $min=min($values);$max=max($values);
            foreach($values as $id=>$v) {
                if ($min===$max) { $s=50.0; }
                elseif($dimension==='profitability') {
                    // RN/100000 makes a reference-capital ratio; normalization maps zero to 50.
                    $rc=$v/self::REFERENCE_CAPITAL_CENTS;
                    $bestPositive=max(0.0,$max/self::REFERENCE_CAPITAL_CENTS);
                    $worstNegative=abs(min(0.0,$min/self::REFERENCE_CAPITAL_CENTS));
                    $s=$rc>=0 ? (50.0+($bestPositive>0 ? 50.0*$rc/$bestPositive : 0.0))
                        : (50.0-($worstNegative>0 ? 50.0*abs($rc)/$worstNegative : 0.0));
                } else {
                    $s=100.0*($v-$min)/($max-$min);
                }
                $scores[$id][$dimension]=max(0.0,min(100.0,$s));
            }
        }
        $rows=[];
        foreach($companies as $id=>$metrics) {
            $total=0.0;
            foreach(self::WEIGHTS as $dimension=>$weight) $total+=$scores[$id][$dimension]*$weight/100.0;
            $rows[]=['company_id'=>$id,'total_score'=>round($total,8),
                'dimension_scores'=>$scores[$id], 'verified_metrics'=>$metrics];
        }
        usort($rows,static function(array $a,array $b):int {
            $cmp=$b['total_score'] <=> $a['total_score']; if($cmp!==0)return $cmp;
            foreach(array_keys(self::WEIGHTS) as $dim) {
                // Approved ordering: RN accumulated, then market/logistics/satisfaction/artists.
                $cmp=$b['verified_metrics'][$dim] <=> $a['verified_metrics'][$dim];
                if($cmp!==0)return $cmp;
            }
            return strcmp($a['company_id'],$b['company_id']); // display stable, shared ranking for full ties
        });
        $previous=null;$position=0;
        foreach($rows as $i=>&$row) {
            $key=array_merge([$row['total_score']],array_map(static fn($d)=>$row['verified_metrics'][$d],array_keys(self::WEIGHTS)));
            if($previous===null || $previous!==$key) $position=$i+1;
            $row['rank']=$position;$previous=$key;
        }unset($row);
        return ['ranking'=>$rows,'dimensions'=>self::WEIGHTS,'round'=>$round,'official'=>false];
    }
}
