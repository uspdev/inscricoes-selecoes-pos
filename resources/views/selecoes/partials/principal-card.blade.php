@section('styles')
@parent
<style>
    #card-fila-principal {
        border: 1px solid coral;
        border-top: 3px solid coral;
    }

</style>
@endsection

<div class="card mb-3 w-100" id="card-fila-principal">
    <div class="card-header">
        Informações básicas
    </div>
    <div class="card-body">
        <div class="list_table_div_form">
            @include('common.list-table-form-contents')
        </div>
    </div>
</div>
