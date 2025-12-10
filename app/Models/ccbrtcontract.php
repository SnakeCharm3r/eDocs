<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CcbrtContract extends Model
{
    use HasFactory;
    
    protected $table = 'ccbrt_contracts';

    // Mass assignable fields
    protected $fillable = [
        'title',
        'contract_type',
        'file_path',
        'uploaded_contract_path',
        'cost',
        'division_id',
        'department_id',
        'vendor_id',
        'creation_date',
        'duration_months',
        'currency',
        'end_date',
        'status',
        'likelihood_rating',
        'impact_if_not_requested',
        'overall_risk',
        'renewal_status',
        'created_by',
    ];

    /**
     * Contract belongs to a Division
     */
    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * Contract belongs to a Department
     */
    public function department()
    {
        return $this->belongsTo(Departments::class);
    }

    /**
     * Contract belongs to a Vendor
     */
    public function vendor()
    {
        return $this->belongsTo(CcbrtVendor::class, 'vendor_id');
    }

    /**
     * Contract was created by a User
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function workflows()
    {
    return $this->hasMany(Workflow::class, 'ccbrt_contract_id');
    }

    public function user()
    {
    return $this->belongsTo(User::class, 'created_by'); 
    }

}
