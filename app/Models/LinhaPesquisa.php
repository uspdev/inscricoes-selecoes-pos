<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class LinhaPesquisa extends Model
{
    use HasFactory;

    # linhaspesquisa não segue convenção do laravel para nomes de tabela
    protected $table = 'linhaspesquisa';

    protected $fillable = [
        'nome',
        'programa_id',
    ];

    // uso no crud generico
    protected const fields = [
        [
            'name' => 'nome',
            'label' => 'Nome',
        ],
        [
            'name' => 'programa_id',
            'label' => 'Programa',
            'type' => 'select',
            'model' => 'Programa',
            'data' => [],
        ],
    ];

    // uso no crud generico
    public static function getFields()
    {
        $fields = self::fields;
        foreach ($fields as &$field) {
            if (substr($field['name'], -3) == '_id') {
                $class = '\\App\\Models\\' . $field['model'];
                $field['data'] = $class::allToSelect();
            }
        }
        return $fields;
    }

    /**
     * retorna todas as linhas de pesquisa/temas autorizados para o usuário
     * utilizado nas views common, para o select
     */
    public static function allToSelect()
    {
        $linhaspesquisa = self::get();
        $ret = [];
        foreach ($linhaspesquisa as $linhapesquisa)
            if (Gate::allows('linhaspesquisa.view', $linhapesquisa))
                $ret[$linhapesquisa->id] = $linhapesquisa->nome;
        return $ret;
    }

    public static function listarLinhasPesquisa(Programa $programa)
    {
        if ((!is_null($programa)) && ($programa->id > 0))
            return self::where('programa_id', $programa->id)->get();
        else
            return self::get();
    }

    /**
     * relacionamento com seleções
     */
    public function selecoes()
    {
        return $this->belongsToMany('App\Models\Selecao', 'selecao_linhapesquisa', 'linhapesquisa_id', 'selecao_id')->withTimestamps();
    }

    /**
     * relacionamento com orientadores
     */
    public function orientadores()
    {
        return $this->belongsToMany('App\Models\Orientador', 'linhapesquisa_orientador', 'linhapesquisa_id', 'orientador_id')->withTimestamps();
    }

    /**
     * Relacionamento: linha de pesquisa/tema pertence a programa
     */
    public function programa()
    {
        return $this->belongsTo('App\Models\Programa');
    }

    /**
     * relacionamento com níveis
     */
    public function niveis()
    {
        return $this->belongsToMany('App\Models\Nivel', 'nivel_linhapesquisa', 'linhapesquisa_id', 'nivel_id')->withTimestamps();
    }
}
