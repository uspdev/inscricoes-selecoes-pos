<div class="d-flex">
  <b>
    {{ $inscricao_disciplina->sigla }} - {{ $inscricao_disciplina->nome }}
  </b>
  <div class="hidden-btn d-none ml-auto">
    @if (in_array($inscricao->selecao->estado, ['Período de Solicitações de Isenção de Taxa e de Inscrições', 'Período de Inscrições']) && (session('perfil') == 'usuario'))
      @include('common.btn-delete-sm', ['action' => "inscricoes/{$inscricao->id}/disciplinas/{$inscricao_disciplina->id}"])
    @endif
  </div>
</div>

@once
@section('javascripts_bottom')
@parent
  <script type="text/javascript">
    $(function() {
      $('.disciplina-item').hover(
        function() {
          $(this).find('.hidden-btn').removeClass('d-none');
        },
        function() {
          $(this).find('.hidden-btn').addClass('d-none');
        }
      );
    });
  </script>
@endsection
@endonce
