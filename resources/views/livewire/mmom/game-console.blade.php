<section wire:poll.15s="refreshSnapshot" x-data="{
    activeTab: 'decisions',
    // MPEAS calculator state
    mpPrice: 60,
    mpVariable: 18,
    mpFixed: 5000,
    mpDemand: 150,
    get mpContribution() { return Math.max(0, this.mpPrice - this.mpVariable); },
    get mpBreakeven() { return this.mpContribution > 0 ? Math.ceil(this.mpFixed / this.mpContribution) : null; },
    get mpProfit() { return (this.mpDemand * this.mpPrice) - (this.mpFixed + this.mpDemand * this.mpVariable); },
    get mpProfitMinus15() { let d = this.mpDemand * 0.85; return (d * this.mpPrice) - (this.mpFixed + d * this.mpVariable); },
    get mpProfitPlus15() { let d = this.mpDemand * 1.15; return (d * this.mpPrice) - (this.mpFixed + d * this.mpVariable); },

    // MEFER calculator state
    meInvestment: 12000,
    meFlow: 3500,
    meRate: 2,
    meRounds: {{ max(1, 8 - (int)($snapshot['game']['current_round'] ?: 1)) }},
    get meNpv() {
        let r = this.meRate / 100;
        let npv = -this.meInvestment;
        for (let i = 1; i <= this.meRounds; i++) {
            npv += this.meFlow / Math.pow(1 + r, i);
        }
        return Math.round(npv * 100) / 100;
    },
    get mePayback() {
        return this.meFlow > 0 ? (this.meInvestment / this.meFlow).toFixed(1) + ' rondas' : 'No recuperable';
    }
}">
    {{-- BARRA SUPERIOR --}}
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;flex-wrap:wrap;gap:0.75rem;">
        <a href="{{ route('mmom.games.index') }}" style="color:#58a6ff;text-decoration:none;font-weight:bold;font-size:0.95rem;">
            ← Volver al Listado de Partidas
        </a>
        <div style="display:flex;gap:0.5rem;align-items:center;">
            <a href="{{ route('mmom.games.export', ['gameId' => $snapshot['game']['id']]) }}" 
               target="_blank"
               style="background:#21262d;color:#79c0ff;border:1px solid #30363d;padding:0.4rem 0.8rem;border-radius:6px;text-decoration:none;font-size:0.85rem;font-weight:600;">
                💾 Exportar JSON
            </a>
            <span class="tag">Simulación MMOM · E7/E8 V1.0</span>
        </div>
    </div>

    {{-- ENCABEZADO DE LA PARTIDA --}}
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;flex-wrap:wrap;gap:0.75rem;">
        <div>
            <h1 style="color:#eef1fb;margin:0 0 0.3rem 0;font-size:1.8rem;">{{ $snapshot['game']['name'] }}</h1>
            <div style="display:flex;gap:0.5rem;flex-wrap:wrap;align-items:center;">
                @php
                    $statusColor = match($snapshot['game']['status']) {
                        'active' => '#238636',
                        'prepared' => '#1f6feb',
                        'completed' => '#8957e5',
                        'draft' => '#d29922',
                        default => '#6e7681'
                    };
                @endphp
                <span style="background:{{ $statusColor }};color:#fff;padding:0.2rem 0.6rem;border-radius:4px;font-weight:bold;font-size:0.8rem;">
                    {{ strtoupper($snapshot['game']['status']) }}
                </span>
                <span style="color:#8b949e;font-size:0.85rem;">·</span>
                <span style="color:#79c0ff;font-weight:600;font-size:0.9rem;">
                    {{ $snapshot['game']['current_round'] ? "Ronda {$snapshot['game']['current_round']} de 8" : 'Fase de Preparación' }}
                </span>
                <span style="color:#8b949e;font-size:0.85rem;">·</span>
                <span style="color:#8b949e;font-size:0.85rem;">Capital ref.: Bs 100.000 / equipo</span>
            </div>
        </div>

        {{-- CRONÓMETRO CENTRAL --}}
        <div style="background:#161b22;border:1px solid #30363d;padding:0.6rem 1rem;border-radius:8px;text-align:right;">
            <div style="font-size:0.75rem;color:#8b949e;text-transform:uppercase;letter-spacing:0.05em;">Reloj del Servidor</div>
            @if($snapshot['seconds_remaining'] > 0)
                <div style="font-size:1.3rem;font-weight:bold;color:#7ee787;">
                    ⏱️ {{ gmdate("i:s", $snapshot['seconds_remaining']) }}
                </div>
            @else
                <div style="font-size:1.05rem;font-weight:600;color:#8b949e;">
                    ⏱️ Reloj detenido
                </div>
            @endif
        </div>
    </div>

    {{-- MENSAJES Y NOTIFICACIONES --}}
    @if($notice)
        <div style="background: {{ str_starts_with($notice, '⚠️') ? '#491b1f' : '#133929' }};
                    border: 1px solid {{ str_starts_with($notice, '⚠️') ? '#f85149' : '#2ea043' }};
                    color: {{ str_starts_with($notice, '⚠️') ? '#ff7b72' : '#7ee787' }};
                    padding: 0.9rem 1.2rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 500;">
            {{ $notice }}
        </div>
    @endif

    {{-- BARRA DE PESTAÑAS (0ms latencia con Alpine.js) --}}
    <div style="display:flex;gap:0.4rem;border-bottom:2px solid #30363d;margin-bottom:1.5rem;overflow-x:auto;padding-bottom:2px;">
        <button type="button" 
                @click="activeTab = 'decisions'"
                :style="activeTab === 'decisions' ? 'background:#1f6feb;color:#fff;border-color:#1f6feb;' : 'background:#21262d;color:#8b949e;border-color:#30363d;'"
                style="padding:0.6rem 1.1rem;border-radius:6px 6px 0 0;font-weight:bold;font-size:0.9rem;border:1px solid;cursor:pointer;display:flex;align-items:center;gap:0.4rem;white-space:nowrap;">
            🎮 Decisiones de Ronda
        </button>

        @if($snapshot['can_manage'])
            <button type="button" 
                    @click="activeTab = 'operator'"
                    :style="activeTab === 'operator' ? 'background:#8957e5;color:#fff;border-color:#8957e5;' : 'background:#21262d;color:#8b949e;border-color:#30363d;'"
                    style="padding:0.6rem 1.1rem;border-radius:6px 6px 0 0;font-weight:bold;font-size:0.9rem;border:1px solid;cursor:pointer;display:flex;align-items:center;gap:0.4rem;white-space:nowrap;">
                🛠️ Panel del Operador
            </button>
        @endif

        <button type="button" 
                @click="activeTab = 'results'"
                :style="activeTab === 'results' ? 'background:#238636;color:#fff;border-color:#238636;' : 'background:#21262d;color:#8b949e;border-color:#30363d;'"
                style="padding:0.6rem 1.1rem;border-radius:6px 6px 0 0;font-weight:bold;font-size:0.9rem;border:1px solid;cursor:pointer;display:flex;align-items:center;gap:0.4rem;white-space:nowrap;">
            📈 Resultados Financieros
        </button>

        <button type="button" 
                @click="activeTab = 'ranking'"
                :style="activeTab === 'ranking' ? 'background:#d29922;color:#fff;border-color:#d29922;' : 'background:#21262d;color:#8b949e;border-color:#30363d;'"
                style="padding:0.6rem 1.1rem;border-radius:6px 6px 0 0;font-weight:bold;font-size:0.9rem;border:1px solid;cursor:pointer;display:flex;align-items:center;gap:0.4rem;white-space:nowrap;">
            🏆 Clasificación MIEC
        </button>

        <button type="button" 
                @click="activeTab = 'calculators'"
                :style="activeTab === 'calculators' ? 'background:#388bfd;color:#fff;border-color:#388bfd;' : 'background:#21262d;color:#8b949e;border-color:#30363d;'"
                style="padding:0.6rem 1.1rem;border-radius:6px 6px 0 0;font-weight:bold;font-size:0.9rem;border:1px solid;cursor:pointer;display:flex;align-items:center;gap:0.4rem;white-space:nowrap;">
            🧮 Laboratorio MPEAS / MEFER
        </button>
    </div>

    {{-- ======================================================== --}}
    {{-- PESTAÑA 1: DECISIONES DE RONDA                           --}}
    {{-- ======================================================== --}}
    <div x-show="activeTab === 'decisions'" style="display:block;">
        @if($snapshot['round'])
            <div class="card" style="border-left:4px solid #58a6ff;margin-bottom:1.5rem;">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
                    <div>
                        <h2 style="color:#58a6ff;margin:0 0 0.4rem 0;">Ronda {{ $snapshot['round']['round_number'] }} de 8</h2>
                        <div style="display:flex;gap:1.5rem;flex-wrap:wrap;">
                            <div>
                                <span class="muted">Estado:</span> 
                                <strong>{{ strtoupper($snapshot['round']['status']) }}</strong>
                            </div>
                            <div>
                                <span class="muted">Etapa activa:</span> 
                                <strong style="color:#e3b341;">
                                    {{ $snapshot['round']['current_stage'] ? "Etapa {$snapshot['round']['current_stage']} de 7" : 'Ronda sin etapa activa' }}
                                </strong>
                            </div>
                        </div>
                    </div>
                    <div>
                        @php
                            $stageNames = [
                                1 => 'Personal (Nómina)',
                                2 => 'Inventario (Bebidas y Mixers)',
                                3 => 'Precios (Entrada, Barra y VIP)',
                                4 => 'Publicidad (Visibilidad)',
                                5 => 'Infraestructura (Aforo y VIP)',
                                6 => 'Investigación de Mercado',
                                7 => 'Artistas Invitados'
                            ];
                            $cStage = $snapshot['round']['current_stage'];
                        @endphp
                        @if($cStage)
                            <span style="background:#1f6feb22;color:#79c0ff;border:1px solid #1f6feb;padding:0.4rem 0.8rem;border-radius:6px;font-weight:600;font-size:0.9rem;">
                                📍 {{ $stageNames[$cStage] ?? "Etapa {$cStage}" }}
                            </span>
                        @endif
                    </div>
                </div>

                @if(!$snapshot['can_manage'])
                    <p style="margin:1rem 0 0 0;font-size:0.9rem;">
                        Etapas confirmadas por tu empresa: 
                        <strong style="color:#7ee787;">{{ implode(', ', $snapshot['confirmed_stages']) ?: 'Ninguna todavía' }}</strong>
                    </p>
                @endif
            </div>
        @endif

        {{-- FORMULARIO DE DECISIÓN DEL JUGADOR --}}
        @if($snapshot['can_decide'])
            <div class="card" style="border:1px solid #38465f;">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.75rem;margin-bottom:1rem;">
                    <div>
                        <h2 style="color:#58a6ff;margin:0;">💼 Formulario de Decisión Empresarial</h2>
                        <p class="muted" style="font-size:0.85rem;margin:0.2rem 0 0 0;">
                            Formula los parámetros de tu discoteca para la Etapa {{ $snapshot['round']['current_stage'] }}.
                        </p>
                    </div>

                    {{-- BOTÓN DE PLANTILLA RECOMENDADA --}}
                    @if($snapshot['round']['current_stage'])
                        <button type="button" 
                                wire:click="loadStageTemplate({{ (int)$snapshot['round']['current_stage'] }})"
                                style="background:#21262d;color:#7ee787;border:1px solid #2ea043;padding:0.4rem 0.8rem;border-radius:6px;font-size:0.85rem;font-weight:600;cursor:pointer;">
                            📋 Cargar Plantilla Recomendada
                        </button>
                    @endif
                </div>

                @if(in_array($snapshot['round']['current_stage'], $snapshot['confirmed_stages'], true))
                    <div style="background:#133929;border:1px solid #2ea043;padding:1.2rem;border-radius:8px;color:#7ee787;">
                        🔒 <strong>Decisión Inmutable Confirmada:</strong> Tu empresa ya ha confirmado la decisión para la Etapa {{ $snapshot['round']['current_stage'] }}. No puede ser alterada. Espera a que el facilitador avance de etapa.
                    </div>
                @else
                    <label for="mmom-decision" style="font-weight:bold;color:#c9d1d9;margin-bottom:0.4rem;display:block;">
                        Borrador de Decisión (Objeto JSON estructurado):
                    </label>
                    <textarea id="mmom-decision" wire:model="draftJson" spellcheck="false" rows="8"
                              style="font-family:ui-monospace,SFMono-Regular,Consolas,monospace;font-size:0.95rem;line-height:1.4;width:100%;"></textarea>

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
                                Comprendo que confirmar vuelve <strong>irreversible e inmutable</strong> esta decisión para el cálculo de la simulación.
                            </span>
                        </label>
                    </div>

                    <button type="button" 
                            wire:click="confirmDecision" 
                            wire:loading.attr="disabled"
                            wire:target="confirmDecision"
                            wire:confirm="¿Estás completamente seguro de confirmar de forma irreversible este borrador?"
                            style="background:#6e40c9;padding:0.8rem 1.5rem;font-weight:bold;width:100%;color:#fff;border:none;border-radius:6px;font-size:0.95rem;cursor:pointer;">
                        <span wire:loading.remove wire:target="confirmDecision">🔒 Confirmar Decisión Definitiva (Inmutable)</span>
                        <span wire:loading wire:target="confirmDecision">⏳ Registrando confirmación...</span>
                    </button>
                @endif
            </div>
        @else
            @if($snapshot['can_manage'])
                <div class="card" style="background:#161b22;border:1px solid #30363d;text-align:center;padding:2rem;">
                    <p style="color:#79c0ff;font-size:1.1rem;margin:0 0 0.5rem 0;font-weight:bold;">
                        👑 Vista del Administrador / Facilitador
                    </p>
                    <p class="muted" style="font-size:0.9rem;margin:0 0 1.2rem 0;">
                        Como operador del sistema, utiliza la pestaña <strong>🛠️ Panel del Operador</strong> para avanzar etapas, autocompletar decisiones de prueba y ejecutar la simulación de la ronda.
                    </p>
                    <button type="button" @click="activeTab = 'operator'" 
                            style="background:#8957e5;color:#fff;padding:0.6rem 1.2rem;border:none;border-radius:6px;font-weight:bold;cursor:pointer;">
                        Ir al Panel del Operador →
                    </button>
                </div>
            @else
                <div class="card" style="background:#1c293f;padding:1.5rem;border-radius:8px;text-align:center;">
                    <p style="color:#8b949e;margin:0;font-size:1.05rem;">
                        ⏸️ No hay una etapa activa abierta para formular decisiones en este momento. Espera a que el facilitador active la ronda o etapa.
                    </p>
                </div>
            @endif
        @endif
    </div>

    {{-- ======================================================== --}}
    {{-- PESTAÑA 2: PANEL DEL OPERADOR (SOLO ADMIN)                --}}
    {{-- ======================================================== --}}
    @if($snapshot['can_manage'])
        <div x-show="activeTab === 'operator'" style="display:block;">
            <div class="card" style="background:#16243b;border:2px solid #1f6feb;margin-bottom:1.5rem;">
                <h2 style="color:#79c0ff;margin-top:0;">🛠️ Control del Facilitador y Simulación</h2>
                <p class="muted" style="font-size:0.9rem;margin-bottom:1.2rem;">
                    Acciones maestras para dirigir la simulación. Todos los botones cuentan con salvaguardas contra dobles clics.
                </p>

                <div style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:center;">
                    {{-- Preparar --}}
                    @if($snapshot['game']['status'] === 'draft')
                        <button type="button" 
                                wire:click="prepareGame" 
                                wire:loading.attr="disabled"
                                wire:target="prepareGame"
                                wire:confirm="¿Preparar esta partida? Se verificarán las 7 discotecas del catálogo."
                                style="background:#1f6feb;padding:0.8rem 1.4rem;font-size:0.95rem;font-weight:bold;border:none;border-radius:6px;color:#fff;cursor:pointer;">
                            <span wire:loading.remove wire:target="prepareGame">⚙️ Preparar Partida</span>
                            <span wire:loading wire:target="prepareGame">⏳ Preparando...</span>
                        </button>
                    @endif

                    {{-- Iniciar Ronda 1 --}}
                    @if($snapshot['game']['status'] === 'prepared')
                        <button type="button" 
                                wire:click="startFirstRound" 
                                wire:loading.attr="disabled"
                                wire:target="startFirstRound"
                                wire:confirm="¿Iniciar la Ronda 1 con 20 minutos de cronómetro en el servidor?"
                                style="background:#238636;padding:0.8rem 1.4rem;font-size:0.95rem;font-weight:bold;border:none;border-radius:6px;color:#fff;cursor:pointer;box-shadow:0 4px 12px rgba(35,134,54,0.3);">
                            <span wire:loading.remove wire:target="startFirstRound">▶️ Iniciar Ronda 1 (20 Minutos)</span>
                            <span wire:loading wire:target="startFirstRound">⏳ Iniciando ronda 1...</span>
                        </button>
                    @endif

                    {{-- Controles durante ronda activa --}}
                    @if($snapshot['game']['status'] === 'active' && $snapshot['round'] && $snapshot['round']['status'] === 'active')
                        {{-- Avanzar Etapa --}}
                        <button type="button" 
                                wire:click="nextStage" 
                                wire:loading.attr="disabled"
                                wire:target="nextStage"
                                wire:confirm="¿Avanzar a la siguiente etapa de decisión para todos los participantes?"
                                style="background:#8957e5;padding:0.75rem 1.2rem;font-size:0.9rem;font-weight:bold;border:none;border-radius:6px;color:#fff;cursor:pointer;">
                            <span wire:loading.remove wire:target="nextStage">⏩ Avanzar Etapa</span>
                            <span wire:loading wire:target="nextStage">⏳ Avanzando etapa...</span>
                        </button>

                        {{-- Autocompletar Etapa Actual (Demo) --}}
                        <button type="button" 
                                wire:click="autofillDemo(false)" 
                                wire:loading.attr="disabled"
                                wire:target="autofillDemo(false)"
                                wire:confirm="¿Autocompletar las decisiones de la etapa actual para los 7 equipos?"
                                style="background:#21262d;color:#e3b341;border:1px solid #d29922;padding:0.75rem 1.2rem;font-size:0.9rem;font-weight:bold;border-radius:6px;cursor:pointer;">
                            <span wire:loading.remove wire:target="autofillDemo(false)">⚡ Autocompletar Etapa Actual (Demo)</span>
                            <span wire:loading wire:target="autofillDemo(false)">⏳ Autocompletando...</span>
                        </button>

                        {{-- Autocompletar Toda la Ronda (Demo) --}}
                        <button type="button" 
                                wire:click="autofillDemo(true)" 
                                wire:loading.attr="disabled"
                                wire:target="autofillDemo(true)"
                                wire:confirm="¿Autocompletar todas las etapas restantes de la ronda para los 7 equipos?"
                                style="background:#21262d;color:#79c0ff;border:1px solid #1f6feb;padding:0.75rem 1.2rem;font-size:0.9rem;font-weight:bold;border-radius:6px;cursor:pointer;">
                            <span wire:loading.remove wire:target="autofillDemo(true)">⚡ Autocompletar Toda la Ronda (Demo)</span>
                            <span wire:loading wire:target="autofillDemo(true)">⏳ Completando ronda...</span>
                        </button>

                        {{-- Simular y Cerrar Ronda con MIFIV --}}
                        <button type="button" 
                                wire:click="simulateRound" 
                                wire:loading.attr="disabled"
                                wire:target="simulateRound"
                                wire:confirm="¿Ejecutar la simulación de mercado y cerrar la ronda con validación MIFIV?"
                                style="background:#238636;color:#fff;padding:0.75rem 1.4rem;font-size:0.9rem;font-weight:bold;border:none;border-radius:6px;cursor:pointer;box-shadow:0 4px 12px rgba(35,134,54,0.3);">
                            <span wire:loading.remove wire:target="simulateRound">📊 Validar y Cerrar Ronda con MIFIV</span>
                            <span wire:loading wire:target="simulateRound">⏳ Simulando mercado y MIFIV...</span>
                        </button>
                    @endif

                    {{-- Iniciar Siguiente Ronda --}}
                    @if($snapshot['round'] && in_array($snapshot['round']['status'], ['closed', 'published'], true) && (int)$snapshot['game']['current_round'] < 8)
                        @php $nextRNum = (int)$snapshot['game']['current_round'] + 1; @endphp
                        <button type="button" 
                                wire:click="startRound({{ $nextRNum }})" 
                                wire:loading.attr="disabled"
                                wire:target="startRound({{ $nextRNum }})"
                                wire:confirm="¿Iniciar la Ronda {{ $nextRNum }} con 20 minutos de reloj?"
                                style="background:#238636;padding:0.8rem 1.5rem;font-size:0.95rem;font-weight:bold;border:none;border-radius:6px;color:#fff;cursor:pointer;box-shadow:0 4px 12px rgba(35,134,54,0.3);">
                            <span wire:loading.remove wire:target="startRound({{ $nextRNum }})">▶️ Iniciar Ronda {{ $nextRNum }} →</span>
                            <span wire:loading wire:target="startRound({{ $nextRNum }})">⏳ Iniciando Ronda {{ $nextRNum }}...</span>
                        </button>
                    @endif

                    @if($snapshot['game']['status'] === 'completed')
                        <span style="background:#238636;color:#fff;padding:0.6rem 1rem;border-radius:6px;font-weight:bold;font-size:0.9rem;">
                            🏁 Partida Finalizada (8 Rondas Completadas)
                        </span>
                    @endif
                </div>
            </div>

            {{-- TABLA DE SEGUIMIENTO DE EMPRESAS --}}
            <div class="card" style="margin-bottom:1.5rem;">
                <h3 style="color:#58a6ff;margin-top:0;">📊 Avance de los 7 Equipos en la Ronda Actual</h3>
                <div class="tablewrap" style="overflow-x:auto;">
                    <table class="table" style="width:100%;border-collapse:collapse;font-size:0.9rem;">
                        <thead>
                            <tr style="border-bottom:1px solid #30363d;text-align:left;color:#8b949e;">
                                <th style="padding:0.6rem;">#</th>
                                <th style="padding:0.6rem;">Discoteca</th>
                                <th style="padding:0.6rem;">Participante Asignado</th>
                                <th style="padding:0.6rem;">Etapas Confirmadas</th>
                                <th style="padding:0.6rem;">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $maxStageExpected = ((int)($snapshot['game']['current_round'] ?: 1) === 1) ? 6 : 7;
                            @endphp
                            @foreach($snapshot['companies'] as $idx => $comp)
                                <tr style="border-bottom:1px solid #21262d;">
                                    <td style="padding:0.6rem;color:#8b949e;">{{ $idx + 1 }}</td>
                                    <td style="padding:0.6rem;font-weight:bold;color:#eef1fb;">{{ $comp['club_name'] }}</td>
                                    <td style="padding:0.6rem;color:#8b949e;">{{ $comp['player_name'] ?: 'Sin asignar' }}</td>
                                    <td style="padding:0.6rem;">
                                        <span style="font-weight:bold;color:{{ $comp['confirmed_stages_count'] >= $maxStageExpected ? '#7ee787' : '#e3b341' }};">
                                            {{ $comp['confirmed_stages_count'] }} / {{ $maxStageExpected }}
                                        </span>
                                    </td>
                                    <td style="padding:0.6rem;">
                                        @if($comp['confirmed_stages_count'] >= $maxStageExpected)
                                            <span style="background:#133929;color:#7ee787;padding:0.2rem 0.5rem;border-radius:4px;font-size:0.8rem;font-weight:bold;">
                                                ✓ Completa
                                            </span>
                                        @else
                                            <span style="background:#21262d;color:#8b949e;padding:0.2rem 0.5rem;border-radius:4px;font-size:0.8rem;">
                                                Pendiente
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- ======================================================== --}}
    {{-- PESTAÑA 3: RESULTADOS FINANCIEROS Y OPERATIVOS            --}}
    {{-- ======================================================== --}}
    <div x-show="activeTab === 'results'" style="display:block;">
        @if(!empty($snapshot['last_round_results']))
            <div class="card" style="margin-bottom:1.5rem;">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.75rem;margin-bottom:1rem;">
                    <div>
                        <h2 style="color:#58a6ff;margin:0;">📈 Resumen Operativo y Financiero (Ronda {{ $snapshot['candidate_round'] }})</h2>
                        <p class="muted" style="font-size:0.85rem;margin:0.2rem 0 0 0;">
                            Cálculos reconciliados de demanda Softmax, ventas, CPP, salarios, mantenimiento y préstamos PALO.
                        </p>
                    </div>
                    <span style="background:#133929;color:#7ee787;border:1px solid #2ea043;padding:0.3rem 0.7rem;border-radius:6px;font-size:0.85rem;font-weight:bold;">
                        ✓ Comprobación MIFIV Aprobada
                    </span>
                </div>

                <div class="tablewrap" style="overflow-x:auto;">
                    <table class="table" style="width:100%;border-collapse:collapse;font-size:0.9rem;">
                        <thead>
                            <tr style="border-bottom:2px solid #30363d;text-align:left;color:#8b949e;">
                                <th style="padding:0.6rem;">Discoteca</th>
                                <th style="padding:0.6rem;text-align:right;">Ingresos (Bs)</th>
                                <th style="padding:0.6rem;text-align:right;">Gastos (Bs)</th>
                                <th style="padding:0.6rem;text-align:right;">Utilidad Neta (Bs)</th>
                                <th style="padding:0.6rem;text-align:center;">Atendidos</th>
                                <th style="padding:0.6rem;text-align:center;">Rechazados</th>
                                <th style="padding:0.6rem;text-align:right;">PALO Emitido (Bs)</th>
                                <th style="padding:0.6rem;text-align:right;">Caja Resultante (Bs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($snapshot['last_round_results'] as $r)
                                <tr style="border-bottom:1px solid #21262d;">
                                    <td style="padding:0.6rem;font-weight:bold;color:#eef1fb;">{{ $r['club_name'] }}</td>
                                    <td style="padding:0.6rem;text-align:right;color:#79c0ff;font-weight:600;">
                                        Bs {{ number_format($r['revenue'], 2) }}
                                    </td>
                                    <td style="padding:0.6rem;text-align:right;color:#ff7b72;">
                                        Bs {{ number_format($r['expenses'], 2) }}
                                    </td>
                                    <td style="padding:0.6rem;text-align:right;font-weight:bold;color:{{ $r['net_profit'] >= 0 ? '#7ee787' : '#f85149' }};">
                                        Bs {{ number_format($r['net_profit'], 2) }}
                                    </td>
                                    <td style="padding:0.6rem;text-align:center;color:#eef1fb;">{{ number_format($r['served']) }}</td>
                                    <td style="padding:0.6rem;text-align:center;color:#8b949e;">{{ number_format($r['rejected']) }}</td>
                                    <td style="padding:0.6rem;text-align:right;color:{{ $r['palo_issued'] > 0 ? '#e3b341' : '#8b949e' }};">
                                        Bs {{ number_format($r['palo_issued'], 2) }}
                                    </td>
                                    <td style="padding:0.6rem;text-align:right;font-weight:600;color:#7ee787;">
                                        Bs {{ number_format($r['cash'], 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="card" style="text-align:center;padding:3rem 1.5rem;background:#161b22;border:1px solid #30363d;">
                <div style="font-size:2.5rem;margin-bottom:0.8rem;">📊</div>
                <h3 style="color:#eef1fb;margin:0 0 0.5rem 0;">Aún no se han generado resultados de simulación</h3>
                <p class="muted" style="max-width:550px;margin:0 auto 1.2rem auto;font-size:0.9rem;">
                    Los resultados financieros, la asistencia de clientes y los préstamos PALO se calcularán automáticamente cuando el facilitador cierre la primera ronda con validación MIFIV.
                </p>
            </div>
        @endif
    </div>

    {{-- ======================================================== --}}
    {{-- PESTAÑA 4: CLASIFICACIÓN COMPETITIVA (MIEC)               --}}
    {{-- ======================================================== --}}
    <div x-show="activeTab === 'ranking'" style="display:block;">
        @if(!empty($snapshot['miec_ranking']))
            <div class="card" style="margin-bottom:1.5rem;">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.75rem;margin-bottom:1rem;">
                    <div>
                        <h2 style="color:#d29922;margin:0;">🏆 Ranking Competitivo Multidimensional (MIEC)</h2>
                        <p class="muted" style="font-size:0.85rem;margin:0.2rem 0 0 0;">
                            Clasificación acumulada ponderada según las 5 dimensiones oficiales de desempeño empresarial.
                        </p>
                    </div>
                    <span style="background:#21262d;color:#d29922;border:1px solid #d29922;padding:0.3rem 0.7rem;border-radius:6px;font-size:0.85rem;font-weight:bold;">
                        Ronda Evaluada: {{ $snapshot['candidate_round'] }}
                    </span>
                </div>

                <div class="tablewrap" style="overflow-x:auto;">
                    <table class="table" style="width:100%;border-collapse:collapse;font-size:0.9rem;">
                        <thead>
                            <tr style="border-bottom:2px solid #30363d;text-align:left;color:#8b949e;">
                                <th style="padding:0.6rem;text-align:center;">Puesto</th>
                                <th style="padding:0.6rem;">Discoteca</th>
                                <th style="padding:0.6rem;text-align:right;">Puntos Totales</th>
                                <th style="padding:0.6rem;text-align:right;">Rentabilidad (30%)</th>
                                <th style="padding:0.6rem;text-align:right;">Mercado (25%)</th>
                                <th style="padding:0.6rem;text-align:right;">Logística (20%)</th>
                                <th style="padding:0.6rem;text-align:right;">Satisfacción (15%)</th>
                                <th style="padding:0.6rem;text-align:right;">Artistas (10%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($snapshot['miec_ranking'] as $row)
                                <tr style="border-bottom:1px solid #21262d;">
                                    <td style="padding:0.6rem;text-align:center;">
                                        <span style="font-size:1.1rem;font-weight:bold;color:{{ $row['rank'] === 1 ? '#d29922' : ($row['rank'] === 2 ? '#8b949e' : ($row['rank'] === 3 ? '#b08800' : '#8b949e')) }};">
                                            #{{ $row['rank'] }}
                                        </span>
                                    </td>
                                    <td style="padding:0.6rem;font-weight:bold;color:#eef1fb;">{{ $row['club_name'] }}</td>
                                    <td style="padding:0.6rem;text-align:right;font-weight:bold;color:#d29922;font-size:1.05rem;">
                                        {{ number_format($row['weighted_score'], 2) }}
                                    </td>
                                    <td style="padding:0.6rem;text-align:right;color:#79c0ff;">{{ number_format($row['profit_score'], 1) }}</td>
                                    <td style="padding:0.6rem;text-align:right;color:#7ee787;">{{ number_format($row['market_score'], 1) }}</td>
                                    <td style="padding:0.6rem;text-align:right;color:#e3b341;">{{ number_format($row['logistics_score'], 1) }}</td>
                                    <td style="padding:0.6rem;text-align:right;color:#bc8cff;">{{ number_format($row['satisfaction_score'], 1) }}</td>
                                    <td style="padding:0.6rem;text-align:right;color:#ff7b72;">{{ number_format($row['artist_score'], 1) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- GRÁFICO VISUAL DE BARRAS DE PUNTUACIÓN --}}
                <div style="margin-top:2rem;">
                    <h3 style="color:#58a6ff;margin-bottom:1rem;font-size:1rem;">Composición Comparativa del Desempeño</h3>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:1rem;">
                        @foreach($snapshot['miec_ranking'] as $r)
                            <div style="background:#161b22;border:1px solid #30363d;padding:0.8rem 1rem;border-radius:8px;">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.4rem;">
                                    <strong style="color:#eef1fb;">#{{ $r['rank'] }} {{ $r['club_name'] }}</strong>
                                    <span style="color:#d29922;font-weight:bold;">{{ number_format($r['weighted_score'], 1) }} pts</span>
                                </div>
                                <div style="background:#21262d;border-radius:4px;height:8px;overflow:hidden;">
                                    <div style="background:linear-gradient(90deg, #1f6feb, #238636, #d29922);height:100%;width:{{ min(100, max(5, $r['weighted_score'])) }}%;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @else
            <div class="card" style="text-align:center;padding:3rem 1.5rem;background:#161b22;border:1px solid #30363d;">
                <div style="font-size:2.5rem;margin-bottom:0.8rem;">♛</div>
                <h3 style="color:#eef1fb;margin:0 0 0.5rem 0;">La clasificación MIEC comenzará al cerrar la primera ronda</h3>
                <p class="muted" style="max-width:550px;margin:0 auto;font-size:0.9rem;">
                    El ranking evalúa a las 7 empresas mediante las 5 dimensiones ponderadas oficiales: Rentabilidad (30%), Mercado (25%), Logística (20%), Satisfacción (15%) y Artistas (10%).
                </p>
            </div>
        @endif
    </div>

    {{-- ======================================================== --}}
    {{-- PESTAÑA 5: LABORATORIO FINANCIERO (MPEAS / MEFER)         --}}
    {{-- ======================================================== --}}
    <div x-show="activeTab === 'calculators'" style="display:block;">
        <div style="margin-bottom:1.5rem;">
            <h2 style="color:#58a6ff;margin:0 0 0.3rem 0;">🧮 Laboratorio de Análisis Financiero</h2>
            <p class="muted" style="font-size:0.9rem;margin:0;">
                Calculadoras analíticas para proyectar y evaluar inversiones antes de confirmar decisiones. Herramientas consultivas de cálculo instantáneo.
            </p>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(340px, 1fr));gap:1.5rem;">
            {{-- MPEAS: PUNTO DE EQUILIBRIO Y SENSIBILIDAD --}}
            <div class="card" style="border:1px solid #38465f;">
                <h3 style="color:#79c0ff;margin-top:0;">📊 MPEAS · Punto de Equilibrio y Sensibilidad</h3>
                <p class="muted" style="font-size:0.85rem;margin-bottom:1rem;">
                    Determina la cantidad mínima de clientes necesarios para absorber costos fijos y proyecta la rentabilidad esperada.
                </p>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:1rem;">
                    <div>
                        <label style="font-size:0.8rem;color:#8b949e;display:block;margin-bottom:0.2rem;">Precio Promedio (Bs)</label>
                        <input type="number" x-model.number="mpPrice" min="1" step="1" style="width:100%;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="font-size:0.8rem;color:#8b949e;display:block;margin-bottom:0.2rem;">Costo Variable Unitario (Bs)</label>
                        <input type="number" x-model.number="mpVariable" min="0" step="1" style="width:100%;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="font-size:0.8rem;color:#8b949e;display:block;margin-bottom:0.2rem;">Costos Fijos por Ronda (Bs)</label>
                        <input type="number" x-model.number="mpFixed" min="0" step="100" style="width:100%;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="font-size:0.8rem;color:#8b949e;display:block;margin-bottom:0.2rem;">Demanda Estimada (Clientes)</label>
                        <input type="number" x-model.number="mpDemand" min="0" step="10" style="width:100%;box-sizing:border-box;">
                    </div>
                </div>

                <div style="background:#131e30;padding:1rem;border-radius:8px;border:1px solid #304870;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;font-size:0.9rem;">
                        <span class="muted">Margen de contribución:</span>
                        <strong style="color:#7ee787;">Bs <span x-text="mpContribution.toFixed(2)"></span> / cliente</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;font-size:0.9rem;">
                        <span class="muted">Punto de equilibrio:</span>
                        <strong style="color:#e3b341;"><span x-text="mpBreakeven !== null ? mpBreakeven : 'No alcanzable'"></span> clientes</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;font-size:0.9rem;">
                        <span class="muted">Utilidad esperada:</span>
                        <strong :style="mpProfit >= 0 ? 'color:#7ee787;' : 'color:#f85149;'">Bs <span x-text="mpProfit.toFixed(2)"></span></strong>
                    </div>
                    <hr style="border:0;border-top:1px solid #30363d;margin:0.6rem 0;">
                    <div style="font-size:0.8rem;color:#8b949e;">
                        Sensibilidad (-15% demanda): <strong :style="mpProfitMinus15 >= 0 ? 'color:#7ee787;' : 'color:#f85149;'">Bs <span x-text="mpProfitMinus15.toFixed(2)"></span></strong><br>
                        Sensibilidad (+15% demanda): <strong :style="mpProfitPlus15 >= 0 ? 'color:#7ee787;' : 'color:#f85149;'">Bs <span x-text="mpProfitPlus15.toFixed(2)"></span></strong>
                    </div>
                </div>
            </div>

            {{-- MEFER: EVALUACIÓN DE INVERSIONES --}}
            <div class="card" style="border:1px solid #38465f;">
                <h3 style="color:#79c0ff;margin-top:0;">💡 MEFER · Evaluación de Inversiones y VAN</h3>
                <p class="muted" style="font-size:0.85rem;margin-bottom:1rem;">
                    Estima el Valor Actual Neto (VAN) y período de retorno de inversiones en infraestructura o aforo.
                </p>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:1rem;">
                    <div>
                        <label style="font-size:0.8rem;color:#8b949e;display:block;margin-bottom:0.2rem;">Inversión Inicial (Bs)</label>
                        <input type="number" x-model.number="meInvestment" min="0" step="500" style="width:100%;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="font-size:0.8rem;color:#8b949e;display:block;margin-bottom:0.2rem;">Flujo Neto / Ronda (Bs)</label>
                        <input type="number" x-model.number="meFlow" step="250" style="width:100%;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="font-size:0.8rem;color:#8b949e;display:block;margin-bottom:0.2rem;">Tasa de Descuento (%)</label>
                        <input type="number" x-model.number="meRate" min="0" max="100" step="0.5" style="width:100%;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="font-size:0.8rem;color:#8b949e;display:block;margin-bottom:0.2rem;">Rondas Restantes</label>
                        <input type="number" x-model.number="meRounds" min="1" max="8" style="width:100%;box-sizing:border-box;">
                    </div>
                </div>

                <div style="background:#131e30;padding:1rem;border-radius:8px;border:1px solid #304870;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;font-size:0.9rem;">
                        <span class="muted">VAN estimado:</span>
                        <strong :style="meNpv >= 0 ? 'color:#7ee787;' : 'color:#f85149;'">Bs <span x-text="meNpv.toFixed(2)"></span></strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;font-size:0.9rem;">
                        <span class="muted">Período de retorno simple:</span>
                        <strong style="color:#79c0ff;"><span x-text="mePayback"></span></strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;font-size:0.9rem;">
                        <span class="muted">Viabilidad financiera:</span>
                        <strong :style="meNpv > 0 ? 'color:#7ee787;' : 'color:#e3b341;'"><span x-text="meNpv > 0 ? 'Rentable (VAN > 0)' : 'Requiere revisión'"></span></strong>
                    </div>
                    <hr style="border:0;border-top:1px solid #30363d;margin:0.6rem 0;">
                    <div style="font-size:0.8rem;color:#8b949e;">
                        ⚠️ <strong>Regla MMOM:</strong> Las inversiones de capital solo pueden ser solventadas con fondos propios libres de la discoteca; nunca podrán financiarse mediante préstamos automáticos PALO.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
