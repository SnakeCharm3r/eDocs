<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractTemplate extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'type', 'content'];

    public function templateFields()
    {
        return $this->hasMany(TemplateField::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }
}
