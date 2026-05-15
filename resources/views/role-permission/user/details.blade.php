@php
    $initials = strtoupper(substr($user->fname ?? '', 0, 1) . substr($user->lname ?? '', 0, 1));
    if (empty(trim($initials))) $initials = strtoupper(substr($user->username ?? '?', 0, 2));
    $avatarColors = ['#3b82f6','#059669','#d97706','#8b5cf6','#ec4899','#06b6d4','#f97316','#6366f1','#14b8a6','#e11d48'];
    $colorIdx = crc32($user->username ?? '') % count($avatarColors);
    $avatarBg = $avatarColors[abs($colorIdx)];
    $statusClass = match ($user->status) {
        'active' => 'success',
        'inactive' => 'secondary',
        'pending' => 'warning',
        'deactivated' => 'danger',
        default => 'secondary',
    };
    $isLocked = $user->is_locked && $user->locked_until && \Carbon\Carbon::now()->lt(\Carbon\Carbon::parse($user->locked_until));
@endphp

<style>
    .detail-profile-header {
        text-align: center; padding: 1.5rem 1rem 1rem;
        background: linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%);
        border-radius: 12px; margin-bottom: 1rem;
    }
    .detail-avatar {
        width: 70px; height: 70px; border-radius: 16px;
        display: inline-flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 1.5rem; color: #fff;
        text-transform: uppercase; margin-bottom: .5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,.15);
    }
    .detail-tabs .nav-link {
        font-size: .84rem; font-weight: 600; color: #6b7280;
        padding: .55rem 1rem; border: none; border-bottom: 2px solid transparent;
        transition: all .2s;
    }
    .detail-tabs .nav-link:hover { color: #374151; }
    .detail-tabs .nav-link.active {
        color: #1d4ed8; background: transparent;
        border-bottom: 2px solid #3b82f6;
    }
    .detail-field {
        padding: .5rem 0; display: flex; align-items: baseline; gap: .75rem;
        border-bottom: 1px solid #f3f4f6;
    }
    .detail-field:last-child { border-bottom: none; }
    .detail-field-label {
        font-size: .75rem; font-weight: 700; color: #9ca3af;
        text-transform: uppercase; letter-spacing: .04em;
        min-width: 110px; flex-shrink: 0;
    }
    .detail-field-value { font-size: .875rem; color: #1f2937; word-break: break-word; }
    .detail-section-title {
        font-size: .78rem; font-weight: 700; color: #374151;
        text-transform: uppercase; letter-spacing: .05em;
        padding-bottom: .4rem; margin-bottom: .5rem; margin-top: .25rem;
        border-bottom: 2px solid #e5e7eb;
        display: flex; align-items: center; gap: .4rem;
    }
    .role-pill-modal {
        display: inline-block; padding: .22rem .6rem; border-radius: 20px;
        font-size: 11px; font-weight: 600; margin: 2px;
    }
    .role-pill-modal.super-admin { background: #fee2e2; color: #991b1b; }
    .role-pill-modal.admin { background: #dbeafe; color: #1e40af; }
    .role-pill-modal.hr { background: #d1fae5; color: #065f46; }
    .role-pill-modal.it { background: #e0e7ff; color: #3730a3; }
    .role-pill-modal.coo { background: #fef3c7; color: #92400e; }
    .role-pill-modal.cfo { background: #fce7f3; color: #9d174d; }
    .role-pill-modal.cms { background: #f3e8ff; color: #6b21a8; }
    .role-pill-modal.finance { background: #ccfbf1; color: #134e4a; }
    .role-pill-modal.default { background: #f3f4f6; color: #374151; }
    .perm-badge {
        display: inline-block; padding: .18rem .5rem; border-radius: 6px;
        font-size: 11px; font-weight: 500; margin: 2px;
        background: #f9fafb; color: #374151; border: 1px solid #e5e7eb;
    }
</style>

{{-- Profile Header --}}
<div class="detail-profile-header">
    <div class="detail-avatar" style="background:{{ $avatarBg }}">{{ $initials }}</div>
    <h5 class="fw-bold mb-1">{{ $user->fname }} {{ $user->mname }} {{ $user->lname }}</h5>
    <div class="text-muted mb-2" style="font-size:.85rem;">
        {{ optional($user->jobTitle)->job_title ?? 'No Job Title' }}
        @if($user->department)
            &middot; {{ $user->department->dept_name }}
        @endif
    </div>
    <span class="badge bg-{{ $statusClass }} me-1">{{ ucfirst($user->status ?? 'N/A') }}</span>
    @if ($isLocked)
        <span class="badge bg-danger"><i class="fas fa-lock me-1"></i>Locked</span>
    @endif
</div>

{{-- Tabs --}}
<ul class="nav nav-tabs detail-tabs mb-3" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-profile" role="tab">Profile</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-roles" role="tab">Roles & Permissions</a></li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tab-security" role="tab">
            Security
            @if(isset($failedLoginAttempts) && $failedLoginAttempts->count() > 0)
                <span class="badge bg-danger ms-1" style="font-size:10px;">{{ $failedLoginAttempts->count() }}</span>
            @endif
        </a>
    </li>
</ul>

<div class="tab-content">
    {{-- Tab: Profile --}}
    <div class="tab-pane fade show active" id="tab-profile" role="tabpanel">
        <div class="row">
            <div class="col-md-6">
                <div class="detail-section-title"><i class="fas fa-user me-1"></i> Personal</div>
                <div class="detail-field">
                    <span class="detail-field-label">Full Name</span>
                    <span class="detail-field-value">{{ $user->fname }} {{ $user->mname }} {{ $user->lname }}</span>
                </div>
                <div class="detail-field">
                    <span class="detail-field-label">Username</span>
                    <span class="detail-field-value">{{ $user->username }}</span>
                </div>
                <div class="detail-field">
                    <span class="detail-field-label">Email</span>
                    <span class="detail-field-value">{{ $user->email ?? 'N/A' }}</span>
                </div>
                <div class="detail-field">
                    <span class="detail-field-label">Mobile</span>
                    <span class="detail-field-value">{{ $user->mobile ?? 'N/A' }}</span>
                </div>
                <div class="detail-field">
                    <span class="detail-field-label">Gender</span>
                    <span class="detail-field-value">{{ ucfirst($user->gender ?? 'N/A') }}</span>
                </div>
                <div class="detail-field">
                    <span class="detail-field-label">Date of Birth</span>
                    <span class="detail-field-value">{{ $user->DOB ? \Carbon\Carbon::parse($user->DOB)->format('d M Y') : 'N/A' }}</span>
                </div>
                <div class="detail-field">
                    <span class="detail-field-label">Nationality</span>
                    <span class="detail-field-value">{{ $user->nationality ?? 'N/A' }}</span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="detail-section-title"><i class="fas fa-briefcase me-1"></i> Professional</div>
                <div class="detail-field">
                    <span class="detail-field-label">CCBRT Code</span>
                    <span class="detail-field-value"><span style="background:#ecfdf5;color:#059669;padding:.15rem .45rem;border-radius:6px;font-size:.82rem;font-weight:600;">{{ $user->ccbrt_code ?? 'N/A' }}</span></span>
                </div>
                <div class="detail-field">
                    <span class="detail-field-label">Entity</span>
                    <span class="detail-field-value">
                        @if($user->assignedEntities && $user->assignedEntities->count() > 0)
                            {{ $user->assignedEntities->pluck('name')->implode(', ') }}
                        @elseif($user->assignedEntity)
                            {{ $user->assignedEntity->name ?? 'N/A' }}
                        @else
                            N/A
                        @endif
                    </span>
                </div>
                <div class="detail-field">
                    <span class="detail-field-label">Department</span>
                    <span class="detail-field-value">{{ optional($user->department)->dept_name ?? 'N/A' }}</span>
                </div>
                <div class="detail-field">
                    <span class="detail-field-label">Job Title</span>
                    <span class="detail-field-value">{{ optional($user->jobTitle)->job_title ?? 'N/A' }}</span>
                </div>
                <div class="detail-field">
                    <span class="detail-field-label">Employment</span>
                    <span class="detail-field-value">{{ optional($user->employmentType)->employment_type ?? 'N/A' }}</span>
                </div>
                <div class="detail-field">
                    <span class="detail-field-label">Starting Date</span>
                    <span class="detail-field-value">{{ $user->starting_date ? \Carbon\Carbon::parse($user->starting_date)->format('d M Y') : 'N/A' }}</span>
                </div>
                <div class="detail-field">
                    <span class="detail-field-label">Professional Reg.</span>
                    <span class="detail-field-value">{{ $user->professional_reg_number ?? 'N/A' }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab: Roles & Permissions --}}
    <div class="tab-pane fade" id="tab-roles" role="tabpanel">
        <div class="detail-section-title"><i class="fas fa-user-tag"></i> Assigned Roles</div>
        <div class="mb-3">
            @if ($user->roles->count() > 0)
                @foreach ($user->roles as $role)
                    @php
                        $rl = strtolower($role->name);
                        $rc = match(true) {
                            str_contains($rl, 'super-admin') => 'super-admin',
                            str_contains($rl, 'admin') => 'admin',
                            $rl === 'hr' => 'hr',
                            $rl === 'it' => 'it',
                            $rl === 'coo' => 'coo',
                            $rl === 'cfo' => 'cfo',
                            $rl === 'cms' => 'cms',
                            str_contains($rl, 'finance') => 'finance',
                            default => 'default',
                        };
                    @endphp
                    <span class="role-pill-modal {{ $rc }}">{{ $role->name }}</span>
                @endforeach
            @else
                <span class="text-muted" style="font-size:.85rem;">No roles assigned</span>
            @endif
        </div>

        <div class="detail-section-title"><i class="fas fa-shield-alt"></i> Effective Permissions</div>
        <div>
            @php $permissions = $user->getAllPermissions(); @endphp
            @if ($permissions->count() > 0)
                <div class="d-flex flex-wrap gap-1">
                    @foreach ($permissions->sortBy('name') as $perm)
                        <span class="perm-badge">{{ $perm->name }}</span>
                    @endforeach
                </div>
            @else
                <span class="text-muted" style="font-size:.85rem;">No permissions</span>
            @endif
        </div>
    </div>

    {{-- Tab: Security --}}
    <div class="tab-pane fade" id="tab-security" role="tabpanel">
        {{-- Lock Status --}}
        @if ($isLocked)
            @php
                $lockedUntil = \Carbon\Carbon::parse($user->locked_until);
                $minutesRemaining = \Carbon\Carbon::now()->diffInMinutes($lockedUntil);
                $hoursRemaining = floor($minutesRemaining / 60);
                $minsRemaining = $minutesRemaining % 60;
            @endphp
            <div class="alert alert-danger d-flex align-items-center py-2 mb-3" style="border-radius:10px;font-size:.85rem;">
                <i class="fas fa-lock me-2"></i>
                <div>
                    <strong>Account Locked</strong> &mdash;
                    @if ($hoursRemaining > 0)
                        {{ $hoursRemaining }}h {{ $minsRemaining }}m remaining
                    @else
                        {{ $minsRemaining }}m remaining
                    @endif
                    <br>
                    <small class="text-muted">Failed attempts: {{ $user->failed_login_attempts ?? 0 }}</small>
                </div>
            </div>
        @else
            <div class="alert alert-success d-flex align-items-center py-2 mb-3" style="border-radius:10px;font-size:.85rem;">
                <i class="fas fa-unlock me-2"></i>
                <strong>Account Unlocked</strong>
            </div>
        @endif

        {{-- Failed Login Attempts --}}
        <div class="detail-section-title"><i class="fas fa-exclamation-triangle me-1"></i> Failed Login Attempts (Last 20)</div>
        @if (isset($failedLoginAttempts) && $failedLoginAttempts->count() > 0)
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0" style="font-size:.82rem;">
                    <thead class="table-light">
                        <tr>
                            <th>Date & Time</th>
                            <th>IP Address</th>
                            <th>User Agent</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($failedLoginAttempts as $attempt)
                            <tr>
                                <td><small>{{ \Carbon\Carbon::parse($attempt->attempted_at)->format('d M Y, H:i:s') }}</small></td>
                                <td><code class="text-danger">{{ $attempt->ip_address }}</code></td>
                                <td><small class="text-muted">{{ \Illuminate\Support\Str::limit($attempt->user_agent ?? 'N/A', 50) }}</small></td>
                                <td><span class="badge bg-danger">{{ $attempt->failure_reason ?? 'Unknown' }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-3 text-muted" style="font-size:.85rem;">
                <i class="fas fa-check-circle d-block mb-1" style="font-size:1.5rem;color:#059669;"></i>
                No failed login attempts recorded.
            </div>
        @endif
    </div>
</div>

{{-- Footer with edit link --}}
<div class="border-top mt-3 pt-3 d-flex justify-content-between align-items-center">
    <a href="{{ route('user.edit', $user->id) }}" class="btn btn-sm btn-outline-primary" style="border-radius:8px;">
        <i class="fas fa-edit me-1"></i>Edit Profile
    </a>
    <a href="{{ route('users.showEditForm', $user->id) }}" class="btn btn-sm btn-outline-success" style="border-radius:8px;">
        <i class="fas fa-user-edit me-1"></i>Edit Roles
    </a>
</div>
