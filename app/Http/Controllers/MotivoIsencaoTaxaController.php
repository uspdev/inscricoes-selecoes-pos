<?php

namespace App\Http\Controllers;

use App\Http\Requests\MotivoIsencaoTaxaRequest;
use App\Models\MotivoIsencaoTaxa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;

class MotivoIsencaoTaxaController extends Controller
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
        Gate::authorize('motivosisencaotaxa.viewAny');

        \UspTheme::activeUrl('motivosisencaotaxa');
        if (!$request->ajax())
            return view('motivosisencaotaxa.tree', $this->monta_compact_index());
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
        Gate::authorize('motivosisencaotaxa.view');

        \UspTheme::activeUrl('motivosisencaotaxa');
        if ($request->ajax())
            return MotivoIsencaoTaxa::find((int) $id);    // preenche os dados do form de edição de um motivo de isenção de taxa
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\MotivoIsencaoTaxaRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(MotivoIsencaoTaxaRequest $request)
    {
        Gate::authorize('motivosisencaotaxa.create');

        $validator = Validator::make($request->all(), MotivoIsencaoTaxaRequest::rules, MotivoIsencaoTaxaRequest::messages);
        if ($validator->fails())
            return back()->withErrors($validator)->withInput();

        $motivoisencaotaxa = MotivoIsencaoTaxa::create($request->all());

        $request->session()->flash('alert-success', 'Dados adicionados com sucesso');
        \UspTheme::activeUrl('motivosisencaotaxa');
        return redirect()->route('motivosisencaotaxa.index')->with($this->monta_compact_index());    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\MotivoIsencaoTaxaRequest  $request
     * @param  string                                       $id
     * @return \Illuminate\Http\Response
     */
    public function update(MotivoIsencaoTaxaRequest $request, string $id)
    {
        Gate::authorize('motivosisencaotaxa.update');

        $validator = Validator::make($request->all(), MotivoIsencaoTaxaRequest::rules, MotivoIsencaoTaxaRequest::messages);
        if ($validator->fails())
            return back()->withErrors($validator)->withInput();

        $motivoisencaotaxa = MotivoIsencaoTaxa::find((int) $id);
        $motivoisencaotaxa->fill($request->all());
        $motivoisencaotaxa->save();

        $request->session()->flash('alert-success', 'Dados editados com sucesso');
        \UspTheme::activeUrl('motivosisencaotaxa');
        return view('motivosisencaotaxa.tree', $this->monta_compact_index());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Http\Requests\MotivoIsencaoTaxaRequest  $request
     * @param  string                                       $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(MotivoIsencaoTaxaRequest $request, string $id)
    {
        Gate::authorize('motivosisencaotaxa.delete');

        $motivoisencaotaxa = MotivoIsencaoTaxa::find((int) $id);
        if ($motivoisencaotaxa->selecoes()->exists())
            $request->session()->flash('alert-danger', 'Há seleções que fazem uso deste motivo de isenção de taxa!');
        else {
            $motivoisencaotaxa->delete();
            $request->session()->flash('alert-success', 'Dados removidos com sucesso!');
        }
        \UspTheme::activeUrl('motivosisencaotaxa');
        return view('motivosisencaotaxa.tree', $this->monta_compact_index());
    }

    private function monta_compact_index()
    {
        $motivosisencaotaxa = MotivoIsencaoTaxa::all();
        $fields = MotivoIsencaoTaxa::getFields();
        $modal['url'] = 'motivosisencaotaxa';
        $modal['title'] = 'Editar Motivo de Isenção de Taxa';
        $rules = MotivoIsencaoTaxaRequest::rules;

        return compact('motivosisencaotaxa', 'fields', 'modal', 'rules');
    }
}
