<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Programa;
use App\Models\Selecao;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Uspdev\CadastrosAuxiliaresClient\Contracts\ProgramasClientInterface;

class SelecaoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categoria_id_ALUNOREGULAR = Categoria::where('nome', 'Aluno Regular')->first()->id;
        $categoria_id_ALUNOESPECIAL = Categoria::where('nome', 'Aluno Especial')->first()->id;

        $programa_id_NEUROCIENCIASECOMPORTAMENTONEC = Programa::where('nome', 'Neurociências e Comportamento (NEC)')->first()->id;
        $programa = app(ProgramasClientInterface::class)->listar()[0];
        // se formos incluir tags com aspas duplas, precisamos usar <a href=\"...\">...</a> ao invés de <a href="...">...</a>

        $selecoes = [
            [
                'nome' => 'Seleção 2025 Aluno Regular NEC',
                'estado' => 'Em Elaboração',
                'descricao' => 'Processo Seletivo 2025 Aluno Regular',
                'solicitacoesisencaotaxa_datahora_inicio' => Carbon::createFromFormat('d/m/Y H:i', '30/10/2024 08:00')->format('Y-m-d H:i'),
                'solicitacoesisencaotaxa_datahora_fim' => Carbon::createFromFormat('d/m/Y H:i', '31/10/2024 23:59')->format('Y-m-d H:i'),
                'inscricoes_datahora_inicio' => Carbon::createFromFormat('d/m/Y H:i', '01/11/2024 08:00')->format('Y-m-d H:i'),
                'inscricoes_datahora_fim' => Carbon::createFromFormat('d/m/Y H:i', '01/03/2025 23:59')->format('Y-m-d H:i'),
                'boleto_valor' => 50.0,
                'boleto_texto' => 'Boleto de Inscrição do Processo Seletivo da Pós-Graduação',
                'boleto_data_vencimento' => Carbon::createFromFormat('d/m/Y', '02/03/2025')->format('Y-m-d'),
                'template' => '{
                    "nome": {
                        "label": "Nome",
                        "type": "text",
                        "validate": "required",
                        "order": 0
                    },
                    "nome_social": {
                        "label": "Nome Social",
                        "type": "text",
                        "help": "Decreto Estadual n. 55.588, de 17/03/2010",
                        "order": 1
                    },
                    "tipo_de_documento": {
                        "label": "Tipo de Documento",
                        "type": "select",
                        "value": [
                            {
                                "label": "RG",
                                "value": "rg",
                                "order": 0
                            },
                            {
                                "label": "RNE",
                                "value": "rne",
                                "order": 1
                            },
                            {
                                "label": "Passaporte",
                                "value": "passaporte",
                                "order": 2
                            }
                        ],
                        "help": "Utilize o passaporte apenas se não possuir documento de identidade brasileira (RG)",
                        "validate": "required",
                        "order": 2
                    },
                    "numero_do_documento": {
                        "label": "Número do Documento",
                        "type": "text",
                        "validate": "required",
                        "order": 3
                    },
                    "data_vencto_passaporte": {
                        "label": "Data de Vencimento do Passaporte",
                        "type": "date",
                        "order": 4
                    },
                    "cpf": {
                        "label": "CPF",
                        "type": "text",
                        "validate": "required",
                        "order": 5
                    },
                    "titulo_de_eleitor": {
                        "label": "Título de Eleitor",
                        "type": "text",
                        "order": 6
                    },
                    "documento_militar": {
                        "label": "Documento Militar",
                        "type": "text",
                        "help": "Quando pertinente",
                        "order": 7
                    },
                    "nome_da_mae": {
                        "label": "Nome da Mãe",
                        "type": "text",
                        "validate": "required",
                        "order": 8
                    },
                    "nome_do_pai": {
                        "label": "Nome do Pai",
                        "type": "text",
                        "order": 9
                    },
                    "data_de_nascimento": {
                        "label": "Data de Nascimento",
                        "type": "date",
                        "validate": "required",
                        "order": 10
                    },
                    "local_de_nascimento": {
                        "label": "Local de Nascimento",
                        "type": "text",
                        "validate": "required",
                        "order": 11
                    },
                    "uf_de_nascimento": {
                        "label": "UF de Nascimento",
                        "type": "select",
                        "value": [
                            {
                                "label": "AC",
                                "value": "ac",
                                "order": 0
                            },
                            {
                                "label": "AL",
                                "value": "al",
                                "order": 1
                            },
                            {
                                "label": "AM",
                                "value": "am",
                                "order": 2
                            },
                            {
                                "label": "AP",
                                "value": "ap",
                                "order": 3
                            },
                            {
                                "label": "BA",
                                "value": "ba",
                                "order": 4
                            },
                            {
                                "label": "CE",
                                "value": "ce",
                                "order": 5
                            },
                            {
                                "label": "DF",
                                "value": "df",
                                "order": 6
                            },
                            {
                                "label": "ES",
                                "value": "es",
                                "order": 7
                            },
                            {
                                "label": "GO",
                                "value": "go",
                                "order": 8
                            },
                            {
                                "label": "MA",
                                "value": "ma",
                                "order": 9
                            },
                            {
                                "label": "MG",
                                "value": "mg",
                                "order": 10
                            },
                            {
                                "label": "MS",
                                "value": "ms",
                                "order": 11
                            },
                            {
                                "label": "MT",
                                "value": "mt",
                                "order": 12
                            },
                            {
                                "label": "PA",
                                "value": "pa",
                                "order": 13
                            },
                            {
                                "label": "PB",
                                "value": "pb",
                                "order": 14
                            },
                            {
                                "label": "PE",
                                "value": "pe",
                                "order": 15
                            },
                            {
                                "label": "PI",
                                "value": "pi",
                                "order": 16
                            },
                            {
                                "label": "PR",
                                "value": "pr",
                                "order": 17
                            },
                            {
                                "label": "RJ",
                                "value": "rj",
                                "order": 18
                            },
                            {
                                "label": "RN",
                                "value": "rn",
                                "order": 19
                            },
                            {
                                "label": "RO",
                                "value": "ro",
                                "order": 20
                            },
                            {
                                "label": "RR",
                                "value": "rr",
                                "order": 21
                            },
                            {
                                "label": "RS",
                                "value": "rs",
                                "order": 22
                            },
                            {
                                "label": "SC",
                                "value": "sc",
                                "order": 23
                            },
                            {
                                "label": "SE",
                                "value": "se",
                                "order": 24
                            },
                            {
                                "label": "SP",
                                "value": "sp",
                                "order": 25
                            },
                            {
                                "label": "TO",
                                "value": "to",
                                "order": 26
                            }
                        ],
                        "validate": "required",
                        "order": 12
                    },
                    "sexo": {
                        "label": "Sexo",
                        "type": "select",
                        "value": [
                            {
                                "label": "Masculino",
                                "value": "masculino",
                                "order": 0
                            },
                            {
                                "label": "Feminino",
                                "value": "feminino",
                                "order": 1
                            }
                        ],
                        "validate": "required",
                        "order": 13
                    },
                    "raca_cor": {
                        "label": "Raça/Cor",
                        "type": "select",
                        "value": [
                            {
                                "label": "Amarela",
                                "value": "amarela",
                                "order": 0
                            },
                            {
                                "label": "Branca",
                                "value": "branca",
                                "order": 1
                            },
                            {
                                "label": "Indígena",
                                "value": "indigena",
                                "order": 2
                            },
                            {
                                "label": "Parda",
                                "value": "parda",
                                "order": 3
                            },
                            {
                                "label": "Preta",
                                "value": "preta",
                                "order": 4
                            },
                            {
                                "label": "Prefiro Não Responder",
                                "value": "prefiro_nao_responder",
                                "order": 5
                            }
                        ],
                        "validate": "required",
                        "order": 14
                    },
                    "declaro_ppi": {
                        "label": "Declaro, para os devidos fins, que sou preto, pardo ou indígena",
                        "type": "radio",
                        "value": [
                            {
                                "label": "Não",
                                "value": "nao",
                                "order": 0
                            },
                            {
                                "label": "Sim",
                                "value": "sim",
                                "order": 1
                            }
                        ],
                        "validate": "required",
                        "order": 15
                    },
                    "portador_de_deficiencia": {
                        "label": "Portador de Deficiência",
                        "type": "radio",
                        "value": [
                            {
                                "label": "Não",
                                "value": "nao",
                                "order": 0
                            },
                            {
                                "label": "Sim",
                                "value": "sim",
                                "order": 1
                            }
                        ],
                        "validate": "required",
                        "order": 16
                    },
                    "qual_a_sua_deficiencia": {
                        "label": "Qual a sua deficiência",
                        "type": "text",
                        "order": 17
                    },
                    "condicoes_prova": {
                        "label": "Condições Necessárias para a Realização da Prova",
                        "type": "textarea",
                        "order": 18
                    },
                    "cep": {
                        "label": "CEP",
                        "type": "text",
                        "validate": "required",
                        "order": 19
                    },
                    "endereco_residencial": {
                        "label": "Endereço Residencial",
                        "type": "text",
                        "validate": "required",
                        "order": 20
                    },
                    "numero": {
                        "label": "Número",
                        "type": "text",
                        "validate": "required",
                        "order": 21
                    },
                    "complemento": {
                        "label": "Complemento",
                        "type": "text",
                        "order": 22
                    },
                    "bairro": {
                        "label": "Bairro",
                        "type": "text",
                        "validate": "required",
                        "order": 23
                    },
                    "cidade": {
                        "label": "Cidade",
                        "type": "text",
                        "validate": "required",
                        "order": 24
                    },
                    "uf": {
                        "label": "UF",
                        "type": "select",
                        "value": [
                            {
                                "label": "AC",
                                "value": "ac",
                                "order": 0
                            },
                            {
                                "label": "AL",
                                "value": "al",
                                "order": 1
                            },
                            {
                                "label": "AM",
                                "value": "am",
                                "order": 2
                            },
                            {
                                "label": "AP",
                                "value": "ap",
                                "order": 3
                            },
                            {
                                "label": "BA",
                                "value": "ba",
                                "order": 4
                            },
                            {
                                "label": "CE",
                                "value": "ce",
                                "order": 5
                            },
                            {
                                "label": "DF",
                                "value": "df",
                                "order": 6
                            },
                            {
                                "label": "ES",
                                "value": "es",
                                "order": 7
                            },
                            {
                                "label": "GO",
                                "value": "go",
                                "order": 8
                            },
                            {
                                "label": "MA",
                                "value": "ma",
                                "order": 9
                            },
                            {
                                "label": "MG",
                                "value": "mg",
                                "order": 10
                            },
                            {
                                "label": "MS",
                                "value": "ms",
                                "order": 11
                            },
                            {
                                "label": "MT",
                                "value": "mt",
                                "order": 12
                            },
                            {
                                "label": "PA",
                                "value": "pa",
                                "order": 13
                            },
                            {
                                "label": "PB",
                                "value": "pb",
                                "order": 14
                            },
                            {
                                "label": "PE",
                                "value": "pe",
                                "order": 15
                            },
                            {
                                "label": "PI",
                                "value": "pi",
                                "order": 16
                            },
                            {
                                "label": "PR",
                                "value": "pr",
                                "order": 17
                            },
                            {
                                "label": "RJ",
                                "value": "rj",
                                "order": 18
                            },
                            {
                                "label": "RN",
                                "value": "rn",
                                "order": 19
                            },
                            {
                                "label": "RO",
                                "value": "ro",
                                "order": 20
                            },
                            {
                                "label": "RR",
                                "value": "rr",
                                "order": 21
                            },
                            {
                                "label": "RS",
                                "value": "rs",
                                "order": 22
                            },
                            {
                                "label": "SC",
                                "value": "sc",
                                "order": 23
                            },
                            {
                                "label": "SE",
                                "value": "se",
                                "order": 24
                            },
                            {
                                "label": "SP",
                                "value": "sp",
                                "order": 25
                            },
                            {
                                "label": "TO",
                                "value": "to",
                                "order": 26
                            }
                        ],
                        "validate": "required",
                        "order": 25
                    },
                    "celular": {
                        "label": "Celular",
                        "type": "text",
                        "validate": "required",
                        "order": 26
                    },
                    "e_mail": {
                        "label": "E-mail",
                        "type": "email",
                        "validate": "required",
                        "order": 27
                    },
                    "declaro_concordo_termos": {
                        "label": "Declaro estar ciente e concordo com os <a href=\"https://www.ip.usp.br/site/pos_graduacao/regimentos-da-comissao-de-pos-graduacao-e-regulamentos-dos-programas/\">termos de inscrição no Programa de Pós-Graduação do Instituto de Psicologia da USP</a>",
                        "type": "checkbox",
                        "validate": "required",
                        "order": 28
                    },
                    "declaro_revisei_inscricao": {
                        "label": "Declaro que revisei todas as informações inseridas neste formulário e que elas estão corretas, e venho requerer minha inscrição como candidato(a) à vaga no Programa de Pós-Graduação do Instituto de Psicologia da USP",
                        "type": "checkbox",
                        "validate": "required",
                        "order": 29
                    },
                    "declaro_ciente_nao_presencial": {
                        "label": "Declaro estar ciente de que o processo seletivo será realizado no formato não presencial, on-line, e que a <u>Comissão de Seleção não se responsabiliza por eventuais falhas técnicas por parte do(a) candidato(a) (tais como falta de internet, cortes de som, corte de luz, etc.) durante a realização das provas e das arguições relizadas online</u>. A sugestão é que o(a) candidato(a) se organize com antecedência para o bom andamento da prova",
                        "type": "checkbox",
                        "validate": "required",
                        "order": 30
                    }
                }',
                'categoria_id' => $categoria_id_ALUNOREGULAR,
                'programa_id' => $programa_id_NEUROCIENCIASECOMPORTAMENTONEC,
                'settings' => '{
                    "instrucoes": "Os campos marcados com (*) são de preenchimento obrigatório"
                }'
            ],
            [
                'nome' => 'Seleção 2025 Aluno Especial',
                'estado' => 'Em Elaboração',
                'descricao' => 'Processo Seletivo 2025 Aluno Especial',
                'tem_taxa' => false,
                'inscricoes_datahora_inicio' => Carbon::createFromFormat('d/m/Y H:i', '01/11/2024 08:00')->format('Y-m-d H:i'),
                'inscricoes_datahora_fim' => Carbon::createFromFormat('d/m/Y H:i', '01/03/2025 23:59')->format('Y-m-d H:i'),
                'template' => '{
                    "nome": {
                        "label": "Nome",
                        "type": "text",
                        "validate": "required",
                        "order": 0
                    },
                    "nome_social": {
                        "label": "Nome Social",
                        "type": "text",
                        "help": "Decreto Estadual n. 55.588, de 17/03/2010",
                        "order": 1
                    },
                    "tipo_de_documento": {
                        "label": "Tipo de Documento",
                        "type": "select",
                        "value": [
                            {
                                "label": "RG",
                                "value": "rg",
                                "order": 0
                            },
                            {
                                "label": "RNE",
                                "value": "rne",
                                "order": 1
                            },
                            {
                                "label": "Passaporte",
                                "value": "passaporte",
                                "order": 2
                            }
                        ],
                        "help": "Utilize o passaporte apenas se não possuir documento de identidade brasileira (RG)",
                        "validate": "required",
                        "order": 2
                    },
                    "numero_do_documento": {
                        "label": "Número do Documento",
                        "type": "text",
                        "validate": "required",
                        "order": 3
                    },
                    "data_vencto_passaporte": {
                        "label": "Data de Vencimento do Passaporte",
                        "type": "date",
                        "order": 4
                    },
                    "cpf": {
                        "label": "CPF",
                        "type": "text",
                        "validate": "required",
                        "order": 5
                    },
                    "titulo_de_eleitor": {
                        "label": "Título de Eleitor",
                        "type": "text",
                        "order": 6
                    },
                    "documento_militar": {
                        "label": "Documento Militar",
                        "type": "text",
                        "help": "Quando pertinente",
                        "order": 7
                    },
                    "nome_da_mae": {
                        "label": "Nome da Mãe",
                        "type": "text",
                        "validate": "required",
                        "order": 8
                    },
                    "nome_do_pai": {
                        "label": "Nome do Pai",
                        "type": "text",
                        "order": 9
                    },
                    "data_de_nascimento": {
                        "label": "Data de Nascimento",
                        "type": "date",
                        "validate": "required",
                        "order": 10
                    },
                    "local_de_nascimento": {
                        "label": "Local de Nascimento",
                        "type": "text",
                        "validate": "required",
                        "order": 11
                    },
                    "uf_de_nascimento": {
                        "label": "UF de Nascimento",
                        "type": "select",
                        "value": [
                            {
                                "label": "AC",
                                "value": "ac",
                                "order": 0
                            },
                            {
                                "label": "AL",
                                "value": "al",
                                "order": 1
                            },
                            {
                                "label": "AM",
                                "value": "am",
                                "order": 2
                            },
                            {
                                "label": "AP",
                                "value": "ap",
                                "order": 3
                            },
                            {
                                "label": "BA",
                                "value": "ba",
                                "order": 4
                            },
                            {
                                "label": "CE",
                                "value": "ce",
                                "order": 5
                            },
                            {
                                "label": "DF",
                                "value": "df",
                                "order": 6
                            },
                            {
                                "label": "ES",
                                "value": "es",
                                "order": 7
                            },
                            {
                                "label": "GO",
                                "value": "go",
                                "order": 8
                            },
                            {
                                "label": "MA",
                                "value": "ma",
                                "order": 9
                            },
                            {
                                "label": "MG",
                                "value": "mg",
                                "order": 10
                            },
                            {
                                "label": "MS",
                                "value": "ms",
                                "order": 11
                            },
                            {
                                "label": "MT",
                                "value": "mt",
                                "order": 12
                            },
                            {
                                "label": "PA",
                                "value": "pa",
                                "order": 13
                            },
                            {
                                "label": "PB",
                                "value": "pb",
                                "order": 14
                            },
                            {
                                "label": "PE",
                                "value": "pe",
                                "order": 15
                            },
                            {
                                "label": "PI",
                                "value": "pi",
                                "order": 16
                            },
                            {
                                "label": "PR",
                                "value": "pr",
                                "order": 17
                            },
                            {
                                "label": "RJ",
                                "value": "rj",
                                "order": 18
                            },
                            {
                                "label": "RN",
                                "value": "rn",
                                "order": 19
                            },
                            {
                                "label": "RO",
                                "value": "ro",
                                "order": 20
                            },
                            {
                                "label": "RR",
                                "value": "rr",
                                "order": 21
                            },
                            {
                                "label": "RS",
                                "value": "rs",
                                "order": 22
                            },
                            {
                                "label": "SC",
                                "value": "sc",
                                "order": 23
                            },
                            {
                                "label": "SE",
                                "value": "se",
                                "order": 24
                            },
                            {
                                "label": "SP",
                                "value": "sp",
                                "order": 25
                            },
                            {
                                "label": "TO",
                                "value": "to",
                                "order": 26
                            }
                        ],
                        "validate": "required",
                        "order": 12
                    },
                    "sexo": {
                        "label": "Sexo",
                        "type": "select",
                        "value": [
                            {
                                "label": "Masculino",
                                "value": "masculino",
                                "order": 0
                            },
                            {
                                "label": "Feminino",
                                "value": "feminino",
                                "order": 1
                            }
                        ],
                        "validate": "required",
                        "order": 13
                    },
                    "raca_cor": {
                        "label": "Raça/Cor",
                        "type": "select",
                        "value": [
                            {
                                "label": "Amarela",
                                "value": "amarela",
                                "order": 0
                            },
                            {
                                "label": "Branca",
                                "value": "branca",
                                "order": 1
                            },
                            {
                                "label": "Indígena",
                                "value": "indigena",
                                "order": 2
                            },
                            {
                                "label": "Parda",
                                "value": "parda",
                                "order": 3
                            },
                            {
                                "label": "Preta",
                                "value": "preta",
                                "order": 4
                            },
                            {
                                "label": "Prefiro Não Responder",
                                "value": "prefiro_nao_responder",
                                "order": 5
                            }
                        ],
                        "validate": "required",
                        "order": 14
                    },
                    "declaro_ppi": {
                        "label": "Declaro, para os devidos fins, que sou preto, pardo ou indígena",
                        "type": "radio",
                        "value": [
                            {
                                "label": "Não",
                                "value": "nao",
                                "order": 0
                            },
                            {
                                "label": "Sim",
                                "value": "sim",
                                "order": 1
                            }
                        ],
                        "validate": "required",
                        "order": 15
                    },
                    "portador_de_deficiencia": {
                        "label": "Portador de Deficiência",
                        "type": "radio",
                        "value": [
                            {
                                "label": "Não",
                                "value": "nao",
                                "order": 0
                            },
                            {
                                "label": "Sim",
                                "value": "sim",
                                "order": 1
                            }
                        ],
                        "validate": "required",
                        "order": 16
                    },
                    "qual_a_sua_deficiencia": {
                        "label": "Qual a sua deficiência",
                        "type": "text",
                        "order": 17
                    },
                    "condicoes_prova": {
                        "label": "Condições Necessárias para a Realização da Prova",
                        "type": "textarea",
                        "order": 18
                    },
                    "cep": {
                        "label": "CEP",
                        "type": "text",
                        "validate": "required",
                        "order": 19
                    },
                    "endereco_residencial": {
                        "label": "Endereço Residencial",
                        "type": "text",
                        "validate": "required",
                        "order": 20
                    },
                    "numero": {
                        "label": "Número",
                        "type": "text",
                        "validate": "required",
                        "order": 21
                    },
                    "complemento": {
                        "label": "Complemento",
                        "type": "text",
                        "order": 22
                    },
                    "bairro": {
                        "label": "Bairro",
                        "type": "text",
                        "validate": "required",
                        "order": 23
                    },
                    "cidade": {
                        "label": "Cidade",
                        "type": "text",
                        "validate": "required",
                        "order": 24
                    },
                    "uf": {
                        "label": "UF",
                        "type": "select",
                        "value": [
                            {
                                "label": "AC",
                                "value": "ac",
                                "order": 0
                            },
                            {
                                "label": "AL",
                                "value": "al",
                                "order": 1
                            },
                            {
                                "label": "AM",
                                "value": "am",
                                "order": 2
                            },
                            {
                                "label": "AP",
                                "value": "ap",
                                "order": 3
                            },
                            {
                                "label": "BA",
                                "value": "ba",
                                "order": 4
                            },
                            {
                                "label": "CE",
                                "value": "ce",
                                "order": 5
                            },
                            {
                                "label": "DF",
                                "value": "df",
                                "order": 6
                            },
                            {
                                "label": "ES",
                                "value": "es",
                                "order": 7
                            },
                            {
                                "label": "GO",
                                "value": "go",
                                "order": 8
                            },
                            {
                                "label": "MA",
                                "value": "ma",
                                "order": 9
                            },
                            {
                                "label": "MG",
                                "value": "mg",
                                "order": 10
                            },
                            {
                                "label": "MS",
                                "value": "ms",
                                "order": 11
                            },
                            {
                                "label": "MT",
                                "value": "mt",
                                "order": 12
                            },
                            {
                                "label": "PA",
                                "value": "pa",
                                "order": 13
                            },
                            {
                                "label": "PB",
                                "value": "pb",
                                "order": 14
                            },
                            {
                                "label": "PE",
                                "value": "pe",
                                "order": 15
                            },
                            {
                                "label": "PI",
                                "value": "pi",
                                "order": 16
                            },
                            {
                                "label": "PR",
                                "value": "pr",
                                "order": 17
                            },
                            {
                                "label": "RJ",
                                "value": "rj",
                                "order": 18
                            },
                            {
                                "label": "RN",
                                "value": "rn",
                                "order": 19
                            },
                            {
                                "label": "RO",
                                "value": "ro",
                                "order": 20
                            },
                            {
                                "label": "RR",
                                "value": "rr",
                                "order": 21
                            },
                            {
                                "label": "RS",
                                "value": "rs",
                                "order": 22
                            },
                            {
                                "label": "SC",
                                "value": "sc",
                                "order": 23
                            },
                            {
                                "label": "SE",
                                "value": "se",
                                "order": 24
                            },
                            {
                                "label": "SP",
                                "value": "sp",
                                "order": 25
                            },
                            {
                                "label": "TO",
                                "value": "to",
                                "order": 26
                            }
                        ],
                        "validate": "required",
                        "order": 25
                    },
                    "celular": {
                        "label": "Celular",
                        "type": "text",
                        "validate": "required",
                        "order": 26
                    },
                    "e_mail": {
                        "label": "E-mail",
                        "type": "email",
                        "validate": "required",
                        "order": 27
                    },
                    "declaro_concordo_termos": {
                        "label": "Declaro estar ciente e concordo com os <a href=\"https://www.ip.usp.br/site/pos_graduacao/regimentos-da-comissao-de-pos-graduacao-e-regulamentos-dos-programas/\">termos de inscrição no Programa de Pós-Graduação do Instituto de Psicologia da USP</a>",
                        "type": "checkbox",
                        "validate": "required",
                        "order": 28
                    },
                    "declaro_revisei_inscricao": {
                        "label": "Declaro que revisei todas as informações inseridas neste formulário e que elas estão corretas, e venho requerer minha inscrição como candidato(a) à vaga no Programa de Pós-Graduação do Instituto de Psicologia da USP",
                        "type": "checkbox",
                        "validate": "required",
                        "order": 29
                    },
                    "declaro_ciente_nao_presencial": {
                        "label": "Declaro estar ciente de que o processo seletivo será realizado no formato não presencial, on-line, e que a <u>Comissão de Seleção não se responsabiliza por eventuais falhas técnicas por parte do(a) candidato(a) (tais como falta de internet, cortes de som, corte de luz, etc.) durante a realização das provas e das arguições relizadas online</u>. A sugestão é que o(a) candidato(a) se organize com antecedência para o bom andamento da prova",
                        "type": "checkbox",
                        "validate": "required",
                        "order": 30
                    }
                }',
                'categoria_id' => $categoria_id_ALUNOESPECIAL,
                'settings' => '{
                    "instrucoes": "Os campos marcados com (*) são de preenchimento obrigatório"
                }'
            ]
        ];

        // adiciona registros na tabela selecoes
        foreach ($selecoes as $selecao)
            Selecao::create($selecao);
    }
}
