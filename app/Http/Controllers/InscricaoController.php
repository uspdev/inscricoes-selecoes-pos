<?php

namespace App\Http\Controllers;

use App\Http\Requests\InscricaoRequest;
use App\Jobs\AtualizaStatusSelecoes;
use App\Mail\InscricaoMail;
use App\Models\Arquivo;
use App\Models\Disciplina;
use App\Models\Inscricao;
use App\Models\LinhaPesquisa;
use App\Models\LocalUser;
use App\Models\Nivel;
use App\Models\Orientador;
use App\Models\Parametro;
use App\Models\Programa;
use App\Models\Selecao;
use App\Models\SolicitacaoIsencaoTaxa;
use App\Models\TipoArquivo;
use App\Models\User;
use App\Services\BoletoService;
use App\Utils\JSONForms;
use App\Utils\Nomenclatura;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class InscricaoController extends Controller
{
    protected $boletoService;

    // crud generico
    public static $data = [
        'title' => 'Inscrições',
        'url' => 'inscricoes',     // caminho da rota do resource
        'modal' => true,
        'showId' => false,
        'viewBtn' => true,
        'editBtn' => false,
        'model' => 'App\Models\Inscricao',
    ];

    public function __construct(BoletoService $boletoService)
    {
        $this->middleware('auth')->except([
            'listaSelecoesParaNovaInscricao',
            'create',
            'store'
        ]);    // exige que o usuário esteja logado, exceto para estes métodos listados
        $this->boletoService = $boletoService;

        if (request()->segment(1) == 'matriculas') {
            self::$data['title'] = 'Matrículas';
            self::$data['url'] = 'matriculas';
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (in_array(session('perfil'), ['admin', 'gerente', 'docente']))
            Gate::authorize('inscricoes.viewAny');
        else
            Gate::authorize('inscricoes.viewTheir');

        \UspTheme::activeUrl(request()->segment(1));
        return view('inscricoes.index', $this->monta_compact_index());
    }

    /**
     * Mostra lista de seleções e respectivas categorias
     * para selecionar e criar nova inscrição/matrícula
     *
     * @param  \Illuminate\Http\Request   $request
     * @return \Illuminate\Http\Response
     */
    public function listaSelecoesParaNovaInscricao(Request $request)
    {
        Gate::authorize('inscricoes.create');

        $request->validate(['filtro' => 'nullable|string']);

        \UspTheme::activeUrl(request()->segment(1) . '/create');
        AtualizaStatusSelecoes::dispatch()->onConnection('sync');
        $categorias = Selecao::listarSelecoesParaNovaInscricao(request()->segment(1));    // obtém as seleções dentro das categorias
        return view('inscricoes.listaselecoesparanovainscricao', compact('categorias'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param  \App\Models\Selecao        $selecao
     * @param  ?\App\Models\Nivel         $nivel
     * @return \Illuminate\Http\Response
     */
    public function create(Selecao $selecao, ?Nivel $nivel = null)
    {
        $this->validaRotaInscricaoOuMatricula($selecao);
        Gate::authorize('inscricoes.create', $selecao);

        $inscricao = new Inscricao;
        $inscricao->selecao = $selecao;
        $user = Auth::user();
        // se o usuário já solicitou isenção de taxa para esta seleção...
        $solicitacaoisencaotaxa = $user->solicitacoesIsencaoTaxa()?->where('selecao_id', $selecao->id)->first();
        if ($solicitacaoisencaotaxa) {
            $solicitacaoisencaotaxa_extras = json_decode($solicitacaoisencaotaxa->extras, true);
            $extras = array(
                'nome' => $user->name,
                'tipo_de_documento' => $solicitacaoisencaotaxa_extras['tipo_de_documento'],
                'numero_do_documento' => $solicitacaoisencaotaxa_extras['numero_do_documento'],
                'cpf' => $solicitacaoisencaotaxa_extras['cpf'],
                'celular' => ((!Str::contains($user->telefone, 'ramal USP')) ? $user->telefone : ''),
                'e_mail' => $user->email,
            );
        } else
            $extras = array(
                'nome' => $user->name,
                'celular' => ((!Str::contains($user->telefone, 'ramal USP')) ? $user->telefone : ''),
                'e_mail' => $user->email,
            );
        if ($selecao->categoria->nome !== 'Aluno Especial')
            $extras['nivel'] = $nivel->id;
        $inscricao->extras = json_encode($extras);

        \UspTheme::activeUrl(request()->segment(1) . '/create');
        return view('inscricoes.edit', $this->monta_compact($inscricao, 'create'));
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
        $this->validaRotaInscricaoOuMatricula($selecao);
        Gate::authorize('inscricoes.create', $selecao);

        $user = \Auth::user();

        // transaction para não ter problema de inconsistência do DB
        $inscricao = DB::transaction(function () use ($request, $user, $selecao) {

            // grava a inscrição/matrícula
            $inscricao = new Inscricao;
            $inscricao->selecao_id = $selecao->id;
            $inscricao->estado = 'Aguardando Envio';
            $inscricao->extras = json_encode($request->extras);
            $inscricao->saveQuietly();      // vamos salvar sem evento pois o autor ainda não está cadastrado
            $inscricao->load('selecao');    // com isso, $inscricao->selecao é carregado
            $inscricao->users()->attach($user, ['papel' => 'Autor']);

            return $inscricao;
        });

        // agora sim vamos disparar o evento (necessário porque acima salvamos com saveQuietly)
        event('eloquent.created: App\Models\Inscricao', $inscricao);

        $request->session()->flash('alert-success', 'Envie os documentos necessários para a avaliação da sua ' . Nomenclatura::InscricaoOuMatricula() . '<br />' .
            'Sem eles, sua ' . Nomenclatura::InscricaoOuMatricula() . ' não será avaliada!');
        \UspTheme::activeUrl(request()->segment(1) . '/create');
        return redirect()->to(url(request()->segment(1) . '/edit/' . $inscricao->id))->with($this->monta_compact($inscricao, 'edit', 'arquivos'));    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request   $request
     * @param  \App\Models\Inscricao      $inscricao
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, Inscricao $inscricao)
    {
        $this->validaRotaInscricaoOuMatricula($inscricao->selecao);
        Gate::authorize('inscricoes.view', $inscricao);    // este 1o passo da edição é somente um show, não chega a haver um update

        \UspTheme::activeUrl(request()->segment(1));
        $inscricao->selecao->atualizarStatus();
        return view('inscricoes.edit', $this->monta_compact($inscricao, 'edit', session('scroll')));    // repassa scroll que eventualmente veio de redirect()->to(url(
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request   $request
     * @param  \App\Models\Inscricao      $inscricao
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Inscricao $inscricao)
    {
        $this->validaRotaInscricaoOuMatricula($inscricao->selecao);
        \UspTheme::activeUrl(request()->segment(1));

        if ($request->input('acao', null) == 'envio') {
            Gate::authorize('inscricoes.update', $inscricao);

            $extras = json_decode(stripslashes($inscricao->extras), true);
            if ($inscricao->todosArquivosRequeridosPresentes($extras['nivel'] ?? null)) {

                $disciplinas_id = (isset($extras['disciplinas']) ? $extras['disciplinas'] : []);
                if (($inscricao->selecao->categoria->nome != 'Aluno Especial') || (count($disciplinas_id) > 0)) {

                    $inscricao->estado = 'Enviada';
                    $inscricao->save();

                    $info_adicional = '';
                    $user = \Auth::user();
                    if ($inscricao->selecao->tem_taxa && !$user->solicitacoesIsencaoTaxa()->where('selecao_id', $inscricao->selecao->id)->whereIn('estado', ['Isenção de Taxa Aprovada', 'Isenção de Taxa Aprovada Após Recurso'])->exists())
                        $info_adicional = ($inscricao->selecao->categoria->nome !== 'Aluno Especial' ? ' e seu boleto foi enviado, não deixe de pagá-lo' : ((count($disciplinas_id) == 1) ? ' e seu boleto foi enviado, não deixe de pagá-lo' : ' e seus boletos foram enviados, não deixe de pagá-los'));

                    $request->session()->flash('alert-success', 'Sua ' . Nomenclatura::InscricaoOuMatricula() . ' foi enviada' . $info_adicional);
                    \UspTheme::activeUrl(request()->segment(1));
                    return redirect()->to(url(request()->segment(1)))->with($this->monta_compact_index());    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
                } else {
                    $request->session()->flash('alert-danger', 'É necessário antes escolher a(s) disciplina(s)');
                    \UspTheme::activeUrl(request()->segment(1));
                    return view('inscricoes.edit', $this->monta_compact($inscricao, 'edit'));
                }
            } else {
                $request->session()->flash('alert-danger', 'É necessário antes enviar todos os documentos exigidos');
                \UspTheme::activeUrl(request()->segment(1));
                return view('inscricoes.edit', $this->monta_compact($inscricao, 'edit'));
            }
        }

        if ($request->conjunto_alterado == 'estado') {
            Gate::authorize('inscricoes.updateStatus', $inscricao);

            $inscricao->estado = $request->estado;
            $inscricao->save();

            $request->session()->flash('alert-success', 'Estado da ' . Nomenclatura::InscricaoOuMatricula() . ' alterado com sucesso');

        } else {
            Gate::authorize('inscricoes.update', $inscricao);

            $extras = json_decode($inscricao->extras, true);
            if (isset($extras['disciplinas']))
                $request->merge(['extras' => array_merge($request->input('extras', []), ['disciplinas' => $extras['disciplinas']])]);    // pelo fato de vir do card-principal, $request->extras não vem com as disciplinas... então precisamos recuperá-las a partir de $extras
            $inscricao->extras = json_encode($request->input('extras'));
            $inscricao->save();

            $request->session()->flash('alert-success', ucfirst(Nomenclatura::InscricaoOuMatricula()) . ' alterada com sucesso');
        }

        \UspTheme::activeUrl(request()->segment(1));
        return view('inscricoes.edit', $this->monta_compact($inscricao, 'edit'));
    }

    /**
     * Adiciona uma disciplina relacionada à matrícula
     * autorizado a qualquer um que tenha acesso à matrícula
     */
    public function storeDisciplina(Request $request, Inscricao $inscricao)
    {
        $this->validaRotaInscricaoOuMatricula($inscricao->selecao);
        Gate::authorize('inscricoes.update', $inscricao);

        $request->validate([
            'id' => 'required',
        ],
        [
            'id.required' => 'Disciplina obrigatória',
        ]);

        // transaction para não ter problema de inconsistência do DB
        $db_transaction = DB::transaction(function () use ($request, $inscricao) {

            $info_adicional = '';
            $disciplina = Disciplina::where('id', $request->id)->first();

            $extras = json_decode($inscricao->extras, true);
            $disciplinas_id = (isset($extras['disciplinas']) ? $extras['disciplinas'] : []);
            $existia = is_array($disciplinas_id) && in_array($request->id, $disciplinas_id);

            if (!$existia) {
                $extras['disciplinas'][] = $request->id;
                $inscricao->extras = json_encode($extras);

                // se já havia enviado a inscrição, avisa para reenviá-la
                if ($inscricao->estado == 'Enviada') {
                    $inscricao->estado = 'Aguardando Envio';
                    $info_adicional = '<br />Reenvie esta matrícula para gerar ' . ((count($extras['disciplinas']) == 1) ? 'novo boleto' : 'novos boletos');
                }

                $inscricao->save();
            }

            return ['disciplina' => $disciplina, 'existia' => $existia, 'info_adicional' => $info_adicional];
        });

        if (!$db_transaction['existia'])
            $request->session()->flash('alert-success', 'A disciplina ' . $db_transaction['disciplina']->sigla . ' - ' . $db_transaction['disciplina']->nome . ' foi adicionada à essa matrícula.' . $db_transaction['info_adicional']);
        else
            $request->session()->flash('alert-info', 'A disciplina ' . $db_transaction['disciplina']->sigla . ' - ' . $db_transaction['disciplina']->nome . ' já estava vinculada à essa matrícula.');
        \UspTheme::activeUrl(request()->segment(1));
        return redirect()->to(url(request()->segment(1) . '/edit/' . $inscricao->id))->with($this->monta_compact($inscricao, 'edit', 'disciplinas'));    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    /**
     * Remove uma disciplina relacionada à matrícula
     */
    public function destroyDisciplina(Request $request, Inscricao $inscricao, Disciplina $disciplina)
    {
        $this->validaRotaInscricaoOuMatricula($inscricao->selecao);
        Gate::authorize('inscricoes.update', $inscricao);

        $info_adicional = '';

        $extras = json_decode($inscricao->extras, true);
        $disciplinas_id = (isset($extras['disciplinas']) ? $extras['disciplinas'] : []);
        $indice = array_search($disciplina->id, $disciplinas_id);

        if ($indice !== false) {
            unset($extras['disciplinas'][$indice]);
            $inscricao->extras = json_encode($extras);

            // se já havia enviado a matrícula, avisa para reenviá-la
            if ($inscricao->estado == 'Enviada') {
                $inscricao->estado = 'Aguardando Envio';
                $info_adicional = '<br />Reenvie esta matrícula para gerar ' . ((count($extras['disciplinas']) == 1) ? 'novo boleto' : 'novos boletos');
            }

            $inscricao->save();
        }

        $request->session()->flash('alert-success', 'A disciplina ' . $disciplina->sigla . ' - '. $disciplina->nome . ' foi removida dessa matrícula.' . $info_adicional);
        \UspTheme::activeUrl(request()->segment(1));
        return redirect()->to(url(request()->segment(1) . '/edit/' . $inscricao->id))->with($this->monta_compact($inscricao, 'edit', 'disciplinas'));    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    /**
     * Gera o(s) boleto(s) para a inscrição/matrícula - usado por admins para gerar manualmente o boleto, caso necessário
     */
    public function geraBoletos(Request $request, Inscricao $inscricao)
    {
        $this->validaRotaInscricaoOuMatricula($inscricao->selecao);

        if ($inscricao->selecao->categoria->nome !== 'Aluno Especial') {
            // gera o boleto da inscrição/matrícula
            if (empty($this->boletoService->gerarBoleto($inscricao)['nome_original'])) {
                $request->session()->flash('alert-danger', 'Não foi possível gerar o boleto para essa ' . Nomenclatura::InscricaoOuMatricula() . '.');
                \UspTheme::activeUrl(request()->segment(1));
                return redirect()->to(url(request()->segment(1) . '/edit/' . $inscricao->id))->with($this->monta_compact($inscricao, 'edit'));    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
            }
        } else
            // gera um boleto para cada disciplina solicitada
            foreach ($request->disciplinas as $sigla => $valor)
                if (empty($this->boletoService->gerarBoleto($inscricao, $sigla)['nome_original'])) {
                    $request->session()->flash('alert-danger', 'Não foi possível gerar o boleto da disciplina ' . $sigla . ' para essa matrícula<br />' .
                        'A geração do(s) boleto(s) foi abortada');
                    \UspTheme::activeUrl(request()->segment(1));
                    return redirect()->to(url(request()->segment(1) . '/edit/' . $inscricao->id))->with($this->monta_compact($inscricao, 'edit'));
                }

        $request->session()->flash('alert-success', ($inscricao->selecao->categoria->nome !== 'Aluno Especial' ? 'O boleto foi gerado com sucesso' : 'O(s) boleto(s) foi(ram) gerado(s) com sucesso'));
        \UspTheme::activeUrl(request()->segment(1));
        return redirect()->to(url(request()->segment(1) . '/edit/' . $inscricao->id))->with($this->monta_compact($inscricao, 'edit', 'arquivos'));
    }

    /**
     * Envia um boleto da inscrição/matrícula
     */
    public function enviaBoleto(Request $request, Inscricao $inscricao, Arquivo $arquivo)
    {
        $this->validaRotaInscricaoOuMatricula($inscricao->selecao);

        if (!$arquivo || !$arquivo->inscricoes->contains($inscricao)) {
            $request->session()->flash('alert-danger', 'Esse documento não existe ou não pertence a essa ' . Nomenclatura::InscricaoOuMatricula());
            \UspTheme::activeUrl(request()->segment(1));
            return redirect()->to(url(request()->segment(1) . '/edit/' . $inscricao->id))->with($this->monta_compact($inscricao, 'edit'));
        }

        // envia e-mail para o candidato com o boleto
        // envio do e-mail "13" do README.md
        $passo = 'boleto - envio manual';
        $user = $inscricao->pessoas('Autor');
        $arquivo->conteudo = base64_encode(Storage::get($arquivo->caminho));
        \Mail::to($user->email)
            ->queue(new InscricaoMail(compact('passo', 'inscricao', 'user', 'arquivo')));

        $request->session()->flash('alert-success', 'O boleto foi enviado com sucesso');
        \UspTheme::activeUrl(request()->segment(1));
        return redirect()->to(url(request()->segment(1) . '/edit/' . $inscricao->id))->with($this->monta_compact($inscricao, 'edit', 'arquivos'));
    }

    private function validaRotaInscricaoOuMatricula(Selecao $selecao)
    {
        $is_matricula = (($selecao->categoria->nome == 'Aluno Especial') || $selecao->programa->matricula);
        if (((request()->segment(1) == 'inscricoes') && $is_matricula) || ((request()->segment(1) == 'matriculas') && !$is_matricula))
            abort(403, 'A rota acessada não corresponde à classe esperada.');
    }

    public function monta_compact_index()
    {
        $data = self::$data;
        $objetos = Inscricao::listarInscricoes(request()->segment(1));
        foreach ($objetos as $objeto) {
            $extras = json_decode($objeto->extras, true);
            $objeto->linha_pesquisa = (isset($extras['linha_pesquisa']) ? (LinhaPesquisa::where('id', $extras['linha_pesquisa'])->first()->nome ?? null) : null);
            $objeto->disciplinas = (isset($extras['disciplinas']) ? (Disciplina::whereIn('id', $extras['disciplinas'])->orderBy('sigla')->get()->map(function ($disciplina) {
                return $disciplina->sigla . ' - ' . $disciplina->nome;
            })->implode(',<br />')) : null);
        }
        $classe_nome = 'Inscricao';
        $max_upload_size = config('inscricoes-selecoes-pos.upload_max_filesize');
        $niveis = Nivel::all();

        return compact('data', 'objetos', 'classe_nome', 'max_upload_size', 'niveis');
    }

    public function monta_compact(Inscricao $inscricao, string $modo, ?string $scroll = null)
    {
        $data = (object) self::$data;
        $inscricao->selecao->template = JSONForms::orderTemplate($inscricao->selecao->template);
        $objeto = $inscricao;
        $classe_nome = 'Inscricao';
        $classe_nome_plural = 'inscricoes';
        $form = JSONForms::generateForm($objeto->selecao, $classe_nome, $objeto);
        $responsaveis = $objeto->selecao->programa?->obterResponsaveis() ?? (new Programa())->obterResponsaveis();
        $extras = json_decode($objeto->extras, true);
        $inscricao_disciplinas = ((isset($extras['disciplinas']) && is_array($extras['disciplinas'])) ? Disciplina::whereIn('id', $extras['disciplinas'])->orderBy('sigla')->get() : collect());
        $disciplinas = Disciplina::obterDisciplinasPossiveis($objeto->selecao);
        $nivel = (isset($extras['nivel']) ? Nivel::where('id', $extras['nivel'])->first()->nome : '');
        $objeto->tiposarquivo = TipoArquivo::obterTiposArquivoDaSelecao('Inscricao', ($objeto->selecao->categoria?->nome == 'Aluno Especial' ? new Collection() : collect([['nome' => $nivel]])), $objeto->selecao)
            ->filter(function ($tipoarquivo) use ($inscricao) { return (!str_starts_with($tipoarquivo->nome, 'Boleto(s) de Pagamento')) || $inscricao->selecao->tem_taxa; })
            ->sortBy(function ($tipoarquivo) { return str_starts_with($tipoarquivo->nome, 'Boleto(s) de Pagamento') ? 1 : 0; });
        $tiposarquivo_selecao = TipoArquivo::obterTiposArquivoPossiveis('Selecao', null, $objeto->selecao->programa_id)
            ->filter(function ($tipoarquivo) use ($inscricao) { return ($tipoarquivo->nome !== 'Normas para Isenção de Taxa') || $inscricao->selecao->tem_taxa; });
        $solicitacaoisencaotaxa_aprovada = $inscricao->pessoas('Autor')?->solicitacoesIsencaoTaxa()?->where('selecao_id', $objeto->selecao->id)->whereIn('estado', ['Isenção de Taxa Aprovada', 'Isenção de Taxa Aprovada Após Recurso'])->first();
        $disciplinas_sem_boleto = [];
        if ($inscricao->selecao->categoria->nome == 'Aluno Especial')
            foreach ($inscricao_disciplinas as $disciplina)
                if ($inscricao->arquivos->filter(fn($a) => ($a->pivot->tipo == 'Boleto(s) de Pagamento') && str_contains(strtolower($a->nome_original), strtolower($disciplina->sigla)))->count() == 0)
                    $disciplinas_sem_boleto[] = $disciplina;
        $inscricao->disciplinas_sem_boleto = $disciplinas_sem_boleto;
        $max_upload_size = config('inscricoes-selecoes-pos.upload_max_filesize');

        return compact('data', 'objeto', 'classe_nome', 'classe_nome_plural', 'form', 'modo', 'responsaveis', 'inscricao_disciplinas', 'disciplinas', 'nivel', 'tiposarquivo_selecao', 'solicitacaoisencaotaxa_aprovada', 'max_upload_size', 'scroll');
    }
}
