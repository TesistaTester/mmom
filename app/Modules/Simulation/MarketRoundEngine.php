<?php
namespace App\Modules\Simulation;

use InvalidArgumentException;

/** Pure and deterministic prototype; no commercial postings or official round closure. */
final class MarketRoundEngine
{
    public const SEGMENTS=['STUDENTS'=>40,'YOUNG_WORKERS'=>30,'ADULTS'=>20,'VIP'=>10];
    public function __construct(private SoftmaxEngine $softmax=new SoftmaxEngine(),
       private CapacityEngine $capacity=new CapacityEngine()) {}

    /**
     * Strict contract: seven catalog discotheque codes (not numeric indices), configured marketSize,
     * attendance rates by segment (0..1), per-segment utility for seven clubs + ABSTAIN,
     * resource capacities per club, optional configured VIP tables; deterministic seed and round.
     * Allocated segment populations use largest remainder, stable lexical ties.
     */
    public function simulate(array $input): array
    {
        $clubs=$input['clubs']??null; $marketSize=$input['market_size']??null;
        $seed=$input['seed']??null; $round=$input['round']??null;
        if (!is_array($clubs) || count($clubs)!==7 || count(array_unique($clubs))!==7 ||
            count(array_filter($clubs,static fn($x)=>is_string($x)&&preg_match('/^[A-Z][A-Z0-9_]{1,39}$/',$x)))!==7)
            throw new InvalidArgumentException('Se requieren siete códigos únicos de discoteca.');
        sort($clubs, SORT_STRING);
        if (!is_int($marketSize) || $marketSize<0 || $marketSize>1000000 ||
            !is_string($seed) || $seed==='' || !is_int($round) || $round<1 || $round>8)
            throw new InvalidArgumentException('Mercado, semilla o ronda inválidos.');
        $rates=$input['attendance_rates']??null; $utilities=$input['utilities']??null;
        $resources=$input['resources']??null; $tables=$input['vip_tables']??[];
        if (!is_array($rates)||!is_array($utilities)||!is_array($resources)||!is_array($tables))
            throw new InvalidArgumentException('Configuración incompleta.');
        if (array_diff(array_keys(self::SEGMENTS),array_keys($rates)) || count($rates)!==4 ||
            array_diff(array_keys(self::SEGMENTS),array_keys($utilities)) || count($utilities)!==4)
            throw new InvalidArgumentException('Se requieren los cuatro segmentos definidos.');
        if (array_diff($clubs,array_keys($resources)) || count($resources)!==7 || array_diff(array_keys($tables),$clubs))
            throw new InvalidArgumentException('Recursos o mesas no corresponden a las siete discotecas.');
        foreach ($rates as $rate) if (!is_numeric($rate)||!is_finite((float)$rate)||(float)$rate<0||(float)$rate>1)
            throw new InvalidArgumentException('Tasa de asistencia fuera de [0,1].');
        $pop=$this->segmentPopulations($marketSize);
        $demand=array_fill_keys($clubs, ['VIP'=>0,'OTHER'=>0]);
        $bySegment=[];$abstentions=0;$notAttending=0;
        foreach (self::SEGMENTS as $segment=>$share) {
            $u=$utilities[$segment];
            if (!is_array($u) || count($u)!==8 || array_diff($clubs,array_keys($u)) ||
                count(array_diff(array_keys($u),array_merge($clubs,['ABSTAIN'])))!==0)
                throw new InvalidArgumentException('Utilidades faltantes o alternativas desconocidas.');
            $prob=$this->softmax->probabilities($u);
            $attend=0;
            $counts=array_fill_keys($clubs,0);$counts['ABSTAIN']=0;
            for($person=0;$person<$pop[$segment];$person++) {
                if (DeterministicRandom::unit($seed,"round:$round|segment:$segment|person:$person|attendance") >= (float)$rates[$segment]) {
                    $notAttending++;continue;
                }
                $attend++;
                $draw=DeterministicRandom::unit($seed,"round:$round|segment:$segment|person:$person|choice");
                $acc=0.0; $selected='ABSTAIN';
                // Sorted keys make results independent from JSON input key order.
                foreach (array_merge($clubs,['ABSTAIN']) as $club) {
                    $acc += $prob[$club];
                    if ($draw<$acc) { $selected=$club;break; }
                }
                $counts[$selected]++;
                if ($selected==='ABSTAIN') $abstentions++;
                else $demand[$selected][$segment==='VIP'?'VIP':'OTHER']++;
            }
            $bySegment[$segment]=['population'=>$pop[$segment],'attended_market'=>$attend,
               'no_attendance'=>$pop[$segment]-$attend,'choices'=>$counts,'probabilities'=>$prob];
        }
        $out=[];
        foreach($clubs as $club) {
            $effective=$this->capacity->effective($resources[$club]);
            $t=$tables[$club]??5;
            if (!is_int($t)||$t<0) throw new InvalidArgumentException('Mesas VIP inválidas.');
            $out[$club]=['demand'=>$demand[$club],
              'capacity'=>$this->capacity->allocate($demand[$club]['VIP'],$demand[$club]['OTHER'],$effective,$t)];
        }
        $chosen=array_sum(array_map(static fn($x)=>$x['demand']['VIP']+$x['demand']['OTHER'],$out));
        return ['engine_version'=>'MMOM-E4-V0.1','round'=>$round,'population'=>$marketSize,
          'no_attendance'=>$notAttending,'abstained_after_attendance'=>$abstentions,
          'chosen_discotheque'=>$chosen,'segments'=>$bySegment,'companies'=>$out,
          'official'=>false];
    }

    private function segmentPopulations(int $marketSize): array
    {
        $counts=[];$parts=[];$allocated=0;
        foreach(self::SEGMENTS as $name=>$share) {
            $exact=$marketSize*$share/100;
            $counts[$name]=(int)floor($exact);$allocated+=$counts[$name];
            $parts[$name]=$exact-$counts[$name];
        }
        // stable sort descending remainder, then segment name
        $names=array_keys($parts);
        usort($names,static fn($a,$b)=>($parts[$b]<=>$parts[$a]) ?: strcmp($a,$b));
        for($i=0;$i<$marketSize-$allocated;$i++) $counts[$names[$i]]++;
        return $counts;
    }
}
