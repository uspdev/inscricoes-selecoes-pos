<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTiposArquivoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tiposarquivo', function (Blueprint $table) {
            $table->id();
            $table->string('classe_nome', 40);
            $table->string('nome', 100);
            $table->boolean('editavel')->default(true);
            $table->boolean('obrigatorio');
            $table->integer('minimo')->nullable();
            $table->boolean('aluno_especial')->default(false);    // indica se o tipo de arquivo fica disponível para alunos especiais
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tiposarquivo');
    }
}
