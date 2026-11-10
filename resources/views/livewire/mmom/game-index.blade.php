<section x-data="{ activeTab: 'games', showModal: false }">
    {{-- Navegación por pestañas instantánea en el navegador (0 ms) --}}
    <div style="display:flex;gap:0.75rem;margin-bottom:1.5rem;border-bottom:2px solid #30363d;padding-bottom:0.75rem;">
        <button type="button" 
                @click="activeTab = 'games'" 
                :style="activeTab === 'games' ? 'background:#1f6feb;color:#ffffff;border-color:#1f6feb;' : 'background:#21262d;color:#c9d1d9;border-color:#30363d;'"
                style="padding:0.65rem 1.3rem;font-size:0.95rem;border-radius:6px;font-weight:700;border:1px solid;cursor:pointer;">
            🎮 Partidas de Simulación
        </button>
        <button type="button" 
                @click="activeTab = 'tutorial'" 
                :style="activeTab === 'tutorial' ? 'background:#238636;color:#ffffff;border-color:#238636;' : 'background:#21262d;color:#c9d1d9;border-color:#30363d;'"
                style="padding:0.65rem 1.3rem;font-size:0.95rem;border-radius:6px;font-weight:700;border:1px solid;cursor:pointer;">
            📖 Tutorial y Guía del Operador
        </button>
    </div>

    {{-- Avisos del sistema --}}
    @if($actionNotice)
        <div style="background:#133929;border:1px solid #2ea043;padding:1rem 1.25rem;border-radius:8px;margin-bottom:1.5rem;color:#7ee787;font-weight:600;font-size:0.95rem;">
            ✓ {{ $actionNotice }}
        </div>
    @endif

    @if($actionError)
        <div style="background:#491b1f;border:1px solid #f85149;padding:1rem 1.25rem;border-radius:8px;margin-bottom:1.5rem;color:#ff7b72;font-weight:600;font-size:0.95rem;">
            ⚠️ {{ $actionError }}
        </div>
    @endif

    {{-- ============================================================== --}}
    {{-- PESTAÑA 1: LISTADO Y GESTIÓN DE PARTIDAS                       --}}
    {{-- ============================================================== --}}
    <div x-show="activeTab === 'games'" style="display:block;">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
            <div>
                <h1 style="margin:0 0 0.3rem 0;color:#f0f6fc;font-size:1.8rem;font-weight:700;">Partidas de Simulación</h1>
                <p style="margin:0;color:#8b949e;font-size:0.95rem;">Listado de entornos competitivos activos y preparados.</p>
            </div>

            @if($isAdmin)
                <button type="button" 
                        @click="showModal = true" 
                        style="background:#238636;color:#ffffff;padding:0.75rem 1.5rem;font-weight:700;font-size:1rem;border:none;border-radius:6px;cursor:pointer;box-shadow:0 4px 12px rgba(35,134,54,0.3);">
                    ➕ Crear Nueva Partida
                </button>
            @endif
        </div>

        {{-- Modal / Formulario de Creación de Partida --}}
        <div x-show="showModal" style="display:none;" 
             style="background:#161b22;border:2px solid #58a6ff;padding:1.75rem;border-radius:10px;margin-bottom:2rem;box-shadow:0 12px 28px rgba(0,0,0,0.5);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                <h2 style="margin:0;color:#58a6ff;font-size:1.3rem;">Nueva Partida de Simulación MMOM</h2>
                <button type="button" class="secondary" @click="showModal = false" style="padding:0.35rem 0.75rem;font-size:0.85rem;cursor:pointer;">✖ Cancelar</button>
            </div>
            
            <p style="color:#c9d1d9;font-size:0.9rem;margin-bottom:1.2rem;">
                Este asistente configurará automáticamente el entorno para las 7 discotecas competidoras y sus 8 rondas de juego.
            </p>

            <form wire:submit.prevent="createGame">
                <div style="margin-bottom:1.2rem;">
                    <label for="gameName" style="color:#f0f6fc;font-weight:600;margin-bottom:0.4rem;display:block;">Nombre de la Partida / Torneo:</label>
                    <input id="gameName" type="text" wire:model="newGameName" 
                           style="width:100%;padding:0.75rem;background:#0d1117;border:1px solid #30363d;border-radius:6px;color:#f0f6fc;font-size:1rem;"
                           placeholder="Ejemplo: Torneo Universitario 2026 - Grupo A">
                    @error('newGameName') <span style="color:#ff7b72;font-size:0.85rem;font-weight:500;">{{ $message }}</span> @enderror
                </div>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;margin-bottom:1.2rem;">
                    <div>
                        <label style="color:#f0f6fc;font-weight:600;margin-bottom:0.4rem;display:block;">Modelo de Mercado Inicial:</label>
                        <select wire:model="initialModel" style="width:100%;padding:0.75rem;background:#0d1117;border:1px solid #30363d;border-radius:6px;color:#f0f6fc;font-size:0.95rem;">
                            <option value="A" style="background:#0d1117;color:#f0f6fc;">Modelo A (Condiciones Estándar de Mercado)</option>
                            <option value="B" style="background:#0d1117;color:#f0f6fc;">Modelo B (Mayor Variabilidad Competitiva)</option>
                        </select>
                    </div>
                </div>

                <div style="background:#0d1117;padding:1.1rem;border-radius:8px;border:1px dashed #30363d;margin-bottom:1.5rem;">
                    <label style="display:flex;align-items:flex-start;gap:0.75rem;cursor:pointer;margin:0;">
                        <input type="checkbox" wire:model="autoAssignAndPrepare" style="margin-top:0.25rem;transform:scale(1.2);">
                        <div>
                            <strong style="color:#79c0ff;font-size:0.95rem;">Configuración Automática con 1 Clic (Recomendado)</strong>
                            <p style="margin:0.25rem 0 0 0;font-size:0.85rem;color:#c9d1d9;">
                                Asignará automáticamente a los 7 participantes oficiales (<code>jugador1@mmom.com</code> al <code>jugador7@mmom.com</code>) a las 7 discotecas del catálogo y dejará las 8 rondas listas para iniciar.
                            </p>
                        </div>
                    </label>
                </div>

                <div style="display:flex;gap:1rem;align-items:center;">
                    <button type="submit" 
                            wire:loading.attr="disabled"
                            wire:target="createGame"
                            style="background:#238636;color:#ffffff;padding:0.85rem 1.8rem;font-weight:700;font-size:1rem;border:none;border-radius:6px;cursor:pointer;">
                        <span wire:loading.remove wire:target="createGame">🚀 Crear y Preparar Partida</span>
                        <span wire:loading wire:target="createGame">⏳ Configurando partida... por favor espera</span>
                    </button>
                    <button type="button" class="secondary" @click="showModal = false" wire:loading.attr="disabled" style="cursor:pointer;">
                        Descartar
                    </button>
                </div>
            </form>
        </div>

        {{-- Grid de Partidas --}}
        <div class="grid">
            @forelse($games as $game)
                <article class="card" style="border:1px solid #30363d;background:#161b22;box-shadow:0 6px 16px rgba(0,0,0,0.3);">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;">
                        <h2 style="margin:0;font-size:1.25rem;color:#f0f6fc;font-weight:700;">{{ $game['name'] }}</h2>
                        @php
                            $badgeColor = match($game['status']) {
                                'active' => '#238636',
                                'prepared' => '#1f6feb',
                                'draft' => '#d29922',
                                'completed' => '#8957e5',
                                default => '#6e7681'
                            };
                            $badgeText = match($game['status']) {
                                'active' => 'EN JUEGO',
                                'prepared' => 'PREPARADA',
                                'draft' => 'BORRADOR',
                                'completed' => 'FINALIZADA',
                                default => strtoupper($game['status'])
                            };
                        @endphp
                        <span style="background:{{ $badgeColor }};color:#ffffff;font-size:0.75rem;padding:0.25rem 0.55rem;border-radius:4px;font-weight:700;letter-spacing:0.03em;">
                            {{ $badgeText }}
                        </span>
                    </div>

                    <p style="margin:0.5rem 0;color:#c9d1d9;font-size:0.95rem;">
                        Ronda actual: <strong style="color:#58a6ff;">{{ $game['current_round'] ? "Ronda {$game['current_round']} de 8" : 'Sin iniciar' }}</strong>
                    </p>

                    <p style="font-size:0.85rem;color:#8b949e;margin-bottom:1.3rem;">
                        ID: <code style="font-size:0.8rem;color:#79c0ff;background:#0d1117;padding:0.2rem 0.4rem;border-radius:4px;border:1px solid #30363d;">{{ substr($game['id'], 0, 8) }}...</code>
                    </p>

                    <div style="display:flex;gap:0.5rem;align-items:center;">
                        <a href="{{ route('mmom.games.show',['gameId'=>$game['id']]) }}" 
                           style="flex:1;text-align:center;background:#1f6feb;color:#ffffff;text-decoration:none;padding:0.75rem;border-radius:6px;font-weight:700;font-size:0.95rem;">
                            🎮 Abrir Consola
                        </a>
                        <a href="{{ route('mmom.games.export',['gameId'=>$game['id']]) }}" 
                           target="_blank"
                           title="Exportar respaldo JSON de la partida"
                           style="background:#21262d;color:#79c0ff;border:1px solid #30363d;padding:0.75rem 0.95rem;border-radius:6px;text-decoration:none;font-size:0.9rem;font-weight:700;">
                            💾 JSON
                        </a>
                    </div>
                </article>
            @empty
                <div style="grid-column: 1 / -1; background:#161b22;border:1px dashed #30363d;padding:3rem 2rem;text-align:center;border-radius:10px;">
                    <h3 style="color:#f0f6fc;margin-top:0;font-size:1.25rem;">No hay partidas disponibles para esta cuenta</h3>
                    <p style="max-width:550px;margin:0.5rem auto 1.5rem auto;color:#c9d1d9;font-size:0.95rem;line-height:1.5;">
                        @if($isAdmin)
                            Como administrador, puedes crear la primera partida de simulación con el botón de abajo. Los 7 jugadores se asignarán automáticamente a las discotecas oficiales.
                        @else
                            Tu cuenta aún no tiene asignada una discoteca en ninguna partida activa. Solicita al administrador que te incorpore.
                        @endif
                    </p>
                    @if($isAdmin)
                        <button type="button" @click="showModal = true" style="background:#238636;color:#ffffff;padding:0.8rem 1.7rem;font-weight:700;font-size:1rem;border:none;border-radius:6px;cursor:pointer;">
                            ➕ Crear Primera Partida Ahora
                        </button>
                    @endif
                </div>
            @endforelse
        </div>
    </div>

    {{-- ============================================================== --}}
    {{-- PESTAÑA 2: TUTORIAL Y GUÍA PASO A PASO DEL OPERADOR           --}}
    {{-- ============================================================== --}}
    <div x-show="activeTab === 'tutorial'" style="display:none;">
        <article class="card" style="padding:2rem;line-height:1.65;background:#161b22;border:1px solid #30363d;">
            <h1 style="color:#58a6ff;margin-top:0;font-size:1.6rem;font-weight:700;">📖 Manual de Operación de la Simulación MMOM</h1>
            <p style="color:#c9d1d9;font-size:0.95rem;">
                Esta guía está diseñada para que cualquier facilitador, docente u operador pueda conducir una sesión de simulación estratégica sin cometer errores técnicos.
            </p>

            <hr style="border:0;border-top:1px solid #30363d;margin:1.5rem 0;">

            <h2 style="color:#79c0ff;font-size:1.25rem;">Fase 1: Preparación y Cuentas de Acceso</h2>
            <p style="color:#c9d1d9;">La simulación MMOM funciona como un mercado competitivo cerrado donde operan exactamente las <strong style="color:#f0f6fc;">7 discotecas oficiales del catálogo</strong> a lo largo de <strong style="color:#f0f6fc;">8 rondas</strong>. Los accesos del sistema son:</p>
            
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;margin:1rem 0;font-size:0.9rem;">
                    <thead>
                        <tr style="background:#21262d;color:#f0f6fc;text-align:left;">
                            <th style="padding:0.75rem;border:1px solid #30363d;">Rol</th>
                            <th style="padding:0.75rem;border:1px solid #30363d;">Correo</th>
                            <th style="padding:0.75rem;border:1px solid #30363d;">Contraseña</th>
                            <th style="padding:0.75rem;border:1px solid #30363d;">Discoteca del Catálogo Asignada</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="background:#161b22;"><td style="padding:0.7rem;border:1px solid #30363d;"><span style="background:#238636;color:#fff;padding:0.15rem 0.45rem;border-radius:4px;font-weight:700;font-size:0.8rem;">ADMIN</span></td><td style="padding:0.7rem;border:1px solid #30363d;"><code style="color:#79c0ff;background:#0d1117;padding:0.15rem 0.35rem;border-radius:4px;">admin@mmom.com</code></td><td style="padding:0.7rem;border:1px solid #30363d;"><code style="color:#c9d1d9;background:#0d1117;padding:0.15rem 0.35rem;border-radius:4px;">password123</code></td><td style="padding:0.7rem;border:1px solid #30363d;color:#f0f6fc;font-weight:600;">Control Central / Facilitador</td></tr>
                        <tr style="background:#161b22;"><td style="padding:0.7rem;border:1px solid #30363d;color:#c9d1d9;">JUGADOR 1</td><td style="padding:0.7rem;border:1px solid #30363d;"><code style="color:#79c0ff;background:#0d1117;padding:0.15rem 0.35rem;border-radius:4px;">jugador1@mmom.com</code></td><td style="padding:0.7rem;border:1px solid #30363d;"><code style="color:#c9d1d9;background:#0d1117;padding:0.15rem 0.35rem;border-radius:4px;">password123</code></td><td style="padding:0.7rem;border:1px solid #30363d;color:#f0f6fc;font-weight:600;">La Paceña</td></tr>
                        <tr style="background:#161b22;"><td style="padding:0.7rem;border:1px solid #30363d;color:#c9d1d9;">JUGADOR 2</td><td style="padding:0.7rem;border:1px solid #30363d;"><code style="color:#79c0ff;background:#0d1117;padding:0.15rem 0.35rem;border-radius:4px;">jugador2@mmom.com</code></td><td style="padding:0.7rem;border:1px solid #30363d;"><code style="color:#c9d1d9;background:#0d1117;padding:0.15rem 0.35rem;border-radius:4px;">password123</code></td><td style="padding:0.7rem;border:1px solid #30363d;color:#f0f6fc;font-weight:600;">Jet</td></tr>
                        <tr style="background:#161b22;"><td style="padding:0.7rem;border:1px solid #30363d;color:#c9d1d9;">JUGADOR 3</td><td style="padding:0.7rem;border:1px solid #30363d;"><code style="color:#79c0ff;background:#0d1117;padding:0.15rem 0.35rem;border-radius:4px;">jugador3@mmom.com</code></td><td style="padding:0.7rem;border:1px solid #30363d;"><code style="color:#c9d1d9;background:#0d1117;padding:0.15rem 0.35rem;border-radius:4px;">password123</code></td><td style="padding:0.7rem;border:1px solid #30363d;color:#f0f6fc;font-weight:600;">Luna Park</td></tr>
                        <tr style="background:#161b22;"><td style="padding:0.7rem;border:1px solid #30363d;color:#c9d1d9;">JUGADOR 4</td><td style="padding:0.7rem;border:1px solid #30363d;"><code style="color:#79c0ff;background:#0d1117;padding:0.15rem 0.35rem;border-radius:4px;">jugador4@mmom.com</code></td><td style="padding:0.7rem;border:1px solid #30363d;"><code style="color:#c9d1d9;background:#0d1117;padding:0.15rem 0.35rem;border-radius:4px;">password123</code></td><td style="padding:0.7rem;border:1px solid #30363d;color:#f0f6fc;font-weight:600;">Forum</td></tr>
                        <tr style="background:#161b22;"><td style="padding:0.7rem;border:1px solid #30363d;color:#c9d1d9;">JUGADOR 5</td><td style="padding:0.7rem;border:1px solid #30363d;"><code style="color:#79c0ff;background:#0d1117;padding:0.15rem 0.35rem;border-radius:4px;">jugador5@mmom.com</code></td><td style="padding:0.7rem;border:1px solid #30363d;"><code style="color:#c9d1d9;background:#0d1117;padding:0.15rem 0.35rem;border-radius:4px;">password123</code></td><td style="padding:0.7rem;border:1px solid #30363d;color:#f0f6fc;font-weight:600;">Fabri-K</td></tr>
                        <tr style="background:#161b22;"><td style="padding:0.7rem;border:1px solid #30363d;color:#c9d1d9;">JUGADOR 6</td><td style="padding:0.7rem;border:1px solid #30363d;"><code style="color:#79c0ff;background:#0d1117;padding:0.15rem 0.35rem;border-radius:4px;">jugador6@mmom.com</code></td><td style="padding:0.7rem;border:1px solid #30363d;"><code style="color:#c9d1d9;background:#0d1117;padding:0.15rem 0.35rem;border-radius:4px;">password123</code></td><td style="padding:0.7rem;border:1px solid #30363d;color:#f0f6fc;font-weight:600;">Ibiza</td></tr>
                        <tr style="background:#161b22;"><td style="padding:0.7rem;border:1px solid #30363d;color:#c9d1d9;">JUGADOR 7</td><td style="padding:0.7rem;border:1px solid #30363d;"><code style="color:#79c0ff;background:#0d1117;padding:0.15rem 0.35rem;border-radius:4px;">jugador7@mmom.com</code></td><td style="padding:0.7rem;border:1px solid #30363d;"><code style="color:#c9d1d9;background:#0d1117;padding:0.15rem 0.35rem;border-radius:4px;">password123</code></td><td style="padding:0.7rem;border:1px solid #30363d;color:#f0f6fc;font-weight:600;">Green Planet</td></tr>
                    </tbody>
                </table>
            </div>

            <hr style="border:0;border-top:1px solid #30363d;margin:1.5rem 0;">

            <h2 style="color:#79c0ff;font-size:1.25rem;">Fase 2: Cómo Crear una Partida (1 Clic)</h2>
            <ol style="padding-left:1.2rem;color:#c9d1d9;">
                <li style="margin-bottom:0.4rem;">Haz clic en el botón verde <strong style="color:#f0f6fc;">"➕ Crear Nueva Partida"</strong> en la pestaña de Partidas.</li>
                <li style="margin-bottom:0.4rem;">Escribe un nombre identificable para la sesión (ejemplo: <em>"Torneo Gestión Estratégica 2026"</em>).</li>
                <li style="margin-bottom:0.4rem;">Deja marcada la casilla <strong style="color:#79c0ff;">"Configuración Automática con 1 Clic"</strong>. El sistema creará las 7 empresas, vinculará a los 7 jugadores oficiales y preparará las 8 rondas automáticamente.</li>
                <li style="margin-bottom:0.4rem;">Presiona <strong style="color:#3fb950;">"🚀 Crear y Preparar Partida"</strong>. La partida quedará en estado <code style="color:#79c0ff;background:#0d1117;padding:0.1rem 0.3rem;border-radius:3px;">PREPARADA</code>.</li>
            </ol>

            <hr style="border:0;border-top:1px solid #30363d;margin:1.5rem 0;">

            <h2 style="color:#79c0ff;font-size:1.25rem;">Fase 3: Inicio de Ronda y Control del Reloj (20 Minutos)</h2>
            <ol style="padding-left:1.2rem;color:#c9d1d9;">
                <li style="margin-bottom:0.4rem;">Haz clic en <strong style="color:#58a6ff;">"🎮 Abrir Consola de Simulación"</strong> en la tarjeta de la partida creada.</li>
                <li style="margin-bottom:0.4rem;">En la pestaña <strong style="color:#a371f7;">🛠️ Panel del Operador</strong> presiona el botón verde <strong style="color:#3fb950;">"▶️ Iniciar Ronda 1"</strong>.</li>
                <li style="margin-bottom:0.4rem;">El reloj central del servidor arrancará en cuenta regresiva de <strong style="color:#f0f6fc;">20 minutos (1200 segundos)</strong> sincronizado vía UTC para todos los participantes.</li>
            </ol>

            <hr style="border:0;border-top:1px solid #30363d;margin:1.5rem 0;">

            <h2 style="color:#79c0ff;font-size:1.25rem;">Fase 4: Dinámica de Decisiones y Modo Demo (Autocompletar)</h2>
            <p style="color:#c9d1d9;">Cada ronda contiene una secuencia estricta de 7 decisiones gerenciales:</p>
            <ul style="color:#c9d1d9;">
                <li style="margin-bottom:0.3rem;"><strong style="color:#f0f6fc;">Etapa 1: Personal:</strong> Contratación y retiros (Personal base: 6 colaboradores; nómina Bs 550 c/u).</li>
                <li style="margin-bottom:0.3rem;"><strong style="color:#f0f6fc;">Etapa 2: Inventario:</strong> Compra de bebidas (Bs 12/u) y mixers (Bs 5/u) con valorización CPP.</li>
                <li style="margin-bottom:0.3rem;"><strong style="color:#f0f6fc;">Etapa 3: Precios:</strong> Entrada general, bebida suelta, combo (1 bebida + 1 mixer) y servicio VIP.</li>
                <li style="margin-bottom:0.3rem;"><strong style="color:#f0f6fc;">Etapa 4: Publicidad:</strong> Presupuesto de visibilidad y posicionamiento comercial.</li>
                <li style="margin-bottom:0.3rem;"><strong style="color:#f0f6fc;">Etapa 5: Infraestructura:</strong> Ampliación de aforo (Bs 250/plaza) y mesas VIP (Bs 1.800/mesa).</li>
                <li style="margin-bottom:0.3rem;"><strong style="color:#f0f6fc;">Etapa 6: Investigación:</strong> Presupuesto de información gerencial y benchmarking.</li>
                <li style="margin-bottom:0.3rem;"><strong style="color:#f0f6fc;">Etapa 7: Artistas Invitados:</strong> <em>(Bloqueada en Ronda 1; habilitada de la Ronda 2 a la 8)</em>.</li>
            </ul>

            <div style="background:#0d1117;border-left:4px solid #e3b341;border-top:1px solid #30363d;border-right:1px solid #30363d;border-bottom:1px solid #30363d;padding:1.1rem;margin:1.2rem 0;border-radius:6px;">
                <strong style="color:#e3b341;font-size:1rem;">⚡ Asistencia Rápida al Operador (Modo Demostración):</strong><br>
                <span style="color:#c9d1d9;font-size:0.9rem;line-height:1.5;display:block;margin-top:0.4rem;">
                    Si estás realizando una demostración individual o evaluando el sistema, no necesitas iniciar sesión con los 7 usuarios.<br>
                    En el <strong style="color:#f0f6fc;">🛠️ Panel del Operador</strong> dispones de dos botones inmediatos:<br>
                    • <strong style="color:#e3b341;">⚡ Autocompletar Etapa Actual:</strong> Genera decisiones válidas para los 7 equipos en la etapa actual.<br>
                    • <strong style="color:#79c0ff;">⚡ Autocompletar Toda la Ronda:</strong> Completa y confirma de una sola vez todas las etapas pendientes de la ronda para las 7 discotecas.
                </span>
            </div>

            <hr style="border:0;border-top:1px solid #30363d;margin:1.5rem 0;">

            <h2 style="color:#79c0ff;font-size:1.25rem;">Fase 5: Cierre de Ronda, Simulación y Resultados MIFIV</h2>
            <ol style="padding-left:1.2rem;color:#c9d1d9;">
                <li style="margin-bottom:0.4rem;">Cuando los 7 equipos hayan confirmado sus etapas (o tras usar el autocompletado demo), pulsa <strong style="color:#3fb950;">"📊 Validar y Cerrar Ronda con MIFIV"</strong>.</li>
                <li style="margin-bottom:0.4rem;">El motor ejecutará el cálculo de demanda Softmax (4 segmentos), liquidará ventas de barra/entradas, deducirá nóminas y mantenimiento, y emitirá préstamos PALO automáticos si alguna discoteca requirió liquidez obligatoria.</li>
                <li style="margin-bottom:0.4rem;">El sistema generará la reconciliación MIFIV y publicará los resultados en las pestañas:
                    <ul style="margin-top:0.3rem;">
                        <li><strong style="color:#58a6ff;">📈 Resultados Financieros:</strong> Ingresos, egresos, utilidad neta, demanda atendida/rechazada y saldo en caja.</li>
                        <li><strong style="color:#e3b341;">🏆 Clasificación MIEC:</strong> Ranking acumulado ponderado (Rentabilidad 30%, Mercado 25%, Logística 20%, Satisfacción 15%, Artistas 10%).</li>
                    </ul>
                </li>
                <li style="margin-bottom:0.4rem;">En el Panel del Operador aparecerá el botón <strong style="color:#3fb950;">"▶️ Iniciar Ronda 2 →"</strong> (y así sucesivamente hasta la Ronda 8).</li>
            </ol>

            <hr style="border:0;border-top:1px solid #30363d;margin:1.5rem 0;">

            <h2 style="color:#79c0ff;font-size:1.25rem;">Fase 6: Laboratorio Financiero (MPEAS / MEFER) y Exportación</h2>
            <ul style="padding-left:1.2rem;color:#c9d1d9;">
                <li style="margin-bottom:0.4rem;"><strong style="color:#58a6ff;">🧮 Laboratorio MPEAS / MEFER:</strong> Permite a los participantes proyectar antes de invertir: calcular el punto de equilibrio en personas, análisis de sensibilidad (+/- 15%) y el Valor Actual Neto (VAN) de inversiones con fondos propios.</li>
                <li style="margin-bottom:0.4rem;"><strong style="color:#79c0ff;">💾 Exportación JSON:</strong> En cualquier momento puedes pulsar el botón <code style="color:#79c0ff;background:#0d1117;padding:0.15rem 0.35rem;border-radius:4px;">💾 JSON</code> para descargar el respaldo estructurado completo de la partida.</li>
            </ul>

            <hr style="border:0;border-top:1px solid #30363d;margin:1.5rem 0;">

            <h2 style="color:#f85149;font-size:1.25rem;">⚠️ Decálogo Anti-Errores para el Operador</h2>
            <ul style="padding-left:1.2rem;color:#c9d1d9;">
                <li style="margin-bottom:0.4rem;"><strong style="color:#f0f6fc;">Protección anti-doble clic:</strong> Todos los botones críticos del sistema se deshabilitan visualmente al presionarse para impedir envíos duplicados. Realiza un solo clic y aguarda la notificación verde.</li>
                <li style="margin-bottom:0.4rem;"><strong style="color:#f0f6fc;">Inmutabilidad formal:</strong> Una vez confirmada una etapa, la decisión no puede modificarse en la base de datos (según las reglas de integridad académica del MMOM).</li>
                <li style="margin-bottom:0.4rem;"><strong style="color:#f0f6fc;">Vencimiento del reloj (20 min):</strong> Si el cronómetro de la ronda llega a cero, el servidor bloquea nuevas confirmaciones para garantizar juego limpio en torneos cronometrados.</li>
                <li style="margin-bottom:0.4rem;"><strong style="color:#f0f6fc;">Financiamiento PALO exclusivo:</strong> Los préstamos automáticos PALO (límite Bs 20.000) solo cubren nóminas y mantenimiento insatisfechos; nunca pueden utilizarse para comprar inventario, contratar publicidad ni hacer obras.</li>
            </ul>
        </article>
    </div>
</section>
