<?php

namespace Database\Seeders;

use App\Models\Programa;
use Illuminate\Database\Seeder;
use Uspdev\CadastrosAuxiliaresClient\Contracts\ProgramasClientInterface;
use Uspdev\Replicado\Posgraduacao;

class ProgramaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $isIntegrado = config('inscricoes-selecoes-pos.integracao-cadastros-auxiliares');
        
        if(!$isIntegrado) {
            $programas = Posgraduacao::listarProgramas();
            foreach ($programas as $programa) {
                $nome = "{$programa['nomcur']}";
                $descricao = "Programa de Pós-Graduação em {$programa['nomcur']}";

                Programa::updateOrCreate(
                    ['nome' => $nome],
                    ['descricao' => $descricao] 
                );
            }
        }
        else{
            $programas = app(ProgramasClientInterface::class)->listar();
            foreach ($programas as $programa) {
                $nome = "{$programa['nomcur']} ({$programa['codslg']})";
                $descricao = "Programa de Pós-Graduação em {$programa['nomcur']} ({$programa['codslg']})";

                Programa::updateOrCreate(
                    ['nome' => $nome],
                    ['descricao' => $descricao] 
                );
            }
        }
    }
}