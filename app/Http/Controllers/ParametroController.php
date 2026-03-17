<?php

namespace App\Http\Controllers;

use App\Http\Requests\ParametroRequest;
use App\Models\Parametro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;

class ParametroController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit()
    {
        Gate::authorize('parametros.update');

        \UspTheme::activeUrl('parametros');
        return view('parametros.edit', $this->monta_compact());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\ParametroRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function update(ParametroRequest $request)
    {
        Gate::authorize('parametros.update');

        $validator = Validator::make($request->all(), ParametroRequest::rules, ParametroRequest::messages);
        if ($validator->fails())
            return back()->withErrors($validator)->withInput();

        $parametro = Parametro::first();
        $parametro->boleto_codigo_fonte_recurso = $request->boleto_codigo_fonte_recurso;
        $parametro->boleto_estrutura_hierarquica = $request->boleto_estrutura_hierarquica;
        $parametro->link_acompanhamento_especiais = $request->link_acompanhamento_especiais;
        $parametro->email_servicoposgraduacao = $request->email_servicoposgraduacao;
        $parametro->email_secaoinformatica = $request->email_secaoinformatica;
        $parametro->email_gerenciamentosite = $request->email_gerenciamentosite;
        $parametro->save();

        $request->session()->flash('alert-success', 'Dados editados com sucesso');
        \UspTheme::activeUrl('parametros');
        return view('parametros.edit', $this->monta_compact());
    }

    private function monta_compact()
    {
        $parametros = Parametro::first();    // preenche os dados do form de edição dos parâmetros
        $fields = Parametro::getFields();
        $rules = ParametroRequest::rules;

        return compact('parametros', 'fields', 'rules');
    }
}
