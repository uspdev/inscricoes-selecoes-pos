<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class LocalUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'codpes',
    ];

    // uso no crud generico
    protected const fields = [
        [
            'name' => 'name',
            'label' => 'Nome',
        ],
        [
            'name' => 'codpes',
            'label' => 'Nome de Usuário',
        ],
        [
            'name' => 'email',
            'label' => 'E-mail',
        ],
        [
            'name' => 'password',
            'label' => 'Senha',
            'type' => 'password',
        ],
    ];

    public static function getFields()
    {
        $fields = SELF::fields;
        return $fields;
    }
}
