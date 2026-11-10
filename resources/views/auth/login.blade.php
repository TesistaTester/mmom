<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <title>Iniciar Sesión · Simulador MMOM</title>
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
            padding: 1.1rem 2rem;
            border-bottom: 1px solid #30363d;
            text-align: center;
            color: #f0f6fc;
            font-size: 1.05rem;
        }
        main {
            max-width: 440px;
            width: 100%;
            margin: 3.5rem auto;
            padding: 0 1.2rem;
        }
        .card {
            border: 1px solid #30363d;
            background-color: #161b22;
            padding: 2.2rem;
            border-radius: 12px;
            box-shadow: 0 12px 28px rgba(0,0,0,0.4);
            color: #f0f6fc;
        }
        h1 {
            margin-top: 0;
            font-size: 1.55rem;
            font-weight: 700;
            text-align: center;
            color: #f0f6fc;
        }
        .subtitle {
            text-align: center;
            color: #8b949e;
            font-size: 0.9rem;
            margin-bottom: 1.6rem;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        label {
            display: block;
            margin-bottom: 0.45rem;
            font-size: 0.85rem;
            color: #c9d1d9;
            font-weight: 600;
        }
        input[type="email"], input[type="password"] {
            width: 100%;
            background-color: #0d1117;
            color: #f0f6fc;
            border: 1px solid #30363d;
            border-radius: 6px;
            padding: 0.75rem 0.95rem;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.2s;
        }
        input:focus {
            border-color: #58a6ff;
            box-shadow: 0 0 0 3px rgba(88,166,255,0.25);
        }
        .remember-row {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin: 1rem 0 1.5rem;
            font-size: 0.85rem;
            color: #c9d1d9;
        }
        button {
            width: 100%;
            background-color: #1f6feb;
            color: #ffffff;
            border: none;
            border-radius: 6px;
            padding: 0.85rem;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: filter 0.15s ease;
        }
        button:hover {
            filter: brightness(1.1);
        }
        .error {
            color: #ff7b72;
            font-size: 0.85rem;
            margin-top: 0.4rem;
            font-weight: 500;
        }
        .quick-hint {
            margin-top: 2rem;
            background-color: #0d1117;
            border: 1px dashed #30363d;
            padding: 1.1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            color: #c9d1d9;
            line-height: 1.5;
        }
        .quick-hint strong {
            color: #f0f6fc;
        }
        .tag {
            background-color: #21262d;
            border: 1px solid #30363d;
            padding: 0.15rem 0.45rem;
            border-radius: 4px;
            font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
            color: #79c0ff;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <header>
        <strong>Simulador Estratégico de Discotecas · MMOM</strong>
    </header>

    <main>
        <div class="card">
            <h1>Acceso al Simulador</h1>
            <div class="subtitle">Ingresa con tus credenciales de participante o administrador</div>

            @if ($errors->any())
                <div style="background:#491b1f;border:1px solid #f85149;padding:0.75rem 1rem;border-radius:6px;margin-bottom:1.2rem;color:#ff7b72;font-size:0.85rem;font-weight:600;">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="/login">
                @csrf
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="admin@mmom.com">
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
                </div>

                <div class="remember-row">
                    <input type="checkbox" id="remember" name="remember" style="transform:scale(1.15);">
                    <label for="remember" style="margin:0;cursor:pointer;color:#c9d1d9;">Recordar mi sesión</label>
                </div>

                <button type="submit">Entrar al Sistema</button>
            </form>

            <div class="quick-hint">
                <strong>Credenciales de prueba:</strong><br>
                • Admin: <span class="tag">admin@mmom.com</span><br>
                • Jugador: <span class="tag">jugador1@mmom.com</span><br>
                • Clave: <span class="tag">password123</span>
            </div>
        </div>
    </main>
</body>
</html>
