<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoriaRequest;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;

class CategoriaController extends Controller
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
        Gate::authorize('categorias.viewAny');

        \UspTheme::activeUrl('categorias');
        if (!$request->ajax())
            return view('categorias.tree', $this->monta_compact_index());
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
        Gate::authorize('categorias.viewAny');

        \UspTheme::activeUrl('categorias');
        if ($request->ajax())
            return Categoria::find((int) $id);    // preenche os dados do form de edição de uma categoria
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\CategoriaRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CategoriaRequest $request)
    {
        Gate::authorize('categorias.create');

        $validator = Validator::make($request->all(), CategoriaRequest::rules, CategoriaRequest::messages);
        if ($validator->fails())
            return back()->withErrors($validator)->withInput();

        $categoria = Categoria::create($request->all());

        $request->session()->flash('alert-success', 'Dados adicionados com sucesso');
        \UspTheme::activeUrl('categorias');
        return redirect()->route('categorias.index')->with($this->monta_compact_index());    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\CategoriaRequest  $request
     * @param  string                               $id
     * @return \Illuminate\Http\Response
     */
    public function update(CategoriaRequest $request, string $id)
    {
        Gate::authorize('categorias.update');

        $validator = Validator::make($request->all(), CategoriaRequest::rules, CategoriaRequest::messages);
        if ($validator->fails())
            return back()->withErrors($validator)->withInput();

        $categoria = Categoria::find((int) $id);
        $categoria->fill($request->all());
        $categoria->save();

        $request->session()->flash('alert-success', 'Dados editados com sucesso');
        \UspTheme::activeUrl('categorias');
        return view('categorias.tree', $this->monta_compact_index());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Http\Requests\CategoriaRequest  $request
     * @param  string                               $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(CategoriaRequest $request, string $id)
    {
        Gate::authorize('categorias.delete');

        $categoria = Categoria::find((int) $id);
        if ($categoria->selecoes()->exists())
            $request->session()->flash('alert-danger', 'Há seleções para esta categoria!');
        else {
            $categoria->delete();
            $request->session()->flash('alert-success', 'Dados removidos com sucesso!');
        }
        \UspTheme::activeUrl('categorias');
        return view('categorias.tree', $this->monta_compact_index());
    }

    private function monta_compact_index()
    {
        $categorias = Categoria::all();
        $fields = Categoria::getFields();
        $modal['url'] = 'categorias';
        $modal['title'] = 'Editar Categoria';
        $rules = CategoriaRequest::rules;

        return compact('categorias', 'fields', 'modal', 'rules');
    }
}
