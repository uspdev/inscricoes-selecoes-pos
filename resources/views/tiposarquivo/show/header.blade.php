<div class="d-flex">
  <b>
    {{ $tipoarquivo->nome }}
  </b>
  <div class="hidden-btn d-none ml-auto">
    @can('selecoes.update', $selecao)
      @if ($condicao_ativa)
        @include('common.btn-delete-sm', ['action' => "selecoes/{$selecao->id}/tiposarquivo" . strtolower($tipoarquivo_classe_nome) . "/{$tipoarquivo->id}"])
      @endif
    @endcan
  </div>
</div>

@once
@section('javascripts_bottom')
@parent
  <script type="text/javascript">
    $(function() {
      $('.tipoarquivo-item').hover(
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
