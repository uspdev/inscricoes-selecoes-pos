<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Disciplina;
use App\Models\Selecao;
use Uspdev\Replicado\Posgraduacao;
use Illuminate\Support\Str;
class DisciplinaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $programas = Posgraduacao::programas();
        foreach ($programas as $programa) {
            $disciplinas = Posgraduacao::catalogoDisciplinas($programa['codare']);
            foreach($disciplinas as $disciplina) {
                $sigla = "{$disciplina['sgldis']}";
                $nome = Str::limit($disciplina['nomdis'], 100);
                Disciplina::updateOrCreate(
                    ['sigla' => $sigla],
                    ['nome' => $nome]
                );
            }
        }
    }
}
