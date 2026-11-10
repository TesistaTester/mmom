<?php
namespace App\Modules\Audit;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class MmomAudit
{
    public function record(?string $gameId, ?string $actorId, string $type, array $details=[]): void
    {
        DB::table('audit.change_events')->insert([
          'id'=>(string)Str::uuid(),'game_id'=>$gameId,'actor_id'=>$actorId,
          'event_type'=>$type,'correlation_id'=>(string)Str::uuid(),
          'payload'=>json_encode($details,JSON_THROW_ON_ERROR), 'happened_at'=>now(),
        ]);
    }
}
