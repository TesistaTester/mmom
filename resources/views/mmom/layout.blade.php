<!DOCTYPE html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="color-scheme" content="dark light"><title>MMOM · Gestión empresarial</title>
<style>
:root{font-family:system-ui,-apple-system,Segoe UI,sans-serif;color-scheme:dark;background:#0e1422;color:#eef1fb}
*{box-sizing:border-box}body{margin:0}header{background:#182338;padding:1rem 2rem;border-bottom:1px solid #37455f}
a{color:#92c5ff}main{max-width:1050px;margin:2rem auto;padding:0 1.2rem}.card{border:1px solid #38465f;background:#172236;padding:1.25rem;margin:1rem 0;border-radius:12px}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:1rem}.muted{color:#b6c3da}.tag{padding:.2rem .5rem;border:1px solid #627591;border-radius:6px}
button{background:#346cca;color:#fff;border:0;border-radius:7px;padding:.7rem 1rem;cursor:pointer}button:disabled{opacity:.45;cursor:not-allowed}button.secondary{background:#4a5670}
textarea{display:block;width:100%;min-height:160px;background:#0d1625;color:#eef1fb;border:1px solid #6e809f;border-radius:6px;padding:.9rem;font-family:ui-monospace,monospace}
label{display:block;margin:.7rem 0}.error{color:#ffc1bc}.notice{padding:.8rem;background:#183a36;border:1px solid #318474;border-radius:6px}
:focus-visible{outline:3px solid #e6c666;outline-offset:2px} @media(prefers-color-scheme:light){:root{background:#f7f9fd;color:#17243c;color-scheme:light}.card{background:white;border-color:#ccd5e3}header{background:#e5edfa;border-color:#ccd5e3}.muted{color:#47576d}textarea{background:white;color:#111}}
</style>@livewireStyles</head><body>
<header style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.5rem;">
    <div><strong>MMOM · Simulador de Discotecas</strong> · E7 V0.1 <span class="tag">Entorno de desarrollo</span></div>
    @auth
    <div style="display:flex;align-items:center;gap:1rem;">
        <span class="muted" style="font-size:0.9rem;">👤 {{ auth()->user()->display_name ?? auth()->user()->email }}</span>
        <form method="POST" action="{{ route('logout') }}" style="display:inline;margin:0;">
            @csrf
            <button type="submit" class="secondary" style="padding:0.35rem 0.75rem;font-size:0.8rem;">Cerrar sesión</button>
        </form>
    </div>
    @endauth
</header>
<main>{{ $slot }}<p class="muted">Resultados no oficiales. Sin cierre MIFIV ni efectos financieros automáticos.</p></main>
@livewireScripts</body></html>