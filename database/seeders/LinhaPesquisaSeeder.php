<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LinhaPesquisa;
use App\Models\Nivel;
use App\Models\NivelLinhaPesquisa;
use App\Models\Programa;
use App\Models\Selecao;
use Uspdev\Replicado\Posgraduacao;
class LinhaPesquisaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $linhasPesquisa = Posgraduacao::programas();
            foreach ($linhasPesquisa as $linhaPesquisa) {
                $nome = "{$linhaPesquisa['nomare']}";
                $nomeCurso = "{$linhaPesquisa['nomcur']}";
                $programa = Programa::where('nome', 'LIKE', "{$nomeCurso}%")->first();
                if($programa)
                {
                    LinhaPesquisa::updateOrCreate(
                        ['nome' => $nome],
                        ['programa_id' => $programa->id] 
                    );
                }
            }

        // adiciona registros na tabela nivel_linhapesquisa
        foreach (LinhaPesquisa::all() as $linhapesquisa)
            foreach (Nivel::all() as $nivel)
                $linhapesquisa->niveis()->attach($nivel->id);

        // $selecao_SELECAO2025ALUNOREGULARNEC = Selecao::where('nome', 'Seleção 2025 Aluno Regular NEC')->first();

        // // adiciona registros na tabela selecao_nivellinhapesquisa
        // $nivellinhapesquisa_id_MESTRADOSENSACAOPERCEPCAOEMOVIMENTO = NivelLinhaPesquisa::whereHas('nivel', function ($query) { $query->where('nome', 'Mestrado'); })->whereHas('linhapesquisa', function ($query) { $query->where('nome', 'Sensação, Percepção e Movimento'); })->first()->id;
        // $selecao_SELECAO2025ALUNOREGULARNEC->niveislinhaspesquisa()->attach($nivellinhapesquisa_id_MESTRADOSENSACAOPERCEPCAOEMOVIMENTO);
    }
}
