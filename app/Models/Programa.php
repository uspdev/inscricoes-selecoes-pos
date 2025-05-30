<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class Programa extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'descricao',
        'email_secretaria',
        'link_acompanhamento',
    ];

    // uso no crud generico
    protected const fields = [
        [
            'name' => 'nome',
            'label' => 'Nome',
        ],
        [
            'name' => 'descricao',
            'label' => 'Descrição',
        ],
        [
            'name' => 'email_secretaria',
            'label' => 'E-mail da Secretaria',
        ],
        [
            'name' => 'link_acompanhamento',
            'label' => 'Link de Acompanhamento',
        ],
    ];

    // uso no crud generico
    public static function getFields()
    {
        return self::fields;
    }

    /**
     * retorna todos os programas
     * utilizado nas views common, para o select
     */
    public static function allToSelect()
    {
        $programas = self::get();
        $ret = [];
        foreach ($programas as $programa)
            if (Gate::allows('programas.view', $programa)) {
                $ret[$programa->id] = $programa->nome;
            }
        return $ret;
    }

    public function obterResponsaveis()
    {
        return [
            [
                'funcao' => 'Docentes do Programa',
                'users' => $this->users()->wherePivot('funcao', 'Docentes do Programa')->orderBy('id')->get(),
            ],
            [
                'funcao' => 'Secretários(as) do Programa',
                'users' => $this->users()->wherePivot('funcao', 'Secretários(as) do Programa')->orderBy('id')->get(),
            ],
            [
                'funcao' => 'Coordenadores do Programa',
                'users' => $this->users()->wherePivot('funcao', 'Coordenadores do Programa')->orderBy('id')->get(),
            ],
            [
                'funcao' => 'Serviço de Pós-Graduação',
                'users' => DB::table('user_programa')->join('users', 'user_programa.user_id', '=', 'users.id')->where('user_programa.funcao', 'Serviço de Pós-Graduação')->orderBy('user_programa.id')->get(),    // não dá pra partir de Programa::, pelo fato de programa_id ser null na tabela relacional
            ],
            [
                'funcao' => 'Coordenadores da Pós-Graduação',
                'users' => DB::table('user_programa')->join('users', 'user_programa.user_id', '=', 'users.id')->where('user_programa.funcao', 'Coordenadores da Pós-Graduação')->orderBy('user_programa.id')->get(),    // não dá pra partir de Programa::, pelo fato de programa_id ser null na tabela relacional
            ],
        ];
    }

    /**
     * Programa possui seleções
     */
    public function selecoes()
    {
        return $this->hasMany('App\Models\Selecao');
    }

    /**
     * Programa possui linhas de pesquisa/temas
     */
    public function linhaspesquisa()
    {
        return $this->hasMany('App\Models\LinhaPesquisa');
    }

    /**
     * relacionamento com níveis
     */
    public function niveis()
    {
        return $this->belongsToMany('App\Models\Nivel', 'nivel_programa', 'programa_id', 'nivel_id')->withTimestamps();
    }

    /**
     * relacionamento com users
     */
    public function users()
    {
        return $this->belongsToMany('App\Models\User', 'user_programa')->withPivot('funcao')->withTimestamps();
    }
}
