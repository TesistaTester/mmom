<?php
require_once __DIR__.'/../../app/Modules/Finance/PaloSchedule.php';
require_once __DIR__.'/../../app/Modules/Analytics/MpeasEngine.php';
require_once __DIR__.'/../../app/Modules/Analytics/MeferEngine.php';
use App\Modules\Finance\PaloSchedule;
use App\Modules\Analytics\MpeasEngine;
use App\Modules\Analytics\MeferEngine;
$n=0;
function check($yes,$message) {global $n; $n++; if (!$yes) throw new Exception('FAIL: '.$message); echo "OK $n: $message\n";}
$s=PaloSchedule::plan(600000,2);
check(array_column($s,'round')===[3,4,5,6],'PALO R2: interés desde R3 y primer principal R4');
check(array_column($s,'interest_cents')===[6000,6000,4000,2000],'PALO R2 Bs6000: intereses Bs60/60/40/20');
check(array_column($s,'principal_cents')===[0,200000,200000,200000],'PALO 3 cuotas Bs2000');
check(array_sum(array_column($s,'interest_cents'))===18000,'Interés total Bs180');
check(PaloSchedule::allowed(1950000,0,200000)===50000,'Tope agregado Bs20000');
check(PaloSchedule::allowed(0,10000,40000)===30000,'Préstamo solo para déficit elegible');
check(PaloSchedule::allowed(0,50000,40000)===0,'No se presta si existe caja libre suficiente');
check(array_sum(array_column(PaloSchedule::plan(100,8),'principal_cents'))===100,'Redondeo de centavos en tres cuotas');
check(PaloSchedule::plan(10000,8)[3]['within_game']===false,'Saldo PALO posterior a R8 sigue pendiente');
check(MpeasEngine::breakeven(100000,1500,500)['units_to_break_even']===100,'Punto equilibrio exacto');
check(MpeasEngine::breakeven(100000,500,500)['units_to_break_even']===null,'Contribución no positiva');
$sens=MpeasEngine::sensitivity([['name'=>'base','units'=>10,'price_cents'=>100,'variable_cost_cents'=>40,'fixed_costs_cents'=>300],['name'=>'mas','units'=>12,'price_cents'=>100,'variable_cost_cents'=>40,'fixed_costs_cents'=>300]]);
check($sens[1]['delta_vs_baseline_cents']===120,'Sensibilidad vs línea base');
$mc1=MpeasEngine::monteCarlo([['value_cents'=>100,'weight'=>1],['value_cents'=>-50,'weight'=>2]],100,'semilla');
check($mc1===MpeasEngine::monteCarlo([['value_cents'=>100,'weight'=>1],['value_cents'=>-50,'weight'=>2]],100,'semilla'),'Monte Carlo reproducible');
check(MeferEngine::evaluate(10000,[6000,6000],0)['npv_cents']===2000,'VAN sin descuento');
check(MeferEngine::evaluate(10000,[6000,6000],0)['simple_payback_round']===2,'Recuperación simple en dos rondas');
check(MeferEngine::canFund(100000,100000)===true && MeferEngine::canFund(99999,100000)===false,'Inversión solo con liquidez propia suficiente');
try {MeferEngine::evaluate(10000,[100,100,100],0.01,2); check(false,'Límite de rondas restantes');} catch (InvalidArgumentException $e) {check(true,'Bloqueo de inversión con flujos fuera de horizonte');}
echo "PASS: $n pruebas E5 sin Laravel ni PostgreSQL.\n";
