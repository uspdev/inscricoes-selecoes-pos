<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitacaoIsencaoTaxaRequest;
use App\Jobs\AtualizaStatusSelecoes;
use App\Models\LocalUser;
use App\Models\MotivoIsencaoTaxa;
use App\Models\Nivel;
use App\Models\Programa;
use App\Models\Selecao;
use App\Models\SolicitacaoIsencaoTaxa;
use App\Models\TipoArquivo;
use App\Models\User;
use App\Utils\JSONForms;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Uspdev\Replicado\Pessoa;

class SolicitacaoIsencaoTaxaController extends Controller
{
    // crud generico
    public static $data = [
        'title' => 'Solicitações de Isenção de Taxa',
        'url' => 'solicitacoesisencaotaxa',     // caminho da rota do resource
        'modal' => true,
        'showId' => false,
        'viewBtn' => true,
        'editBtn' => false,
        'model' => 'App\Models\SolicitacaoIsencaoTaxa',
    ];

    public function __construct()
    {
        $this->middleware('auth')->except([
            'listaSelecoesParaSolicitacaoIsencaoTaxa',
            'create',
            'store'
        ]);    // exige que o usuário esteja logado, exceto para estes métodos listados
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $perfil_admin_ou_gerente_ou_docente =  in_array(session('perfil'), ['admin', 'gerente', 'docente']);
        Gate::authorize('solicitacoesisencaotaxa.view' . ($perfil_admin_ou_gerente_ou_docente ? 'Any' : 'Their'));

        \UspTheme::activeUrl('solicitacoesisencaotaxa');
        return view('solicitacoesisencaotaxa.index', $this->monta_compact_index());
    }

