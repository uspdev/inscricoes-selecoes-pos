<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSelecoesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('selecoes', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->string('estado', 90);
            $table->string('descricao', 255)->nullable();
            $table->boolean('tem_taxa')->default(true);
            $table->datetime('solicitacoesisencaotaxa_datahora_inicio')->nullable();
            $table->datetime('solicitacoesisencaotaxa_datahora_fim')->nullable();
            $table->datetime('inscricoes_datahora_inicio');
            $table->datetime('inscricoes_datahora_fim');
            $table->decimal('boleto_valor', 8, 2)->nullable();
            $table->string('boleto_texto')->nullable();
            $table->date('boleto_data_vencimento')->nullable();
            $table->string('email_inscricaoaprovacao_texto')->nullable();
            $table->string('email_inscricaorejeicao_texto')->nullable();
            $table->json('template')->nullable();
            $table->json('settings')->nullable();
            $table->foreignId('categoria_id')->constrained('categorias');
            $table->foreignId('programa_id')->nullable()->constrained('programas');
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
        Schema::dropIfExists('selecoes');
    }
}
