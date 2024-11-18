@section('styles')
  @parent
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.css" />
  <link rel="stylesheet" href="css/arquivos.css">
  <style>
    #card-arquivos {
      border: 1px solid DarkGoldenRod;
      border-top: 3px solid DarkGoldenRod;
    }
  </style>
@endsection

<a name="card_arquivos"></a>
<div class="card bg-light mb-3 w-100" id="card-arquivos">
  <div class="card-header form-inline">
    Arquivos
    <span data-toggle="tooltip" data-html="true" title="Tamanho máximo de cada arquivo: {{ $max_upload_size }}KB ">
      <i class="fas fa-question-circle text-secondary ml-2"></i>
    </span>
  </div>
  <div class="card-body">
    @if (Gate::check('update', $modelo) && $condicao_ativa)    {{-- desativando quando inativa --}}
      @php
        $i = 0;
      @endphp
      @foreach ($modelo->tiposArquivo() as $tipo_arquivo)
        <div class="arquivos-lista">
          {{ $tipo_arquivo }}
          <label for="input_arquivo_{{ $i }}">
            <span class="btn btn-sm btn-light text-primary ml-2"> <i class="fas fa-plus"></i> Adicionar</span>
          </label>
          <form id="form_arquivo_{{ $i }}" action="arquivos" method="post" enctype="multipart/form-data" class="w-100">
            @csrf
            <input type="hidden" name="tipo_modelo" value="{{ $tipo_modelo }}">
            <input type="hidden" name="modelo_id" value="{{ $modelo->id }}">
            <input type="hidden" name="tipo_arquivo" value="{{ $tipo_arquivo }}">

            <input type="file" name="arquivo[]" id="input_arquivo_{{ $i }}" accept="image/jpeg,image/png,application/pdf"
              class="d-none" multiple capture="environment">
          </form>
          @if ($modelo->arquivos->where('pivot.tipo', $tipo_arquivo)->count() > 0)
            <ul class="list-unstyled">
              @foreach ($modelo->arquivos->where('pivot.tipo', $tipo_arquivo) as $arquivo)
                @if (preg_match('/pdf/i', $arquivo->mimeType))
                  <li class="modo-visualizacao">
                    @if (Gate::check('update', $modelo) && $condicao_ativa)    {{-- desativando quando inativa --}}
                      <div class="arquivo-acoes">
                        <form action="arquivos/{{ $arquivo->id }}" method="post" class="d-inline-block">
                          @csrf
                          @method('delete')
                          <input type="hidden" name="tipo_modelo" value="{{ $tipo_modelo }}">
                          <input type="hidden" name="modelo_id" value="{{ $modelo->id }}">
                          <input type="hidden" name="tipo_arquivo" value="{{ $tipo_arquivo }}">
                          
                          <button type="submit"
                            onclick="return ativar_exclusao_arquivo('{{ $arquivo->nome_original }}');"
                            class="btn btn-outline-danger btn-sm btn-deletar btn-arquivo-acao"><i
                            class="far fa-trash-alt"></i></button>
                        </form>
                        <form class="d-inline-block">
                          <button type="button" class="btn btn-outline-warning btn-sm btn-editar btn-arquivo-acao"><i
                            class="far fa-edit"></i></button>
                        </form>
                      </div>
                    @endif
                    <a href="arquivos/{{ $arquivo->id }}" title="{{ $arquivo->nome_original }}"
                      class="nome-arquivo-display"><i class="fas fa-file-pdf"></i>
                      <span>
                        {{ $arquivo->nome_original }}
                      </span>
                    </a>
                    <form action="arquivos/{{ $arquivo->id }}" method="post" class="editar-nome-arquivo-form">
                      @csrf
                      @method('patch')
                      <input type="hidden" name="tipo_modelo" value="{{ $tipo_modelo }}">
                      <input type="hidden" name="modelo_id" value="{{ $modelo->id }}">
                      <div class="input-wrapper">
                        <input type="text" name="nome_arquivo" class="input-nome-arquivo"
                          value="{{ pathinfo($arquivo->nome_original, PATHINFO_FILENAME) }}">
                      </div>
                      <div class="btns-wrapper">
                        <button type="submit"
                          onclick="return ativar_alteracao_arquivo();"
                          class="btn btn-outline-success btn-sm ml-2 btn-arquivo-acao"><i
                          class="fas fa-check"></i></button>
                        <button type="button" class="btn btn-outline-danger btn-sm btn-arquivo-acao limpar-edicao-nome"><i
                          class="fas fa-times"></i></button>
                      </div>
                    </form>
                  </li>
                @endif            
              @endforeach
            </ul>
          </div>
        @endif
        @php
          $i++;
        @endphp
      @endforeach
    @endif
  </div>
</div>

@include('common.modal-processando')

@section('javascripts_bottom')
  @parent
  <script src="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.js"></script>
  <script type="text/javascript">
    var max_upload_size = {{ $max_upload_size }};
    var count_tipos_arquivo = {{ count($modelo->tiposArquivo()) }};
  </script>
  <script src="js/arquivos.js"></script>
@endsection
