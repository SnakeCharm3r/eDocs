<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnCallRequest extends Model
{
    use HasFactory;

    protected $table = 'on_call_requests';

    protected $fillable = [
        'user_id',
        'locum_month',
        'locum_year',
        'number_of_days',
        'total_hours',
        'total_amount',
        'total_amount_payable',
        'worked_days', // Include worked_days as fillable for JSON storage
        'reason',
        'description',
        'submited_by_incharge',
        'education_level',
        'status',
        'has_special_task',
        'special_task_path',
        'special_task_original_name',
        'special_task_mime',
        'special_task_size',
    ];

    protected $casts = [
        'worked_days' => 'array',
        'total_hours' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'total_amount_payable' => 'decimal:2',
        'education_level' => 'string',
        'status' => 'string',
        'has_special_task' => 'boolean',

    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function submittedByIncharge()
    {
        return $this->belongsTo(User::class, 'submited_by_incharge');
    }

    public function workflow()
    {
        return $this->hasOne(Workflow::class, 'on_call_request_id');
    }
}
