<?php

namespace App\Http\Controllers;

use App\Http\Requests\DisciplinaRequest;
use App\Models\Disciplina;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;

class DisciplinaController extends Controller
{
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
        Gate::authorize('disciplinas.viewAny');

        \UspTheme::activeUrl('disciplinas');
        if (!$request->ajax())
            return view('disciplinas.tree', $this->monta_compact_index());
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request   $request
     * @param  string                     $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, string $id)
    {
        Gate::authorize('disciplinas.viewAny');

        \UspTheme::activeUrl('disciplinas');
        if ($request->ajax())
            return Disciplina::find((int) $id);    // preenche os dados do form de edição de uma disciplina
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\DisciplinaRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(DisciplinaRequest $request)
    {
        Gate::authorize('disciplinas.create');

        $validator = Validator::make($request->all(), DisciplinaRequest::rules, DisciplinaRequest::messages);
        if ($validator->fails())
            return back()->withErrors($validator)->withInput();

        $disciplinas = Disciplina::create($request->all());

        $request->session()->flash('alert-success', 'Disciplina cadastrada com sucesso');
        \UspTheme::activeUrl('disciplinas');
        return redirect()->route('disciplinas.index')->with($this->monta_compact_index());    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\DisciplinaRequest  $request
     * @param  string                               $id
     * @return \Illuminate\Http\Response
     */
    public function update(DisciplinaRequest $request, string $id)
    {
        Gate::authorize('disciplinas.update');

        $validator = Validator::make($request->all(), DisciplinaRequest::rules, DisciplinaRequest::messages);
        if ($validator->fails())
            return back()->withErrors($validator)->withInput();

        $disciplina = Disciplina::find((int) $id);
        $disciplina->sigla = $request->sigla;
        $disciplina->nome = $request->nome;
        $disciplina->save();

        $request->session()->flash('alert-success', 'Disciplina alterada com sucesso');
        \UspTheme::activeUrl('disciplinas');
        return view('disciplinas.tree', $this->monta_compact_index());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Http\Requests\DisciplinaRequest  $request
     * @param  string                                $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(DisciplinaRequest $request, string $id)
    {
        Gate::authorize('disciplinas.delete');

        $disciplina = Disciplina::find((int) $id);
        if ($disciplina->selecoes()->exists())
            $request->session()->flash('alert-danger', 'Há seleções para esta disciplina!');
        else {
            $disciplina->delete();
            $request->session()->flash('alert-success', 'Dados removidos com sucesso!');
        }
        \UspTheme::activeUrl('disciplinas');
        return view('disciplinas.tree', $this->monta_compact_index());
    }

    private function monta_compact_index()
    {
        $disciplinas = Disciplina::orderBy('sigla')->get();
        $fields = Disciplina::getFields();
        $modal['url'] = 'disciplinas';
        $modal['title'] = 'Editar Disciplina';
        $rules = DisciplinaRequest::rules;

        return compact('disciplinas', 'fields', 'modal', 'rules');
    }
}
