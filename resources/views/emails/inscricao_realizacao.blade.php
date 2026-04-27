@nomenclatura(['selecao' => $inscricao->selecao])

{{ $responsavel_nome }},
<br />
Foi enviada uma {{ $inscricao_ou_matricula }} para {{ $objetivo }}.<br />
Clique <a href="{{ config('app.url') }}/{{ str($inscricao_ou_matricula_plural)->ascii() }}/edit/{{ $inscricao->id }}">aqui</a> para avaliar os dados e documentos do candidato, e pré-aprovar (ou pré-rejeitar) sua {{ $inscricao_ou_matricula }} no sistema.<br />
<br />
@include('emails.rodape')
