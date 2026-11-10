<?php
require __DIR__.'/../../app/Modules/Analytics/MiecEngine.php';
require __DIR__.'/../../app/Modules/Analytics/MifivValidator.php';
use App\Modules\Analytics\MiecEngine;
use App\Modules\Analytics\MifivValidator;
function check(bool $yes,string $message):void { if(!$yes)throw new RuntimeException('FAIL '.$message);echo 'OK '.$message.PHP_EOL; }
$ids=array_map(static fn($n)=>sprintf('00000000-0000-4000-8000-%012d',$n),range(1,7));
$engine=new MiecEngine();
$metrics=[];
foreach($ids as $i=>$id) $metrics[$id]=['profitability'=>0,'market'=>0,'logistics'=>0,'satisfaction'=>0,'artists'=>0];
$r=$engine->rank($metrics,1);
check(count($r['ranking'])===7,'7 empresas');
check($r['ranking'][0]['total_score']===50.0,'Empate completo: 50 puntos');
check(array_unique(array_column($r['ranking'],'rank'))===[1] || count(array_unique(array_column($r['ranking'],'rank')))===1,'Empate absoluto: rango compartido');
check(array_sum(MiecEngine::WEIGHTS)===100,'Ponderaciones suman 100');
$metrics[$ids[0]]['profitability']=10000000;
$metrics[$ids[1]]['profitability']=-10000000;
$r=$engine->rank($metrics,8);
$index=[];foreach($r['ranking'] as $row)$index[$row['company_id']]=$row;
check(abs($index[$ids[0]]['dimension_scores']['profitability']-100.0)<1.e-8,'RN positivo máximo 100');
check(abs($index[$ids[1]]['dimension_scores']['profitability']-0.0)<1.e-8,'RN negativo mínimo 0');
check(abs($index[$ids[2]]['dimension_scores']['profitability']-50.0)<1.e-8,'RN cero centrado 50');
check($index[$ids[0]]['rank']===1,'Mayor beneficio lidera');
check($r['official']===false,'No es clasificación oficial');
// All loss ties still produce 50, not zero.
foreach($metrics as &$m)$m['profitability']=-500000;unset($m);
$r=$engine->rank($metrics,7);
check($r['ranking'][0]['dimension_scores']['profitability']===50.0,'Pérdidas idénticas puntúan 50');
$metrics[$ids[0]]['market']=10;
$r=$engine->rank($metrics,7);check($r['ranking'][0]['company_id']===$ids[0],'Desempate por mercado');
try { $engine->rank(array_slice($metrics,0,6,true),1);throw new RuntimeException('No rejected'); }
catch(InvalidArgumentException $e) { check(true,'Rechaza seis empresas'); }
try {$engine->rank($metrics,9);throw new RuntimeException('No rejected');}
catch(InvalidArgumentException $e) {check(true,'Rechaza ronda 9');}
$v=new MifivValidator();$reports=[];
foreach($ids as $id)foreach(MifivValidator::REQUIRED as $name)
 $reports[$id][$name]=['passed'=>true,'evidence_id'=>'sha256:'.str_repeat('a',64)];
$valid=$v->validate($reports,$ids);
check($valid['ready_for_independent_verification']===true,'56 comprobaciones de reporte completas');
check($valid['passed_checks']===56 && $valid['official']===false,'Checklist no publica ni certifica');
$reports[$ids[1]]['palo_reconciled']['passed']=false;
$invalid=$v->validate($reports,$ids);
check($invalid['ready_for_independent_verification']===false,'Bloqueo ante PALO sin conciliar');
unset($reports[$ids[2]]['inventory_reconciled']);
check(count($v->validate($reports,$ids)['issues'])===2,'Se reportan omisiones múltiples');
echo 'ALL E6 LOCAL TESTS PASSED'.PHP_EOL;
