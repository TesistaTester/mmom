<?php
// REQUIRE this file from routes/web.php (session-authenticated Laravel routes).
// Not exposed as unauthenticated routes/api.php. In production, add throttling.
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MmomE2Controller;

Route::middleware(['auth','throttle:60,1'])->prefix('mmom/e2')->group(function () {
    Route::post('/games',[MmomE2Controller::class,'create']);
    Route::post('/games/{gameId}/assignments',[MmomE2Controller::class,'assign']);
    Route::post('/games/{gameId}/prepare',[MmomE2Controller::class,'prepare']);
    Route::post('/games/{gameId}/rounds/start',[MmomE2Controller::class,'startRound']);
    Route::post('/games/{gameId}/stages/next',[MmomE2Controller::class,'nextStage']);
    Route::post('/games/{gameId}/rounds/lock-expired',[MmomE2Controller::class,'lockExpired']);
    Route::post('/games/{gameId}/drafts',[MmomE2Controller::class,'draft']);
    Route::post('/games/{gameId}/decisions/confirm',[MmomE2Controller::class,'confirm']);
    Route::get('/games/{gameId}/clock',[MmomE2Controller::class,'clock']);
});
