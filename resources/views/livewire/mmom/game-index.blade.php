<section>
    {{-- Navegación por pestañas --}}
    <div style="display:flex;gap:0.75rem;margin-bottom:1.5rem;border-bottom:1px solid #37455f;padding-bottom:0.75rem;">
        <button type="button" 
                wire:click="setTab('games')" 
                style="background: {{ $activeTab === 'games' ? '#346cca' : '#223049' }}; padding: 0.6rem 1.2rem; font-size: 0.95rem;">
            🎮 Partidas de Simulación
        </button>
        <button type="button" 
                wire:click="setTab('tutorial')" 
                style="background: {{ $activeTab === 'tutorial' ? '#1f7a55' : '#223049' }}; padding: 0.6rem 1.2rem; font-size: 0.95rem;">
            📖 Tutorial y Guía del Operador
        </button>
    </div>

    {{-- Avisos del sistema --}}
    @if($actionNotice)
        <div style="background:#133929;border:1px solid #2ea043;padding:1rem;border-radius:8px;margin-bottom:1.5rem;color:#7ee787;font-weight:500;">
            ✓ {{ $actionNotice }}
        </div>
    @endif

    @if($actionError)
        <div style="background:#491b1f;border:1px solid #f85149;padding:1rem;border-radius:8px;margin-bottom:1.5rem;color:#ff7b72;font-weight:500;">
            ⚠️ {{ $actionError }}
        </div>
    @endif

    {{-- ============================================================== --}}
    {{-- PESTAÑA 1: LISTADO Y GESTIÓN DE PARTIDAS                       --}}
    {{-- ============================================================== --}}
    @if($activeTab === 'games')
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
            <div>
                <h1 style="margin:0 0 0.3rem 0;color:#92c5ff;">Partidas de Simulación</h1>
                <p class="muted" style="margin:0;">Listado de entornos competitivos activos y preparados.</p>
            </div>

            @if($isAdmin)
                <button type="button" 
                        wire:click="openCreateModal" 
                        style="background:#238636;padding:0.75rem 1.5rem;font-weight:bold;font-size:1rem;box-shadow:0 4px 12px rgba(35,134,54,0.3);">
                    ➕ Crear Nueva Partida
                </button>
            @endif
        </div>

        {{-- Modal / Formulario de Creación de Partida --}}
        @if($showCreateModal)
            <div style="background:#1c293f;border:2px solid #388bfd;padding:1.5rem;border-radius:12px;margin-bottom:2rem;box-shadow:0 12px 28px rgba(0,0,0,0.4);">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                    <h2 style="margin:0;color:#58a6ff;">Nueva Partida de Simulación MMOM</h2>
                    <button type="button" class="secondary" wire:click="closeCreateModal" style="padding:0.3rem 0.7rem;font-size:0.85rem;">✖ Cancelar</button>
                </div>
                
                <p class="muted" style="font-size:0.9rem;margin-bottom:1.2rem;">
                    Este asistente configurará automáticamente el entorno para las 7 discotecas competidoras y sus 8 rondas de juego.
                </p>

                <form wire:submit.prevent="createGame">
                    <div style="margin-bottom:1rem;">
                        <label for="gameName" style="color:#c9d1d9;font-weight:bold;margin-bottom:0.4rem;">Nombre de la Partida / Torneo:</label>
                        <input id="gameName" type="text" wire:model="newGameName" 
                               style="width:100%;padding:0.75rem;background:#0d1625;border:1px solid #3b4d6e;border-radius:6px;color:#eef1fb;font-size:1rem;"
                               placeholder="Ejemplo: Torneo Universitario 2026 - Grupo A">
                        @error('newGameName') <span style="color:#ff7b72;font-size:0.85rem;">{{ $message }}</span> @enderror
                    </div>

                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:1.2rem;">
                        <div>
                            <label style="color:#c9d1d9;font-weight:bold;margin-bottom:0.4rem;">Modelo de Mercado Inicial:</label>
                            <select wire:model="initialModel" style="width:100%;padding:0.75rem;background:#0d1625;border:1px solid #3b4d6e;border-radius:6px;color:#eef1fb;font-size:0.95rem;">
                                <option value="A">Modelo A (Condiciones Estándar de Mercado)</option>
                                <option value="B">Modelo B (Mayor Variabilidad Competitiva)</option>
                            </select>
                        </div>
                    </div>

                    <div style="background:#131e30;padding:1rem;border-radius:8px;border:1px dashed #304870;margin-bottom:1.5rem;">
                        <label style="display:flex;align-items:flex-start;gap:0.75rem;cursor:pointer;margin:0;">
                            <input type="checkbox" wire:model="autoAssignAndPrepare" style="margin-top:0.25rem;transform:scale(1.2);">
                            <div>
                                <strong style="color:#79c0ff;">Configuración Automática con 1 Clic (Recomendado)</strong>
                                <p class="muted" style="margin:0.2rem 0 0 0;font-size:0.85rem;">
                                    Asignará automáticamente a los 7 participantes oficiales (<code>jugador1@mmom.com</code> al <code>jugador7@mmom.com</code>) a las 7 discotecas y dejará las 8 rondas listas para iniciar.
                                </p>
                            </div>
                        </label>
                    </div>

                    <div style="display:flex;gap:1rem;align-items:center;">
                        <button type="submit" 
                                wire:loading.attr="disabled"
                                wire:target="createGame"
                                style="background:#238636;padding:0.85rem 1.8rem;font-weight:bold;font-size:1rem;">
                            <span wire:loading.remove wire:target="createGame">🚀 Crear y Preparar Partida</span>
                            <span wire:loading wire:target="createGame">⏳ Configurando partida... por favor espera</span>
                        </button>
                        <button type="button" class="secondary" wire:click="closeCreateModal" wire:loading.attr="disabled">
                            Descartar
                        </button>
                    </div>
                </form>
            </div>
        @endif

        {{-- Grid de Partidas --}}
        <div class="grid">
            @forelse($games as $game)
                <article class="card" style="border:1px solid #38465f;transition:transform 0.15s;box-shadow:0 6px 16px rgba(0,0,0,0.2);">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;">
                        <h2 style="margin:0;font-size:1.25rem;color:#eef1fb;">{{ $game['name'] }}</h2>
                        @php
                            $badgeColor = match($game['status']) {
                                'active' => '#238636',
                                'prepared' => '#1f6feb',
                                'draft' => '#d29922',
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
                        <span style="background:{{ $badgeColor }};color:#fff;font-size:0.75rem;padding:0.2rem 0.5rem;border-radius:4px;font-weight:bold;">
                            {{ $badgeText }}
                        </span>
                    </div>

                    <p style="margin:0.5rem 0;color:#c9d1d9;">
                        Ronda actual: <strong style="color:#58a6ff;">{{ $game['current_round'] ? "Ronda {$game['current_round']} de 8" : 'Sin iniciar' }}</strong>
                    </p>

                    <p class="muted" style="font-size:0.85rem;margin-bottom:1.2rem;">
                        ID: <code style="font-size:0.75rem;color:#8b949e;">{{ substr($game['id'], 0, 8) }}...</code>
                    </p>

                    <a href="{{ route('mmom.games.show',['gameId'=>$game['id']]) }}" 
                       style="display:block;text-align:center;background:#346cca;color:#fff;text-decoration:none;padding:0.7rem;border-radius:6px;font-weight:bold;">
                        🎮 Abrir Consola de Simulación
                    </a>
                </article>
            @empty
                <div style="grid-column: 1 / -1; background:#161f30;border:1px dashed #304870;padding:3rem 2rem;text-align:center;border-radius:12px;">
                    <h3 style="color:#c9d1d9;margin-top:0;">No hay partidas disponibles para esta cuenta</h3>
                    <p class="muted" style="max-width:550px;margin:0.5rem auto 1.5rem auto;">
                        @if($isAdmin)
                            Como administrador, puedes crear la primera partida de simulación con el botón de abajo. Los 7 jugadores se asignarán automáticamente.
                        @else
                            Tu cuenta aún no tiene asignada una discoteca en ninguna partida activa. Solicita al administrador que te incorpore.
                        @endif
                    </p>
                    @if($isAdmin)
                        <button type="button" wire:click="openCreateModal" style="background:#238636;padding:0.75rem 1.6rem;font-weight:bold;font-size:1rem;">
                            ➕ Crear Primera Partida Ahora
                        </button>
                    @endif
                </div>
            @endforelse
        </div>
    @endif

    {{-- ============================================================== --}}
    {{-- PESTAÑA 2: TUTORIAL Y GUÍA PASO A PASO DEL OPERADOR           --}}
    {{-- ============================================================== --}}
    @if($activeTab === 'tutorial')
        <article class="card" style="padding:2rem;line-height:1.6;">
            <h1 style="color:#58a6ff;margin-top:0;">📖 Manual de Operación de la Simulación MMOM</h1>
            <p class="muted">
                Esta guía está diseñada para que cualquier operador, facilitador o docente pueda conducir una sesión de simulación sin cometer errores técnicos.
            </p>

            <hr style="border:0;border-top:1px solid #37455f;margin:1.5rem 0;">

            <h2 style="color:#79c0ff;">Fase 1: Preparación y Cuentas de Acceso</h2>
            <p>La simulación MMOM funciona como un mercado cerrado donde compiten exactamente <strong>7 empresas (discotecas)</strong> durante <strong>8 rondas</strong>. Los accesos del sistema son:</p>
            
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;margin:1rem 0;font-size:0.9rem;">
                    <thead>
                        <tr style="background:#21304a;color:#c9d1d9;text-align:left;">
                            <th style="padding:0.6rem;border:1px solid #37455f;">Rol</th>
                            <th style="padding:0.6rem;border:1px solid #37455f;">Correo</th>
                            <th style="padding:0.6rem;border:1px solid #37455f;">Contraseña</th>
                            <th style="padding:0.6rem;border:1px solid #37455f;">Empresa Asignada</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td style="padding:0.6rem;border:1px solid #37455f;"><span style="background:#238636;color:#fff;padding:0.1rem 0.4rem;border-radius:4px;">ADMIN</span></td><td style="padding:0.6rem;border:1px solid #37455f;"><code>admin@mmom.com</code></td><td style="padding:0.6rem;border:1px solid #37455f;"><code>password123</code></td><td style="padding:0.6rem;border:1px solid #37455f;">Control Central / Facilitador</td></tr>
                        <tr><td style="padding:0.6rem;border:1px solid #37455f;">JUGADOR 1</td><td style="padding:0.6rem;border:1px solid #37455f;"><code>jugador1@mmom.com</code></td><td style="padding:0.6rem;border:1px solid #37455f;"><code>password123</code></td><td style="padding:0.6rem;border:1px solid #37455f;">Club Apolo</td></tr>
                        <tr><td style="padding:0.6rem;border:1px solid #37455f;">JUGADOR 2</td><td style="padding:0.6rem;border:1px solid #37455f;"><code>jugador2@mmom.com</code></td><td style="padding:0.6rem;border:1px solid #37455f;"><code>password123</code></td><td style="padding:0.6rem;border:1px solid #37455f;">Club Boreal</td></tr>
                        <tr><td style="padding:0.6rem;border:1px solid #37455f;">JUGADOR 3</td><td style="padding:0.6rem;border:1px solid #37455f;"><code>jugador3@mmom.com</code></td><td style="padding:0.6rem;border:1px solid #37455f;"><code>password123</code></td><td style="padding:0.6rem;border:1px solid #37455f;">Club Cosmos</td></tr>
                        <tr><td style="padding:0.6rem;border:1px solid #37455f;">JUGADOR 4</td><td style="padding:0.6rem;border:1px solid #37455f;"><code>jugador4@mmom.com</code></td><td style="padding:0.6rem;border:1px solid #37455f;"><code>password123</code></td><td style="padding:0.6rem;border:1px solid #37455f;">Club Danubio</td></tr>
                        <tr><td style="padding:0.6rem;border:1px solid #37455f;">JUGADOR 5</td><td style="padding:0.6rem;border:1px solid #37455f;"><code>jugador5@mmom.com</code></td><td style="padding:0.6rem;border:1px solid #37455f;"><code>password123</code></td><td style="padding:0.6rem;border:1px solid #37455f;">Club Eclipse</td></tr>
                        <tr><td style="padding:0.6rem;border:1px solid #37455f;">JUGADOR 6</td><td style="padding:0.6rem;border:1px solid #37455f;"><code>jugador6@mmom.com</code></td><td style="padding:0.6rem;border:1px solid #37455f;"><code>password123</code></td><td style="padding:0.6rem;border:1px solid #37455f;">Club Fénix</td></tr>
                        <tr><td style="padding:0.6rem;border:1px solid #37455f;">JUGADOR 7</td><td style="padding:0.6rem;border:1px solid #37455f;"><code>jugador7@mmom.com</code></td><td style="padding:0.6rem;border:1px solid #37455f;"><code>password123</code></td><td style="padding:0.6rem;border:1px solid #37455f;">Club Galaxia</td></tr>
                    </tbody>
                </table>
            </div>

            <hr style="border:0;border-top:1px solid #37455f;margin:1.5rem 0;">

            <h2 style="color:#79c0ff;">Fase 2: Cómo Crear una Partida</h2>
            <ol style="padding-left:1.2rem;">
                <li>Haz clic en el botón verde <strong>"Crear Nueva Partida"</strong> en la pestaña de Partidas.</li>
                <li>Escribe un nombre identificable para la sesión (ejemplo: <em>"Torneo Grupo B"</em>).</li>
                <li>Deja marcada la casilla <strong>"Configuración Automática con 1 Clic"</strong>. El sistema creará las 7 empresas y vinculará a cada jugador automáticamente.</li>
                <li>Presiona <strong>"Crear y Preparar Partida"</strong>. La partida quedará en estado <code>PREPARADA</code>.</li>
            </ol>

            <hr style="border:0;border-top:1px solid #37455f;margin:1.5rem 0;">

            <h2 style="color:#79c0ff;">Fase 3: Inicio de Ronda y Control del Reloj (20 Minutos)</h2>
            <ol style="padding-left:1.2rem;">
                <li>Haz clic en <strong>"Abrir Consola de Simulación"</strong> en la tarjeta de la partida creada.</li>
                <li>En el bloque de administración verás el botón verde <strong>"▶️ Iniciar Ronda 1"</strong>.</li>
                <li>Al presionarlo, el reloj central del servidor arrancará en cuenta regresiva de <strong>20 minutos (1200 segundos)</strong>.</li>
                <li>El reloj está coordinado en el servidor UTC: todos los participantes verán el mismo tiempo exacto restante en sus pantallas.</li>
            </ol>

            <hr style="border:0;border-top:1px solid #37455f;margin:1.5rem 0;">

            <h2 style="color:#79c0ff;">Fase 4: Dinámica de las 7 Etapas de Decisión</h2>
            <p>Cada ronda contiene una secuencia estricta de 7 decisiones gerenciales:</p>
            <ul>
                <li><strong>Etapa 1: Personal y RRHH:</strong> Contratación de personal operativo, sueldos y guardias.</li>
                <li><strong>Etapa 2: Inventario y Compras:</strong> Aprovisionamiento de bebidas y cálculo de Costo Promedio Ponderado (CPP).</li>
                <li><strong>Etapa 3: Precios:</strong> Fijación del precio de entrada y consumiciones.</li>
                <li><strong>Etapa 4: Publicidad y Marketing:</strong> Asignación de presupuesto por segmento de mercado.</li>
                <li><strong>Etapa 5: Inversiones:</strong> Obras, ampliación de aforo o mejoras de infraestructura.</li>
                <li><strong>Etapa 6: Estudios de Mercado:</strong> Compra de reportes de inteligencia comercial.</li>
                <li><strong>Etapa 7: Artistas y Espectáculos:</strong> <em>(Bloqueada en Ronda 1; habilitada de Ronda 2 a 8)</em>.</li>
            </ul>

            <div style="background:#131e30;border-left:4px solid #58a6ff;padding:1rem;margin:1rem 0;border-radius:4px;">
                <strong>Instrucciones para el Operador al avanzar de etapa:</strong><br>
                1. Avisa a los participantes: <em>"Tienen 2 minutos para confirmar la Etapa actual"</em>.<br>
                2. Los jugadores guardan su borrador y marcan la casilla de confirmación inmutable.<br>
                3. Una vez confirmados, el Administrador hace clic en <strong>"⏩ Avanzar Etapa"</strong>.<br>
                4. La pantalla de todos los jugadores se actualizará automáticamente a la siguiente etapa.
            </div>

            <hr style="border:0;border-top:1px solid #37455f;margin:1.5rem 0;">

            <h2 style="color:#f85149;">⚠️ Decálogo Anti-Errores para el Operador</h2>
            <ul style="padding-left:1.2rem;color:#eef1fb;">
                <li><strong>Evita el doble clic compulsivo:</strong> Los botones tienen protección contra reenvío, pero es buena práctica hacer un solo clic y esperar el mensaje de confirmación.</li>
                <li><strong>Confirmación Inmutable:</strong> Una vez que un jugador presiona "Confirmar borrador", esa decisión queda blindada en la base de datos y no puede reescribirse.</li>
                <li><strong>Vencimiento de los 20 minutos:</strong> Al expirar el tiempo de la ronda, el servidor bloquea las decisiones no enviadas para proteger la integridad financiera del torneo.</li>
                <li><strong>MFA Administrativo:</strong> Tu cuenta <code>admin@mmom.com</code> tiene su segundo factor activado por defecto para garantizar que las acciones de avance de etapa sean autorizadas.</li>
            </ul>
        </article>
    @endif
</section>
