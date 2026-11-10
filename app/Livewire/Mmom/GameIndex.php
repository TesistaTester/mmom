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
        $actor=Auth::user();
        abort_unless($actor instanceof MmomUser,403);
        return view('livewire.mmom.game-index',['games'=>$reads->listGames($actor)])
            ->layout('mmom.layout');
    }
}
