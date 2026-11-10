<section>
<h1>Partidas disponibles</h1>
<p class="muted">Las partidas se muestran según la identidad y las asignaciones autorizadas.</p>
<div class="grid">
@forelse($games as $game)
<article class="card"><h2>{{ $game['name'] }}</h2>
<p>Estado: <strong>{{ $game['status'] }}</strong></p>
<p>Ronda: {{ $game['current_round'] ?? 'No iniciada' }} de 8</p>
<a href="{{ route('mmom.games.show',['gameId'=>$game['id']]) }}">Abrir consola</a></article>
@empty <p>No hay partidas disponibles para esta cuenta.</p> @endforelse
</div></section>