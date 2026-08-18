<ul class="tasks">
    @foreach ($affectations as $affectation)
        @include('partials.tache-ligne', [
            'affectation' => $affectation,
            'avance' => $avance ?? false,
        ])
    @endforeach
</ul>
