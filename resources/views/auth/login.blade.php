<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar Sesión · Simulador MMOM</title>
    <style>
        :root {
            font-family: system-ui, -apple-system, Segoe UI, sans-serif;
            color-scheme: dark;
            background: #0e1422;
            color: #eef1fb;
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; flex-direction: column; }
        header { background: #182338; padding: 1rem 2rem; border-bottom: 1px solid #37455f; text-align: center; }
        main { max-width: 440px; width: 100%; margin: 3rem auto; padding: 0 1.2rem; }
        .card { border: 1px solid #38465f; background: #172236; padding: 2rem; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
        h1 { margin-top: 0; font-size: 1.5rem; text-align: center; color: #92c5ff; }
        .subtitle { text-align: center; color: #b6c3da; font-size: 0.9rem; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1.2rem; }
        label { display: block; margin-bottom: 0.4rem; font-size: 0.85rem; color: #b6c3da; font-weight: 500; }
        input[type="email"], input[type="password"] {
            width: 100%;
            background: #0d1625;
            color: #eef1fb;
            border: 1px solid #6e809f;
            border-radius: 6px;
            padding: 0.75rem 0.9rem;
            font-size: 0.95rem;
        }
        input:focus { outline: 3px solid #e6c666; outline-offset: 2px; }
        .remember-row { display: flex; align-items: center; gap: 0.5rem; margin: 1rem 0 1.5rem; font-size: 0.85rem; color: #b6c3da; }
        button {
            width: 100%;
            background: #346cca;
            color: #fff;
            border: 0;
            border-radius: 7px;
            padding: 0.85rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        button:hover { background: #2659b0; }
        .error { color: #ff857a; font-size: 0.85rem; margin-top: 0.4rem; }
        .quick-hint {
            margin-top: 2rem;
            background: #111a2d;
            border: 1px dashed #3a4b6b;
            padding: 1rem;
            border-radius: 8px;
            font-size: 0.8rem;
            color: #8ea1c1;
        }
        .quick-hint strong { color: #eef1fb; }
        .tag { background: #21324d; padding: 0.15rem 0.4rem; border-radius: 4px; font-family: monospace; color: #92c5ff; }
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
                <div style="background:#421d22;border:1px solid #842029;padding:0.75rem;border-radius:6px;margin-bottom:1.2rem;color:#f8d7da;font-size:0.85rem;">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}">
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
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember" style="margin:0;cursor:pointer;">Recordar mi sesión</label>
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
