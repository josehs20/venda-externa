<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $fillable = ['loja_id', 'alltech_id', 'nome', 'email', 'fone1', 'fone2', 'celular'];
    
    public function loja() {
        return $this->belongsTo('App\Models\Loja');
    }
    
    public function infoCliente()
    {
        return $this->hasMany('App\Models\InfoCliente');
    }
}
