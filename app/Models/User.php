<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\ResetPasswordNotification($token));
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'fname',
        'mname',
        'lname',
        'email',
        //'role_id',
        'username',
        'DOB',
        'gender',
        'marital_status',
        'religion',
        'mobile',
        'job_title',
        'home_address',
        'region',
        'district',
        'professional_reg_number',
        'place_of_birth',
        'nationality',
        'house_no',
        'street',
        'box_no',
        'plot_no',
        'passport_no',
        'popular_landmark',
        'emp_id',
        'starting_date',
        'ending_date',
        'country',
        'employee_cv',
        'profile_picture',
        'signature',
        // 'marriage_certificate',
        'divorced_certificate',
        'NIN',
        'nssf_no',
        'tin_no',
        'domicile',
        'deptId',
        'employment_typeId',
        'password',
        'status',
        'ccbrt_code',
        'hec_allocation',
        'conflict_officer_role',
        'officer_details',
        'financial_interest',
        'financial_details',
        'other_interests',
        'interest_details',
        'primary_employer_ccbrt',
        'primary_employer_details',
        'court_proceedings',
        'court_details',
        'delete_status',
        'hr_detail_declare',
        'passport_numb',
        'tin_number',
        'marriage_certificate',
        // Education-related fields
        'primary_institution',
        'primary_country',
        'primary_start_year',
        'primary_completion_year',
        'primary_certificate',
        'o_level_institution',
        'o_level_country',
        'o_level_start_year',
        'o_level_completion_year',
        'o_level_certificate',
        'a_level_institution',
        'a_level_country',
        'a_level_start_year',
        'a_level_completion_year',
        'a_level_certificate',
        'certificate_institution',
        'certificate_country',
        'certificate_start_year',
        'certificate_completion_year',
        'certificate_certificate',
        'certificate_transcript',
        'diploma_institution',
        'diploma_country',
        'diploma_start_year',
        'diploma_completion_year',
        'diploma_certificate',
        'diploma_transcript',
        'degree_institution',
        'degree_country',
        'degree_start_year',
        'degree_completion_year',
        'degree_certificate',
        'degree_transcript',
        'masters_institution',
        'masters_country',
        'masters_start_year',
        'masters_completion_year',
        'masters_certificate',
        'masters_transcript',
        'phd_institution',
        'phd_country',
        'phd_start_year',
        'phd_completion_year',
        'phd_certificate',
        'phd_transcript',
        'nida',
        'driving_license',
        'transport_id',
        'voting_id',
        'other_document',
        'approvelocum',
        'platform_id',
        'primary_platform_id',
    ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'locked_until' => 'datetime',
        'last_failed_login_at' => 'datetime',
    ];


    public function department()
    {
        return $this->belongsTo(Departments::class, 'deptId');
    }

    public function employmentType()
    {
        return $this->belongsTo(EmploymentTypes::class, 'employment_typeId');
    }

    public function healthDetails()
    {
        return $this->hasMany(HealthDetails::class, 'userId'); // Adjust if necessary
    }

    public function userFamilyDetails()
    {
        return $this->hasMany(UserFamilyDetails::class, 'userId');
    }

    public function languageKnowledge()
    {
        return $this->hasMany(LanguageKnowledge::class, 'userId');
    }

    public function ccbrtRelation()
    {
        return $this->hasMany(CcbrtRelation::class, 'userId');
    }

    public function jobTitle()
    {
        return $this->belongsTo(JobTitle::class, 'job_title');
    }

    public function hecsCreated()
    {
        return $this->hasMany(Hec::class, 'createdBy');
    }

    public function hecsUpdated()
    {
        return $this->hasMany(Hec::class, 'updatedBy');
    }
    public function Change()
    {
        return $this->hasMany(ChangeRequest::class, 'userId');
    }
    public function jobDescriptions()
    {
        return $this->hasMany(JobDescription::class);
    }
    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function consCreated()
    {
        return $this->hasMany(ConsultantModel::class, 'createdBy');
    }

    public function locumAgreements()
    {
        return $this->hasMany(LocumAgreement::class, 'user_id');
    }
    public function platforms()
    {
        return $this->belongsToMany(Platform::class, 'platform_user');
    }

    public function primaryPlatform()
    {
        return $this->belongsTo(Platform::class, 'platform_id');
    }

    public function requiredLocumHours(): int
    {
        $primary = strtoupper(optional($this->primaryPlatform)->name ?? '');
        if ($primary === 'IPD') return 12;
        if ($primary === 'OPD' || $primary === 'OTD') return 8;

        // Fallback: infer from assigned platforms
        if (!$this->relationLoaded('platforms')) {
            $this->load(['platforms:id,name']);
        }
        $hasIpd = $this->platforms
            ->pluck('name')
            ->filter()
            ->contains(fn($n) => strtoupper($n) === 'IPD');

        return $hasIpd ? 12 : 8;
    }
    public function inchargeOfUnits()
    {
        return $this->hasMany(\App\Models\Unit::class, 'incharge_user_id');
    }

    public function getDisplayNameAttribute(): string
    {
        $full = trim(($this->fname ?? '') . ' ' . ($this->mname ?? '') . ' ' . ($this->lname ?? ''));
        return $full !== '' ? $full : ($this->username ?? $this->email ?? '—');
    }
}
