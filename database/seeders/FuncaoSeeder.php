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

    /**
     * Por enquanto só preenche automaticamente os Docenses do Programa, ainda não há criação automática para:
     * a) Secretários(as) do Programa
     * b) Coordenadores do Programa
     * c) Serviço de Pós-Graduação
     * d) Coordenadores da Pós-Graduação
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
}
