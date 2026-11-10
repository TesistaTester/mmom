<?php
namespace App\Livewire\Mmom;

use App\Models\MmomUser;
use App\Modules\Game\MmomReadService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class GameIndex extends Component
{
    public function render(MmomReadService $reads)
    {
        $actor = Auth::user();
        abort_unless($actor instanceof MmomUser, 403);

        try {
            $games = $reads->listGames($actor);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('No se pudieron listar partidas: ' . $e->getMessage());
            $games = [];
        }

        return view('livewire.mmom.game-index', ['games' => $games])
            ->layout('mmom.layout');
    }
}
