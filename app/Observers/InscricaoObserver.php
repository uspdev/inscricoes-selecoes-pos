<?php

namespace App\Observers;

use App\Mail\InscricaoMail;
use App\Models\Disciplina;
use App\Models\Inscricao;
use App\Models\Parametro;
use App\Models\Programa;
use App\Models\TipoArquivo;
use App\Models\User;
use App\Services\BoletoService;
use Illuminate\Support\Facades\DB;
use Uspdev\Replicado\Pessoa;

class InscricaoObserver
{
    protected $boletoService;

    public function __construct(BoletoService $boletoService)
    {
        $this->boletoService = $boletoService;
    }

    /**
     * Handle the Inscricao "created" event.
     *
     * @param  \App\Models\Inscricao  $inscricao
     * @return void
     */
    public function created(Inscricao $inscricao)
    {
        // envia e-mail avisando o candidato da necessidade de enviar os arquivos e enviar a própria inscrição/matrícula
        // envio do e-mail "7" do README.md
        $passo = 'início';
        $user = $inscricao->pessoas('Autor');
        $rota = request()->segment(1);

        \Mail::to($user->email)
            ->queue(new InscricaoMail(compact('passo', 'inscricao', 'user', 'rota')));
    }

    /**
     * Listen to the Inscricao updating event.
     *
     * @param  \App\Models\Inscricao  $inscricao
     * @return void
     */
    public function updating(Inscricao $inscricao)
    {
        //
    }

    /**
     * Handle the Inscricao "updated" event.
     *
     * @param  \App\Models\Inscricao  $inscricao
     * @return void
     */
    public function updated(Inscricao $inscricao)
    {
        $user = $inscricao->pessoas('Autor');

        if ($inscricao->isDirty('estado')) {                                    // se a alteração na inscrição foi no estado
            if (($inscricao->getOriginal('estado') == 'Aguardando Envio') &&    // se o estado anterior era Aguardando Envio
                ($inscricao->estado == 'Enviada')) {                            // se o novo estado é Enviada

                if ($inscricao->selecao->tem_taxa && !$user->solicitacoesIsencaoTaxa()->where('selecao_id', $inscricao->selecao->id)->whereIn('estado', ['Isenção de Taxa Aprovada', 'Isenção de Taxa Aprovada Após Recurso'])->exists()) {
                    $passo = 'boleto(s)';
                    $arquivos = [];
                    $email_secaoinformatica = Parametro::first()->email_secaoinformatica;
                    if ($inscricao->selecao->categoria->nome !== 'Aluno Especial') {
                        $arquivos = [$this->boletoService->gerarBoleto($inscricao)];

                        // envia e-mail para o candidato com o(s) boleto(s)
                        // envio do e-mail "8" do README.md
                        \Mail::to($user->email)
                            ->queue(new InscricaoMail(compact('passo', 'inscricao', 'user', 'arquivos', 'email_secaoinformatica')));
                    } else
                        $this->processa_disciplinas_alteradas($inscricao, $user, $email_secaoinformatica);
                }

                $passo = 'realização';
                if (!$inscricao->selecao->isMatricula()) {
                    // envia e-mail avisando a secretaria do programa da seleção da inscrição/matrícula sobre a realização da inscrição/matrícula
                    // envio do e-mail "9" do README.md
                    $responsavel_nome = 'Prezados(as) Srs(as). da Secretaria do Programa ' . $inscricao->selecao->programa->nome;
                    \Mail::to($inscricao->selecao->programa->email_secretaria)
                        ->queue(new InscricaoMail(compact('passo', 'inscricao', 'user', 'responsavel_nome')));

                    // envia e-mails avisando os coordenadores do programa da seleção da inscrição/matrícula sobre a realização da inscrição/matrícula
                    // envio do e-mail "10" do README.md
                    foreach (collect($inscricao->selecao->programa->obterResponsaveis())->firstWhere('funcao', 'Coordenadores do Programa')['users'] as $coordenador) {
                        $responsavel_nome = 'Prezado(a) Sr(a). ' . Pessoa::obterNome($coordenador->codpes);
                        \Mail::to($coordenador->email)
                            ->queue(new InscricaoMail(compact('passo', 'inscricao', 'user', 'responsavel_nome')));
                    }
                } else {
                    // envia e-mails avisando o serviço de pós-graduação sobre a realização da matrícula
                    // envio do e-mail "11" do README.md
                    foreach (collect((new Programa)->obterResponsaveis())->firstWhere('funcao', 'Serviço de Pós-Graduação')['users'] as $servicoposgraduacao) {
                        $responsavel_nome = 'Prezado(a) Sr.(a) ' . Pessoa::obterNome($servicoposgraduacao->codpes);
                        \Mail::to($servicoposgraduacao->email)
                            ->queue(new InscricaoMail(compact('passo', 'inscricao', 'user', 'responsavel_nome')));
                    }
                }
            } elseif (($inscricao->getOriginal('estado') == 'Em Pré-Avaliação') &&    // se o estado anterior era Em Pré-Avaliação
                      ($inscricao->estado == 'Pré-Aprovada')) {                       // se o novo estado é Pré-Aprovada

                // envia e-mail avisando o candidato da pré-aprovação da inscrição/matrícula
                // envio do e-mail "14" do README.md
                $passo = 'pré-aprovação';
                $link_acompanhamento = (($inscricao->selecao->categoria->nome == 'Aluno Especial') ? Parametro::first()->link_acompanhamento_especiais : $inscricao->selecao->programa->link_acompanhamento);
                \Mail::to($user->email)
                    ->queue(new InscricaoMail(compact('passo', 'inscricao', 'user', 'link_acompanhamento')));

            } elseif (($inscricao->getOriginal('estado') == 'Em Pré-Avaliação') &&    // se o estado anterior era Em Pré-Avaliação
                      ($inscricao->estado == 'Pré-Rejeitada')) {                      // se o novo estado é Pré-Rejeitada

                // envia e-mail avisando o candidato da pré-rejeição da inscrição/matrícula
                // envio do e-mail "15" do README.md
                $passo = 'pré-rejeição';
                \Mail::to($user->email)
                    ->queue(new InscricaoMail(compact('passo', 'inscricao', 'user')));

            } elseif (($inscricao->getOriginal('estado') == 'Em Avaliação') &&        // se o estado anterior era Em Avaliação
                      (in_array($inscricao->estado, ['Aprovada', 'Rejeitada']))) {    // se o novo estado é Aprovada ou Rejeitada

                // envia e-mail avisando o candidato da aprovação/rejeição da inscrição/matrícula
                // envio do e-mail "16" do README.md
                $passo = (($inscricao->estado == 'Aprovada') ? 'aprovação' : 'rejeição');
                \Mail::to($user->email)
                    ->queue(new InscricaoMail(compact('passo', 'inscricao', 'user')));
            }
        }
    }

