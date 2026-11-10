<section x-data="{ activeTab: 'games', showModal: false }">
    {{-- Navegación por pestañas instantánea en el navegador (0 ms) --}}
    <div style="display:flex;gap:0.75rem;margin-bottom:1.5rem;border-bottom:1px solid #37455f;padding-bottom:0.75rem;">
        <button type="button" 
                @click="activeTab = 'games'" 
                :style="activeTab === 'games' ? 'background:#346cca;box-shadow:0 2px 8px rgba(52,108,202,0.4);' : 'background:#223049;'"
                style="padding: 0.6rem 1.2rem; font-size: 0.95rem; transition: all 0.15s; border-radius:6px; font-weight:bold;">
            🎮 Partidas de Simulación
        </button>
        <button type="button" 
                @click="activeTab = 'tutorial'" 
                :style="activeTab === 'tutorial' ? 'background:#1f7a55;box-shadow:0 2px 8px rgba(31,122,85,0.4);' : 'background:#223049;'"
                style="padding: 0.6rem 1.2rem; font-size: 0.95rem; transition: all 0.15s; border-radius:6px; font-weight:bold;">
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
    <div x-show="activeTab === 'games'">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
            <div>
                <h1 style="margin:0 0 0.3rem 0;color:#92c5ff;">Partidas de Simulación</h1>
                <p class="muted" style="margin:0;">Listado de entornos competitivos activos y preparados.</p>
            </div>

            @if($isAdmin)
                <button type="button" 
                        @click="showModal = true" 
                        style="background:#238636;padding:0.75rem 1.5rem;font-weight:bold;font-size:1rem;box-shadow:0 4px 12px rgba(35,134,54,0.3);">
                    ➕ Crear Nueva Partida
                </button>
            @endif
        </div>

        {{-- Modal / Formulario de Creación de Partida (Apertura instantánea) --}}
        <div x-show="showModal" style="display:none;" 
             style="background:#1c293f;border:2px solid #388bfd;padding:1.5rem;border-radius:12px;margin-bottom:2rem;box-shadow:0 12px 28px rgba(0,0,0,0.4);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                <h2 style="margin:0;color:#58a6ff;">Nueva Partida de Simulación MMOM</h2>
                <button type="button" class="secondary" @click="showModal = false" style="padding:0.3rem 0.7rem;font-size:0.85rem;">✖ Cancelar</button>
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
                    <button type="button" class="secondary" @click="showModal = false" wire:loading.attr="disabled">
                        Descartar
                    </button>
                </div>
            </form>
        </div>

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

                    <div style="display:flex;gap:0.5rem;align-items:center;">
                        <a href="{{ route('mmom.games.show',['gameId'=>$game['id']]) }}" 
                           style="flex:1;text-align:center;background:#346cca;color:#fff;text-decoration:none;padding:0.7rem;border-radius:6px;font-weight:bold;">
                            🎮 Abrir Consola de Simulación
                        </a>
                        <a href="{{ route('mmom.games.export',['gameId'=>$game['id']]) }}" 
                           target="_blank"
                           title="Exportar respaldo JSON de la partida"
                           style="background:#21262d;color:#79c0ff;border:1px solid #30363d;padding:0.7rem 0.9rem;border-radius:6px;text-decoration:none;font-size:0.9rem;font-weight:bold;">
                            💾 JSON
                        </a>
                    </div>
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
                        <button type="button" @click="showModal = true" style="background:#238636;padding:0.75rem 1.6rem;font-weight:bold;font-size:1rem;">
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
        <article class="card" style="padding:2rem;line-height:1.6;">
            <h1 style="color:#58a6ff;margin-top:0;">📖 Manual de Operación de la Simulación MMOM</h1>
            <p class="muted">
                Esta guía está diseñada para que cualquier operador, facilitador o docente pueda conducir una sesión de simulación sin cometer errores técnicos.
            </p>

            <hr style="border:0;border-top:1px solid #37455f;margin:1.5rem 0;">

            <h2 style="color:#79c0ff;">Fase 1: Preparación y Cuentas de Acceso</h2>
            <p>La simulación MMOM funciona como un mercado competitivo cerrado donde operan exactamente las <strong>7 discotecas oficiales del catálogo</strong> a lo largo de <strong>8 rondas</strong>. Los accesos del sistema son:</p>
            
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;margin:1rem 0;font-size:0.9rem;">
                    <thead>
                        <tr style="background:#21304a;color:#c9d1d9;text-align:left;">
                            <th style="padding:0.6rem;border:1px solid #37455f;">Rol</th>
                            <th style="padding:0.6rem;border:1px solid #37455f;">Correo</th>
                            <th style="padding:0.6rem;border:1px solid #37455f;">Contraseña</th>
                            <th style="padding:0.6rem;border:1px solid #37455f;">Discoteca del Catálogo Asignada</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td style="padding:0.6rem;border:1px solid #37455f;"><span style="background:#238636;color:#fff;padding:0.1rem 0.4rem;border-radius:4px;font-weight:bold;">ADMIN</span></td><td style="padding:0.6rem;border:1px solid #37455f;"><code>admin@mmom.com</code></td><td style="padding:0.6rem;border:1px solid #37455f;"><code>password123</code></td><td style="padding:0.6rem;border:1px solid #37455f;">Control Central / Facilitador</td></tr>
                        <tr><td style="padding:0.6rem;border:1px solid #37455f;">JUGADOR 1</td><td style="padding:0.6rem;border:1px solid #37455f;"><code>jugador1@mmom.com</code></td><td style="padding:0.6rem;border:1px solid #37455f;"><code>password123</code></td><td style="padding:0.6rem;border:1px solid #37455f;">La Paceña</td></tr>
                        <tr><td style="padding:0.6rem;border:1px solid #37455f;">JUGADOR 2</td><td style="padding:0.6rem;border:1px solid #37455f;"><code>jugador2@mmom.com</code></td><td style="padding:0.6rem;border:1px solid #37455f;"><code>password123</code></td><td style="padding:0.6rem;border:1px solid #37455f;">Jet</td></tr>
                        <tr><td style="padding:0.6rem;border:1px solid #37455f;">JUGADOR 3</td><td style="padding:0.6rem;border:1px solid #37455f;"><code>jugador3@mmom.com</code></td><td style="padding:0.6rem;border:1px solid #37455f;"><code>password123</code></td><td style="padding:0.6rem;border:1px solid #37455f;">Luna Park</td></tr>
                        <tr><td style="padding:0.6rem;border:1px solid #37455f;">JUGADOR 4</td><td style="padding:0.6rem;border:1px solid #37455f;"><code>jugador4@mmom.com</code></td><td style="padding:0.6rem;border:1px solid #37455f;"><code>password123</code></td><td style="padding:0.6rem;border:1px solid #37455f;">Forum</td></tr>
                        <tr><td style="padding:0.6rem;border:1px solid #37455f;">JUGADOR 5</td><td style="padding:0.6rem;border:1px solid #37455f;"><code>jugador5@mmom.com</code></td><td style="padding:0.6rem;border:1px solid #37455f;"><code>password123</code></td><td style="padding:0.6rem;border:1px solid #37455f;">Fabri-K</td></tr>
                        <tr><td style="padding:0.6rem;border:1px solid #37455f;">JUGADOR 6</td><td style="padding:0.6rem;border:1px solid #37455f;"><code>jugador6@mmom.com</code></td><td style="padding:0.6rem;border:1px solid #37455f;"><code>password123</code></td><td style="padding:0.6rem;border:1px solid #37455f;">Ibiza</td></tr>
                        <tr><td style="padding:0.6rem;border:1px solid #37455f;">JUGADOR 7</td><td style="padding:0.6rem;border:1px solid #37455f;"><code>jugador7@mmom.com</code></td><td style="padding:0.6rem;border:1px solid #37455f;"><code>password123</code></td><td style="padding:0.6rem;border:1px solid #37455f;">Green Planet</td></tr>
                    </tbody>
                </table>
            </div>

            <hr style="border:0;border-top:1px solid #37455f;margin:1.5rem 0;">

            <h2 style="color:#79c0ff;">Fase 2: Cómo Crear una Partida (1 Clic)</h2>
            <ol style="padding-left:1.2rem;">
                <li>Haz clic en el botón verde <strong>"➕ Crear Nueva Partida"</strong> en la pestaña de Partidas.</li>
                <li>Escribe un nombre identificable para la sesión (ejemplo: <em>"Torneo Gestión Estratégica 2026"</em>).</li>
                <li>Deja marcada la casilla <strong>"Configuración Automática con 1 Clic"</strong>. El sistema creará las 7 empresas, vinculará a los 7 jugadores oficiales y preparará las 8 rondas automáticamente.</li>
                <li>Presiona <strong>"🚀 Crear y Preparar Partida"</strong>. La partida quedará en estado <code>PREPARADA</code>.</li>
            </ol>

            <hr style="border:0;border-top:1px solid #37455f;margin:1.5rem 0;">

            <h2 style="color:#79c0ff;">Fase 3: Inicio de Ronda y Control del Reloj (20 Minutos)</h2>
            <ol style="padding-left:1.2rem;">
                <li>Haz clic en <strong>"🎮 Abrir Consola de Simulación"</strong> en la tarjeta de la partida creada.</li>
                <li>En la pestaña <strong>🛠️ Panel del Operador</strong> presiona el botón verde <strong>"▶️ Iniciar Ronda 1"</strong>.</li>
                <li>El reloj central del servidor arrancará en cuenta regresiva de <strong>20 minutos (1200 segundos)</strong> sincronizado vía UTC para todos los participantes.</li>
            </ol>

            <hr style="border:0;border-top:1px solid #37455f;margin:1.5rem 0;">

            <h2 style="color:#79c0ff;">Fase 4: Dinámica de Decisiones y Modo Demo (Autocompletar)</h2>
            <p>Cada ronda contiene una secuencia estricta de 7 decisiones gerenciales:</p>
            <ul>
                <li><strong>Etapa 1: Personal:</strong> Contratación y retiros (Personal base: 6 colaboradores; nómina Bs 550 c/u).</li>
                <li><strong>Etapa 2: Inventario:</strong> Compra de bebidas (Bs 12/u) y mixers (Bs 5/u) con valorización CPP.</li>
                <li><strong>Etapa 3: Precios:</strong> Entrada general, bebida suelta, combo (1 bebida + 1 mixer) y servicio VIP.</li>
                <li><strong>Etapa 4: Publicidad:</strong> Presupuesto de visibilidad y posicionamiento comercial.</li>
                <li><strong>Etapa 5: Infraestructura:</strong> Ampliación de aforo (Bs 250/plaza) y mesas VIP (Bs 1.800/mesa).</li>
                <li><strong>Etapa 6: Investigación:</strong> Presupuesto de información gerencial y benchmarking.</li>
                <li><strong>Etapa 7: Artistas Invitados:</strong> <em>(Bloqueada en Ronda 1; habilitada de la Ronda 2 a la 8)</em>.</li>
            </ul>

            <div style="background:#131e30;border-left:4px solid #e3b341;padding:1rem;margin:1rem 0;border-radius:4px;">
                <strong style="color:#e3b341;">⚡ Asistencia Rápida al Operador (Modo Demostración):</strong><br>
                Si estás realizando una demostración individual, evaluando el sistema o algún equipo no asistió, no necesitas iniciar sesión con los 7 usuarios.<br>
                En el <strong>🛠️ Panel del Operador</strong> dispones de dos botones inmediatos:<br>
                • <strong>⚡ Autocompletar Etapa Actual:</strong> Genera decisiones óptimas y equilibradas para los 7 equipos en la etapa actual.<br>
                • <strong>⚡ Autocompletar Toda la Ronda:</strong> Completa y confirma de una sola vez todas las etapas pendientes de la ronda para las 7 discotecas.
            </div>

            <hr style="border:0;border-top:1px solid #37455f;margin:1.5rem 0;">

            <h2 style="color:#79c0ff;">Fase 5: Cierre de Ronda, Simulación y Resultados MIFIV</h2>
            <ol style="padding-left:1.2rem;">
                <li>Cuando los 7 equipos hayan confirmado sus etapas (o tras usar el autocompletado demo), pulsa <strong>"📊 Validar y Cerrar Ronda con MIFIV"</strong>.</li>
                <li>El motor ejecutará el cálculo de demanda Softmax (4 segmentos: Universitarios, Jóvenes, Adultos y VIP), calculará la capacidad operativa, liquidará ventas de barra/entradas, deducirá nóminas y mantenimiento, y emitirá préstamos PALO automáticos si alguna discoteca requirió liquidez obligatoria.</li>
                <li>El sistema generará la reconciliación MIFIV y publicará los resultados en las pestañas:
                    <ul>
                        <li><strong>📈 Resultados Financieros:</strong> Ingresos, egresos, utilidad neta, demanda atendida/rechazada y saldo en caja.</li>
                        <li><strong>🏆 Clasificación MIEC:</strong> Ranking acumulado ponderado (Rentabilidad 30%, Mercado 25%, Logística 20%, Satisfacción 15%, Artistas 10%).</li>
                    </ul>
                </li>
                <li>En el Panel del Operador aparecerá el botón <strong>"▶️ Iniciar Ronda 2 →"</strong> (y así sucesivamente hasta la Ronda 8).</li>
            </ol>

            <hr style="border:0;border-top:1px solid #37455f;margin:1.5rem 0;">

            <h2 style="color:#79c0ff;">Fase 6: Laboratorio Financiero (MPEAS / MEFER) y Exportación</h2>
            <ul style="padding-left:1.2rem;">
                <li><strong>🧮 Laboratorio MPEAS / MEFER:</strong> Permite a los participantes proyectar antes de invertir: calcular el punto de equilibrio en personas, análisis de sensibilidad (+/- 15%) y el Valor Actual Neto (VAN) de inversiones con fondos propios.</li>
                <li><strong>💾 Exportación JSON:</strong> En cualquier momento puedes pulsar el botón <code>💾 JSON</code> para descargar el respaldo estructurado completo de la partida (equipos, decisiones inmutables, rondas, candidaturas MIFIV y pistas de auditoría).</li>
            </ul>

            <hr style="border:0;border-top:1px solid #37455f;margin:1.5rem 0;">

            <h2 style="color:#f85149;">⚠️ Decálogo Anti-Errores para el Operador</h2>
            <ul style="padding-left:1.2rem;color:#eef1fb;">
                <li><strong>Protección anti-doble clic:</strong> Todos los botones críticos del sistema se deshabilitan visualmente al presionarse para impedir envíos duplicados. Realiza un solo clic y aguarda la notificación verde.</li>
                <li><strong>Inmutabilidad formal:</strong> Una vez confirmada una etapa, la decisión no puede modificarse en la base de datos (según las reglas de integridad académica del MMOM).</li>
                <li><strong>Vencimiento del reloj (20 min):</strong> Si el cronómetro de la ronda llega a cero, el servidor bloquea nuevas confirmaciones para garantizar juego limpio en torneos cronometrados.</li>
                <li><strong>Financiamiento PALO exclusivo:</strong> Los préstamos automáticos PALO (límite Bs 20.000) solo cubren nóminas y mantenimiento insatisfechos; nunca pueden utilizarse para comprar inventario, contratar publicidad ni hacer obras.</li>
            </ul>
        </article>
    </div>
</section>
