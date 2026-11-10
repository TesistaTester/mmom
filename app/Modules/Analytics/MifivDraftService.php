<?php
namespace App\Modules\Analytics;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Internal staging only. Never promotes a round to published/closed; no public endpoint. */
final class MifivDraftService
{
    public function __construct(private MiecEngine $miec=new MiecEngine(),private MifivValidator $validator=new MifivValidator()){}

    /** @param array<string,array<string,int|float>> $cumulativeMetrics from verified server data only */
    public function stageCandidate(string $gameId,int $round,array $cumulativeMetrics,array $evidence):array
    {
        return DB::transaction(function()use($gameId,$round,$cumulativeMetrics,$evidence) {
            $game=DB::table('game.games')->where('id',$gameId)->lockForUpdate()->first();
            $roundRow=DB::table('game.rounds')->where('game_id',$gameId)->where('round_number',$round)->lockForUpdate()->first();
            if(!$game||!$roundRow || !in_array($roundRow->status,['locked','processing','validating'],true))
                throw ValidationException::withMessages(['round'=>'Ronda no preparada para verificación.']);
            $ids=DB::table('game.companies')->where('game_id',$gameId)->orderBy('id')->pluck('id')->all();
            if(count($ids)!==7 || count($cumulativeMetrics)!==7 || array_diff($ids,array_keys($cumulativeMetrics)) || array_diff(array_keys($cumulativeMetrics),$ids))
                throw ValidationException::withMessages(['companies'=>'No hay siete compañías idénticas en el cálculo.']);
            // Input evidence is a proposed checklist, NOT a trusted attestation of DB reconciliation.
            $report=$this->validator->validate($evidence,$ids);
            $ranking=$this->miec->rank($cumulativeMetrics,$round);
            $existing=DB::table('analytics.mifiv_candidates')->where('game_id',$gameId)->where('round_number',$round)->first();
            if($existing) throw ValidationException::withMessages(['round'=>'Ya existe candidato; no reemplazar sin revisión autorizada.']);
            $candidateId=(string)Str::uuid();
            DB::table('analytics.mifiv_candidates')->insert([
              'id'=>$candidateId,'game_id'=>$gameId,'round_number'=>$round,
              'metric_payload'=>json_encode($cumulativeMetrics,JSON_THROW_ON_ERROR),
              'evidence_payload'=>json_encode($evidence,JSON_THROW_ON_ERROR),
              'validation_report'=>json_encode($report,JSON_THROW_ON_ERROR),
              'status'=>'candidate']);
            foreach($ranking['ranking'] as $row) {
                DB::table('analytics.miec_candidate_scores')->insert([
                  'id'=>(string)Str::uuid(),'candidate_id'=>$candidateId,'game_id'=>$gameId,
                  'company_id'=>$row['company_id'],'rank'=>$row['rank'],
                  'weighted_score'=>number_format($row['total_score'],8,'.',''),
                  'dimension_scores'=>json_encode($row['dimension_scores'],JSON_THROW_ON_ERROR),
                  'verified_metrics'=>json_encode($row['verified_metrics'],JSON_THROW_ON_ERROR)]);
            }
            return ['candidate_id'=>$candidateId,'validation'=>$report,'ranking'=>$ranking,'official'=>false];
        },3);
    }
}
