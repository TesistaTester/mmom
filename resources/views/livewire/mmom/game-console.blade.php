<section wire:poll.15s="refreshSnapshot">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;">
        <a href="{{ route('mmom.games.index') }}" style="color:#58a6ff;text-decoration:none;font-weight:bold;">
            ← Volver al Listado de Partidas
        </a>
        <span class="tag">Simulación MMOM · E7 V0.1</span>
    </div>

    <h1 style="color:#eef1fb;margin-top:0;">{{ $snapshot['game']['name'] }}</h1>

    @if($notice)
        <div style="background: {{ str_starts_with($notice, '⚠️') ? '#491b1f' : '#133929' }};
                    border: 1px solid {{ str_starts_with($notice, '⚠️') ? '#f85149' : '#2ea043' }};
                    color: {{ str_starts_with($notice, '⚠️') ? '#ff7b72' : '#7ee787' }};
                    padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 500;">
            {{ $notice }}
        </div>
    @endif

    <div class="grid">
        <div class="card">
            <h2 style="color:#58a6ff;margin-top:0;">Estado de la Partida</h2>
            <p>Estado actual: 
                @php
                    $statusColor = match($snapshot['game']['status']) {
                        'active' => '#238636',
                        'prepared' => '#1f6feb',
                        'draft' => '#d29922',
                        default => '#6e7681'
                    };
                @endphp
                <span style="background:{{ $statusColor }};color:#fff;padding:0.2rem 0.5rem;border-radius:4px;font-weight:bold;font-size:0.85rem;">
                    {{ strtoupper($snapshot['game']['status']) }}
                </span>
            </p>
            <p>Ronda en curso: <strong style="color:#79c0ff;">{{ $snapshot['game']['current_round'] ? "Ronda {$snapshot['game']['current_round']} de 8" : 'Sin iniciar' }}</strong></p>
            <p class="muted" style="font-size:0.85rem;">Modalidad: Gestión supervisada con reloj central.</p>
        </div>

        <div class="card">
            <h2 style="color:#58a6ff;margin-top:0;">Reloj Central del Servidor</h2>
            @if($snapshot['seconds_remaining'] > 0)
                <p style="font-size:1.3rem;margin:0.5rem 0;color:#7ee787;font-weight:bold;">
                    ⏱️ {{ gmdate("i:s", $snapshot['seconds_remaining']) }}
                    <span style="font-size:0.85rem;font-weight:normal;color:#8b949e;">({{ $snapshot['seconds_remaining'] }} segundos)</span>
                </p>
            @else
                <p style="font-size:1.1rem;margin:0.5rem 0;color:#8b949e;">
                    ⏱️ Sin cronómetro activo
                </p>
            @endif
            <p class="muted" style="font-size:0.8rem;margin:0;">Sincronización UTC: {{ $snapshot['server_now'] }}</p>
            <p class="muted" style="font-size:0.8rem;margin-top:0.2rem;">El reloj valida cada decisión en el servidor para evitar discrepancias locales.</p>
        </div>
    </div>

    @if($snapshot['round'])
        <div class="card" style="border-left:4px solid #58a6ff;">
            <h2 style="color:#58a6ff;margin-top:0;">Ronda {{ $snapshot['round']['round_number'] }}</h2>
            <div style="display:flex;gap:1.5rem;flex-wrap:wrap;">
                <div>
                    <span class="muted">Estado de la Ronda:</span> 
                    <strong>{{ strtoupper($snapshot['round']['status']) }}</strong>
                </div>
                <div>
                    <span class="muted">Etapa actual de decisión:</span> 
                    <strong style="color:#e3b341;">{{ $snapshot['round']['current_stage'] ? "Etapa {$snapshot['round']['current_stage']} de 7" : 'Sin etapa activa' }}</strong>
                </div>
            </div>
            <p style="margin:0.75rem 0 0 0;font-size:0.9rem;">
                Etapas confirmadas por tu empresa: 
                <strong>{{ implode(', ', $snapshot['confirmed_stages']) ?: 'Ninguna todavía' }}</strong>
            </p>
        </div>
    @endif

    {{-- PANEL DEL ADMINISTRADOR / OPERADOR --}}
    @if($snapshot['can_manage'])
        <div class="card" style="background:#16243b;border:2px solid #1f6feb;">
            <h2 style="color:#79c0ff;margin-top:0;">🛠️ Control del Operador (Administrador)</h2>
            <p class="muted" style="font-size:0.9rem;margin-bottom:1.2rem;">
                Controles de avance de la simulación. Los botones cuentan con protección contra dobles envíos.
            </p>

            <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:center;">
                @if($snapshot['game']['status'] === 'draft')
                    <button type="button" 
                            wire:click="prepareGame" 
                            wire:loading.attr="disabled"
                            wire:target="prepareGame"
                            wire:confirm="¿Preparar esta partida? Se verificarán las siete asignaciones del catálogo."
                            style="background:#1f6feb;padding:0.8rem 1.4rem;font-size:0.95rem;font-weight:bold;">
                        <span wire:loading.remove wire:target="prepareGame">⚙️ Preparar Partida</span>
                        <span wire:loading wire:target="prepareGame">⏳ Preparando partida...</span>
                    </button>
                @endif

                @if($snapshot['game']['status'] === 'prepared')
                    <button type="button" 
                            wire:click="startFirstRound" 
                            wire:loading.attr="disabled"
                            wire:target="startFirstRound"
                            wire:confirm="¿Deseas iniciar la Ronda 1 con 20 minutos en el reloj del servidor?"
                            style="background:#238636;padding:0.8rem 1.4rem;font-size:0.95rem;font-weight:bold;box-shadow:0 4px 12px rgba(35,134,54,0.3);">
                        <span wire:loading.remove wire:target="startFirstRound">▶️ Iniciar Ronda 1 (20 Minutos)</span>
                        <span wire:loading wire:target="startFirstRound">⏳ Iniciando cronómetro...</span>
                    </button>
                @endif

                @if($snapshot['game']['status'] === 'active')
                    <button type="button" 
                            wire:click="nextStage" 
                            wire:loading.attr="disabled"
                            wire:target="nextStage"
                            wire:confirm="¿Avanzar a la siguiente etapa de decisión para todos los participantes?"
                            style="background:#8957e5;padding:0.8rem 1.4rem;font-size:0.95rem;font-weight:bold;">
                        <span wire:loading.remove wire:target="nextStage">⏩ Avanzar Etapa</span>
                        <span wire:loading wire:target="nextStage">⏳ Avanzando etapa...</span>
                    </button>
                @endif
            </div>
        </div>
    @else
        {{-- PANEL DE DECISIÓN DEL PARTICIPANTE / JUGADOR --}}
        <div class="card" style="border:1px solid #38465f;">
            <h2 style="color:#58a6ff;margin-top:0;">💼 Formulario de Decisión Empresarial</h2>

            @if($snapshot['can_decide'])
                @if(in_array($snapshot['round']['current_stage'], $snapshot['confirmed_stages'], true))
                    <div style="background:#133929;border:1px solid #2ea043;padding:1rem;border-radius:8px;color:#7ee787;">
                        🔒 <strong>Decisión Inmutable:</strong> La decisión para la Etapa {{ $snapshot['round']['current_stage'] }} ya ha sido confirmada por tu empresa. Espera a que el facilitador avance de etapa.
                    </div>
                @else
                    <p class="muted" style="font-size:0.9rem;margin-bottom:1rem;">
                        Formula los parámetros de tu decisión para la etapa actual en formato JSON. Recuerda guardar el borrador y luego confirmar su inmutabilidad.
                    </p>

                    <label for="mmom-decision" style="font-weight:bold;color:#c9d1d9;margin-bottom:0.4rem;">
                        Borrador de Decisión (Objeto JSON):
                    </label>
                    <textarea id="mmom-decision" wire:model="draftJson" spellcheck="false" 
                              style="font-family:ui-monospace,SFMono-Regular,Consolas,monospace;font-size:0.95rem;line-height:1.4;"></textarea>

                    @error('draftJson')
                        <p class="error" role="alert" style="color:#ff7b72;margin-top:0.4rem;">{{ $message }}</p>
                    @enderror

                    <div style="display:flex;gap:1rem;align-items:center;margin:1.2rem 0;flex-wrap:wrap;">
                        <button type="button" 
                                wire:click="saveDraft" 
                                wire:loading.attr="disabled"
                                wire:target="saveDraft"
                                style="background:#346cca;padding:0.75rem 1.4rem;font-weight:bold;">
                            <span wire:loading.remove wire:target="saveDraft">💾 Guardar Borrador</span>
                            <span wire:loading wire:target="saveDraft">⏳ Guardando...</span>
                        </button>
                    </div>

                    <div style="background:#131e30;padding:1rem;border-radius:8px;border:1px dashed #304870;margin-bottom:1.2rem;">
                        <label style="display:flex;align-items:center;gap:0.75rem;cursor:pointer;margin:0;">
                            <input type="checkbox" wire:model="confirmAcknowledged" style="transform:scale(1.2);">
                            <span style="color:#eef1fb;font-size:0.9rem;">
                                Comprendo que confirmar vuelve <strong>irreversible e inmutable</strong> esta decisión para el cálculo de la ronda.
                            </span>
                        </label>
                    </div>

                    <button type="button" 
                            class="secondary" 
                            wire:click="confirmDecision" 
                            wire:loading.attr="disabled"
                            wire:target="confirmDecision"
                            wire:confirm="¿Estás completamente seguro de confirmar de forma irreversible este borrador?"
                            style="background:#6e40c9;padding:0.75rem 1.5rem;font-weight:bold;width:100%;">
                        <span wire:loading.remove wire:target="confirmDecision">🔒 Confirmar Decisión Definitiva (Inmutable)</span>
                        <span wire:loading wire:target="confirmDecision">⏳ Registrando confirmación inmutable...</span>
                    </button>
                @endif
            @else
                <div style="background:#1c293f;padding:1.5rem;border-radius:8px;text-align:center;">
                    <p style="color:#8b949e;margin:0;font-size:1.05rem;">
                        ⏸️ No hay una etapa activa abierta para formular decisiones en este momento.
                    </p>
                </div>
            @endif
        </div>
    @endif
</section>
