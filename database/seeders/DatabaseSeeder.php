<?php

namespace Database\Seeders;

use App\Models\Arquivo;
use App\Models\Inscricao;
use App\Models\Selecao;
use App\Models\SolicitacaoIsencaoTaxa;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // desativando eventos no seeder
        Arquivo::flushEventListeners();
        Inscricao::flushEventListeners();
        Selecao::flushEventListeners();
        SolicitacaoIsencaoTaxa::flushEventListeners();

        $this->call([
            FeriadoSeeder::class,           // adiciona feriados
            PermissionSeeder::class,        // adiciona permissions
            SetorReplicadoSeeder::class,    // adiciona todos os setores da unidade do replicado
            ProgramaSeeder::class,          // adiciona programas
            CategoriaSeeder::class,         // adiciona categorias
            FuncaoSeeder::class,            // adiciona funções
            NivelSeeder::class,             // adiciona níveis
            LinhaPesquisaSeeder::class,     // adiciona linhas de pesquisa/temas
            DisciplinaSeeder::class,        // adiciona disciplinas
            ParametroSeeder::class,         // adiciona parâmetros
            MotivoIsencaoTaxaSeeder::class, // adiciona motivos de isenção de taxa
            TipoArquivoSeeder::class,       // adiciona tipos de arquivo
        ]);
    }
}