    /**
     * Mostra lista de seleções e respectivas categorias
     * para solicitar isenção de taxa
     *
     * @param  \Illuminate\Http\Request   $request
     * @return \Illuminate\Http\Response
     */
    public function listaSelecoesParaSolicitacaoIsencaoTaxa(Request $request)
    {
        Gate::authorize('solicitacoesisencaotaxa.create');

        $request->validate(['filtro' => 'nullable|string']);

        \UspTheme::activeUrl('solicitacoesisencaotaxa/create');
        AtualizaStatusSelecoes::dispatch()->onConnection('sync');
        $categorias = Selecao::listarSelecoesParaSolicitacaoIsencaoTaxa();          // obtém as seleções dentro das categorias
        return view('solicitacoesisencaotaxa.listaselecoesparasolicitacaoisencaotaxa', compact('categorias'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param  \App\Models\Selecao        $selecao
     * @return \Illuminate\Http\Response
     */
    public function create(Selecao $selecao)
    {
        Gate::authorize('solicitacoesisencaotaxa.create', $selecao);

        $solicitacaoisencaotaxa = new SolicitacaoIsencaoTaxa;
        $solicitacaoisencaotaxa->selecao = $selecao;
        $user = Auth::user();
        $extras = array(
            'nome' => $user->name,
            'e_mail' => $user->email,
        );
        $solicitacaoisencaotaxa->extras = json_encode($extras);

        \UspTheme::activeUrl('solicitacoesisencaotaxa/create');
        return view('solicitacoesisencaotaxa.edit', $this->monta_compact($solicitacaoisencaotaxa, 'create'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request        $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $selecao = Selecao::find($request->selecao_id);
        Gate::authorize('solicitacoesisencaotaxa.create', $selecao);

        $user = \Auth::user();

        // transaction para não ter problema de inconsistência do DB
        $solicitacaoisencaotaxa = DB::transaction(function () use ($request, $user, $selecao) {

            // grava a solicitação de isenção de taxa
            $solicitacaoisencaotaxa = new SolicitacaoIsencaoTaxa;
            $solicitacaoisencaotaxa->selecao_id = $selecao->id;
            $solicitacaoisencaotaxa->estado = 'Aguardando Envio';
            $solicitacaoisencaotaxa->extras = json_encode($request->extras);
            $solicitacaoisencaotaxa->saveQuietly();      // vamos salvar sem evento pois o autor ainda não está cadastrado
            $solicitacaoisencaotaxa->load('selecao');    // com isso, $solicitacaoisencaotaxa->selecao é carregado
            $solicitacaoisencaotaxa->users()->attach($user, ['papel' => 'Autor']);

            return $solicitacaoisencaotaxa;
        });

        // agora sim vamos disparar o evento (necessário porque acima salvamos com saveQuietly)
        event('eloquent.created: App\Models\SolicitacaoIsencaoTaxa', $solicitacaoisencaotaxa);

        $request->session()->flash('alert-success', 'Envie os documentos necessários para a avaliação da sua solicitação<br />' .
            'Sem eles, sua solicitação não será avaliada!');

        \UspTheme::activeUrl('solicitacoesisencaotaxa/create');
        return redirect()->to(url('solicitacoesisencaotaxa/edit/' . $solicitacaoisencaotaxa->id))->with($this->monta_compact($solicitacaoisencaotaxa, 'edit', 'arquivos'));    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request            $request
     * @param  \App\Models\SolicitacaoIsencaoTaxa  $solicitacaoisencaotaxa
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, SolicitacaoIsencaoTaxa $solicitacaoisencaotaxa)
    {
        Gate::authorize('solicitacoesisencaotaxa.view', $solicitacaoisencaotaxa);    // este 1o passo da edição é somente um show, não chega a haver um update

        \UspTheme::activeUrl('solicitacoesisencaotaxa');
        $solicitacaoisencaotaxa->selecao->atualizarStatus();
        return view('solicitacoesisencaotaxa.edit', $this->monta_compact($solicitacaoisencaotaxa, 'edit', session('scroll')));    // repassa scroll que eventualmente veio de redirect()->to(url(
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request            $request
     * @param  \App\Models\SolicitacaoIsencaoTaxa  $solicitacaoisencaotaxa
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SolicitacaoIsencaoTaxa $solicitacaoisencaotaxa)
    {
        if ($request->input('acao', null) == 'envio') {
            Gate::authorize('solicitacoesisencaotaxa.update', $solicitacaoisencaotaxa);

            if ($solicitacaoisencaotaxa->todosArquivosRequeridosPresentes()) {

                $solicitacaoisencaotaxa->estado = 'Isenção de Taxa Solicitada';
                $solicitacaoisencaotaxa->save();

                $request->session()->flash('alert-success', 'Sua solicitação de isenção de taxa foi enviada');
                \UspTheme::activeUrl('solicitacoesisencaotaxa');
                return redirect()->to(url('solicitacoesisencaotaxa'))->with($this->monta_compact_index());    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect

            } else {
                $request->session()->flash('alert-success', 'É necessário antes enviar todos os documentos exigidos');
                \UspTheme::activeUrl('solicitacoesisencaotaxa');
                return view('solicitacoesisencaotaxa.edit', $this->monta_compact($solicitacaoisencaotaxa, 'edit'));
            }
        }

        if ($request->conjunto_alterado == 'estado') {
            Gate::authorize('solicitacoesisencaotaxa.updateStatus', $solicitacaoisencaotaxa);

            // transaction para não ter problema de inconsistência do DB
            $solicitacaoisencaotaxa = DB::transaction(function () use ($request, $solicitacaoisencaotaxa) {

                $solicitacaoisencaotaxa->estado = $request->estado;
                $solicitacaoisencaotaxa->save();

                return $solicitacaoisencaotaxa;
            });

            $request->session()->flash('alert-success', 'Estado da solicitação de isenção de taxa alterado com sucesso');

        } else {
            Gate::authorize('solicitacoesisencaotaxa.update', $solicitacaoisencaotaxa);

            $solicitacaoisencaotaxa->extras = json_encode($request->extras);
            $solicitacaoisencaotaxa->save();

            $request->session()->flash('alert-success', 'Solicitação de isenção de taxa alterada com sucesso');
        }

        \UspTheme::activeUrl('solicitacoesisencaotaxa');
        return view('solicitacoesisencaotaxa.edit', $this->monta_compact($solicitacaoisencaotaxa, 'edit'));
    }

    public function monta_compact_index()
    {
        $data = self::$data;
        $objetos = SolicitacaoIsencaoTaxa::listarSolicitacoesIsencaoTaxa();
        $classe_nome = 'SolicitacaoIsencaoTaxa';
        $max_upload_size = config('inscricoes-selecoes-pos.upload_max_filesize');

        return compact('data', 'objetos', 'classe_nome', 'max_upload_size');
    }

    public function monta_compact(SolicitacaoIsencaoTaxa $solicitacaoisencaotaxa, string $modo, ?string $scroll = null)
    {
        $data = (object) self::$data;
        $solicitacaoisencaotaxa->selecao->template = JSONForms::orderTemplate($solicitacaoisencaotaxa->selecao->template);
        $objeto = $solicitacaoisencaotaxa;
        $classe_nome = 'SolicitacaoIsencaoTaxa';
        $classe_nome_plural = 'solicitacoesisencaotaxa';
        $form = JSONForms::generateForm($objeto->selecao, $classe_nome, $objeto);
        $responsaveis = $objeto->selecao->programa?->obterResponsaveis() ?? (new Programa())->obterResponsaveis();
        $objeto->tiposarquivo = TipoArquivo::obterTiposArquivoDaSelecao('SolicitacaoIsencaoTaxa', null, $objeto->selecao);
        $tiposarquivo_selecao = TipoArquivo::obterTiposArquivoPossiveis('Selecao', null, $objeto->selecao->programa_id);
        $max_upload_size = config('inscricoes-selecoes-pos.upload_max_filesize');

        return compact('data', 'objeto', 'classe_nome', 'classe_nome_plural', 'form', 'modo', 'responsaveis', 'tiposarquivo_selecao', 'max_upload_size', 'scroll');
    }
}
