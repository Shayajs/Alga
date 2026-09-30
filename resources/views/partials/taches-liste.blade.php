<ul class="tasks">
    @foreach ($affectations as $affectation)
        @include('partials.tache-ligne', [
            'affectation' => $affectation,
            'avance' => $avance ?? false,
            'vol' => $vol ?? false,
        ])
    @endforeach
</ul>
