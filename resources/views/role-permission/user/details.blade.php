<div class="row">
    <div class="col-md-6">
        <div class="detail-row">
            <div class="detail-label">Full Name</div>
            <div>{{ $user->fname }} {{ $user->mname }} {{ $user->lname }}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Username</div>
            <div>{{ $user->username }}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Email</div>
            <div>{{ $user->email ?? 'N/A' }}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Mobile</div>
            <div>{{ $user->mobile ?? 'N/A' }}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Department</div>
            <div>{{ optional($user->department)->dept_name ?? 'N/A' }}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Job Title</div>
            <div>{{ optional($user->jobTitle)->job_title ?? 'N/A' }}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">CCBRT Code</div>
            <div>{{ $user->ccbrt_code ?? 'N/A' }}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Status</div>
            <div>
                @php
                    $statusClass = match ($user->status) {
                        'active' => 'success',
                        'inactive' => 'secondary',
                        'pending' => 'warning',
                        'deactivated' => 'danger',
                        default => 'secondary',
                    };
                @endphp
                <span class="badge bg-{{ $statusClass }}">{{ ucfirst($user->status ?? 'N/A') }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="detail-row">
            <div class="detail-label">Employment Type</div>
            <div>{{ optional($user->employmentType)->employment_type ?? 'N/A' }}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Date of Birth</div>
            <div>{{ $user->DOB ? \Carbon\Carbon::parse($user->DOB)->format('d M Y') : 'N/A' }}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Gender</div>
            <div>{{ ucfirst($user->gender ?? 'N/A') }}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Starting Date</div>
            <div>{{ $user->starting_date ? \Carbon\Carbon::parse($user->starting_date)->format('d M Y') : 'N/A' }}
            </div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Roles</div>
            <div>
                @if ($user->roles->count() > 0)
                    @foreach ($user->roles as $role)
                        <span class="badge bg-secondary me-1">{{ $role->name }}</span>
                    @endforeach
                @else
                    <span class="text-muted">No roles assigned</span>
                @endif
            </div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Professional Registration</div>
            <div>{{ $user->professional_reg_number ?? 'N/A' }}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Nationality</div>
            <div>{{ $user->nationality ?? 'N/A' }}</div>
        </div>
        @if ($user->is_locked && $user->locked_until)
            @php
                $lockedUntil = \Carbon\Carbon::parse($user->locked_until);
                $isStillLocked = \Carbon\Carbon::now()->lt($lockedUntil);
            @endphp
            @if ($isStillLocked)
                <div class="detail-row">
                    <div class="detail-label">Account Status</div>
                    <div>
                        @php
                            $minutesRemaining = \Carbon\Carbon::now()->diffInMinutes($lockedUntil);
                            $hoursRemaining = floor($minutesRemaining / 60);
                            $minsRemaining = $minutesRemaining % 60;
                        @endphp
                        <span class="badge bg-danger">
                            <i class="fas fa-lock me-1"></i>Locked
                            @if ($hoursRemaining > 0)
                                ({{ $hoursRemaining }}h {{ $minsRemaining }}m remaining)
                            @else
                                ({{ $minsRemaining }}m remaining)
                            @endif
                        </span>
                        <br>
                        <small class="text-muted">Failed attempts: {{ $user->failed_login_attempts ?? 0 }}</small>
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>

{{-- Login Attempts Section --}}
@if (isset($failedLoginAttempts) && $failedLoginAttempts->count() > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Failed Login Attempts (Last 20)
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date & Time</th>
                                    <th>IP Address</th>
                                    <th>User Agent</th>
                                    <th>Failure Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($failedLoginAttempts as $attempt)
                                    <tr>
                                        <td>
                                            <small>{{ \Carbon\Carbon::parse($attempt->attempted_at)->format('d M Y, H:i:s') }}</small>
                                        </td>
                                        <td>
                                            <code class="text-danger">{{ $attempt->ip_address }}</code>
                                        </td>
                                        <td>
                                            <small
                                                class="text-muted">{{ \Illuminate\Support\Str::limit($attempt->user_agent ?? 'N/A', 50) }}</small>
                                        </td>
                                        <td>
                                            <span
                                                class="badge bg-danger">{{ $attempt->failure_reason ?? 'Unknown' }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@else
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-info mb-0">
                <i class="fas fa-info-circle me-2"></i>
                No failed login attempts recorded for this user.
            </div>
        </div>
    </div>
@endif
