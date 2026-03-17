<?php

namespace App\Http\Controllers;

use App\Models\Arquivo;
use App\Models\Disciplina;
use App\Models\Inscricao;
use App\Models\LinhaPesquisa;
use App\Models\MotivoIsencaoTaxa;
use App\Models\Nivel;
use App\Models\NivelLinhaPesquisa;
use App\Models\Programa;
use App\Models\Selecao;
use App\Models\SolicitacaoIsencaoTaxa;
use App\Models\TipoArquivo;
use App\Models\User;
use App\Services\ZipService;
use App\Utils\JSONForms;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class ArquivoController extends Controller
{
    protected $zipService;

    public function __construct(ZipService $zipService)
    {
        $this->middleware('auth')->except('show');
        $this->zipService = $zipService;
    }

    public function index()
    {
        // pelo fato de eu ter definido as rotas do ArquivoController com Route::resource, o Laravel espera que exista esta action, mesmo que eu nunca a invoque
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Arquivo        $arquivo
     * @return \Illuminate\Http\Response
     */
    public function show(Arquivo $arquivo)
    {
        if (Arquivo::find($arquivo->id)->selecoes()->exists())
            $classe_nome = 'Selecao';
        elseif (Arquivo::find($arquivo->id)->inscricoes()->exists() && in_array(Arquivo::find($arquivo->id)->inscricoes()->first()->estado, (new SolicitacaoIsencaoTaxa())->estados()))
            $classe_nome = 'SolicitacaoIsencaoTaxa';
        else
            $classe_nome = 'Inscricao';
        Gate::authorize('arquivos.view', [$arquivo, $classe_nome]);

        while (ob_get_level() > 0)    // este while é para não estourar erro quando usando docker
            ob_end_clean();           // https://stackoverflow.com/questions/39329299/laravel-file-downloaded-from-storage-folder-gets-corrupted

        return Storage::download($arquivo->caminho, $arquivo->nome_original);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request   $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $classe_nome = fixJson($request->classe_nome);
        $classe_nome_plural = $this->obterClasseNomePlural($classe_nome);
        $classe = $this->obterClasse($classe_nome);
        $objeto = $classe::find($request->objeto_id);
        $segmento_rota = ($classe_nome === 'Inscricao' && $objeto->selecao->isMatricula()) ? 'matriculas' : $classe_nome_plural;
        $classe_nome_plural_acentuado = $this->obterClasseNomePluralAcentuado($classe_nome);
        $classe_nome_abreviada = $this->obterClasseNomeAbreviada($classe_nome, ($classe_nome == 'Inscricao' ? $objeto->selecao : null));
        $form = $this->obterForm($classe_nome, $objeto);
        $tipoarquivo = TipoArquivo::where('classe_nome', $classe_nome_plural_acentuado)->where('nome', $request->tipoarquivo)->first();

        $validator = \Validator::make($request->all(), [
            'arquivo.*' => 'required|mimes:jpeg,jpg,png,pdf|max:' . config('inscricoes-selecoes-pos.upload_max_filesize'),
            'objeto_id' => 'required|integer|exists:' . $classe_nome_plural . ',id',
        ]);
        if ($validator->fails()) {
            \UspTheme::activeUrl($classe_nome_plural);
            return view($classe_nome_plural . '.edit', array_merge($this->monta_compact($objeto, $classe_nome, $classe_nome_plural, $form, 'edit'), ['errors' => $validator->errors()]));
        }
        Gate::authorize('arquivos.create', [$objeto, $classe_nome]);

        // transaction para não ter problema de inconsistência do DB
        $db_transaction = DB::transaction(function () use ($request, $classe_nome, $classe_nome_plural, $classe_nome_plural_acentuado, $classe_nome_abreviada, $objeto, $tipoarquivo) {

            foreach ($request->arquivo as $arq) {
                $arquivo = new Arquivo;
                $arquivo->user_id = \Auth::user()->id;
                $arquivo->nome_original = $classe_nome_abreviada . $objeto->id . '_'
                                            . $tipoarquivo->abreviacao . '_'
                                            . formatarDataHoraAtualComMilissegundos()
                                            . '.' . pathinfo($arq->getClientOriginalName(), PATHINFO_EXTENSION);
                $arquivo->caminho = $arq->store('./arquivos/' . $objeto->created_at->year);
                $arquivo->mimeType = $arq->getClientMimeType();
                $arquivo->tipoarquivo_id = $tipoarquivo->id;
                $arquivo->saveQuietly();    // vamos salvar sem evento pois a classe ainda não está cadastrada

                $arquivo->{$classe_nome_plural}()->attach($objeto->id, ['tipo' => $request->tipoarquivo]);
            }

            if ($classe_nome == 'Selecao') {
                $objeto->atualizarStatus();
                $objeto->estado = Selecao::where('id', $objeto->id)->value('estado');

                $request->session()->flash('alert-success', 'Documento(s) adicionado(s) com sucesso<br />');
            } else {
                $classe_nome_formatada = ($classe_nome === 'Inscricao' && $objeto->selecao->isMatricula()) ? 'matrícula' : $this->obterClasseNomeFormatada($classe_nome);
                $nome_botao = ($classe_nome === 'SolicitacaoIsencaoTaxa' ? 'Solicitação' : (($classe_nome === 'Inscricao' && $objeto->selecao->isMatricula()) ? 'Matrícula' : 'Inscrição'));
                $request->session()->flash('alert-success', 'Documento(s) adicionado(s) com sucesso<br />' .
                    'Se não houver mais arquivos a enviar, clique no botão "Enviar ' . $nome_botao . '" abaixo para efetivar sua ' . $classe_nome_formatada . '<br />' .
                    'Sem isso, sua ' . $classe_nome_formatada . ' não será avaliada!');
            }

            return ['objeto' => $objeto, 'arquivo' => $arquivo];    // basta retornar somente o último arquivo... desta forma, o evento created logo abaixo será disparado apenas uma vez
        });

        // agora sim vamos disparar o evento (necessário porque acima salvamos com saveQuietly)
        event('eloquent.created: App\Models\Arquivo', $db_transaction['arquivo']);

        \UspTheme::activeUrl($segmento_rota);
        return redirect()->to(url($segmento_rota . '/edit/' . $db_transaction['objeto']->id))->with($this->monta_compact($db_transaction['objeto'], $classe_nome, $classe_nome_plural, $form, 'edit', 'arquivos'));    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request   $request
     * @param  \App\Models\Arquivo        $arquivo
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Arquivo $arquivo)
    {
        $classe_nome = fixJson($request->classe_nome);
        $classe_nome_plural = $this->obterClasseNomePlural($classe_nome);
        $classe = $this->obterClasse($classe_nome);
        $objeto = $classe::find($request->objeto_id);
        $segmento_rota = ($classe_nome === 'Inscricao' && $objeto->selecao->isMatricula()) ? 'matriculas' : $classe_nome_plural;
        $form = $this->obterForm($classe_nome, $objeto);

        Gate::authorize('arquivos.delete', [$arquivo, $objeto, $classe_nome]);

        if (Storage::exists($arquivo->caminho))
            Storage::delete($arquivo->caminho);

        // transaction para não ter problema de inconsistência do DB
        $objeto = DB::transaction(function () use ($request, $arquivo, $classe_nome, $classe_nome_plural, $objeto) {

            $arquivo->{$classe_nome_plural}()->detach($objeto->id, ['tipo' => $request->tipoarquivo]);
            $arquivo->delete();

            if ($classe_nome == 'Selecao') {
                $objeto->atualizarStatus();
                $objeto->estado = Selecao::where('id', $objeto->id)->value('estado');
            }

            return $objeto;
        });

        $request->session()->flash('alert-success', 'Documento removido com sucesso');
        \UspTheme::activeUrl($segmento_rota);
        return redirect()->to(url($segmento_rota . '/edit/' . $objeto->id))->with($this->monta_compact($objeto, $classe_nome, $classe_nome_plural, $form, 'edit', 'arquivos'));
    }

    /**
     * Gera zip com todos os arquivos do objeto indicado
     *
     * @param  string  $classe_nome
     * @param  int     $objeto_id      - pelo fato do objeto poder ser de diferentes tipos, é melhor usarmos o id dele ao invés dele propriamente dito
     * @return \Illuminate\Http\JsonResponse
     */
    public function zipTodosDoObjeto(string $classe_nome, int $objeto_id)
    {
        $objeto = $this->obterClasse($classe_nome)::findOrFail($objeto_id);
        Gate::authorize('viewAny', [$objeto, $classe_nome]);

        $zip_name = $this->obterClasseNomeAbreviada($classe_nome, ($classe_nome == 'Inscricao' ? $objeto->selecao : null)) . $objeto->id . '_' . formatarDataHoraAtualComMilissegundos() . '.zip';
        return $this->zip($objeto->arquivos, $zip_name);
    }

    /**
     * Faz o download do zip com todos os arquivos do objeto indicado
     *
     * @param  string  $classe_nome
     * @param  int     $objeto_id      - pelo fato do objeto poder ser de diferentes tipos, é melhor usarmos o id dele ao invés dele propriamente dito
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function downloadTodosDoObjeto(string $classe_nome, int $objeto_id, Request $request)
    {
        $objeto = $this->obterClasse($classe_nome)::findOrFail($objeto_id);
        Gate::authorize('viewAny', [$objeto, $classe_nome]);

        return $this->downloadZip($request->query('zip_name'));
    }

    /**
     * Gera zip com todos os arquivos de todos os objetos da classe indicada da seleção indicada
     *
     * @param  string               $classe_nome
     * @param  \App\Models\Selecao  $selecao
     * @return \Illuminate\Http\JsonResponse
     */
    public function zipTodosDosObjetosDaSelecao(string $classe_nome, Selecao $selecao)
    {
        Gate::authorize('selecoes.view', $selecao);

        $zip_name = $this->obterClasseNomeAbreviada('Selecao') . $selecao->id . '_' . $this->obterClasseNomeAbreviadaPlural($classe_nome, $selecao) . '_' . formatarDataHoraAtualComMilissegundos() . '.zip';
        $arquivos = collect();
        switch ($classe_nome) {
            case 'SolicitacaoIsencaoTaxa':
                $arquivos = $selecao->solicitacoesisencaotaxa->flatMap(function ($solicitacaoisencaotaxa) { return $solicitacaoisencaotaxa->arquivos; });
                break;
            case 'Inscricao':
                $arquivos = $selecao->inscricoes->flatMap(function ($inscricao) { return $inscricao->arquivos; });
        }
        return $this->zip($arquivos, $zip_name);
    }

    /**
     * Faz o download do zip com todos os arquivos de todos os objetos da classe indicada da seleção indicada
     *
     * @param  string               $classe_nome
     * @param  \App\Models\Selecao  $selecao
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function downloadTodosDosObjetosDaSelecao(string $classe_nome, Selecao $selecao, Request $request)
    {
        Gate::authorize('selecoes.view', $selecao);

        return $this->downloadZip($request->query('zip_name'));
    }

    private function zip(Collection $arquivos, string $zip_name)
    {
        $totalsize = $arquivos->sum(function ($arquivo) { return Storage::size($arquivo->caminho); });
        ini_set('max_execution_time', $this->obterTimeoutMaximo($totalsize));    // aumenta o tempo máximo de execução deste método com base no tamanho do arquivo a baixar

        $zip_fullfilename = $this->zipService->gerarZip($arquivos, $zip_name);
        if (!$zip_fullfilename)
            return response()->json(['status' => 'erro', 'mensagem' => 'Erro ao gerar o arquivo zip.']);

        return response()->json(['status' => 'concluído', 'zip_name' => $zip_name]);
    }

    private function downloadZip(string $zip_name)
    {
        $zip_fullfilename = storage_path('app/temp/' . $zip_name);
        if (!File::exists($zip_fullfilename))
            return response('Arquivo zip não encontrado.', 404);

        $totalsize = filesize($zip_fullfilename);
        ini_set('max_execution_time', $this->obterTimeoutMaximo($totalsize));    // aumenta o tempo máximo de execução deste método com base no tamanho do arquivo a baixar

        while (ob_get_level() > 0)    // este while é para não estourar erro quando usando docker
            ob_end_clean();           // sem este clean, o arquivo zip será baixado corrompido

        return response()->download($zip_fullfilename, basename($zip_fullfilename))->deleteFileAfterSend(true);
    }

    private function obterTimeoutMaximo($filesize)
    {
        $filesize = $filesize / (1024 * 1024 * 1024);    // tamanho do arquivo em Gb
        return max(60, ceil($filesize * env('inscricoes-selecoes-pos.timeout_por_gb')));    // o tempo máximo será de no mínimo 60 segundos
    }

    private function obterClasseNomeFormatada(string $classe_nome) {
        switch ($classe_nome) {
            case 'Selecao':
                return 'seleção';
            case 'SolicitacaoIsencaoTaxa':
                return 'solicitação de isenção de taxa';
            case 'Inscricao':
                return 'inscrição';
        }
    }

    private function obterClasseNomePlural(string $classe_nome) {
        switch ($classe_nome) {
            case 'Selecao':
                return 'selecoes';
            case 'SolicitacaoIsencaoTaxa':
                return 'solicitacoesisencaotaxa';
            case 'Inscricao':
                return 'inscricoes';
        }
    }

    private function obterClasseNomePluralAcentuado(string $classe_nome) {
        switch ($classe_nome) {
            case 'Selecao':
                return 'Seleções';
            case 'SolicitacaoIsencaoTaxa':
                return 'Solicitações de Isenção de Taxa';
            case 'Inscricao':
                return 'Inscrições';
        }
    }

    private function obterClasseNomeAbreviada(string $classe_nome, ?Selecao $selecao = null) {
        switch ($classe_nome) {
            case 'Selecao':
                return 'Sel';
            case 'SolicitacaoIsencaoTaxa':
                return 'SolicIsenc';
            case 'Inscricao':
                return ((($selecao->categoria->nome != 'Aluno Especial') && !$selecao->isMatricula()) ? 'Insc' : 'Matr');
        }
    }

    private function obterClasseNomeAbreviadaPlural(string $classe_nome, ?Selecao $selecao = null) {
        if ($classe_nome == 'SolicitacaoIsencaoTaxa')
                return 'SolicsIsenc';

        return $this->obterClasseNomeAbreviada($classe_nome, $selecao) . 's';
    }

    private function obterClasse(string $classe_nome) {
        switch ($classe_nome) {
            case 'Selecao':
                return Selecao::class;
            case 'SolicitacaoIsencaoTaxa':
                return SolicitacaoIsencaoTaxa::class;
            case 'Inscricao':
                return Inscricao::class;
        }
    }

    private function obterForm(string $classe_nome, object $objeto) {
        switch ($classe_nome) {
            case 'Selecao':
                return null;
            case 'SolicitacaoIsencaoTaxa':
            case 'Inscricao':
                // ambos 'SolicitacaoIsencaoTaxa' e 'Inscricao' executam as linhas abaixo
                $objeto->selecao->template = JSONForms::orderTemplate($objeto->selecao->template);
                return JSONForms::generateForm($objeto->selecao, $classe_nome, $objeto);
        }
    }

    private function monta_compact(object $objeto, string $classe_nome, string $classe_nome_plural, $form, string $modo, ?string $scroll = null)
    {
        $data = (object) ('App\\Http\\Controllers\\' . $classe_nome . 'Controller')::$data;
        $selecao = ($classe_nome == 'Selecao' ? $objeto : $objeto->selecao);
        $disciplinas = Disciplina::all();
        $motivosisencaotaxa = MotivoIsencaoTaxa::listarMotivosIsencaoTaxa();
        $responsaveis = $selecao->programa?->obterResponsaveis() ?? (new Programa())->obterResponsaveis();
        $extras = json_decode($objeto->extras, true);
        $objeto->niveislinhaspesquisa = NivelLinhaPesquisa::obterNiveisLinhasPesquisaDaSelecao($selecao);
        $niveislinhaspesquisa = NivelLinhaPesquisa::obterNiveisLinhasPesquisaPossiveis($selecao->programa_id);
        $inscricao_disciplinas = ((isset($extras['disciplinas']) && is_array($extras['disciplinas'])) ? Disciplina::whereIn('id', $extras['disciplinas'])->get() : collect());
        $nivel = (isset($extras['nivel']) ? Nivel::where('id', $extras['nivel'])->first()->nome : '');
        $solicitacaoisencaotaxa_aprovada = (($classe_nome == 'Inscricao') ? $objeto->pessoas('Autor')?->solicitacoesIsencaoTaxa()?->where('selecao_id', $objeto->selecao->id)->whereIn('estado', ['Isenção de Taxa Aprovada', 'Isenção de Taxa Aprovada Após Recurso'])->first() : null);
        $objeto->tiposarquivo = TipoArquivo::obterTiposArquivoDaSelecao($classe_nome, ($selecao->categoria->nome == 'Aluno Especial' ? new Collection() : (!empty($nivel) ? collect([['nome' => $nivel]]) : Nivel::all())), $selecao);
        $tiposarquivo_selecao = TipoArquivo::obterTiposArquivoPossiveis('Selecao', null, $selecao->programa_id);
        if ($classe_nome == 'Selecao') {
            $objeto->disciplinas = $objeto->disciplinas->sortBy('sigla');
            $objeto->tiposarquivo = TipoArquivo::obterTiposArquivoPossiveis('Selecao', null, $selecao->programa_id)
                                ->filter(function ($tipoarquivo) use ($selecao) { return ($tipoarquivo->nome !== 'Normas para Isenção de Taxa') || $selecao->tem_taxa; })
                            ->merge(TipoArquivo::obterTiposArquivoDaSelecao('SolicitacaoIsencaoTaxa', null, $selecao))
                            ->merge(TipoArquivo::obterTiposArquivoDaSelecao('Inscricao', ($selecao->categoria?->nome == 'Aluno Especial' ? new Collection() : (!empty($nivel) ? collect([['nome' => $nivel]]) : Nivel::all())), $selecao)
                                ->filter(function ($tipoarquivo) { return !str_starts_with($tipoarquivo->nome, 'Boleto(s) de Pagamento'); }))
                                ->sortBy(function ($tipoarquivo) { return str_starts_with($tipoarquivo->nome, 'Boleto(s) de Pagamento') ? 1 : 0; });
        } elseif ($classe_nome == 'Inscricao') {
            $objeto->tiposarquivo = $objeto->tiposarquivo->filter(function ($tipoarquivo) use ($selecao) { return (!str_starts_with($tipoarquivo->nome, 'Boleto(s) de Pagamento')) || $selecao->tem_taxa; })
                                                         ->sortBy(function ($tipoarquivo) { return str_starts_with($tipoarquivo->nome, 'Boleto(s) de Pagamento') ? 1 : 0; });
            $tiposarquivo_selecao = $tiposarquivo_selecao->filter(function ($tipoarquivo) use ($selecao) { return ($tipoarquivo->nome !== 'Normas para Isenção de Taxa') || $selecao->tem_taxa; });
        }
        $tiposarquivo_solicitacaoisencaotaxa = TipoArquivo::obterTiposArquivoPossiveis('SolicitacaoIsencaoTaxa', null, $selecao->programa_id);
        $tiposarquivo_inscricao = TipoArquivo::obterTiposArquivoPossiveis('Inscricao', ($selecao->categoria->nome == 'Aluno Especial' ? new Collection() : Nivel::all()), $selecao->programa_id);
        $max_upload_size = config('inscricoes-selecoes-pos.upload_max_filesize');

        return compact('data', 'objeto', 'classe_nome', 'classe_nome_plural', 'form', 'modo', 'disciplinas', 'motivosisencaotaxa', 'responsaveis', 'niveislinhaspesquisa', 'inscricao_disciplinas', 'nivel', 'solicitacaoisencaotaxa_aprovada', 'tiposarquivo_selecao', 'tiposarquivo_solicitacaoisencaotaxa', 'tiposarquivo_inscricao', 'max_upload_size', 'scroll');
    }
}
