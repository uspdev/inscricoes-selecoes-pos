@section('styles')
@parent
  <style>
    #card-disciplinas {
      border: 1px solid brown;
      border-top: 3px solid brown;
    }
  </style>
@endsection

<a name="card_disciplinas"></a>
<div class="card bg-light mb-3" id="card-disciplinas">
  <div class="card-header">
    Disciplinas
    <span class="badge badge-pill badge-primary">{{ is_null($inscricao_disciplinas) ? 0 : count($inscricao_disciplinas) }}</span>
    @if (in_array($inscricao->selecao->estado, ['Período de Solicitações de Isenção de Taxa e de Inscrições', 'Período de Inscrições']) && (session('perfil') == 'usuario'))
      @include('disciplinas.partials.modal-add', ['inclusor_url' => 'inscricoes', 'inclusor_objeto' => $inscricao])
    @endif
  </div>
  <div class="card-body">
    <div class="accordion" id="accordionDisciplinas">
      @if (!is_null($inscricao_disciplinas))
        @foreach ($inscricao_disciplinas as $inscricao_disciplina)
          <div class="card disciplina-item">
            <div class="card-header" style="font-size:15px">
              @include('disciplinas.show.header-inscricoes')
            </div>
          </div>
        @endforeach
      @endif
    </div>
  </div>
</div>
