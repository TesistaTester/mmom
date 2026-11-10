<section wire:poll.15s="refreshSnapshot">
<a href="{{ route('mmom.games.index') }}">← Volver a partidas</a>
<h1>{{ $snapshot['game']['name'] }}</h1>
@if($notice)<p class="notice" role="status">{{ $notice }}</p>@endif
<div class="grid">
<div class="card"><h2>Partida</h2><p>Estado: <strong>{{ $snapshot['game']['status'] }}</strong></p>
<p>Ronda: {{ $snapshot['game']['current_round'] ?? 'Sin iniciar' }} / 8</p>
<p>Modalidad: gestión supervisada</p></div>
<div class="card"><h2>Reloj del servidor</h2>
<p>Tiempo restante aproximado: <strong>{{ $snapshot['seconds_remaining'] }} segundos</strong></p>
<p class="muted">Referencia UTC: {{ $snapshot['server_now'] }}</p>
<p class="muted">Reloj mostrado por sondeo; el servidor valida cada acción.</p></div>
</div>
@if($snapshot['round'])
<div class="card"><h2>Ronda {{ $snapshot['round']['round_number'] }}</h2>
<p>Estado: {{ $snapshot['round']['status'] }} · Etapa actual: {{ $snapshot['round']['current_stage'] ?? 'Sin etapa activa' }}</p>
<p>Etapas confirmadas: {{ implode(', ', $snapshot['confirmed_stages']) ?: 'Ninguna' }}</p>
</div>
@endif
@if($snapshot['can_manage'])
<div class="card"><h2>Administración</h2>
<p class="muted">Acciones exclusivas de administrador con MFA confirmado. Las asignaciones de jugadores se gestionan por el servicio E2 y aún requieren una interfaz completa.</p>
@if($snapshot['game']['status']==='draft') <button wire:click="prepareGame" wire:confirm="¿Preparar esta partida? Se exigen siete asignaciones vigentes.">Preparar partida</button> @endif
@if($snapshot['game']['status']==='prepared') <button wire:click="startFirstRound" wire:confirm="¿Iniciar ronda 1 por veinte minutos de reloj servidor?">Iniciar ronda 1</button> @endif
@if($snapshot['game']['status']==='active') <button wire:click="nextStage" wire:confirm="¿Avanzar la etapa para todos los jugadores?">Avanzar etapa</button> @endif
</div>
@else
<div class="card"><h2>Decisión empresarial</h2>
@if($snapshot['can_decide'])
@if(in_array($snapshot['round']['current_stage'],$snapshot['confirmed_stages'],true))
<p class="notice">La decisión de esta etapa ya está confirmada; no se puede editar.</p>
@else
<p class="muted">Editor técnico JSON para integración. No ejecuta los efectos de las decisiones. La interfaz guiada definitiva requiere los catálogos y validadores completos por etapa.</p>
<label for="mmom-decision">Borrador de decisión (objeto JSON)</label>
<textarea id="mmom-decision" wire:model="draftJson" spellcheck="false" aria-describedby="mmom-help"></textarea>
<p id="mmom-help" class="muted">Ejemplo de borrador de integración: {"observacion":"revisión de estrategia"}. No representa una compra ni un precio válido.</p>
@error('draftJson')<p class="error" role="alert">{{ $message }}</p>@enderror
<button wire:click="saveDraft" wire:loading.attr="disabled">Guardar borrador</button>
<label><input type="checkbox" wire:model="confirmAcknowledged"> Comprendo que confirmar vuelve inmutable esta decisión y que sus efectos están diferidos.</label>
<button class="secondary" wire:click="confirmDecision" wire:confirm="¿Confirmar de forma irreversible el borrador guardado?" wire:loading.attr="disabled">Confirmar borrador guardado</button>
@endif
@else <p>No hay una etapa activa disponible para decisiones.</p> @endif
</div>
@endif
</section>