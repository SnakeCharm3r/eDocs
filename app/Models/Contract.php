<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;
    // protected $fillable = [
    //     'user_id',
    //     'place_of_recruitment',
    //     'duty_station',
    //     'start_date',
    //     'duration',
    //     'working_hours',
    //     'probation_period',
    //     'basic_pay',
    //     'total_gross_pay',
    //     'medical_insurance_employee',
    //     'medical_insurance_employer',
    //     'funeral_insurance_eligibility',
    // ];
    protected $fillable = [
        'contract_template_id',
        'user_id',
        'start_date',
        'end_date',
        'status',
        'place_of_recruitment',
        'duty_station',
        'duration',
        'working_hours',
        'probation_period',
        'basic_pay',
        'total_gross_pay',
        'medical_insurance_employee',
        'medical_insurance_employer',
        'funeral_insurance_eligibility'
    ];

    // public function user()
    // {
    //     return $this->belongsTo(User::class);
    // }
    // public function jobTitle()
    // {
    //     return $this->belongsTo(JobTitle::class);
    // }

    // public function department()
    // {
    //     return $this->belongsTo(Departments::class);
    // }



    public function contractTemplate()
    {
        return $this->belongsTo(ContractTemplate::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contractFields()
    {
        return $this->hasMany(ContractField::class);
    }

    public function generateDocument()
    {
        $content = $this->contractTemplate->content;

        foreach ($this->contractFields as $field) {
            $content = str_replace(
                "{{{$field->templateField->field_name}}}",
                $field->value,
                $content
            );
        }

        return $content;
    }
}