    private function processa_disciplinas_alteradas(Inscricao $inscricao, User $user, string $email_secaoinformatica)
    {
        $extras = json_decode($inscricao->extras, true);
        $disciplinas_id = (isset($extras['disciplinas']) ? $extras['disciplinas'] : []);

        // obtém o conjunto de disciplinas do envio anterior
        $disciplinas_sigla_anterior = $inscricao->arquivos()->whereHas('tipoarquivo', function ($query) { $query->where('nome', 'Boleto(s) de Pagamento'); })->pluck('disciplina')->toArray();
        $disciplinas_id_anterior = Disciplina::whereIn('sigla', $disciplinas_sigla_anterior)->pluck('id')->toArray();

        // transaction para não ter problema de inconsistência do DB
        $arquivos = DB::transaction(function () use ($inscricao, $disciplinas_id, $disciplinas_id_anterior) {

            // marca como removidas as disciplinas as quais o candidato removeu
            $tipoarquivo_boletodisciplinasremovidas = TipoArquivo::where('classe_nome', 'Inscrições')->where('nome', 'Boleto(s) de Pagamento - Disciplinas Removidas')->first();
            foreach (array_diff($disciplinas_id_anterior, $disciplinas_id) as $disciplina_id_removida) {
                $disciplina = Disciplina::find($disciplina_id_removida);
                foreach ($inscricao->arquivos()->whereHas('tipoarquivo', function ($query) { $query->where('nome', 'Boleto(s) de Pagamento'); })->where('disciplina', $disciplina->sigla)->get() as $arquivo) {
                    $inscricao->arquivos()->updateExistingPivot(
                        $arquivo->id,                                                                               // estranhamente, o Laravel precisa que eu passe o arquivo_id aqui, mesmo que eu tenha começado este comando com $inscricao (ou seja, ele deveria saber qual é a inscrição)
                        ['tipo' => 'Boleto(s) de Pagamento - Disciplinas Removidas']                                // atualiza o tipo do arquivo para "Boleto(s) de Pagamento - Disciplinas Removidas"
                    );
                    $arquivo->tipoarquivo_id = $tipoarquivo_boletodisciplinasremovidas->id;             // atualiza o tipo do arquivo para "Boleto(s) de Pagamento - Disciplinas Removidas"
                    $arquivo->nome_original = str_replace('_Boleto_', '_BoletoDiscRemov_', $arquivo->nome_original);    // atualiza o nome do arquivo para refletir o novo tipo
                    $arquivo->save();
                }
            }

            // gera boletos para as novas disciplinas deste reenvio
            $arquivos = [];
            foreach (array_diff($disciplinas_id, $disciplinas_id_anterior) as $disciplina_id_nova) {
                $disciplina = Disciplina::find($disciplina_id_nova);
                $arquivos[] = $this->boletoService->gerarBoleto($inscricao, $disciplina->sigla);
            }

            return $arquivos;
        });

        if (!empty($arquivos)) {
            if (empty($disciplinas_id_anterior))
                // envia e-mail para o candidato com o(s) boleto(s)
                // envio do e-mail "8" do README.md
                $passo = 'boleto(s)';
            else
                // envia e-mail para o candidato com o(s) boleto(s)
                // envio do e-mail "12" do README.md
                $passo = 'boleto(s) - disciplinas alteradas';
            \Mail::to($user->email)
                ->queue(new InscricaoMail(compact('passo', 'inscricao', 'user', 'arquivos', 'email_secaoinformatica')));
        }
    }

    /**
     * Handle the Inscricao "deleted" event.
     *
     * @param  \App\Models\Inscricao  $inscricao
     * @return void
     */
    public function deleted(Inscricao $inscricao)
    {
        //
    }

    /**
     * Handle the Inscricao "restored" event.
     *
     * @param  \App\Models\Inscricao  $inscricao
     * @return void
     */
    public function restored(Inscricao $inscricao)
    {
        //
    }

    /**
     * Handle the Inscricao "force deleted" event.
     *
     * @param  \App\Models\Inscricao  $inscricao
     * @return void
     */
    public function forceDeleted(Inscricao $inscricao)
    {
        //
    }
}
