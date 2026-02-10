<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateField extends Model
{
    use HasFactory;

    protected $fillable = ['contract_template_id', 'field_name', 'field_type', 'source'];

    public function contractTemplate()
    {
        return $this->belongsTo(ContractTemplate::class);
    }
}
