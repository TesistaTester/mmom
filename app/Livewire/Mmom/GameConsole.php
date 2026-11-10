<?php
namespace App\Livewire\Mmom;

use App\Models\MmomUser;
use App\Modules\Game\MmomReadService;
use App\Modules\Game\MmomDecisionService;
use App\Modules\Game\MmomGameService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/** UI is an adapter; mutations go through E2 services and their DB locks/authorization. */
final class GameConsole extends Component
{
    public string $gameId;
    public string $draftJson='{"observacion":""}';
    public bool $confirmAcknowledged=false;
    public string $notice='';
    public ?int $editedStage=null;

    public function mount(string $gameId,MmomReadService $reads): void
    {
        $this->gameId=$gameId;
        $snapshot=$reads->snapshot($this->actor(),$gameId);
        $this->loadDraft($snapshot);
    }
    private function actor(): MmomUser
    {
        $actor=Auth::user();
        abort_unless($actor instanceof MmomUser,403);
        return $actor;
    }
    private function loadDraft(array $snapshot): void
    {
        $stage=$snapshot['round']['current_stage']??null;
        $this->editedStage=$stage ? (int)$stage : null;
        $this->draftJson=json_encode($snapshot['current_draft']['payload']??new \stdClass(),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
        $this->confirmAcknowledged=false;
    }
    public function refreshSnapshot(MmomReadService $reads): void
    {
        $snapshot=$reads->snapshot($this->actor(),$this->gameId);
        if ($this->editedStage!==($snapshot['round']['current_stage']??null)) $this->loadDraft($snapshot);
    }
    public function saveDraft(MmomReadService $reads,MmomDecisionService $decisions): void
    {
        $snapshot=$reads->snapshot($this->actor(),$this->gameId);
        $stage=$snapshot['round']['current_stage']??null;
        if (!$snapshot['can_decide'] || !$stage || in_array($stage,$snapshot['confirmed_stages'],true))
            throw ValidationException::withMessages(['draftJson'=>'No está habilitada la edición de esta etapa.']);
        if ($stage!==$this->editedStage) throw ValidationException::withMessages(['draftJson'=>'La etapa cambió; actualice la pantalla.']);
        if (strlen($this->draftJson)>32768) throw ValidationException::withMessages(['draftJson'=>'El borrador supera 32 KB.']);
        try { $decoded=json_decode($this->draftJson,true,512,JSON_THROW_ON_ERROR); }
        catch (\JsonException $exception) { throw ValidationException::withMessages(['draftJson'=>'El JSON no es válido.']); }
        if (!is_array($decoded) || array_is_list($decoded)) throw ValidationException::withMessages(['draftJson'=>'Se requiere un objeto JSON con claves.']);
        $decisions->draft($this->actor(),$this->gameId,(int)$stage,$decoded);
        $this->notice='Borrador guardado; no se han aplicado efectos económicos.';
        $this->confirmAcknowledged=false;
    }
    public function confirmDecision(MmomReadService $reads,MmomDecisionService $decisions): void
    {
        $snapshot=$reads->snapshot($this->actor(),$this->gameId);
        $stage=$snapshot['round']['current_stage']??null;
        if (!$this->confirmAcknowledged || !$snapshot['can_decide'] || !$stage || $this->editedStage!==$stage
            || in_array($stage,$snapshot['confirmed_stages'],true))
            throw ValidationException::withMessages(['draftJson'=>'Revise el estado actual y confirme la irreversibilidad.']);
        if (!$snapshot['current_draft']) throw ValidationException::withMessages(['draftJson'=>'Primero guarde un borrador.']);
        // The controller generates the key; the client cannot choose a rival's key.
        $decisions->confirm($this->actor(),$this->gameId,(int)$stage,(string)\Illuminate\Support\Str::uuid());
        $this->confirmAcknowledged=false;
        $this->notice='Decisión confirmada e inmutable. Efectos económicos todavía diferidos.';
    }
    public function prepareGame(MmomReadService $reads,MmomGameService $games): void
    {
        $snapshot=$reads->snapshot($this->actor(),$this->gameId);
        abort_unless($snapshot['can_manage'],403);
        $games->prepare($this->actor(),$this->gameId);
        $this->notice='Partida preparada. Verifique las siete asignaciones antes de comenzar.';
    }
    public function startFirstRound(MmomReadService $reads,MmomGameService $games): void
    {
        $snapshot=$reads->snapshot($this->actor(),$this->gameId);
        abort_unless($snapshot['can_manage'],403);
        $games->startRound($this->actor(),$this->gameId,1);
        $this->notice='Ronda 1 iniciada. El servidor determina los 20 minutos.';
    }
    public function nextStage(MmomReadService $reads,MmomGameService $games): void
    {
        $snapshot=$reads->snapshot($this->actor(),$this->gameId);
        abort_unless($snapshot['can_manage'],403);
        $games->advanceStage($this->actor(),$this->gameId);
        $this->notice='Etapa avanzada.';
    }
    public function render(MmomReadService $reads)
    {
        $snapshot=$reads->snapshot($this->actor(),$this->gameId);
        return view('livewire.mmom.game-console',['snapshot'=>$snapshot])->layout('mmom.layout');
    }
}
