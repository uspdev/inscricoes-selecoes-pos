<?php

namespace App\Http\Controllers;

use App\Http\Requests\TipoArquivoRequest;
use App\Models\Categoria;
use App\Models\NivelPrograma;
use App\Models\TipoArquivo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;

class TipoArquivoController extends Controller
{
    // crud generico
    public static $data = [
        'title' => 'Tipos de Documento',
        'url' => 'tiposarquivo',     // caminho da rota do resource
        'modal' => true,
        'showId' => false,
        'viewBtn' => true,
        'editBtn' => false,
        'model' => 'App\Models\TipoArquivo',
    ];

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request   $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        Gate::authorize('tiposarquivo.viewAny');

        \UspTheme::activeUrl('tiposarquivo');
        if (!$request->ajax())
            return view('tiposarquivo.tree', $this->monta_compact_index());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        Gate::authorize('tiposarquivo.create');

        \UspTheme::activeUrl('tiposarquivo');
        return view('tiposarquivo.edit', $this->monta_compact(new TipoArquivo, 'create'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\TipoArquivoRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(TipoArquivoRequest $request)
    {
        Gate::authorize('tiposarquivo.create');

        $validator = Validator::make($request->all(), TipoArquivoRequest::rules, TipoArquivoRequest::messages);
        if ($validator->fails()) {
            \UspTheme::activeUrl('tiposarquivo');
            return back()->withErrors($validator)->withInput();
        }

        // acerta os valores dos campos tipo checkbox (pois, se o usuário deixou false, os campos não vêm no $request e, se o usuário deixou true, ele vêm mas com valor null)
        $request->merge(['obrigatorio' => $request->has('obrigatorio')]);

        // transaction para não ter problema de inconsistência do DB
        $tipoarquivo = DB::transaction(function () use ($request) {

            $tipoarquivo = TipoArquivo::create($request->all());

            if ($tipoarquivo->classe_nome == 'Inscrições') {
                foreach (Categoria::all() as $categoria)            // cadastra automaticamente todas as categorias como possíveis para este tipo de arquivo
                    $tipoarquivo->categorias()->attach($categoria->id);
                foreach (NivelPrograma::all() as $nivelprograma)    // cadastra automaticamente todas as combinações de níveis com programas como possíveis para este tipo de arquivo
                    $tipoarquivo->niveisprogramas()->attach($nivelprograma->id);
            }

            return $tipoarquivo;
        });

        $request->session()->flash('alert-success', 'Tipo de documento cadastrado com sucesso');
        \UspTheme::activeUrl('tiposarquivo');
        return redirect()->to(url('tiposarquivo/edit/' . $tipoarquivo->id))->with($this->monta_compact($tipoarquivo, 'edit'));    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request   $request
     * @param  \App\Models\TipoArquivo  $tipoarquivo
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, TipoArquivo $tipoarquivo)
    {
        Gate::authorize('tiposarquivo.update');

        \UspTheme::activeUrl('tiposarquivo');
        return view('tiposarquivo.edit', $this->monta_compact($tipoarquivo, 'edit'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\TipoArquivoRequest  $request
     * @param  \App\Models\TipoArquivo                $tipoarquivo
     * @return \Illuminate\Http\Response
     */
    public function update(TipoArquivoRequest $request, TipoArquivo $tipoarquivo)
    {
        Gate::authorize('tiposarquivo.update');

        $validator = Validator::make($request->all(), TipoArquivoRequest::rules, TipoArquivoRequest::messages);
        if ($validator->fails()) {
            \UspTheme::activeUrl('tiposarquivo');
            return back()->withErrors($validator)->withInput();
        }

        // acerta os valores dos campos tipo checkbox (pois, se o usuário deixou false, os campos não vêm no $request e, se o usuário deixou true, ele vêm mas com valor null)
        $request->merge(['obrigatorio' => $request->has('obrigatorio')]);

        $tipoarquivo->nome = $request->nome;
        $tipoarquivo->abreviacao = $request->abreviacao;
        $tipoarquivo->obrigatorio = $request->obrigatorio;
        $tipoarquivo->minimo = $request->minimo;
        $tipoarquivo->save();

        $request->session()->flash('alert-success', 'Tipo de documento alterado com sucesso');
        \UspTheme::activeUrl('tiposarquivo');
        return view('tiposarquivo.edit', $this->monta_compact($tipoarquivo, 'edit'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Http\Requests\TipoArquivoRequest  $request
     * @param  string                                 $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(TipoArquivoRequest $request, string $id)
    {
        Gate::authorize('tiposarquivo.delete');

        $tipoarquivo = TipoArquivo::find((int) $id);
        if ($tipoarquivo->selecoes()->exists())
            $request->session()->flash('alert-danger', 'Há seleções que usam este tipo de documento!');
        elseif ($tipoarquivo->arquivos()->exists())
            $request->session()->flash('alert-danger', 'Há arquivos armazenados deste tipo!');
        elseif ($tipoarquivo->categorias()->exists())
            $request->session()->flash('alert-danger', 'Há categorias que usam este tipo de documento!');
        elseif ($tipoarquivo->niveisprogramas()->exists())
            $request->session()->flash('alert-danger', 'Há combinações de níveis com programas que usam este tipo de documento!');
        else {
            $tipoarquivo->delete();
            $request->session()->flash('alert-success', 'Dados removidos com sucesso!');
        }
        \UspTheme::activeUrl('tiposarquivo');
        return view('tiposarquivo.tree', $this->monta_compact_index());
    }

    /**
     * Adicionar categorias relacionadas ao tipo de arquivo
     * autorizado a qualquer um que tenha acesso ao tipo de arquivo
     * request->codpes = required, int
     */
    public function storeCategoria(Request $request, TipoArquivo $tipoarquivo)
    {
        Gate::authorize('tiposarquivo.update', $tipoarquivo);

        $request->validate([
            'id' => 'required',
        ],
        [
            'id.required' => 'Categoria obrigatória',
        ]);

        // transaction para não ter problema de inconsistência do DB
        $db_transaction = DB::transaction(function () use ($request, $tipoarquivo) {

            $categoria = Categoria::where('id', $request->id)->first();

            $existia = $tipoarquivo->categorias()->detach($categoria);

            $tipoarquivo->categorias()->attach($categoria);

            return ['categoria' => $categoria, 'existia' => $existia];
        });

        if (!$db_transaction['existia'])
            $request->session()->flash('alert-success', 'A categoria ' . $db_transaction['categoria']->nome . ' foi adicionada a esse tipo de documento');
        else
            $request->session()->flash('alert-info', 'A categoria ' . $db_transaction['categoria']->nome . ' já estava vinculada a esse tipo de documento');
        \UspTheme::activeUrl('tiposarquivo');
        return redirect()->to(url('tiposarquivo/edit/' . $tipoarquivo->id))->with($this->monta_compact($tipoarquivo, 'edit'));    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    /**
     * Remove categorias relacionadas ao tipo de arquivo
     * $user = required
     */
    public function destroyCategoria(Request $request, TipoArquivo $tipoarquivo, Categoria $categoria)
    {
        Gate::authorize('tiposarquivo.update', $tipoarquivo);

        $tipoarquivo->categorias()->detach($categoria);

        $request->session()->flash('alert-success', 'A categoria ' . $categoria->nome . ' foi removida desse tipo de documento');
        \UspTheme::activeUrl('tiposarquivo');
        return view('tiposarquivo.edit', $this->monta_compact($tipoarquivo, 'edit'));
    }

    /**
     * Adicionar combinações de níveis com programas relacionadas ao tipo de arquivo
     * autorizado a qualquer um que tenha acesso ao tipo de arquivo
     * request->codpes = required, int
     */
    public function storeNivelPrograma(Request $request, TipoArquivo $tipoarquivo)
    {
        Gate::authorize('tiposarquivo.update', $tipoarquivo);

        $request->validate([
            'id' => 'required',
        ],
        [
            'id.required' => 'Combinação de nível com programa obrigatória',
        ]);

        // transaction para não ter problema de inconsistência do DB
        $db_transaction = DB::transaction(function () use ($request, $tipoarquivo) {

            $nivelprograma = NivelPrograma::where('id', $request->id)->first();

            $existia = $tipoarquivo->niveisprogramas()->detach($nivelprograma);

            $tipoarquivo->niveisprogramas()->attach($nivelprograma);

            return ['nivelprograma' => $nivelprograma, 'existia' => $existia];
        });

        if (!$db_transaction['existia'])
            $request->session()->flash('alert-success', 'A combinação nível ' . $db_transaction['nivelprograma']->nivel->nome . ' com o programa ' . $db_transaction['nivelprograma']->programa->nome . ' foi adicionada a esse tipo de documento');
        else
            $request->session()->flash('alert-info', 'A combinação nível ' . $db_transaction['nivelprograma']->nivel->nome . ' com o programa ' . $db_transaction['nivelprograma']->programa->nome . ' já estava vinculada a esse tipo de documento');
        \UspTheme::activeUrl('tiposarquivo');
        return redirect()->to(url('tiposarquivo/edit/' . $tipoarquivo->id))->with($this->monta_compact($tipoarquivo, 'edit'));    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    /**
     * Remove combinações de níveis com programas relacionadas ao tipo de arquivo
     * $user = required
     */
    public function destroyNivelPrograma(Request $request, TipoArquivo $tipoarquivo, NivelPrograma $nivelprograma)
    {
        Gate::authorize('tiposarquivo.update', $tipoarquivo);

        $tipoarquivo->niveisprogramas()->detach($nivelprograma);

        $request->session()->flash('alert-success', 'A combinação nível ' . $nivelprograma->nivel->nome . '  com o programa ' . $nivelprograma->programa->nome . ' foi removida desse tipo de documento');
        \UspTheme::activeUrl('tiposarquivo');
        return view('tiposarquivo.edit', $this->monta_compact($tipoarquivo, 'edit'));
    }

    private function monta_compact_index()
    {
        $tiposarquivo = TipoArquivo::listarTiposArquivo()->orderByRaw("
            CASE
                WHEN classe_nome = 'Seleções'                        THEN 1
                WHEN classe_nome = 'Solicitações de Isenção de Taxa' THEN 2
                WHEN classe_nome = 'Inscrições'                      THEN 3
                ELSE 4
            END
        ")->orderBy('id')->get();
        $fields = TipoArquivo::getFields();
        $modal['url'] = 'tiposarquivo';
        $modal['title'] = 'Editar Tipo de Documento';
        $rules = TipoArquivoRequest::rules;

        return compact('tiposarquivo', 'fields', 'modal', 'rules');
    }

    private function monta_compact(TipoArquivo $tipoarquivo, string $modo)
    {
        $data = (object) self::$data;
        $objeto = $tipoarquivo;
        $objeto->niveisprogramas = NivelPrograma::obterNiveisProgramasDoTipoArquivo($objeto);
        $niveisprogramas = NivelPrograma::obterNiveisProgramasPossiveis();
        $categorias = Categoria::all();
        $rules = TipoArquivoRequest::rules;

        return compact('data', 'objeto', 'niveisprogramas', 'categorias', 'rules', 'modo');
    }
}
