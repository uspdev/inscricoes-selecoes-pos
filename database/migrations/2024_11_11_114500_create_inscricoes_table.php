<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInscricoesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inscricoes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('estado', 90);
            $table->json('extras')->nullable();
            $table->boolean('boleto_enviado')->default(0);

            /* Relacionamentos */
            $table->foreignId('selecao_id')->constrained('selecoes');
            $table->foreignId('linhapesquisa_id')->constrained('linhaspesquisa');

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
        Schema::dropIfExists('inscricoes');
    }
}
