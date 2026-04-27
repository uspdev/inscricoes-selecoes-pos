@nomenclatura(['selecao' => $inscricao->selecao])

Olá {{ $user->name }},<br />
<br />
Sua {{ $inscricao_ou_matricula }} para {{ $objetivo }} está pendente de envio.<br />
Para prosseguir com sua {{ $inscricao_ou_matricula }}, clique <a href="{{ config('app.url') }}/{{ $rota }}/edit/{{ $inscricao->id }}#card_arquivos">aqui</a> e envie os documentos necessários.<br />
Tendo enviado todos os documentos, clique no botão "Enviar {{ ucfirst($inscricao_ou_matricula) }}" que fica abaixo da lista de documentos.<br />
<b>Sem isso, ela não será avaliada!</b><br />
<br />
@include('emails.rodape')
