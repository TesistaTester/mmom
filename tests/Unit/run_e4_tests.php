<?php
require_once __DIR__.'/../../app/Modules/Simulation/DeterministicRandom.php';
require_once __DIR__.'/../../app/Modules/Simulation/SoftmaxEngine.php';
require_once __DIR__.'/../../app/Modules/Simulation/CapacityEngine.php';
require_once __DIR__.'/../../app/Modules/Simulation/MarketRoundEngine.php';
use App\Modules\Simulation\{SoftmaxEngine,CapacityEngine,MarketRoundEngine,DeterministicRandom};
function check(bool $yes, string $label):void { if(!$yes) throw new RuntimeException('FAIL '.$label);echo "PASS $label\n"; }
$soft=new SoftmaxEngine();$cap=new CapacityEngine();$engine=new MarketRoundEngine();
$clubs=['LA_PACENA','JET','LUNA_PARK','FORUM','FABRI_K','IBIZA','GREEN_PLANET'];
$u=array_fill_keys(array_merge($clubs,['ABSTAIN']),0.0);
$p=$soft->probabilities($u);check(count($p)===8 && abs(array_sum($p)-1)<1e-12 && abs($p['ABSTAIN']-.125)<1e-12,'Softmax simétrico, 8 alternativas =12.5%');
$p2=$soft->probabilities(array_fill_keys(array_merge($clubs,['ABSTAIN']),1000));
check(abs(array_sum($p2)-1)<1e-12,'Softmax estable a valores altos');
check(abs($soft->weightedUtility(array_fill_keys(array_map(fn($i)=>'F'.$i,range(1,11)),2),array_fill_keys(array_map(fn($i)=>'F'.$i,range(1,11)),.5))-11)<1e-10,'once factores explícitos');
check($cap->effective(['infrastructure'=>90,'staff'=>70,'bar'=>60,'cashier'=>55,'security'=>80])===55,'aforo por cuello mínimo');
$a=$cap->allocate(30,60,50);check($a['vip_served']===25 && $a['other_served']===25 && $a['unused_capacity']===0,'VIP prioritario y dentro del aforo');
$b=$cap->allocate(5,60,50);check($b['vip_served']===5 && $b['other_served']===45,'plazas VIP libres utilizables por otros');
check(DeterministicRandom::unit('s','a')===DeterministicRandom::unit('s','a') && DeterministicRandom::unit('s','a')!==DeterministicRandom::unit('s','b'),'sorteo independiente por clave');
$input=['clubs'=>$clubs,'market_size'=>101,'round'=>1,'seed'=>'fixture-2026',
  'attendance_rates'=>['STUDENTS'=>1,'YOUNG_WORKERS'=>1,'ADULTS'=>1,'VIP'=>1],
  'utilities'=>['STUDENTS'=>$u,'YOUNG_WORKERS'=>$u,'ADULTS'=>$u,'VIP'=>$u],
  'resources'=>array_fill_keys($clubs,['infrastructure'=>200,'staff'=>200,'bar'=>200,'cashier'=>200,'security'=>200])];
$r=$engine->simulate($input);check($r['population']===101 && $r['no_attendance']===0 && $r['chosen_discotheque']+$r['abstained_after_attendance']===101,'conservación de consumidores sin pérdidas');
check($r=== $engine->simulate($input),'reproducibilidad semilla+entrada');
$rev=$input;$rev['clubs']=array_reverse($clubs);check($r===$engine->simulate($rev),'invariante al orden de clubes de entrada');
$zero=$input;$zero['attendance_rates']=array_fill_keys(array_keys(MarketRoundEngine::SEGMENTS),0);$rz=$engine->simulate($zero);
check($rz['no_attendance']===101 && $rz['chosen_discotheque']===0,'tasa cero = nadie asiste');
$small=$input;$small['resources']=array_fill_keys($clubs,['infrastructure'=>0,'staff'=>100,'bar'=>100,'cashier'=>100,'security'=>100]);
$rs=$engine->simulate($small);check(array_sum(array_map(fn($c)=>$c['capacity']['other_served']+$c['capacity']['vip_served'], $rs['companies']))===0,'capacidad cero no reasigna demanda');
$fail=false;try{$soft->probabilities(['ABSTAIN'=>0]);}catch(InvalidArgumentException $e){$fail=true;}
check($fail,'rechazo de alternativas incompletas');
echo "E4: 13 comprobaciones satisfactorias.\n";
