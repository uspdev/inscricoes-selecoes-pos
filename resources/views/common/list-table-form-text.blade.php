<div class="form-group row">
  @php
    $col['label'] .= ((in_array('required', $rules[$col['name']] ?? [])) ? ' <small class="text-required">(*)</small>' : '');
  @endphp
  {{ html()->label($col['label'] ?? $col['name'], $col['name'])->class('col-form-label col-sm-3') }}
  <div class="col-sm-9">
    @php
      $input = html()->input('text', $col['name'])->value(old($col['name'], $modo == 'edit' ? $objeto->{$col['name']} : ''))->class('form-control');
      if (in_array('required', $rules[$col['name']]))
        $input = $input->required();
    @endphp
    {{ $input }}
  </div>
</div>
