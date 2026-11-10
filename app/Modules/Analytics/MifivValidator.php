<?php
namespace App\Modules\Analytics;

use InvalidArgumentException;

/** Validate independently produced evidence references; cannot turn caller booleans into official proof. */
final class MifivValidator
{
    public const REQUIRED=['stage_decisions','ledger_balanced','cash_reconciled','inventory_reconciled',
        'sales_reconciled','palo_reconciled','metrics_verified','source_traceable'];

    /**
     * @param array<string,array<string,array{passed:bool,evidence_id:string}>> $reports companyUUID => check => evidence
     * @param string[] $expectedIds 7 immutable DB-derived company UUIDs, not client input
     */
    public function validate(array $reports,array $expectedIds): array
    {
        if(count($expectedIds)!==7 || count(array_unique($expectedIds))!==7 || count($reports)!==7 ||
            array_diff($expectedIds,array_keys($reports)) || array_diff(array_keys($reports),$expectedIds)) {
            throw new InvalidArgumentException('MIFIV exige exactamente las siete compañías de la partida.');
        }
        $problems=[];$ok=0;
        foreach($expectedIds as $id) {
            $checks=$reports[$id];
            if (!is_array($checks)) throw new InvalidArgumentException('Informe inválido.');
            foreach(self::REQUIRED as $check) {
                $item=$checks[$check]??null;
                if (!is_array($item) || !isset($item['passed'],$item['evidence_id']) ||
                    !is_bool($item['passed']) || !is_string($item['evidence_id']) ||
                    strlen($item['evidence_id'])<8 || strlen($item['evidence_id'])>160) {
                    $problems[]=['company_id'=>$id,'check'=>$check,'reason'=>'missing_or_untraceable'];continue;
                }
                if (!$item['passed']) {$problems[]=['company_id'=>$id,'check'=>$check,'reason'=>'failed'];continue;}
                $ok++;
            }
            foreach(array_keys($checks) as $name) if(!in_array($name,self::REQUIRED,true))
                $problems[]=['company_id'=>$id,'check'=>$name,'reason'=>'unknown_check'];
        }
        return ['ready_for_independent_verification'=>$problems===[],
            'official'=>false,'passed_checks'=>$ok,'required_checks'=>7*count(self::REQUIRED),
            'issues'=>$problems];
    }
}
