<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractField extends Model
{
    use HasFactory;

    protected $fillable = ['contract_id', 'template_field_id', 'value'];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function templateField()
    {
        return $this->belongsTo(TemplateField::class);
    }
}
