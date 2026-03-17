<?php

namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;
use Hash;
use App\Http\Requests\LocalUserRequest;
use App\Mail\LocalUserMail;
use App\Models\LocalUser;
use App\Models\User;
use App\Services\RecaptchaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class LocalUserController extends Controller
{
    // crud generico
    public static $data = [
        'title' => 'Usuários Locais',
        'url' => 'localusers',     // caminho da rota do resource
        'modal' => true,
        'showId' => false,
        'viewBtn' => true,
        'editBtn' => false,
        'model' => 'App\Models\LocalUser',
    ];

    public function __construct()
    {
        $this->middleware('auth')->except([
            'showLogin',
            'login',
            'esqueceuSenha',
            'iniciaRedefinicaoSenha',
            'redefineSenha',
            'confirmaEmail',
            'reenviaEmailConfirmacao',
            'create',
            'store'
        ]);    // exige que o usuário esteja logado, exceto para estes métodos listados
    }

    public function showLogin()
    {
        return view('localusers.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ], [
            'email.required' => 'O e-mail é obrigatório!',
            'email.email' => 'O e-mail não é válido!',
            'password.required' => 'A senha é obrigatória!'
        ]);

        $credentials = $request->only('email', 'password');
        if (!Auth::attempt($credentials))
            return $this->processa_erro_login('Usuário e senha incorretos');

        $localuser = Auth::user();
        if ($localuser->local && !$localuser->email_confirmado) {
            Auth::logout();

            request()->session()->flash('alert-danger', 'E-mail não confirmado');
            return redirect('/localusers/login');
        }

        session(['perfil' => 'usuario']);
        return redirect('/');
    }

    public function esqueceuSenha(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ], [
            'email.required' => 'O e-mail é obrigatório!',
            'email.email' => 'O e-mail não é válido!'
        ]);

        // procura por usuário local com esse e-mail (somente local... pois não queremos fornecer possibilidade de resetar senha única USP de um usuário não local)
        $localuser = User::where('email', $request->email)->where('local', '1')->first();
        if (is_null($localuser))
            return $this->processa_erro_login('E-mail não encontrado');

        // gera um token e o armazena no banco de dados
        $token = Str::random(60);
        DB::table('password_resets')->updateOrInsert(
            ['email' => $localuser->email],    // procura por registro com este e-mail
            [                                  // atualiza ou insere com os dados abaixo
                'email' => $localuser->email,
                'token' => $token,
                'created_at' => now()
            ]
        );

        // monta a URL de redefinição de senha
        $password_reset_url = url('localusers/redefinesenha', $token);

        // envia e-mail para o usuário local... não utilizo observer como no Chamados pois aqui não faz sentido, o observer faz mais sentido disparando seus eventos próprios (created, updated, etc.)
        // envio do e-mail "3" do README.md
        $passo = 'reset de senha';
        \Mail::to($localuser->email)
            ->queue(new LocalUserMail(compact('passo', 'localuser', 'password_reset_url')));

        request()->session()->flash('alert-success', 'Foi enviado um e-mail com instruções para você redefinir sua senha.');
        return redirect()->route('localusers.login');    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    public function iniciaRedefinicaoSenha(string $token)
    {
        // verifica se o token recebido existe
        $password_reset = DB::table('password_resets')->where('token', $token)->first();
        if (!$password_reset)
            return $this->processa_erro_login('Este link é inválido');

        // verifica se o token recebido expirou
        if (Carbon::parse($password_reset->created_at)->addMinutes(config('inscricoes-selecoes-pos.password_reset_link_expiry_time'))->isPast())
            return $this->processa_erro_login('Este link expirou');

        $email = $password_reset->email;
        return view('localusers.redefinesenha', compact('token', 'email'));
    }

    public function redefineSenha(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'min:8', 'confirmed', 'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/', 'regex:/[@$!%*#?&]/'],
        ], [
            'password.required' => 'O campo de senha é obrigatório!',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres!',
            'password.confirmed' => 'A confirmação da senha não coincide.',
            'password.regex' => 'A senha deve conter pelo menos uma letra maiúscula, uma letra minúscula, um número e um caractere especial!',
        ]);

        // verifica se os dados vieram válidos
        $password_reset = DB::table('password_resets')->where('email', $request->email)->first();
        if ((!$password_reset) || ($request->token != $password_reset->token))
            return $this->processa_erro_login('Este link é inválido');

        // verifica se o token recebido expirou
        if (Carbon::parse($password_reset->created_at)->addMinutes(config('inscricoes-selecoes-pos.password_reset_link_expiry_time'))->isPast())
            return $this->processa_erro_login('Este link expirou');

        // verifica se o usuário existe
        $user = User::where('email', $password_reset->email)
            ->where('local', '1')
            ->first();
        if (!$user)
            return $this->processa_erro_login('Usuário não cadastrado');

        // transaction para não ter problema de inconsistência do DB
        DB::transaction(function () use ($request, $user) {

            // atualiza a senha do usuário
            $user->password = Hash::make($request->password);
            $user->save();

            // remove o token de redefinição de senha da tabela
            DB::table('password_resets')->where('email', $request->email)->delete();
        });

        request()->session()->flash('alert-success', 'Senha redefinida com sucesso');
        return redirect()->route('localusers.login');    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    public function confirmaEmail(string $token)
    {
        // verifica se o token recebido existe
        $email_confirmation = DB::table('email_confirmations')->where('token', $token)->first();
        if (!$email_confirmation)
            return $this->processa_erro_login('Este link é inválido');

        // transaction para não ter problema de inconsistência do DB
        DB::transaction(function () use ($email_confirmation) {

            // marca o e-mail como confirmado
            $localuser = User::where('email', $email_confirmation->email)->first();
            $localuser->givePermissionTo('user');
            $localuser->email_confirmado = true;
            $localuser->email_verified_at = now();
            $localuser->save();

            // apaga o registro de confirmação de e-mail, por não ser mais necessário
            DB::table('email_confirmations')->where('email', $email_confirmation->email)->delete();
        });

        request()->session()->flash('alert-success', 'E-mail confirmado com sucesso<br />' .
            'Faça login e prossiga solicitando isenções de taxa ou efetuando inscrições ou matrículas');

        \UspTheme::activeUrl('inscricoes');
        return view('localusers.login');
    }

    public function adminConfirmaEmail(User $localuser)
    {
        Gate::authorize('localusers.adminConfirmEmail');

        // transaction para não ter problema de inconsistência do DB
        DB::transaction(function () use ($localuser) {

            // marca o e-mail como confirmado
            $localuser->givePermissionTo('user');
            $localuser->email_confirmado = true;
            $localuser->email_verified_at = now();
            $localuser->save();

            // apaga o registro de confirmação de e-mail, por não ser mais necessário
            DB::table('email_confirmations')->where('email', $localuser->email)->delete();
        });

        request()->session()->flash('alert-success', 'E-mail confirmado com sucesso');

        \UspTheme::activeUrl('localusers');
        return view('localusers.index', $this->monta_compact('edit'));
    }

    public function reenviaEmailConfirmacao(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ], [
            'email.required' => 'O e-mail é obrigatório!',
            'email.email' => 'O e-mail não é válido!'
        ]);
        if ($validator->fails()) {
            $request->session()->flash('alert-danger', implode('<br />', $validator->errors()->all()));
            $request->session()->flash('exibir_link_reenvio', true);
            return redirect('/localusers/login');
        }

        $localuser = (object) [
            'name' => '',    // neste ponto, não temos o nome do usuário, pois ele ainda não está logado
            'email' => $request->email,
        ];

        // anteriormente, o token foi gravado hasheado no banco e, portanto, não temos como recuperá-lo... sendo assim, geramos um novo token
        $token = Str::random(60);
        DB::table('email_confirmations')->updateOrInsert(
            ['email' => $localuser->email],    // procura por registro com este e-mail
            [                                  // atualiza ou insere com os dados abaixo
                'email' => $localuser->email,
                'token' => $token,
                'created_at' => now()
            ]
        );

        // reenvia e-mail pedindo a confirmação do endereço de e-mail
        // envio do e-mail "2" do README.md
        $passo = 'confirmação de e-mail';
        $email_confirmation_url = url('localusers/confirmaemail', $token);
        \Mail::to($localuser->email)
            ->queue(new LocalUserMail(compact('passo', 'localuser', 'email_confirmation_url')));

        $request->session()->flash('alert-success', 'E-mail para confirmação reenviado<br />' .
            'Verifique sua caixa de entrada para confirmar seu endereço<br />' .
            'Em seguida, faça login e prossiga solicitando isenções de taxa ou efetuando inscrições ou matrículas');
        return redirect('/localusers/login');
    }

    private function processa_erro_login(string $msg)
    {
        request()->session()->flash('alert-danger', $msg);
        return view('localusers.login');
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request   $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        Gate::authorize('localusers.viewAny');
        \UspTheme::activeUrl('localusers');

        $localusers = User::where('local', '1')->get();
        foreach ($localusers as $localuser) {
            $localuser->qtde_solicitacoesisencaotaxa = $localuser->solicitacoesisencaotaxa()->count();
            $localuser->qtde_inscricoes = $localuser->inscricoes()->count();
        }
        $fields = LocalUser::getFields();

        if ($request->ajax()) {
            // formatado para datatables
            #return response(['data' => $localusers]);
        } else {
            $modal['url'] = 'localusers';
            $modal['title'] = 'Editar Usuário Local';
            $rules = LocalUserRequest::rules;
            return view('localusers.index', compact('localusers', 'fields', 'modal', 'rules'));
        }
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
        Gate::authorize('localusers.view');
        \UspTheme::activeUrl('localusers');

        if ($request->ajax())
            return User::where('id', (int) $id)->where('local', 1)->first();    // preenche os dados do form de edição de um usuário local
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        Gate::authorize('localusers.create');

        return view('localusers.create', $this->monta_compact('create'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request        $request
     * @param  \App\Services\RecaptchaService  $recaptcha_service
     * @return \Illuminate\Http\Response
     */
    public function store(LocalUserRequest $request, RecaptchaService $recaptcha_service)    // este método é invocado tanto pelo candidato, ao se cadastrar, quanto pelo admin, ao cadastrar um localuser no menu de Administração
    {
        Gate::authorize('localusers.create');

        // para as validações, começa sempre com o reCAPTCHA... depois valida cada campo na ordem em que aparecem na tela

        if (session('perfil') != 'admin')
            // revalida o reCAPTCHA
            if (!$recaptcha_service->revalidate($request->input('g-recaptcha-response')))
                return $this->processa_erro_store('Falha na validação do reCAPTCHA. Por favor, tente novamente.', $request);

        $validator = Validator::make($request->all(), LocalUserRequest::rules, LocalUserRequest::messages);
        if ($validator->fails())
            return back()->withErrors($validator)->withInput();

        // verifica se está tentando utilizar o e-mail de outro usuário (pois mais pra baixo este usuário será gravado na tabela users, e não podemos permitir e-mails duplicados)
        if (User::emailExiste($request->email))
            return back()->withErrors(Validator::make([], [])->errors()->add('email', 'Este e-mail já está em uso!'))->withInput();

        $token = Str::random(60);

        // transaction para não ter problema de inconsistência do DB
        $localuser = DB::transaction(function () use ($request, $token) {

            $localuser = User::create([
                'name' => $request->name,
                'telefone' => $request->telefone,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'local' => '1',
            ]);
            $localuser->givePermissionTo('user');

            DB::table('email_confirmations')->updateOrInsert(
                ['email' => $localuser->email],    // procura por registro com este e-mail
                [                                  // atualiza ou insere com os dados abaixo
                    'email' => $localuser->email,
                    'token' => $token,
                    'created_at' => now()
                ]
            );

            return $localuser;
        });

        // envia e-mail pedindo a confirmação do endereço de e-mail
        // envio do e-mail "2" do README.md
        $passo = 'confirmação de e-mail';
        $email_confirmation_url = url('localusers/confirmaemail', $token);
        \Mail::to($localuser->email)
            ->queue(new LocalUserMail(compact('passo', 'localuser', 'email_confirmation_url')));

        if (session('perfil') == 'admin') {
            \UspTheme::activeUrl('localusers');
            return redirect()->route('localusers.index')->with($this->monta_compact('create'));    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect

        } else {
            $request->session()->flash('alert-success', 'Cadastro realizado com sucesso<br />' .
                'Verifique sua caixa de entrada para confirmar seu endereço<br />' .
                'Em seguida, faça login e prossiga solicitando isenções de taxa ou efetuando inscrições ou matrículas');
            return redirect('/');
        }
    }

    private function processa_erro_store(string|array $msgs, Request $request)
    {
        if (is_array($msgs))
            $msgs = implode('<br />', $msgs);
        $request->session()->flash('alert-danger', $msgs);

        return back()->withInput();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request   $request
     * @param  \App\Models\User           $localuser
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $localuser)
    {
        Gate::authorize('localusers.update');

        $validator = Validator::make($request->all(), LocalUserRequest::rules, LocalUserRequest::messages);
        if ($validator->fails())
            return back()->withErrors($validator)->withInput();

        // verifica se está tentando utilizar o e-mail de outro usuário (pois não podemos permitir e-mails duplicados)
        if (User::emailExiste($request->email) && (User::where('email', $request->email)->first()->id != $request->id))
            return back()->withErrors(Validator::make([], [])->errors()->add('email', 'Este e-mail já está em uso por outro usuário!'))->withInput();

        $request->merge(['password' => Hash::make($request->password)]);
        $localuser->update($request->all());

        request()->session()->flash('alert-success', 'Usuário atualizado com sucesso');

        \UspTheme::activeUrl('localusers');
        return view('localusers.index', $this->monta_compact('edit'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User           $localuser
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $localuser)
    {
        Gate::authorize('localusers.delete');

        if ($localuser->local == false) {
            request()->session()->flash('alert-danger', 'Usuário senha única não pode ser apagado.');
            \UspTheme::activeUrl('localusers');
            return redirect('/localusers');
        }
        $localuser->delete();

        request()->session()->flash('alert-success', 'Usuário apagado com sucesso');

        \UspTheme::activeUrl('localusers');
        return view('localusers.index', $this->monta_compact('edit'));
    }

    private function monta_compact(string $modo)
    {
        $data = (object) self::$data;
        $localusers = User::where('local', '1')->get();
        $fields = LocalUser::getFields();
        $rules = LocalUserRequest::rules;

        return compact('data', 'localusers', 'fields', 'rules', 'modo');
    }
}
