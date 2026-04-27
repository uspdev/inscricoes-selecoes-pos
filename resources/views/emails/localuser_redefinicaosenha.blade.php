Olá {{ $localuser->name }},<br />
<br />
Recebemos uma solicitação para redefinir a senha da sua conta.<br />
<br />
Se você não solicitou uma redefinição de senha, por favor, ignore este e-mail. Nenhuma outra ação é necessária.<br />
<br />
Para redefinir sua senha, clique <a href="{{ $password_reset_url }}">aqui.</a><br />
<br />
<br />
<br />
Este link é válido por {{ config('inscricoes-selecoes-pos.password_reset_link_expiry_time') }} minutos e só pode ser usado uma vez.<br />
@include('emails.rodape')
