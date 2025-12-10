@php
    // Always show section, but use blank data if not approved
    $showHECData = $showHECData ?? $hecApproved ?? false;
@endphp
<div class="border p-4 mb-4 rounded shadow-sm" style="background-color: #ffffff;">
    <div class="mb-4 pb-3 border-bottom">
        <h4 class="section-title mb-2" style="color: #495057; font-size: 1.5rem; font-weight: 600;">
            3. Review by
            {{ isset($approver) && $approver->hasRole('chief_accountant') ? 'Chief Accountant' : 'Committee Member' }}
        </h4>
        @if (isset($hecInitiatedByCurrentUser) && $hecInitiatedByCurrentUser)
            <p class="text-muted mb-0" style="font-size: 0.9rem;">
                <i class="fas fa-info-circle me-1"></i>To be filled by HEC Member (who initiated this requisition)
            </p>
        @endif
    </div>
    <div>
        {{-- Display Department Information for HEC Members --}}
        @if (auth()->user()->hasAnyRole(['coo', 'cms', 'chief_accountant']))
            @php
                // Get department from requisition (prefer direct department relationship, fallback to user's department)
                $requisitionDepartment =
                    $requisition->department ?? ($requisition->user->department ?? null);
            @endphp
            @if ($requisitionDepartment)
                <div class="alert alert-info mb-3">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-building me-2 mt-1 text-success"></i>
                        <div class="flex-grow-1">
                            <strong class="d-block mb-1">
                                <i class="fas fa-sitemap me-1 text-success"></i>Department:
                                {{ $requisitionDepartment->dept_name }}
                            </strong>
                            @if (isset($initiatedByHEC) && $initiatedByHEC && $hecInitiator)
                                <small class="text-muted d-block">
                                    <i class="fas fa-user me-1 text-success"></i>Initiated by:
                                    <strong>{{ $hecInitiator->fname }}
                                        {{ $hecInitiator->lname }}</strong>
                                    <span class="badge bg-info ms-1">HEC Member</span>
                                </small>
                                <small class="text-muted d-block mt-1">
                                    <i class="fas fa-user-tie me-1 text-success"></i>For Line
                                    Manager:
                                    <strong>{{ $requisition->user->fname ?? '' }}
                                        {{ $requisition->user->lname ?? '' }}</strong>
                                    <span class="badge bg-secondary ms-1">Line Manager</span>
                                </small>
                            @elseif ($requisition->user)
                                <small class="text-muted d-block">
                                    <i class="fas fa-user me-1 text-success"></i>Initiated by:
                                    <strong>{{ $requisition->user->fname }}
                                        {{ $requisition->user->lname }}</strong>
                                    @if ($requisition->user->hasRole('line-manager'))
                                        <span class="badge bg-secondary ms-1">Line
                                            Manager</span>
                                    @endif
                                </small>
                            @endif
                            @if ($requisitionDepartment->hec && $requisitionDepartment->hec->hec_level_name)
                                <small class="text-muted d-block mt-1">
                                    <i class="fas fa-level-up-alt me-1 text-success"></i>HEC
                                    Level:
                                    <strong>{{ $requisitionDepartment->hec->hec_level_name }}</strong>
                                </small>
                            @endif
                            @if ($requisitionDepartment->hecMember)
                                <small class="text-muted d-block mt-1">
                                    <i class="fas fa-user-shield me-1 text-success"></i>Mapped
                                    HEC Member:
                                    <strong>{{ $requisitionDepartment->hecMember->fname }}
                                        {{ $requisitionDepartment->hecMember->lname }}</strong>
                                </small>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        @endif

        <div class="mb-3">
            <label class="form-label fw-semibold">Funding Status</label>
            <div class="d-flex gap-3">
                <div class="form-check">
                    <input type="radio" name="funding_status" id="funding_with_funds_{{ $position ?? 'default' }}"
                        value="with_funds" class="form-check-input"
                        {{ $requisition->budget_approved == 1 ? 'checked' : '' }}
                        {{ !$canEditHEC ? 'disabled' : '' }} onchange="toggleFundingSections()">
                    <label class="form-check-label" for="funding_with_funds_{{ $position ?? 'default' }}">With
                        Funds</label>
                </div>
                <div class="form-check">
                    <input type="radio" name="funding_status" id="funding_no_funds_{{ $position ?? 'default' }}"
                        value="no_funds" class="form-check-input"
                        {{ $requisition->budget_approved != 1 ? 'checked' : '' }}
                        {{ !$canEditHEC ? 'disabled' : '' }} onchange="toggleFundingSections()">
                    <label class="form-check-label" for="funding_no_funds_{{ $position ?? 'default' }}">No Funds</label>
                </div>
            </div>
        </div>

        {{-- Section 3a: With Funds --}}
        <div id="section_3a_{{ $position ?? 'default' }}" class="border rounded p-3 mb-3"
            style="display: {{ $requisition->budget_approved == 1 ? 'block' : 'none' }};">
            <h6 class="mb-3"><i class="fas fa-check-circle me-2 text-success"></i>3a)
                Position
                is in
                budget and funds are confirmed</h6>
            <div class="mb-3">
                <label class="form-label fw-semibold">Decision</label>
                <div class="d-flex gap-3">
                    <div class="form-check">
                        <input type="radio" name="hec_objection" value="in_budget_no_objection"
                            id="no_objection_{{ $position ?? 'default' }}" class="form-check-input"
                            {{ (isset($initiatedByHEC) && $initiatedByHEC && $requisition->budget_approved == 1 && !$requisition->hec_objection) || $requisition->hec_objection == 'in_budget_no_objection' ? 'checked' : '' }}
                            {{ !$canEditHEC ? 'disabled' : '' }}>
                        <label class="form-check-label" for="no_objection_{{ $position ?? 'default' }}">No objection to
                            start
                            recruitment/renewal</label>
                    </div>
                    <div class="form-check">
                        <input type="radio" name="hec_objection" value="in_budget_with_objection"
                            id="with_objection_{{ $position ?? 'default' }}" class="form-check-input"
                            {{ $requisition->hec_objection == 'in_budget_with_objection' ? 'checked' : '' }}
                            {{ !$canEditHEC ? 'disabled' : '' }}>
                        <label class="form-check-label" for="with_objection_{{ $position ?? 'default' }}">Objection to
                            start
                            recruitment/renewal</label>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Comments</label>
                @if (!$canEditHEC && empty($requisition->hec_member_comment))
                    <div class="p-2 bg-light rounded text-muted">N/A</div>
                @else
                    <textarea name="hec_member_comment" id="hec_member_comment_3a_{{ $position ?? 'default' }}" class="form-control" rows="4"
                        placeholder="Enter your comments here..." {{ !$canEditHEC ? 'readonly disabled' : '' }}
                        style="min-height: 100px; resize: vertical;">{{ isset($initiatedByHEC) && $initiatedByHEC && $requisition->budget_approved == 1 && !$requisition->hec_objection ? '' : $requisition->hec_member_comment ?? '' }}</textarea>
                @endif
                @error('hec_member_comment')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <small class="text-muted"><i class="fas fa-info-circle me-1"></i>After step 3a) is
                signed,
                document to be returned to HOD and forwarded to HR; if no objection HR will
                start
                process.</small>
        </div>

        {{-- Section 3b: No Funds - Objection --}}
        <div id="section_3b_{{ $position ?? 'default' }}" class="border rounded p-3 mb-3"
            style="display: {{ $requisition->budget_approved != 1 && $requisition->hec_objection == 'Objection to start' ? 'block' : 'none' }};">
            <h6 class="mb-3"><i class="fas fa-times-circle me-2 text-danger"></i>3b)
                Position is
                not
                in
                budget and/or no funds are confirmed</h6>
            <div class="mb-3">
                <div class="form-check">
                    <input type="radio" name="hec_objection" value="Objection to start"
                        id="objection_start_{{ $position ?? 'default' }}" class="form-check-input"
                        {{ $requisition->hec_objection == 'Objection to start' ? 'checked' : '' }}
                        {{ !$canEditHEC ? 'disabled' : '' }}>
                    <label class="form-check-label" for="objection_start_{{ $position ?? 'default' }}">Objection to start
                        recruitment/renewal</label>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Comments</label>
                @if (!$canEditHEC && empty($requisition->hec_member_comment))
                    <div class="p-2 bg-light rounded text-muted">N/A</div>
                @else
                    <textarea name="hec_member_comment" class="form-control" rows="4" placeholder="Enter your comments here..."
                        {{ !$canEditHEC ? 'readonly disabled' : '' }} style="min-height: 100px; resize: vertical;">{{ $requisition->hec_member_comment ?? '' }}</textarea>
                @endif
                @error('hec_member_comment')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <small class="text-muted"><i class="fas fa-info-circle me-1"></i>After step 3b) is
                signed,
                document to be brought to HR for filing.</small>
        </div>

        {{-- Section 3c: No Funds - No Objection --}}
        <div id="section_3c_{{ $position ?? 'default' }}" class="border rounded p-3 mb-3"
            style="display: {{ $requisition->budget_approved != 1 && $requisition->hec_objection == 'No objection to start' ? 'block' : 'none' }};">
            <h6 class="mb-3"><i class="fas fa-exclamation-circle me-2 text-warning"></i>3c)
                Position
                is
                not in budget and/or no funds are confirmed</h6>
            <div class="mb-3">
                <div class="form-check">
                    <input type="radio" name="hec_objection" value="No objection to start"
                        id="no_objection_start_{{ $position ?? 'default' }}" class="form-check-input"
                        {{ $requisition->hec_objection == 'No objection to start' ? 'checked' : '' }}
                        {{ !$canEditHEC ? 'disabled' : '' }}>
                    <label class="form-check-label" for="no_objection_start_{{ $position ?? 'default' }}">No objection to
                        start
                        recruitment/renewal</label>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Justification Type <span class="text-danger">*</span></label>
                <div class="d-flex gap-3">
                    <div class="form-check">
                        <input type="radio" name="hec_justification_type" value="no_financial_implications"
                            id="justification_no_financial_{{ $position ?? 'default' }}" class="form-check-input"
                            {{ $requisition->hec_justification_type == 'no_financial_implications' ? 'checked' : '' }}
                            {{ !$canEditHEC ? 'disabled' : '' }}>
                        <label class="form-check-label" for="justification_no_financial_{{ $position ?? 'default' }}">
                            No financial implications
                        </label>
                    </div>
                    <div class="form-check">
                        <input type="radio" name="hec_justification_type" value="other_alternative_for_money"
                            id="justification_alternative_{{ $position ?? 'default' }}" class="form-check-input"
                            {{ $requisition->hec_justification_type == 'other_alternative_for_money' ? 'checked' : '' }}
                            {{ !$canEditHEC ? 'disabled' : '' }}>
                        <label class="form-check-label" for="justification_alternative_{{ $position ?? 'default' }}">
                            Other alternative for money
                        </label>
                    </div>
                </div>
                @error('hec_justification_type')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
                <small class="text-muted d-block mt-2">
                    <i class="fas fa-info-circle me-1"></i>
                    If justification is provided, the form will go directly to HR. 
                    If no justification is provided, it will go to CFO → CEO → HR.
                </small>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Justification Details</label>
                @if (!$canEditHEC && empty($requisition->hec_justification))
                    <div class="p-2 bg-light rounded text-muted">N/A</div>
                @else
                    <textarea name="hec_justification" class="form-control" rows="3"
                        placeholder="Provide details about the justification..."
                        {{ !$canEditHEC ? 'readonly disabled' : '' }}>{{ $requisition->hec_justification ?? '' }}</textarea>
                @endif
                @error('hec_justification')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Proposed Funding</label>
                @if (!$canEditHEC && empty($requisition->hec_proposed_funding))
                    <div class="p-2 bg-light rounded text-muted">N/A</div>
                @else
                    <textarea name="hec_proposed_funding" class="form-control" rows="3"
                        {{ !$canEditHEC ? 'readonly disabled' : '' }}>{{ $requisition->hec_proposed_funding ?? '' }}</textarea>
                @endif
                @error('hec_proposed_funding')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <small class="text-muted"><i class="fas fa-info-circle me-1"></i>After step 3c) is
                signed,
                document to be brought to CFO for financial review, thereafter to CEO.</small>
        </div>

        @if ($showHECData && isset($approver) && $approver)
            <hr class="my-4">
            <h6 class="mb-3"><i
                    class="fas fa-user-tie me-2 text-success"></i>{{ $approver->hasRole('chief_accountant') ? 'Chief Accountant' : 'HEC Member' }}
                Signature</h6>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold text-muted small">Name</label>
                    <div class="p-2 bg-light rounded">
                        {{ $approver->fname }} {{ $approver->lname }}
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold text-muted small">Signature</label>
                    <div class="p-2 bg-light rounded">
                        @if ($approver->signature)
                            <img src="data:image/png;base64,{{ $approver->signature }}"
                                alt="Signature"
                                style="max-width: 150px; max-height: 60px; object-fit: contain;">
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold text-muted small">Date</label>
                    @if ($canEditHEC)
                        <input type="date" name="hec_signature_date" class="form-control"
                            value="{{ $approver->updated_at ? \Carbon\Carbon::parse($approver->updated_at)->format('Y-m-d') : '' }}">
                        @error('hec_signature_date')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    @else
                        <div class="p-2 bg-light rounded">
                            <span class="text-muted">{{ $approver->updated_at ? \Carbon\Carbon::parse($approver->updated_at)->format('Y-m-d') : 'N/A' }}</span>
                        </div>
                    @endif
                </div>
            </div>
        @else
            @if (!$showHECData && !$canEditHEC)
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    HEC approver information will be displayed once the requisition is reviewed by HEC.
                </div>
            @elseif (!$showHECData && $canEditHEC)
                <div class="alert alert-warning mt-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    HEC approver will be assigned automatically when the requisition reaches HEC review stage.
                </div>
            @endif
        @endif
    </div>
</div>

