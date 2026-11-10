<?php
require dirname(__DIR__,2).'/app/Modules/Inventory/CppCalculator.php';
use App\Modules\Inventory\CppCalculator;
$cases=[
 ['partial at constant unit cost',10,10000,3,3000],
 ['full depletion',10,10000,10,10000],
 ['fractional CPP rounded centavo',3,100,1,33],
 ['residual last unit',1,34,1,34],
 ['zero stock value',4,0,2,0],
 ['zero cost remaining',3,1,1,0],
];
foreach($cases as [$name,$available,$value,$used,$expected]) {
 $actual=CppCalculator::costToConsume($available,$value,$used);
 if($actual!==$expected){fwrite(STDERR,"FAIL $name: $actual != $expected\n");exit(1);}
 echo "OK: $name\n";
}
try {CppCalculator::costToConsume(2,100,3);fwrite(STDERR,"FAIL: oversell not rejected\n");exit(1);}
catch(InvalidArgumentException $e){echo "OK: oversell rejected\n";}
