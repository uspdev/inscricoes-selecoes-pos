<button type="button" class="btn btn-sm btn-light text-primary" data-toggle="modal" data-target="#LinhaPesquisaModal">
    <i class="fas fa-plus"></i> Adicionar
</button>

<!-- Modal -->
<div class="modal fade" id="LinhaPesquisaModal" data-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Linha de Pesquisa</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="list_table_div_form">
                    {{ html()->form('post', 'selecoes/' . $selecao->id . '/linhaspesquisa')->open() }}
                        @csrf
                        @method('post')
                        <div class="form-group row">
                            <div class="col-form-label col-sm-3">Linha de Pesquisa</div>
                            <div class="col-sm-8">
                                <select class="form-control" name="id">
                                    @foreach ($linhaspesquisa as $linhapesquisa)
                                        <option value='{{ $linhapesquisa->id }}'>{{ $linhapesquisa->nome }} ({{ $linhapesquisa->codpes_docente }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="text-right">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                            <button type="submit" class="btn btn-primary">Salvar</button>
                        </div>
                    {{ html()->form()->close() }}
                </div>
            </div>
        </div>
    </div>
</div>

@section('javascripts_bottom')
@parent
<script>
    $(document).ready(function() {
        add_modal_form = function() {
            $('#LinhaPesquisaModal').modal()
        }
    })
</script>
@endsection
