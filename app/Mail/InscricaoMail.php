<?php

namespace App\Mail;

use App\Models\Inscricao;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InscricaoMail extends Mailable
{
    use Queueable, SerializesModels;

    // campos gerais
    protected $passo;
    protected $inscricao;
    protected $user;
    protected $rota;

    // campos adicionais para boleto(s) e boleto(s) - disciplinas alteradas
    protected $arquivos;

    // campos adicionais para boleto - envio manual
    protected $arquivo;

    // campos adicionais para inscrição/matrícula enviada
    protected $responsavel_nome;

    // campos adicionais para inscrição/matrícula pré-aprovada
    protected $link_acompanhamento;

    // campos adicionais para inscrição/matrícula pré-rejeitada

    // campos adicionais para inscrição/matrícula aprovada

    // campos adicionais para inscrição/matrícula reprovada

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->passo = $data['passo'];
        $this->inscricao = $data['inscricao'];
        $this->user = $data['user'];
        $this->rota = $data['rota'] ?? null;

        switch ($this->passo) {
            case 'início':
                break;

            case 'boleto(s)':
            case 'boleto(s) - disciplinas alteradas':
                $this->arquivos = [];
                foreach ($data['arquivos'] as $data_arquivo)
                    $this->arquivos[] = [
                        'nome_original' => $data_arquivo['nome_original'],
                        'conteudo' => $data_arquivo['conteudo'],
                        'erro' => (!empty($data_arquivo['conteudo']) ? '' : 'Ocorreu um erro na geração do boleto "' . $data_arquivo['nome_original'] . '".<br />' . PHP_EOL .
                            'Por favor, entre em contato conosco em ' . $data['email_secaoinformatica'] . ', informando-nos sobre esse problema.<br />' . PHP_EOL),
                    ];
                break;

            case 'boleto - envio manual':
                $this->arquivo = [
                    'nome_original' => $data['arquivo']->nome_original,
                    'conteudo' => $data['arquivo']->conteudo,
                ];
                break;

            case 'realização':
                $this->responsavel_nome = $data['responsavel_nome'];
                break;

            case 'pré-aprovação':
                $this->link_acompanhamento = $data['link_acompanhamento'];
                break;

            case 'pré-rejeição':
                break;

            case 'aprovação':
                break;

            case 'rejeição':
        }
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        switch ($this->passo) {
            case 'início':
                return $this
                    ->subject('[' . config('app.name') . '] Inscrição Pendente de Envio')
                    ->from(config('mail.from.address'), config('mail.from.name'))
                    ->view('emails.inscricao_inicio')
                    ->with([
                        'inscricao' => $this->inscricao,
                        'user' => $this->user,
                        'rota' => $this->rota,
                    ]);

            case 'boleto(s)':
            case 'boleto(s) - disciplinas alteradas':
                $arquivos_erro = [];
                foreach ($this->arquivos as $arquivo)
                    $arquivos_erro[] = $arquivo['erro'];
                $mail = $this
                    ->subject('[' . config('app.name') . '] Inscrição Enviada')
                    ->from(config('mail.from.address'), config('mail.from.name'))
                    ->view('emails.inscricao_enviodeboletos' . (($this->passo == 'boleto(s) - disciplinas alteradas') ? 'disciplinasalteradas' : ''))
                    ->with([
                        'inscricao' => $this->inscricao,
                        'user' => $this->user,
                        'arquivos_count' => count($this->arquivos),
                        'arquivos_erro' => $arquivos_erro,
                    ]);
                foreach ($this->arquivos as $arquivo)
                    if (!empty($arquivo['conteudo']))
                        $mail->attachData(base64_decode($arquivo['conteudo']), $arquivo['nome_original'], ['mime' => 'application/pdf']);
                return $mail;

            case 'boleto - envio manual':
                $mail = $this
                    ->subject('[' . config('app.name') . '] Boleto Enviado')
                    ->from(config('mail.from.address'), config('mail.from.name'))
                    ->view('emails.inscricao_enviomanualdeboleto')
                    ->with([
                        'inscricao' => $this->inscricao,
                        'user' => $this->user,
                    ]);
                if (!empty($this->arquivo['conteudo']))
                    $mail->attachData(base64_decode($this->arquivo['conteudo']), $this->arquivo['nome_original'], ['mime' => 'application/pdf']);
                return $mail;

            case 'realização':
                return $this
                    ->subject('[' . config('app.name') . '] Realização de Inscrição')
                    ->from(config('mail.from.address'), config('mail.from.name'))
                    ->view('emails.inscricao_realizacao')
                    ->with([
                        'inscricao' => $this->inscricao,
                        'responsavel_nome' => $this->responsavel_nome,
                    ]);

            case 'pré-aprovação':
                return $this
                    ->subject('[' . config('app.name') . '] Acompanhamento de Inscrição')
                    ->from(config('mail.from.address'), config('mail.from.name'))
                    ->view('emails.inscricao_preaprovacao')
                    ->with([
                        'inscricao' => $this->inscricao,
                        'user' => $this->user,
                        'link_acompanhamento' => $this->link_acompanhamento,
                    ]);

            case 'pré-rejeição':
                return $this
                    ->subject('[' . config('app.name') . '] Rejeição de Inscrição')
                    ->from(config('mail.from.address'), config('mail.from.name'))
                    ->view('emails.inscricao_prerejeicao')
                    ->with([
                        'inscricao' => $this->inscricao,
                        'user' => $this->user,
                    ]);

            case 'aprovação':
                return $this
                    ->subject('[' . config('app.name') . '] Aprovação de Inscrição')
                    ->from(config('mail.from.address'), config('mail.from.name'))
                    ->view('emails.inscricao_aprovacao')
                    ->with([
                        'inscricao' => $this->inscricao,
                        'user' => $this->user,
                    ]);

            case 'rejeição':
                return $this
                    ->subject('[' . config('app.name') . '] Rejeição de Inscrição')
                    ->from(config('mail.from.address'), config('mail.from.name'))
                    ->view('emails.inscricao_rejeicao')
                    ->with([
                        'inscricao' => $this->inscricao,
                        'user' => $this->user,
                    ]);
        }
    }
}
