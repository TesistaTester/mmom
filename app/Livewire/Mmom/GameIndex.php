<?php

namespace App\Livewire\Mmom;

use App\Models\MmomUser;
use App\Modules\Game\MmomGameService;
use App\Modules\Game\MmomReadService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

final class GameIndex extends Component
{
    public string $activeTab = 'games'; // 'games' | 'tutorial'
    public bool $showCreateModal = false;
    public string $newGameName = '';
    public string $initialModel = 'A';
    public bool $autoAssignAndPrepare = true;
    public bool $isProcessing = false;
    public string $actionNotice = '';
    public string $actionError = '';

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->actionNotice = '';
        $this->actionError = '';
    }

    public function openCreateModal(): void
    {
        $this->newGameName = 'Simulación MMOM ' . date('d/m H:i');
        $this->initialModel = 'A';
        $this->autoAssignAndPrepare = true;
        $this->showCreateModal = true;
        $this->actionNotice = '';
        $this->actionError = '';
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->isProcessing = false;
    }

    public function createGame(MmomGameService $gameService, MmomReadService $reads): void
    {
        if ($this->isProcessing) {
            return;
        }

        $this->isProcessing = true;
        $this->actionNotice = '';
        $this->actionError = '';

        $actor = Auth::user();
        if (!$actor instanceof MmomUser) {
            $this->actionError = 'Sesión inválida. Vuelve a iniciar sesión.';
            $this->isProcessing = false;
            return;
        }

        $this->validate([
            'newGameName' => ['required', 'string', 'min:3', 'max:100'],
            'initialModel' => ['required', 'in:A,B'],
        ], [
            'newGameName.required' => 'Debes ingresar un nombre para la partida.',
            'newGameName.min' => 'El nombre debe tener al menos 3 caracteres.',
            'initialModel.in' => 'El modelo inicial debe ser A o B.',
        ]);

        try {
            $gameId = $gameService->create($actor, $this->newGameName, $this->initialModel);

            if ($this->autoAssignAndPrepare) {
                // Obtener las 7 empresas creadas en la partida
                $companies = DB::table('game.companies')
                    ->where('game_id', $gameId)
                    ->orderBy('id')
                    ->get();

                // Obtener los 7 jugadores oficiales
                $players = DB::table('identity.users as u')
                    ->join('identity.user_roles as r', 'r.user_id', '=', 'u.id')
                    ->where('r.role', 'player')
                    ->where('u.is_active', true)
                    ->orderBy('u.email')
                    ->take(7)
                    ->get(['u.id', 'u.email', 'u.display_name']);

                if ($players->count() < 7) {
                    $this->actionNotice = "Partida creada (ID: {$gameId}), pero solo hay {$players->count()} jugadores registrados en el sistema. Se requieren 7 para prepararla automáticamente.";
                    $this->showCreateModal = false;
                    $this->isProcessing = false;
                    return;
                }

                // Asignar cada jugador a una de las 7 empresas
                foreach ($companies as $idx => $company) {
                    $gameService->assign($actor, $gameId, $company->id, $players[$idx]->id);
                }

                // Congelar configuración y preparar las 8 rondas
                $gameService->prepare($actor, $gameId);

                $this->actionNotice = "¡Partida '{$this->newGameName}' creada, vinculada a los 7 jugadores y preparada con éxito! Lista para iniciar.";
            } else {
                $this->actionNotice = "Partida '{$this->newGameName}' creada en estado Borrador.";
            }

            $this->showCreateModal = false;
        } catch (ValidationException $e) {
            $this->actionError = collect($e->errors())->flatten()->first();
        } catch (\Throwable $e) {
            $this->actionError = 'No se pudo crear la partida: ' . $e->getMessage();
        } finally {
            $this->isProcessing = false;
        }
    }

    public function render(MmomReadService $reads)
    {
        $actor = Auth::user();
        abort_unless($actor instanceof MmomUser, 403);

        $isAdmin = DB::table('identity.user_roles')
            ->where('user_id', $actor->id)
            ->where('role', 'admin')
            ->exists();

        try {
            $games = $reads->listGames($actor);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('No se pudieron listar partidas: ' . $e->getMessage());
            $games = [];
        }

        return view('livewire.mmom.game-index', [
            'games' => $games,
            'isAdmin' => $isAdmin,
        ])->layout('mmom.layout');
    }
}
