@extends('master')

@section('content')
@parent
  @auth
    <script type="text/javascript">window.location = "inscricoes";</script>
  @else
    Candidato, se você tiver número USP, <a href="login">faça login usando sua senha única</a>.<br />
    Se você não tiver número USP, <a href="inscricoes/create">realize sua inscrição</a> ou, para acessar suas inscrições, <a href="localusers/login">faça login usando a senha cadastrada no ato da inscrição</a>.<br />
    <br />
    Administrativo, <a href="login">faça login com sua senha única USP</a>.
  @endauth
@endsection
