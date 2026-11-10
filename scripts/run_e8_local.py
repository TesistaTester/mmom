#!/usr/bin/env python3
"""Orquestador de calidad local. No fabrica pruebas Laravel/PostgreSQL."""
from pathlib import Path
import subprocess, shutil, json, datetime, csv, hashlib, sys
root=Path(__file__).resolve().parents[1]
result={'package':'MMOM-E8-V0.1','timestamp_utc':datetime.datetime.now(datetime.timezone.utc).isoformat(),
  'environment':{'php':shutil.which('php'),'composer':shutil.which('composer'),'psql':shutil.which('psql')},
  'php_lint':{},'unit_suites':{},'static_checks':{},'integration_tests':'NOT_RUN', 'security_tests':'NOT_RUN','acceptance':'NOT_GRANTED'}
def cmd(args):
 p=subprocess.run(args,cwd=root,text=True,capture_output=True,timeout=60)
 return {'status':'PASS' if p.returncode==0 else 'FAIL','exit_code':p.returncode,
    'stdout':p.stdout[-4000:],'stderr':p.stderr[-2000:]}
if result['environment']['php']:
 for file in sorted(root.rglob('*.php')):
  relative=str(file.relative_to(root)); result['php_lint'][relative]=cmd(['php','-l',str(file)])
 for suite in ['run_cpp_tests.php','run_e4_tests.php','run_e5_tests.php','run_e6_tests.php','run_e8_regression.php']:
  result['unit_suites'][suite]=cmd(['php',str(root/'tests/Unit'/suite)])
else:result['php_lint_error']='PHP no instalado; no se ejecutaron suites'
# Inspecciones de presencia, no afirman que mecanismos de seguridad funcionen.
required=['routes/mmom_e7.php','app/Modules/Identity/MmomAuthorization.php','app/Modules/Game/MmomDecisionService.php',
 'app/Modules/Analytics/MifivValidator.php','database/migrations/2026_11_09_000006_create_mmom_e6_miec_mifiv.php',
 'resources/views/livewire/mmom/game-console.blade.php']
result['static_checks']={x:('PRESENT' if (root/x).is_file() else 'MISSING') for x in required}
failures=[name for name,data in result['php_lint'].items() if data['status']!='PASS']
failures+= [name for name,data in result['unit_suites'].items() if data['status']!='PASS']
failures+= [name for name,data in result['static_checks'].items() if data=='MISSING']
result['local_status']='FAIL' if failures else ('PASS' if result['environment']['php'] else 'BLOCKED')
result['failures']=failures
out=root/'docs/EVIDENCIA_E8_LOCAL.json';out.write_text(json.dumps(result,ensure_ascii=False,indent=2),encoding='utf-8')
print('MMOM E8 | local:',result['local_status'],'| lint:',len(result['php_lint']),
 '| suites:',len(result['unit_suites']),'| integrated: NOT RUN')
for key,val in result['unit_suites'].items(): print(key,val['status'])
print('Evidence:',out)
sys.exit(1 if failures else 0)
