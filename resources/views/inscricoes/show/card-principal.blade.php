@section('styles')
@parent
  <style>
    #card-selecao-principal {
      border: 1px solid coral;
      border-top: 3px solid coral;
    }
  </style>
@endsection

{{ html()->form('post', $data->url . (($modo == 'edit') ? ('/edit/' . $inscricao->id) : '/create'))
  ->attribute('id', 'form_principal')
  ->open() }}
  @csrf
  @method($modo == 'edit' ? 'put' : 'post')
  {{ html()->hidden('id') }}
  <input type="hidden" id="selecao_id" name="selecao_id" value="{{ $inscricao->selecao->id }}">
  <div class="card mb-3 w-100" id="card-selecao-principal">
    <div class="card-header">
      Informações básicas
    </div>
    <div class="card-body">
      <div class="list_table_div_form">
        @if (isset($form))
          @foreach ($form as $input)
            <div class="form-group row">
              @if (is_array($input))
                @foreach ($input as $element)
                  {!! $element !!}
                @endforeach
                <br>
              @endif
            </div>
          @endforeach
        @endif
      </div>
      <div class="text-right">
      <button type="submit" class="btn btn-primary">{{ ($modo == 'edit' ) ? 'Salvar' : 'Prosseguir' }}</button>
      </div>
    </div>
  </div>
{{ html()->form()->close() }}

@section('javascripts_bottom')
@parent
  <script>
    $(document).ready(function() {
      $('#form_principal').find(':input:visible:first').focus();

      $('#form_principal [required]').each(function () {
        this.oninvalid = function(e) {
          e.target.setCustomValidity('');
          if (!e.target.validity.valid)
            if (e.target.type === 'email')
              if (e.target.value != '')
                e.target.setCustomValidity('E-mail inválido');
              else
                e.target.setCustomValidity('Favor preencher este campo');
            else
              e.target.setCustomValidity('Favor preencher este campo');
        };
        this.oninput = function(e) {
          e.target.setCustomValidity('');
        }
      });

      $('input[id="extras\[cpf\]"], input[id^="extras\[cpf_"]').each(function() {
        $(this).mask('000.000.000-00');
      })

      $('input[id="extras\[cep\]"], input[id^="extras\[cep_"]').each(function() {
        $(this).mask('00000-000');
      })

      $('input[id="extras\[celular\]"], input[id^="extras\[celular_"]').each(function() {
        $(this).mask('(00) 00000-0000');
      })
    });

    function consultar_cep(field_name)
    {
      var cep = $('input[id="extras\[' + field_name + '\]"]').val().replace('-', '');
      if (cep)
        $.ajax({
          url: '{{ route("consulta.cep") }}',
          type: 'get',
          data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            cep: cep
          },
          success: function(data) {
            var field_suffix = '';
            if (field_name.includes('_'))
              field_suffix = '_' + field_name.split('_')[1];

            $('input[id="extras\[endereco_residencial' + field_suffix + '\]"]').val(data.logradouro);
            $('input[id="extras\[bairro' + field_suffix + '\]"]').val(data.bairro);
            $('input[id="extras\[cidade' + field_suffix + '\]"]').val(data.localidade);
            $('select[id="extras\[uf' + field_suffix + '\]"]').val(data.uf.toLowerCase());
          },
          error: function(xhr, status, error) {
            if (xhr.responseJSON && xhr.responseJSON.error)
              window.alert(xhr.responseJSON.error);
            else if (xhr.responseText)
              window.alert(xhr.responseText);
          }
      });
    }
  </script>
@endsection
