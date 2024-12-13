<div class="d-flex">
  <b>
    {{ $linhapesquisa->nome }}
  </b>
  <div class="hidden-btn d-none ml-auto">
    @can('selecoes.update')
      @if ($condicao_ativa)
        @include('common.btn-delete-sm', ['action' => "selecoes/{$selecao->id}/linhaspesquisa/{$linhapesquisa->id}"])
      @endif
    @endcan
  </div>
</div>

@once
@section('javascripts_bottom')
@parent
  <script type="text/javascript">
    $(function() {
      $('.linhapesquisa-item').hover(
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
