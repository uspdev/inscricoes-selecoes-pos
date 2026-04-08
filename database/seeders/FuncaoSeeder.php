<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Uspdev\Replicado\Posgraduacao;
use App\Models\Programa;

class FuncaoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $programas = Posgraduacao::programas();
        foreach($programas as $programa)
            {
                $programaSistema = Programa::where('nome', 'LIKE', "{$programa['nomcur']}%")->first();
                $docentes = Posgraduacao::orientadores($programa['codare']);
                foreach($docentes as $docente)
                {
                    $user = User::findOrCreateFromReplicado($docente['codpes']);
                    if ($user) {
                        // Verifica se o usuário já tem essa função nesse programa específico
                        $jaAssociado = $user->programas()
                            ->where('programa_id', $programaSistema->id ?? null)
                            ->wherePivot('funcao', 'Docentes do Programa')
                            ->exists();

                        if (!$jaAssociado) {
                            $user->associarProgramaFuncao($programaSistema['nome'] ?? null, 'Docentes do Programa');
                        }
                    }
                }
            }
     }
//         $funcoes = [
//             [
//                 'codpes' => 339731,    // Antonio de Padua Serafim
//                 'programa' => 'Neurociências e Comportamento (NEC)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 3386535,    // Avelino Luiz Rodrigues
//                 'programa' => 'Neurociências e Comportamento (NEC)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 5032360,    // Daniela Maria Oliveira Bonci
//                 'programa' => 'Neurociências e Comportamento (NEC)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 24021,    // Dora Selma Fix Ventura
//                 'programa' => 'Neurociências e Comportamento (NEC)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 5800099,    // Felipe Dalessandro Ferreira Corchs
//                 'programa' => 'Neurociências e Comportamento (NEC)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 3257875,    // Marcelo Fernandes da Costa
//                 'programa' => 'Neurociências e Comportamento (NEC)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 2557349,    // Miriam Garcia Mijares
//                 'programa' => 'Neurociências e Comportamento (NEC)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 173293,    // Christina Joselevitch
//                 'programa' => 'Neurociências e Comportamento (NEC)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 2363718,    // Elaine Cristina Zachi
//                 'programa' => 'Neurociências e Comportamento (NEC)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 96853,    // Francisco Baptista Assumpcao Junior
//                 'programa' => 'Neurociências e Comportamento (NEC)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 2538261,    // Francisco Javier Ropero Peláez
//                 'programa' => 'Neurociências e Comportamento (NEC)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 3648589,    // Mirella Gualtieri
//                 'programa' => 'Neurociências e Comportamento (NEC)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 2089772,    // Ana Maria Loffredo
//                 'programa' => 'Psicologia Escolar e do Desenvolvimento Humano (PSA)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 2085249,    // Maria Isabel da Silva Leme
//                 'programa' => 'Psicologia Escolar e do Desenvolvimento Humano (PSA)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 1498922,    // Maria Thereza Costa Coelho de Souza
//                 'programa' => 'Psicologia Escolar e do Desenvolvimento Humano (PSA)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 2662819,    // Marilene Proença Rebello de Souza
//                 'programa' => 'Psicologia Escolar e do Desenvolvimento Humano (PSA)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 70419,    // Marlene Guirado
//                 'programa' => 'Psicologia Escolar e do Desenvolvimento Humano (PSA)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 647530,    // Adriana Marcondes Machado
//                 'programa' => 'Psicologia Escolar e do Desenvolvimento Humano (PSA)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 791212,    // Helena Rinaldi Rosa
//                 'programa' => 'Psicologia Escolar e do Desenvolvimento Humano (PSA)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 907384,    // Leopoldo Pereira Fulgencio Junior
//                 'programa' => 'Psicologia Escolar e do Desenvolvimento Humano (PSA)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 4864151,    // Luciana Maria Caetano
//                 'programa' => 'Psicologia Escolar e do Desenvolvimento Humano (PSA)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 2163232,    // Márcia Helena da Silva Mélo
//                 'programa' => 'Psicologia Escolar e do Desenvolvimento Humano (PSA)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 1290970,    // Marie Claire Sekkel
//                 'programa' => 'Psicologia Escolar e do Desenvolvimento Humano (PSA)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 3001809,    // Paulo César Endo
//                 'programa' => 'Psicologia Escolar e do Desenvolvimento Humano (PSA)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 1841928,    // Rogerio Lerner
//                 'programa' => 'Psicologia Escolar e do Desenvolvimento Humano (PSA)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 339731,    // Antonio de Padua Serafim
//                 'programa' => 'Psicologia Escolar e do Desenvolvimento Humano (PSA)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 3001174,    // Fraulein Vidigal de Paula
//                 'programa' => 'Psicologia Escolar e do Desenvolvimento Humano (PSA)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 44778,    // Irai Cristina Boccato Alves
//                 'programa' => 'Psicologia Escolar e do Desenvolvimento Humano (PSA)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 2157343,    // Lineu Norió Kohatsu
//                 'programa' => 'Psicologia Escolar e do Desenvolvimento Humano (PSA)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 4865573,    // Pedro Fernando da Silva
//                 'programa' => 'Psicologia Escolar e do Desenvolvimento Humano (PSA)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 4967503,    // Betania Alves Veiga Dell Agli
//                 'programa' => 'Psicologia Escolar e do Desenvolvimento Humano (PSA)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 2177912,    // Miriam Debieux Rosa
//                 'programa' => 'Psicologia Escolar e do Desenvolvimento Humano (PSA)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 2084015,    // Tania Maria Jose Aiello Vaisberg
//                 'programa' => 'Psicologia Escolar e do Desenvolvimento Humano (PSA)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 24111,    // Henriette Tognetti Penha Morato
//                 'programa' => 'Psicologia Escolar e do Desenvolvimento Humano (PSA)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 83532,    // Jose Leon Crochik
//                 'programa' => 'Psicologia Escolar e do Desenvolvimento Humano (PSA)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 51411,    // Laura Villares de Freitas
//                 'programa' => 'Psicologia Escolar e do Desenvolvimento Humano (PSA)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 52583,    // Maria Cristina Machado Kupfer
//                 'programa' => 'Psicologia Escolar e do Desenvolvimento Humano (PSA)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 80491,    // Maria Júlia Kovacs
//                 'programa' => 'Psicologia Escolar e do Desenvolvimento Humano (PSA)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 5593721,    // Andrés Eduardo Aguirre Antúnez
//                 'programa' => 'Psicologia Clínica (PSC)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 3386535,    // Avelino Luiz Rodrigues
//                 'programa' => 'Psicologia Clínica (PSC)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 2103422,    // Christian Ingo Lenz Dunker
//                 'programa' => 'Psicologia Clínica (PSC)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 5710209,    // Claudia Kami Bastos Oshiro Clemente
//                 'programa' => 'Psicologia Clínica (PSC)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 5811484,    // Daniel Kupermann
//                 'programa' => 'Psicologia Clínica (PSC)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 96853,    // Francisco Baptista Assumpcao Junior
//                 'programa' => 'Psicologia Clínica (PSC)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 10229702,    // Gabriel Inticher Binkowski
//                 'programa' => 'Psicologia Clínica (PSC)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 2088882,    // Gilberto Safra
//                 'programa' => 'Psicologia Clínica (PSC)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 962810,    // Isabel Cristina Gomes
//                 'programa' => 'Psicologia Clínica (PSC)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 90061,    // Ivonise Fernandes da Motta
//                 'programa' => 'Psicologia Clínica (PSC)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 774870,    // Leila Salomao de la Plata Cury Tardivo
//                 'programa' => 'Psicologia Clínica (PSC)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 2163232,    // Márcia Helena da Silva Mélo
//                 'programa' => 'Psicologia Clínica (PSC)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 3355606,    // Maria Livia Tourinho Moretto
//                 'programa' => 'Psicologia Clínica (PSC)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 7915462,    // Marina Ferreira da Rosa Ribeiro
//                 'programa' => 'Psicologia Clínica (PSC)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 2177912,    // Miriam Debieux Rosa
//                 'programa' => 'Psicologia Clínica (PSC)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 1637670,    // Pablo de Carvalho Godoy Castanho
//                 'programa' => 'Psicologia Clínica (PSC)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 7908099,    // Renatha el Rafihi Ferreira
//                 'programa' => 'Psicologia Clínica (PSC)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 8400178,    // Roselene Ricachenevsky Gurski
//                 'programa' => 'Psicologia Clínica (PSC)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 576462,    // Briseida Dogo de Resende
//                 'programa' => 'Psicologia Experimental (PSE)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 5032360,    // Daniela Maria Oliveira Bonci
//                 'programa' => 'Psicologia Experimental (PSE)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 3463419,    // Danilo Silva Guimarães
//                 'programa' => 'Psicologia Experimental (PSE)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 24021,    // Dora Selma Fix Ventura
//                 'programa' => 'Psicologia Experimental (PSE)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 94249,    // Eduardo Benedicto Ottoni
//                 'programa' => 'Psicologia Experimental (PSE)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 77048,    // Emma Otta
//                 'programa' => 'Psicologia Experimental (PSE)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 60112,    // Fernando Cesar Capovilla
//                 'programa' => 'Psicologia Experimental (PSE)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 821761,    // Gerson Aparecido Yukio Tomanari
//                 'programa' => 'Psicologia Experimental (PSE)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 7811859,    // Jaroslava Varella Valentova
//                 'programa' => 'Psicologia Experimental (PSE)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 92021,    // Livia Mathias Simao
//                 'programa' => 'Psicologia Experimental (PSE)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 3333504,    // Marcelo Frota Lobato Benvenutti
//                 'programa' => 'Psicologia Experimental (PSE)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 3257875,    // Marcelo Fernandes da Costa
//                 'programa' => 'Psicologia Experimental (PSE)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 57139,    // Maria Helena Leite Hunziker
//                 'programa' => 'Psicologia Experimental (PSE)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 1489943,    // Maria Martha Costa Hübner
//                 'programa' => 'Psicologia Experimental (PSE)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 3648589,    // Mirella Gualtieri
//                 'programa' => 'Psicologia Experimental (PSE)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 2557349,    // Miriam Garcia Mijares
//                 'programa' => 'Psicologia Experimental (PSE)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 2142872,    // Nelson Ernesto Coelho Junior
//                 'programa' => 'Psicologia Experimental (PSE)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 8314620,    // Nicolas Gerard Chaline
//                 'programa' => 'Psicologia Experimental (PSE)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 1505248,    // Patricia Izar Mauro
//                 'programa' => 'Psicologia Experimental (PSE)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 2352751,    // Paula Debert
//                 'programa' => 'Psicologia Experimental (PSE)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 48622,    // Vera Silvia Raad Bussab
//                 'programa' => 'Psicologia Experimental (PSE)',
//                 'funcao' => 'Docentes do Programa',
//             ],
//             [
//                 'codpes' => 5098371,    // Moisés do Nascimento Soares
//                 'programa' => 'Neurociências e Comportamento (NEC)',
//                 'funcao' => 'Secretários(as) do Programa',
//             ],
//             [
//                 'codpes' => 2806023,    // Fernanda Leite Paiva
//                 'programa' => 'Psicologia Escolar e do Desenvolvimento Humano (PSA)',
//                 'funcao' => 'Secretários(as) do Programa',
//             ],
//             [
//                 'codpes' => 2503151,    // Cláudia Lima Rodrigues da Rocha
//                 'programa' => 'Psicologia Clínica (PSC)',
//                 'funcao' => 'Secretários(as) do Programa',
//             ],
//             [
//                 'codpes' => 2438068,    // Fátima Tereza Gonçalves
//                 'programa' => 'Psicologia Experimental (PSE)',
//                 'funcao' => 'Secretários(as) do Programa',
//             ],
//             [
//                 'codpes' => 2487800,    // Teresa Cristina de Oliveira Peres
//                 'programa' => 'Psicologia Social (PST)',
//                 'funcao' => 'Secretários(as) do Programa',
//             ],
//             [
//                 'codpes' => 3257875,    // Marcelo Fernandes da Costa
//                 'programa' => 'Neurociências e Comportamento (NEC)',
//                 'funcao' => 'Coordenadores do Programa',
//             ],
//             [
//                 'codpes' => 5032360,    // Daniela Maria Oliveira Bonci
//                 'programa' => 'Neurociências e Comportamento (NEC)',
//                 'funcao' => 'Coordenadores do Programa',
//             ],
//             [
//                 'codpes' => 4864151,    // Luciana Maria Caetano
//                 'programa' => 'Psicologia Escolar e do Desenvolvimento Humano (PSA)',
//                 'funcao' => 'Coordenadores do Programa',
//             ],
//             [
//                 'codpes' =>  907384,    // Leopoldo Pereira Fulgencio Junior
//                 'programa' => 'Psicologia Escolar e do Desenvolvimento Humano (PSA)',
//                 'funcao' => 'Coordenadores do Programa',
//             ],
//             [
//                 'codpes' => 2103422,    // Christian Ingo Lenz Dunker
//                 'programa' => 'Psicologia Clínica (PSC)',
//                 'funcao' => 'Coordenadores do Programa',
//             ],
//             [
//                 'codpes' => 5593721,    // Andrés Eduardo Aguirre Antúnez
//                 'programa' => 'Psicologia Clínica (PSC)',
//                 'funcao' => 'Coordenadores do Programa',
//             ],
//             [
//                 'codpes' =>  576462,    // Briseida Dogo de Resende
//                 'programa' => 'Psicologia Experimental (PSE)',
//                 'funcao' => 'Coordenadores do Programa',
//             ],
//             [
//                 'codpes' => 7811859,    // Jaroslava Varella Valentova
//                 'programa' => 'Psicologia Experimental (PSE)',
//                 'funcao' => 'Coordenadores do Programa',
//             ],
//             [
//                 'codpes' => 1138617,    // Fabio de Oliveira
//                 'programa' => 'Psicologia Social (PST)',
//                 'funcao' => 'Coordenadores do Programa',
//             ],
//             [
//                 'codpes' => 1502231,    // Maria Cristina Gonçalves Vicentin
//                 'programa' => 'Psicologia Social (PST)',
//                 'funcao' => 'Coordenadores do Programa',
//             ],
//             [
//                 'codpes' => 7190868,    // Carina Müller Sasse
//                 'funcao' => 'Serviço de Pós-Graduação',
//             ],
//             [
//                 'codpes' => 2789780,    // Ronaldo Correa de Assis
//                 'funcao' => 'Serviço de Pós-Graduação',
//             ],
//             [
//                 'codpes' => 3656230,    // Joana Darc de Lima Barbosa
//                 'funcao' => 'Serviço de Pós-Graduação',
//             ],
//             [
//                 'codpes' => 5032360,    // Daniela Maria Oliveira Bonci
//                 'funcao' => 'Coordenadores da Pós-Graduação',
//             ],
//             [
//                 'codpes' => 4864151,    // Luciana Maria Caetano
//                 'funcao' => 'Coordenadores da Pós-Graduação',
//             ],
//         ];

//         // adiciona registros na tabela user_programa
//         foreach ($funcoes as $funcao) {
//             $user = User::findOrCreateFromReplicado($funcao['codpes']);
//             if ($user)
//                 $user->associarProgramaFuncao($funcao['programa'] ?? null, $funcao['funcao']);
//         }
//     }
}
