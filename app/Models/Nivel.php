<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class Nivel extends Model
{
    use HasFactory;

    # niveis não segue convenção do laravel para nomes de tabela
    protected $table = 'niveis';

    protected $fillable = [
        'nome',
    ];

    // uso no crud generico
    protected const fields = [
        [
            'name' => 'nome',
            'label' => 'Nome',
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
     * retorna todos os níveis
     * utilizado nas views common, para o select
     */
    public static function allToSelect()
    {
        return self::get();
    }

    /**
     * relacionamento com linhas de pesquisa
     */
    public function linhaspesquisa()
    {
        return $this->belongsToMany('App\Models\LinhaPesquisa', 'linhapesquisa_nivel', 'nivel_id', 'linhapesquisa_id')->withTimestamps();
    }

    /**
     * relacionamento com tipos de arquivo
     */
    public function tiposarquivo()
    {
        return $this->belongsToMany('App\Models\TipoArquivo', 'tipoarquivo_nivel', 'nivel_id', 'tipoarquivo_id')->withTimestamps();
    }
}
