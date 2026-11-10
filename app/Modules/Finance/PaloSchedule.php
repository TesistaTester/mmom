<?php
namespace App\Modules\Finance;

use InvalidArgumentException;

/** Pure arithmetic: centavos, no interest capitalization. E5 scheduled due rounds may exceed 8. */
final class PaloSchedule
{
    public const MAX_PRINCIPAL_CENTS = 2_000_000;
    public const RATE_BP = 100; // 1% per round (basis points)
    public static function plan(int $principalCents, int $originRound): array
    {
        if ($principalCents <= 0 || $principalCents > self::MAX_PRINCIPAL_CENTS || $originRound < 1 || $originRound > 8)
            throw new InvalidArgumentException('Principal o ronda PALO inválidos.');
        $base = intdiv($principalCents,3); $remainder = $principalCents % 3;
        $parts = [$base+($remainder>0?1:0),$base+($remainder>1?1:0),$base];
        $out=[]; $balance=$principalCents;
        // Grace for principal in origin + 1; amortize in origin + 2, +3, +4.
        for ($r=$originRound+1;$r<=$originRound+4;$r++) {
            // Half-up cent rounding; 1% of opening outstanding principal.
            $interest=intdiv($balance*self::RATE_BP+5000,10000);
            $principal=($r>=$originRound+2)?$parts[$r-($originRound+2)]:0;
            $out[]=['round'=>$r,'opening_principal_cents'=>$balance,'interest_cents'=>$interest,
                'principal_cents'=>$principal,'closing_principal_cents'=>$balance-$principal,
                'within_game'=>$r<=8];
            $balance-=$principal;
        }
        if ($balance!==0) throw new InvalidArgumentException('Cronograma no amortiza el principal.');
        return $out;
    }
    public static function allowed(int $outstandingCents,int $freeCashCents,int $eligibleDueCents): int
    {
        if ($outstandingCents<0 || $freeCashCents<0 || $eligibleDueCents<0 || $outstandingCents>self::MAX_PRINCIPAL_CENTS)
            throw new InvalidArgumentException('Montos PALO inválidos.');
        return min(max(0,$eligibleDueCents-$freeCashCents), self::MAX_PRINCIPAL_CENTS-$outstandingCents);
    }
}
