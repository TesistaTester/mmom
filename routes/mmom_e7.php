<?php
// Add ONCE in routes/web.php: require __DIR__.'/mmom_e7.php';
// Routes here inherit web/session/CSRF middleware; must NOT be included in routes/api.php.
use Illuminate\Support\Facades\Route;
use App\Livewire\Mmom\GameIndex;
use App\Livewire\Mmom\GameConsole;
use App\Http\Controllers\Api\MmomV1Controller;

Route::middleware(['auth','throttle:60,1'])->prefix('mmom')->name('mmom.')->group(function () {
    Route::get('/games',GameIndex::class)->name('games.index');
    Route::get('/games/{gameId}',GameConsole::class)->whereUuid('gameId')->name('games.show');
    Route::prefix('api/v1')->name('api.v1.')->group(function () {
        Route::get('/games',[MmomV1Controller::class,'index'])->name('games.index');
        Route::get('/games/{gameId}',[MmomV1Controller::class,'show'])->whereUuid('gameId')->name('games.show');
        Route::post('/games/{gameId}/drafts',[MmomV1Controller::class,'draft'])->whereUuid('gameId')->name('drafts.store');
        Route::post('/games/{gameId}/decisions/confirm',[MmomV1Controller::class,'confirm'])->whereUuid('gameId')->name('decisions.confirm');
    });
});
