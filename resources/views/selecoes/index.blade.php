@extends('master')

@section('content')
    @parent
    <div class="row">
        <div class="col-md-12 form-inline">
            <span class="h4 mt-2">Seleções</span>
            @include('partials.datatable-filter-box', ['otable'=>'oTable'])
            @if(Gate::check('processos.viewAny'))
                <a href="{{ route('selecoes.create') }}" class="btn btn-sm btn-success">
                    <i class="fas fa-plus"></i> Nova
                </a>
            @endif
        </div>
    </div>

    <table class="table table-striped table-hover datatable-nopagination display responsive" style="width:100%">
        <thead>
            <tr>
                <td>Processo</td>
                <td>Nome</td>
                <td>Descrição</td>
            </tr>
        </thead>
        <tbody>
            @foreach ($modelos as $selecao)
            <tr>
                <td>{{ $selecao->processo->nome }}</td>
                <td>
                    @include('selecoes.partials.status-small')
                    <a class="mr-2" href="selecoes/edit/{{ $selecao->id }}">{{ $selecao->nome }}</a>
                    @include('selecoes.partials.status-muted')
                </td>
                <td>{{ $selecao->descricao }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection

@section('javascripts_bottom')
    @parent
    <script>
        $(document).ready(function() {
            oTable = $('.datatable-nopagination').DataTable({
                dom: 't',
                'paging': false
            });
        });
    </script>
@endsection
