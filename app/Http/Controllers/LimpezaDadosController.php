<?php

namespace App\Http\Controllers;

use App\Http\Requests\LimpezaDadosRequest;
use App\Jobs\LimpaDados;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;

class LimpezaDadosController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function showForm()
    {
        Gate::authorize('limpezadados.showForm');

        \UspTheme::activeUrl('limpezadados');
        return view('limpezadados.form');
    }

    public function run(LimpezaDadosRequest $request)
    {
        Gate::authorize('limpezadados.run');

        $validator = Validator::make($request->all(), LimpezaDadosRequest::rules, LimpezaDadosRequest::messages);
        if ($validator->fails())
            return back()->withErrors($validator)->withInput();

        LimpaDados::dispatch(Carbon::createFromFormat('d/m/Y', $request->data_limite))->onConnection('sync');

        $request->session()->flash('alert-success', 'Operação realizada com sucesso');
        \UspTheme::activeUrl('limpezadados');
        return redirect()->route('limpezadados.showForm');    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }
}
