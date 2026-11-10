<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="color-scheme" content="dark">
    <title>MMOM · Simulador Estratégico de Discotecas</title>
    <style>
        :root {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
            color-scheme: dark;
            background-color: #0d1117;
            color: #f0f6fc;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background-color: #0d1117;
            color: #f0f6fc;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        header {
            background-color: #161b22;
            padding: 1rem 2rem;
            border-bottom: 1px solid #30363d;
            color: #f0f6fc;
        }
        a {
            color: #58a6ff;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        main {
            max-width: 1100px;
            width: 100%;
            margin: 2rem auto;
            padding: 0 1.2rem;
            flex: 1;
        }
        .card {
            border: 1px solid #30363d;
            background-color: #161b22;
            padding: 1.5rem;
            margin: 1rem 0;
            border-radius: 10px;
            color: #f0f6fc;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.2rem;
        }
        .muted {
            color: #8b949e;
        }
        .tag {
            padding: 0.2rem 0.55rem;
            border: 1px solid #30363d;
            background: #21262d;
            color: #79c0ff;
            border-radius: 6px;
            font-size: 0.85rem;
        }
        button {
            background-color: #1f6feb;
            color: #ffffff;
            border: 1px solid rgba(240,246,252,0.1);
            border-radius: 6px;
            padding: 0.65rem 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: filter 0.15s ease;
        }
        button:hover:not(:disabled) {
            filter: brightness(1.1);
        }
        button:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }
        button.secondary {
            background-color: #21262d;
            color: #c9d1d9;
            border: 1px solid #30363d;
        }
        input, select, textarea {
            background-color: #0d1117 !important;
            color: #f0f6fc !important;
            border: 1px solid #30363d !important;
            border-radius: 6px;
            padding: 0.65rem 0.85rem;
            font-size: 0.95rem;
            font-family: inherit;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #58a6ff !important;
            box-shadow: 0 0 0 3px rgba(88,166,255,0.25);
        }
        textarea {
            display: block;
            width: 100%;
            min-height: 160px;
            font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
        }
        label {
            display: block;
            color: #c9d1d9;
            font-weight: 500;
            margin-bottom: 0.4rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            color: #f0f6fc;
        }
        th {
            color: #8b949e;
            font-weight: 600;
            text-align: left;
        }
        td {
            color: #f0f6fc;
        }
        .error {
            color: #ff7b72;
        }
        .notice {
            padding: 0.9rem;
            background: #133929;
            border: 1px solid #2ea043;
            color: #7ee787;
            border-radius: 6px;
        }
        :focus-visible {
            outline: 2px solid #58a6ff;
            outline-offset: 2px;
        }
    </style>
    @livewireStyles
</head>
<body>
    <header style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.75rem;">
        <div style="display:flex;align-items:center;gap:0.75rem;">
            <strong style="font-size:1.05rem;color:#f0f6fc;">MMOM · Simulador Estratégico de Discotecas</strong>
            <span class="tag">E8 Producción</span>
        </div>
        @auth
        <div style="display:flex;align-items:center;gap:1rem;">
            <span style="font-size:0.9rem;color:#c9d1d9;">👤 {{ auth()->user()->display_name ?? auth()->user()->email }}</span>
            <form method="POST" action="/logout" style="display:inline;margin:0;">
                @csrf
                <button type="submit" class="secondary" style="padding:0.4rem 0.85rem;font-size:0.85rem;">Cerrar sesión</button>
            </form>
        </div>
        @endauth
    </header>

    <main>
        {{ $slot }}
        <p class="muted" style="font-size:0.8rem;text-align:center;margin-top:2.5rem;border-top:1px solid #21262d;padding-top:1rem;">
            MMOM · Entorno de Simulación Académica y Toma de Decisiones Estratégicas.
        </p>
    </main>

    @livewireScripts
</body>
</html>
