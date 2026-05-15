@extends('layouts.template')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <style>
        /* Single dropdown arrow for length select */
        .dataTables_wrapper .dataTables_length select,
        #ccbrtPoliciesTable_wrapper .dataTables_length select,
        #otherOrgPoliciesTable_wrapper .dataTables_length select {
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            appearance: none !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e") !important;
            background-repeat: no-repeat !important;
            background-position: right 0.5rem center !important;
            background-size: 16px 12px !important;
            padding-right: 2rem !important;
        }
        .policy-card {
            transition: all 0.2s ease;
            border: 1px solid #e9ecef;
        }

        .policy-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .filter-card {
            background: #f8f9fa;
            border: none;
        }

        .no-sort {
            cursor: default !important;
        }

        .no-sort::after {
            display: none !important;
        }

        .policy-clickable-row { cursor: pointer; }

        /* DataTable enhancements */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 1rem;
        }

        .dataTables_wrapper .dataTables_info {
            padding-top: 0.75rem;
        }

        .dataTables_wrapper .dataTables_paginate {
            padding-top: 0.75rem;
        }

        /* Responsive table styling */
        table.dataTable {
            border-collapse: collapse !important;
        }

        table.dataTable thead th {
            border-bottom: 2px solid #dee2e6;
        }

        table.dataTable tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* Policy content in modal: show list numbers and bullets (Quill/editor output) */
        #modalPolicyContent ol,
        #modalPolicyContent ul {
            list-style-position: outside !important;
            padding-left: 1.5rem !important;
            margin-bottom: 0.5rem !important;
        }
        #modalPolicyContent ol {
            list-style-type: decimal !important;
        }
        #modalPolicyContent ul {
            list-style-type: disc !important;
        }
        #modalPolicyContent li {
            display: list-item !important;
            margin-bottom: 0.25rem;
        }
        /* PDF view modal (organization policies) */
        .policy-pdf-modal-dialog { max-width: 900px; width: 100%; }
        .policy-pdf-modal-body { min-height: 75vh; padding: 0; }
        .policy-pdf-iframe { width: 100%; height: 75vh; border: none; }

        /* ── Org Policy Category Accordion ── */
        .accordion-button:not(.collapsed) {
            background-color: #f0faf4;
            color: #198754;
        }
        .accordion-button:focus {
            box-shadow: 0 0 0 0.2rem rgba(0, 122, 51, 0.25);
        }
        .org-category-group {
            overflow: hidden;
            border-radius: .75rem !important;
            border-color: #dfe7e2;
            box-shadow: 0 6px 14px rgba(22, 63, 39, 0.04);
        }
        .org-category-group + .org-category-group {
            margin-top: .85rem;
        }
        .org-category-group .accordion-header {
            background: #fff;
        }
        .org-category-group .accordion-button {
            gap: .15rem;
            padding: .85rem 1rem;
            align-items: center;
        }
        .org-category-group .accordion-button::after {
            margin-left: .85rem;
        }
        .org-category-body {
            background: #fff;
        }
        .org-category-table-wrap {
            border-top: 1px solid #edf2ee;
        }
        .org-category-table-wrap .table {
            margin-bottom: 0;
        }
        .org-category-table-wrap .table thead th {
            font-size: .78rem;
            font-weight: 600;
            color: #44524a;
            white-space: nowrap;
            padding-top: .95rem;
            padding-bottom: .95rem;
        }
        .org-category-table-wrap .table tbody td {
            padding-top: 1rem;
            padding-bottom: 1rem;
        }
        .org-category-children-section {
            position: relative;
            padding: 1rem 1rem 1rem 1.15rem;
            background: linear-gradient(180deg, #fbfefd 0%, #f5fbf7 100%);
        }
        .org-category-children-section.has-parent-policies {
            border-top: 1px solid #e6efe9;
        }
        .org-category-children-section::before {
            content: '';
            position: absolute;
            top: 1rem;
            bottom: 1rem;
            left: .6rem;
            width: 2px;
            border-radius: 999px;
            background: linear-gradient(180deg, #8bc9a0 0%, rgba(139, 201, 160, 0.12) 100%);
        }
        .org-category-children-label {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            margin-bottom: .85rem;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #5b6f62;
        }
        .org-category-children-accordion {
            display: flex;
            flex-direction: column;
            gap: .75rem;
        }

        /* Depth 0 – root categories */
        .org-cat-depth-0 {
            border-left: 4px solid #28a745 !important;
        }
        .org-cat-depth-0 > .accordion-header .accordion-button {
            font-weight: 500;
            font-size: .92rem;
        }

        /* Depth 1 – first sub-level */
        .org-cat-depth-1 {
            border-left: 3px solid #74c990 !important;
            margin-left: .25rem;
        }
        .org-cat-depth-1 > .accordion-header .accordion-button {
            font-weight: 400;
            font-size: .875rem;
            background-color: #fafffe;
        }
        .org-cat-depth-1 > .accordion-header .accordion-button:not(.collapsed) {
            background-color: #f0faf4;
        }

        /* Depth 2+ – deeper nesting */
        .org-cat-depth-2,
        .org-cat-depth-3,
        .org-cat-depth-4 {
            border-left: 2px solid #b8e0c4 !important;
            margin-left: .5rem;
        }
        .org-cat-depth-2 > .accordion-header .accordion-button,
        .org-cat-depth-3 > .accordion-header .accordion-button,
        .org-cat-depth-4 > .accordion-header .accordion-button {
            font-weight: 400;
            font-size: .85rem;
            background-color: #fdfdfd;
        }
        .org-cat-depth-2 > .accordion-header .accordion-button,
        .org-cat-depth-3 > .accordion-header .accordion-button,
        .org-cat-depth-4 > .accordion-header .accordion-button,
        .org-cat-depth-1 > .accordion-header .accordion-button {
            min-height: 58px;
        }

        /* Admin action buttons beside accordion header */
        .org-cat-admin-btns {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 0 10px;
            border-left: 1px solid #dee2e6;
            background: inherit;
            flex-shrink: 0;
        }
        @media (max-width: 768px) {
            .org-category-group .accordion-button {
                padding: .8rem .85rem;
            }
            .org-category-table-wrap .table thead th,
            .org-category-table-wrap .table tbody td {
                font-size: .8rem;
            }
            .org-category-children-section {
                padding: .85rem .85rem .85rem 1rem;
            }
        }

    </style>
@endpush

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            @php
                $showDraftTab = ($canManageOtherOrg ?? false) && ($showOrgSection ?? false);
                $activeTab = request('tab', 'active');

                if (!in_array($activeTab, ['active', 'archived', 'drafts'], true)) {
                    $activeTab = 'active';
                }
                if ($activeTab === 'archived' && !$canManage) {
                    $activeTab = 'active';
                }
                if ($activeTab === 'drafts' && !$showDraftTab) {
                    $activeTab = 'active';
                }
            @endphp
            {{-- Tabs Navigation --}}
            @if ($canManage)
                <ul class="nav nav-tabs mb-4" id="policyTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab === 'active' ? 'active' : '' }}" id="active-tab" data-bs-toggle="tab"
                            data-bs-target="#active-policies" type="button" role="tab" aria-controls="active-policies"
                            aria-selected="{{ $activeTab === 'active' ? 'true' : 'false' }}">
                            <i class="fas fa-check-circle me-2"></i>Active Policies
                        </button>
                    </li>
                    @if ($showDraftTab)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $activeTab === 'drafts' ? 'active' : '' }}" id="drafts-tab" data-bs-toggle="tab"
                                data-bs-target="#draft-policies" type="button" role="tab" aria-controls="draft-policies"
                                aria-selected="{{ $activeTab === 'drafts' ? 'true' : 'false' }}">
                                <i class="fas fa-file-pen me-2"></i>Draft Policies
                                <span class="badge bg-warning text-dark ms-1">{{ $otherOrgDraftPolicies->count() ?? 0 }}</span>
                            </button>
                        </li>
                    @endif
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab === 'archived' ? 'active' : '' }}" id="archived-tab" data-bs-toggle="tab" data-bs-target="#archived-policies"
                            type="button" role="tab" aria-controls="archived-policies" aria-selected="{{ $activeTab === 'archived' ? 'true' : 'false' }}">
                            <i class="fas fa-archive me-2"></i>Archived Policies
                            @if (isset($ccbrtArchivedPolicies) && isset($otherOrgArchivedPolicies))
                                @php
                                    $archivedCount = (($showCCBRTSection ?? true) ? $ccbrtArchivedPolicies->count() : 0) + (($showOrgSection ?? false) ? $otherOrgArchivedPolicies->count() : 0);
                                @endphp
                                <span class="badge bg-secondary ms-1">{{ $archivedCount }}</span>
                            @endif
                        </button>
                    </li>
                </ul>
            @endif

            {{-- Tab Content --}}
            <div class="tab-content" id="policyTabsContent">
                {{-- Active Policies Tab --}}
                <div class="tab-pane fade {{ $activeTab === 'active' ? 'show active' : '' }}" id="active-policies" role="tabpanel" aria-labelledby="active-tab">
                    @if ($showCCBRTSection ?? true)
                        {{-- CCBRT Policies Section (hide card header when type=ccbrt to avoid duplicate with page title) --}}
                        <div class="card shadow-sm mb-4 border-0">
                                <div class="card-header bg-white border-bottom">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                        <h5 class="mb-0 text-dark">
                                            <i class="fas fa-building me-2 text-success"></i>CCBRT Policies
                                        </h5>
                                        @if (($isHR ?? false) || ($isSuperAdmin ?? false))
                                        <div>
                                            <a href="{{ route('policies.create', ['type' => 'ccbrt']) }}" class="btn btn-success btn-sm">
                                                <i class="fas fa-plus me-1"></i>Add CCBRT Policy
                                            </a>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped align-middle" id="ccbrtPoliciesTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 50px;" class="no-sort">#</th>
                                                <th>Policy</th>
                                                @if ($canManage)
                                                    <th>Status</th>
                                                @endif
                                                <th>Created</th>
                                                <th class="text-center no-sort" style="width: 150px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse (($ccbrtActivePolicies ?? $ccbrtPolicies) as $policy)
                                                <tr class="policy-clickable-row {{ $policy->isArchived() ? 'table-secondary' : '' }}"
                                                    data-is-pdf="{{ $policy->content_type === 'pdf' ? '1' : '0' }}"
                                                    data-pdf-url="{{ ($policy->content_type === 'pdf' && $policy->pdf_path) ? asset('storage/' . $policy->pdf_path) : '' }}"
                                                    data-policy-id="{{ $policy->id }}"
                                                    data-policy-title="{{ $policy->title }}"
                                                    data-policy-content="{{ $policy->content }}"
                                                    data-policy-description="{{ $policy->description ?? '' }}"
                                                    data-policy-type="ccbrt"
                                                    data-policy-view-count="0">
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>
                                                        <div>
                                                            {{ Str::limit($policy->title, 60) }}
                                                            @if ($policy->document_code)
                                                                <br><small
                                                                    class="text-muted">{{ $policy->document_code }}</small>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    @if ($canManage)
                                                        <td>
                                                            <span
                                                                class="badge {{ $policy->isActive() ? 'bg-success' : 'bg-secondary' }}">
                                                                {{ ucfirst($policy->status) }}
                                                            </span>
                                                        </td>
                                                    @endif
                                                    <td data-order="{{ $policy->created_at->format('Y-m-d H:i:s') }}">
                                                        {{ $policy->created_at->format('d M Y') }}
                                                        <br>
                                                        <small
                                                            class="text-muted">{{ $policy->created_at->diffForHumans() }}</small>
                                                    </td>
                                                    <td class="text-end" onclick="event.stopPropagation()">
                                                        <div class="d-flex gap-1 justify-content-end flex-wrap">
                                                            @if ($canManageCCBRT && (!isset($isCOO) || !$isCOO))
                                                                <a href="{{ route('policies.edit', $policy->id) }}?type=ccbrt"
                                                                    class="btn btn-sm btn-outline-warning" title="Edit">
                                                                    <i class="fas fa-edit text-success"></i>
                                                                </a>
                                                                @if ($policy->isActive())
                                                                    <button type="button" class="btn btn-sm btn-outline-secondary" title="Archive"
                                                                        data-bs-toggle="modal" data-bs-target="#archivePolicyModal"
                                                                        data-policy-id="{{ $policy->id }}" data-policy-type="ccbrt"
                                                                        data-policy-title="{{ $policy->title }}">
                                                                        <i class="fas fa-archive text-success"></i>
                                                                    </button>
                                                                @elseif ($policy->isArchived())
                                                                    <button type="button" class="btn btn-sm btn-outline-info" title="Restore"
                                                                        data-bs-toggle="modal" data-bs-target="#restorePolicyModal"
                                                                        data-policy-id="{{ $policy->id }}" data-policy-type="ccbrt"
                                                                        data-policy-title="{{ $policy->title }}">
                                                                        <i class="fas fa-undo text-success"></i>
                                                                    </button>
                                                                @endif
                                                                @if ($isSuperAdmin)
                                                                    <button type="button" class="btn btn-sm btn-outline-danger" title="Delete"
                                                                        data-bs-toggle="modal" data-bs-target="#deletePolicyModal"
                                                                        data-policy-id="{{ $policy->id }}" data-policy-type="ccbrt"
                                                                        data-policy-title="{{ $policy->title }}">
                                                                        <i class="fas fa-trash-alt text-success"></i>
                                                                    </button>
                                                                @endif
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="{{ $canManage ? 5 : 4 }}" class="text-center py-5">
                                                        <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                                        <h5 class="text-muted">No CCBRT Policies Found</h5>
                                                        <p class="text-muted">
                                                            @if ($canManageCCBRT)
                                                                Click "Add CCBRT Policy" to create a new policy.
                                                            @else
                                                                No CCBRT policies are available for your department.
                                                            @endif
                                                        </p>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                </div>
                        </div>
                    @endif

                    @if ($showOrgSection ?? false)
                        {{-- Organization Policies Section - Grouped by Section --}}
                        <div class="card shadow-sm mb-4 border-0">
                                <div class="card-header bg-white border-bottom">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                        <h5 class="mb-0 text-dark">
                                            <i class="fas fa-globe me-2 text-success"></i>Organization Policies
                                        </h5>
                                        <div class="d-flex gap-2 flex-wrap">
                                            @if ($canManageOtherOrg ?? false)
                                            <a href="{{ route('policies.create', ['type' => 'other_organization']) }}" class="btn btn-success btn-sm">
                                                <i class="fas fa-plus me-1"></i>Add Organization Policy
                                            </a>
                                            @endif
                                            @if($isSuperAdmin || $isCOO)
                                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#manageCategoriesPanel" aria-expanded="false">
                                                <i class="fas fa-tags me-1"></i>Manage Sections
                                            </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">

                                @if ($canManageOtherOrg ?? false)
                                    <div class="border rounded-3 bg-light-subtle px-3 py-3 mb-3">
                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                            <div>
                                                <div class="fw-semibold text-dark mb-1">
                                                    <i class="fas fa-envelope me-2 text-primary"></i>Manage Email Alerts
                                                </div>
                                            </div>
                                            <form action="{{ route('policies.other-org-email-notifications.update') }}" method="POST" class="d-flex flex-wrap align-items-center gap-2 ms-auto">
                                                @csrf
                                                <input type="hidden" name="tab" value="{{ $activeTab }}">
                                                <div class="form-check form-switch m-0">
                                                    <input class="form-check-input" type="checkbox" role="switch"
                                                        id="otherOrgEmailNotificationsSwitch"
                                                        name="other_org_policy_email_notifications_enabled"
                                                        value="1"
                                                        {{ ($otherOrgEmailsEnabled ?? true) ? 'checked' : '' }}>
                                                    <label class="form-check-label small fw-semibold ms-1" for="otherOrgEmailNotificationsSwitch">
                                                        Enable emails on create and update
                                                    </label>
                                                </div>
                                                <span class="badge {{ ($otherOrgEmailsEnabled ?? true) ? 'bg-success' : 'bg-secondary' }}">
                                                    {{ ($otherOrgEmailsEnabled ?? true) ? 'Enabled' : 'Disabled' }}
                                                </span>
                                                <button type="submit" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-save me-1"></i>Save
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endif

                                @php
                                    $activePolicies = $otherOrgActivePolicies ?? $otherOrgPolicies;
                                    $categoriesById = ($policyCategories ?? collect())->keyBy('id');
                                    $groupedPolicies = $activePolicies->groupBy(function($policy) use ($categoriesById) {
                                        $categoryId = $policy->policy_category_id;
                                        return ($categoryId && $categoriesById->has($categoryId)) ? $categoryId : 0;
                                    });
                                    $categoryChildren = ($policyCategories ?? collect())
                                        ->sortBy('sort_order')
                                        ->groupBy(function ($category) {
                                            return $category->parent_id ?? 0;
                                        });
                                    $rootPolicyCategories = $categoryChildren->get(0, collect());
                                    $categoryHasVisiblePolicies = function ($categoryId) use (&$categoryHasVisiblePolicies, $groupedPolicies, $categoryChildren) {
                                        if ($groupedPolicies->get($categoryId, collect())->isNotEmpty()) {
                                            return true;
                                        }

                                        foreach ($categoryChildren->get($categoryId, collect()) as $childCategory) {
                                            if ($categoryHasVisiblePolicies($childCategory->id)) {
                                                return true;
                                            }
                                        }

                                        return false;
                                    };
                                    $categoryPolicyTotal = function ($categoryId) use (&$categoryPolicyTotal, $groupedPolicies, $categoryChildren) {
                                        $total = $groupedPolicies->get($categoryId, collect())->count();

                                        foreach ($categoryChildren->get($categoryId, collect()) as $childCategory) {
                                            $total += $categoryPolicyTotal($childCategory->id);
                                        }

                                        return $total;
                                    };
                                @endphp

                                @if($isSuperAdmin || $isCOO)
                                <style>
                                    /* ── Section tree ── */
                                    .cat-tree-root,
                                    .cat-tree-children { list-style:none; padding:0; margin:0; }
                                    .cat-tree-root { padding: 8px 4px; }

                                    /* Indent + vertical connector */
                                    .cat-tree-children {
                                        padding-left: 28px;
                                        position: relative;
                                    }
                                    .cat-tree-children::before {
                                        content: '';
                                        position: absolute;
                                        left: 14px;
                                        top: 0;
                                        bottom: 8px;
                                        width: 1px;
                                        background: #ced4da;
                                    }

                                    /* Horizontal connector for each child */
                                    .cat-tree-children > .cat-tree-item {
                                        position: relative;
                                    }
                                    .cat-tree-children > .cat-tree-item::before {
                                        content: '';
                                        position: absolute;
                                        left: -14px;
                                        top: 15px;
                                        width: 14px;
                                        height: 1px;
                                        background: #ced4da;
                                    }

                                    .cat-tree-row {
                                        transition: background .1s;
                                        cursor: default;
                                    }
                                    .cat-tree-row:hover { background: #f8f9fa; }
                                    .cat-tree-row.cat-tree-new {
                                        background: #e7f7ed;
                                        box-shadow: inset 0 0 0 1px #8bc9a0;
                                    }
                                    .cat-tree-icon { width: 18px; text-align: center; }
                                </style>
                                <div class="collapse mb-3" id="manageCategoriesPanel">
                                    <div class="card border-secondary">
                                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                            <span class="fw-semibold"><i class="fas fa-sitemap me-2 text-secondary"></i>Section Tree</span>
                                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                                <i class="fas fa-plus me-1"></i>Add Section
                                            </button>
                                        </div>
                                        <div class="card-body p-0">
                                            @if($rootPolicyCategories->isEmpty())
                                                <p class="text-muted text-center py-3 mb-0">No sections yet.</p>
                                            @else
                                                <ul class="cat-tree-root">
                                                    @foreach($rootPolicyCategories as $cat)
                                                        @include('policies._category_tree_node', [
                                                            'cat'                 => $cat,
                                                            'groupedPolicies'     => $groupedPolicies,
                                                            'categoryChildren'    => $categoryChildren,
                                                            'categoryPolicyTotal' => $categoryPolicyTotal,
                                                        ])
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endif

                                {{-- Search bar --}}
                                @if($activePolicies->isNotEmpty())
                                <div class="mb-3">
                                    <div class="input-group input-group-sm" style="max-width:420px;">
                                        <span class="input-group-text bg-white border-end-0">
                                            <i class="fas fa-search text-muted" style="font-size:.8rem;"></i>
                                        </span>
                                        <input type="text" id="orgPolicySearch"
                                               class="form-control border-start-0 ps-0"
                                               placeholder="Search section, sub-section or policy name…"
                                               autocomplete="off">
                                        <button type="button" id="orgPolicySearchClear"
                                                class="btn btn-outline-secondary d-none" title="Clear search">
                                            <i class="fas fa-times" style="font-size:.8rem;"></i>
                                        </button>
                                    </div>
                                    <div id="orgSearchNoResults" class="text-muted small mt-2 d-none">
                                        No policies match your search.
                                    </div>
                                </div>
                                @endif

                                @if($activePolicies->isEmpty())
                                    <div class="text-center py-5">
                                        <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">No Organization Policies Found</h5>
                                        <p class="text-muted">
                                            @if ($canManageOtherOrg)
                                                Click "Add Organization Policy" to create a new policy.
                                            @else
                                                No organization policies are available.
                                            @endif
                                        </p>
                                    </div>
                                @else
                                    {{-- Accordion for each section --}}
                                    <div class="accordion" id="orgPolicyCategoryAccordion">
                                        @foreach($rootPolicyCategories as $cat)
                                            @include('policies._org_category_group', [
                                                'cat'                        => $cat,
                                                'depth'                      => 0,
                                                'groupedPolicies'            => $groupedPolicies,
                                                'categoryChildren'           => $categoryChildren,
                                                'categoryHasVisiblePolicies' => $categoryHasVisiblePolicies,
                                                'categoryPolicyTotal'        => $categoryPolicyTotal,
                                                'canManage'                  => $canManage,
                                                'isSuperAdmin'               => $isSuperAdmin,
                                                'isCOO'                      => $isCOO,
                                            ])
                                        @endforeach

                                    </div>
                                    @php
                                        $uncategorizedPolicies = $groupedPolicies->get(0, collect());
                                    @endphp
                                    @if($uncategorizedPolicies->isNotEmpty())
                                        <div class="card border-0 shadow-sm mt-3">
                                            <div class="card-header bg-light d-flex justify-content-between align-items-center gap-2 flex-wrap">
                                                <span class="fw-semibold">Without Section</span>

                                            </div>
                                            <div class="card-body p-0">
                                                <div class="table-responsive">
                                                    <table class="table table-hover table-striped align-middle mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th style="width: 50px;">#</th>
                                                                <th>Policy</th>
                                                                <th>Entity</th>
                                                                <th>Department</th>
                                                                @if ($canManage)
                                                                    <th>Status</th>
                                                                @endif
                                                                <th>Created</th>
                                                                <th class="text-center" style="width: 150px;">Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($uncategorizedPolicies as $policy)
                                                                @include('policies._org_policy_row', ['policy' => $policy, 'loop' => $loop])
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endif

                                </div>
                        </div>
                    @endif
                </div>
                {{-- End Active Policies Tab --}}

                @if ($showDraftTab)
                    <div class="tab-pane fade {{ $activeTab === 'drafts' ? 'show active' : '' }}" id="draft-policies" role="tabpanel" aria-labelledby="drafts-tab">
                        <div class="card shadow-sm mb-4 border-0">
                                <div class="card-header bg-white border-bottom">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                        <h5 class="mb-0 text-dark">
                                            <i class="fas fa-file-pen me-2 text-warning"></i>Draft Organization Policies
                                        </h5>
                                        <span class="badge bg-warning text-dark">{{ $otherOrgDraftPolicies->count() ?? 0 }} Draft{{ (($otherOrgDraftPolicies->count() ?? 0) === 1) ? '' : 's' }}</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped align-middle" id="draftOtherOrgPoliciesTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 50px;" class="no-sort">#</th>
                                                <th>Title</th>
                                                <th>Entity</th>
                                                <th>Departments</th>
                                                <th>Status</th>
                                                <th>Created</th>
                                                <th class="text-center no-sort">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($otherOrgDraftPolicies ?? [] as $policy)
                                                @include('policies._org_policy_row', ['policy' => $policy, 'loop' => $loop])
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="text-center py-5">
                                                        <i class="fas fa-file-signature fa-3x text-muted mb-3"></i>
                                                        <h5 class="text-muted">No Draft Organization Policies Found</h5>
                                                        <p class="text-muted mb-0">Draft policies stay hidden from regular users until you publish them.</p>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                </div>
                        </div>
                    </div>
                @endif

                {{-- Archived Policies Tab --}}
                @if ($canManage)
                    <div class="tab-pane fade {{ $activeTab === 'archived' ? 'show active' : '' }}" id="archived-policies" role="tabpanel" aria-labelledby="archived-tab">
                        @if ($showOrgSection ?? false)
                            {{-- Archived Organization Policies Section --}}
                            <div class="card shadow-sm mb-4 border-0">
                                    <div class="card-header bg-white border-bottom">
                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                            <h5 class="mb-0 text-dark">
                                                <i class="fas fa-globe me-2 text-success"></i>Archived Organization Policies
                                            </h5>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-striped align-middle" id="archivedOtherOrgPoliciesTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 50px;" class="no-sort">#</th>
                                                    <th>Title</th>
                                                    <th>Entity</th>
                                                    <th>Departments</th>
                                                    <th>Status</th>
                                                    <th>Created</th>
                                                    <th class="text-center no-sort">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($otherOrgArchivedPolicies ?? [] as $policy)
                                                    <tr class="policy-clickable-row table-secondary"
                                                        data-is-pdf="{{ $policy->content_type === 'pdf' ? '1' : '0' }}"
                                                        data-pdf-url="{{ ($policy->content_type === 'pdf' && $policy->pdf_path) ? asset('storage/' . $policy->pdf_path) : '' }}"
                                                        data-policy-id="{{ $policy->id }}"
                                                        data-policy-title="{{ $policy->title }}"
                                                        data-policy-content="{{ $policy->content }}"
                                                        data-policy-description="{{ $policy->description ?? '' }}"
                                                        data-policy-type="other_organization"
                                                        data-policy-view-count="{{ $policy->view_count ?? 0 }}">
                                                        <td>{{ $loop->iteration }}</td>
                                                        <td>
                                                            <div>
                                                                {{ Str::limit($policy->title, 60) }}
                                                                @if ($policy->document_code)
                                                                    <br><small
                                                                        class="text-muted">{{ $policy->document_code }}</small>
                                                                @endif
                                                            </div>
                                                        </td>
                                                        <td>
                                                            @if ($policy->division)
                                                                {{ $policy->division->name }}
                                                            @elseif ($policy->is_global)
                                                                <span class="text-muted">All Entities</span>
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if ($policy->departments && $policy->departments->isNotEmpty())
                                                                @foreach ($policy->departments->take(2) as $department)
                                                                    <span
                                                                        class="badge bg-success mb-1">{{ $department->dept_name }}</span>
                                                                @endforeach
                                                                @if ($policy->departments->count() > 2)
                                                                    <span
                                                                        class="badge bg-light text-dark">+{{ $policy->departments->count() - 2 }}
                                                                        more</span>
                                                                @endif
                                                            @else
                                                                <span class="badge bg-warning text-dark">No
                                                                    Department</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-secondary">Archived</span>
                                                        </td>
                                                        <td>
                                                            {{ $policy->created_at->format('d M Y') }}
                                                            <br>
                                                            <small
                                                                class="text-muted">{{ $policy->created_at->diffForHumans() }}</small>
                                                        </td>
                                                        <td class="text-end" onclick="event.stopPropagation()">
                                                            <div class="d-flex gap-1 justify-content-end flex-wrap">
                                                                @if ($canManageOtherOrg)
                                                                    <a href="{{ route('policies.edit', $policy->id) }}?type=other_organization"
                                                                        class="btn btn-sm btn-outline-warning" title="Edit">
                                                                        <i class="fas fa-edit text-success"></i>
                                                                    </a>
                                                                    <button type="button" class="btn btn-sm btn-outline-info" title="Restore"
                                                                        data-bs-toggle="modal" data-bs-target="#restorePolicyModal"
                                                                        data-policy-id="{{ $policy->id }}" data-policy-type="other_organization"
                                                                        data-policy-title="{{ $policy->title }}">
                                                                        <i class="fas fa-undo text-success"></i>
                                                                    </button>
                                                                @endif
                                                                @if ($isSuperAdmin)
                                                                    <button type="button" class="btn btn-sm btn-outline-danger" title="Delete"
                                                                        data-bs-toggle="modal" data-bs-target="#deletePolicyModal"
                                                                        data-policy-id="{{ $policy->id }}" data-policy-type="other_organization"
                                                                        data-policy-title="{{ $policy->title }}">
                                                                        <i class="fas fa-trash-alt text-success"></i>
                                                                    </button>
                                                                @endif
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="8" class="text-center py-5">
                                                            <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                                            <h5 class="text-muted">No Archived Organization Policies Found
                                                            </h5>
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    </div>
                            </div>
                        @endif

                        @if ($showCCBRTSection ?? true)
                            {{-- Archived CCBRT Policies Section --}}
                            <div class="card shadow-sm mb-4 border-0">
                                    <div class="card-header bg-white border-bottom">
                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                            <h5 class="mb-0 text-dark">
                                                <i class="fas fa-building me-2 text-success"></i>Archived CCBRT Policies
                                            </h5>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-striped align-middle" id="archivedCcbrtPoliciesTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 50px;" class="no-sort">#</th>
                                                    <th>Title</th>
                                                    <th>Status</th>
                                                    <th>Created</th>
                                                    <th class="text-center no-sort">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($ccbrtArchivedPolicies ?? [] as $policy)
                                                    <tr class="policy-clickable-row table-secondary"
                                                        data-is-pdf="{{ $policy->content_type === 'pdf' ? '1' : '0' }}"
                                                        data-pdf-url="{{ ($policy->content_type === 'pdf' && $policy->pdf_path) ? asset('storage/' . $policy->pdf_path) : '' }}"
                                                        data-policy-id="{{ $policy->id }}"
                                                        data-policy-title="{{ $policy->title }}"
                                                        data-policy-content="{{ $policy->content }}"
                                                        data-policy-description="{{ $policy->description ?? '' }}"
                                                        data-policy-type="ccbrt"
                                                        data-policy-view-count="0">
                                                        <td>{{ $loop->iteration }}</td>
                                                        <td>
                                                            <div>
                                                                {{ Str::limit($policy->title, 60) }}
                                                                @if ($policy->document_code)
                                                                    <br><small
                                                                        class="text-muted">{{ $policy->document_code }}</small>
                                                                @endif
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-secondary">Archived</span>
                                                        </td>
                                                        <td>
                                                            {{ $policy->created_at->format('d M Y') }}
                                                            <br>
                                                            <small
                                                                class="text-muted">{{ $policy->created_at->diffForHumans() }}</small>
                                                        </td>
                                                        <td class="text-end" onclick="event.stopPropagation()">
                                                            <div class="d-flex gap-1 justify-content-end flex-wrap">
                                                                @if ($canManageCCBRT && (!isset($isCOO) || !$isCOO))
                                                                    <a href="{{ route('policies.edit', $policy->id) }}?type=ccbrt"
                                                                        class="btn btn-sm btn-outline-warning" title="Edit">
                                                                        <i class="fas fa-edit text-success"></i>
                                                                    </a>
                                                                    <button type="button" class="btn btn-sm btn-outline-info" title="Restore"
                                                                        data-bs-toggle="modal" data-bs-target="#restorePolicyModal"
                                                                        data-policy-id="{{ $policy->id }}" data-policy-type="ccbrt"
                                                                        data-policy-title="{{ $policy->title }}">
                                                                        <i class="fas fa-undo text-success"></i>
                                                                    </button>
                                                                @endif
                                                                @if ($isSuperAdmin)
                                                                    <button type="button" class="btn btn-sm btn-outline-danger" title="Delete"
                                                                        data-bs-toggle="modal" data-bs-target="#deletePolicyModal"
                                                                        data-policy-id="{{ $policy->id }}" data-policy-type="ccbrt"
                                                                        data-policy-title="{{ $policy->title }}">
                                                                        <i class="fas fa-trash-alt text-success"></i>
                                                                    </button>
                                                                @endif
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="5" class="text-center py-5">
                                                            <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                                            <h5 class="text-muted">No Archived CCBRT Policies Found</h5>
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    </div>
                            </div>
                        @endif
                    </div>
                @endif
                {{-- End Archived Policies Tab --}}
            </div>
            {{-- End Tab Content --}}
        </div>
    </div>

    {{-- View Policy Modal (for text content) --}}
    <div class="modal fade" id="viewPolicyModal" tabindex="-1" aria-labelledby="viewPolicyModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewPolicyModalLabel">Policy Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <h6>Title</h6>
                        <p id="modalPolicyTitle" class="fw-bold"></p>
                    </div>
                    <div class="mb-3" id="modalPolicyDescriptionSection" style="display: none;">
                        <h6>Description</h6>
                        <p id="modalPolicyDescription"></p>
                    </div>
                    <div class="policy-content bg-light p-3 rounded" id="modalPolicyContent">
                        <!-- Policy content will be loaded here -->
                    </div>
                    {{-- Names/Signature/Date only for CCBRT (Staff signed) policies; Organization policies do not show attestation --}}
                    @auth
                        @if (($activePolicyType ?? 'ccbrt') !== 'other_organization')
                            @php
                                $modalUser = auth()->user();
                                $modalJoiningDate = $modalUser->starting_date
                                    ? \Carbon\Carbon::parse($modalUser->starting_date)->format('d-m-Y')
                                    : $modalUser->created_at->format('d-m-Y');
                            @endphp
                            <div class="mt-4 pt-3 border-top p-0" id="modalPolicyAttestation">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted mb-1">Names</label>
                                        <div>{{ $modalUser->fname }} {{ $modalUser->lname }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted mb-1">Signature</label>
                                        <div class="d-flex align-items-center overflow-hidden" style="min-height: 48px;">
                                            @if ($modalUser->signature)
                                                <img src="data:image/png;base64,{{ $modalUser->signature }}"
                                                    alt="Your Signature"
                                                    style="max-width: 100%; max-height: 48px; object-fit: contain;">
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted mb-1">Date</label>
                                        <div>{{ $modalJoiningDate }}</div>
                                    </div>
                                </div>
                            </div>
                        @else
                            {{-- Organization policy: show view count only --}}
                            <div class="mt-4 pt-3 border-top p-0" id="modalPolicyOrgMeta" style="display: none;">
                                <div class="text-muted small">
                                    <span id="modalPolicyViewCountLabel">Views:</span> <span id="modalPolicyViewCount">0</span>
                                </div>
                            </div>
                        @endif
                    @endauth
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- View Organization Policy PDF Modal (900px, same as department-policies) --}}
    <div class="modal fade" id="viewOrgPolicyPdfModal" tabindex="-1" aria-labelledby="viewOrgPolicyPdfModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered policy-pdf-modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewOrgPolicyPdfModalLabel">
                        <i class="fas fa-file-pdf me-2"></i><span id="viewOrgPolicyPdfTitle">Document</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0 text-center policy-pdf-modal-body">
                    <iframe id="viewOrgPolicyPdfIframe" src="" class="policy-pdf-iframe" title="Policy PDF"></iframe>
                </div>
            </div>
        </div>
    </div>

    {{-- Archive Policy Modal --}}
    <div class="modal fade" id="archivePolicyModal" tabindex="-1" aria-labelledby="archivePolicyModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="archivePolicyModalLabel">
                        <i class="fas fa-archive me-2"></i>Archive Policy
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to archive this policy?</p>
                    <p class="mb-0">
                        <strong>Policy:</strong>
                        <span id="archivePolicyTitle" class="text-primary"></span>
                    </p>
                    <small class="text-muted">Archived policies will not be visible to regular users but can be restored
                        later.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="archivePolicyForm" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-archive me-1"></i>Archive
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Restore Policy Modal --}}
    <div class="modal fade" id="restorePolicyModal" tabindex="-1" aria-labelledby="restorePolicyModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="restorePolicyModalLabel">
                        <i class="fas fa-undo me-2"></i>Restore Policy
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to restore this archived policy?</p>
                    <p class="mb-0">
                        <strong>Policy:</strong>
                        <span id="restorePolicyTitle" class="text-primary"></span>
                    </p>
                    <small class="text-muted">The policy will become active and visible to all authorized users.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="restorePolicyForm" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-undo me-1"></i>Restore
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Policy Modal --}}
    <div class="modal fade" id="deletePolicyModal" tabindex="-1" aria-labelledby="deletePolicyModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deletePolicyModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Delete Policy
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Warning: This action cannot be undone!</strong></p>
                    <p>Are you sure you want to permanently delete this policy?</p>
                    <p class="mb-0">
                        <strong>Policy:</strong>
                        <span id="deletePolicyTitle"></span>
                    </p>
                    <small class="text-muted">This will permanently remove the policy and its associated document from
                        the system.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deletePolicyForm" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-secondary">
                            <i class="fas fa-trash-alt me-1"></i>Delete Permanently
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    {{-- Add Section Modal --}}
    @php
        $categorySelectChildren = ($policyCategories ?? collect())
            ->sortBy('sort_order')
            ->groupBy(function ($category) {
                return $category->parent_id ?? 0;
            });

        $flattenCategoryOptions = function ($parentId = 0, $depth = 0) use (&$flattenCategoryOptions, $categorySelectChildren) {
            $items = collect();

            foreach ($categorySelectChildren->get($parentId, collect()) as $category) {
                $items->push(['category' => $category, 'depth' => $depth]);
                $items = $items->merge($flattenCategoryOptions($category->id, $depth + 1));
            }

            return $items;
        };

        $categorySelectOptions = $flattenCategoryOptions();
    @endphp
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add Section</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Parent Section</label>
                        <select id="addCategoryParent" class="form-select">
                            <option value="">Top Level Section</option>
                            @foreach($categorySelectOptions as $categoryOption)
                                <option value="{{ $categoryOption['category']->id }}"
                                    data-parent-id="{{ $categoryOption['category']->parent_id ?? '' }}"
                                    data-section-number="{{ $categoryOption['category']->section_number ?? '' }}"
                                    data-depth="{{ $categoryOption['depth'] }}">
                                    {{ str_repeat('— ', $categoryOption['depth']) }}{{ $categoryOption['category']->section_number ? $categoryOption['category']->section_number . '. ' : '' }}{{ $categoryOption['category']->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold d-flex align-items-center gap-2">
                            Section Number
                            <span id="addSectionSpinner" class="spinner-border spinner-border-sm text-secondary d-none" style="width:.75rem;height:.75rem;"></span>
                        </label>
                        <input type="text" id="addCategorySection" class="form-control bg-light" placeholder="Auto-generated" readonly>
                        <div id="addSectionHint" class="text-muted small mt-1"></div>
                        <div id="addSectionError" class="text-danger small mt-1 d-none"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Section Name <span class="text-danger">*</span></label>
                        <input type="text" id="addCategoryName" class="form-control" placeholder="Section name">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="saveNewCategoryBtn">Add Section</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit Section Modal --}}
    <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-pencil-alt me-2"></i>Edit Section</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editCategoryId">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Parent Section</label>
                        <select id="editCategoryParent" class="form-select">
                            <option value="">Top Level Section</option>
                            @foreach($categorySelectOptions as $categoryOption)
                                <option value="{{ $categoryOption['category']->id }}"
                                    data-parent-id="{{ $categoryOption['category']->parent_id ?? '' }}"
                                    data-section-number="{{ $categoryOption['category']->section_number ?? '' }}"
                                    data-depth="{{ $categoryOption['depth'] }}">
                                    {{ str_repeat('— ', $categoryOption['depth']) }}{{ $categoryOption['category']->section_number ? $categoryOption['category']->section_number . '. ' : '' }}{{ $categoryOption['category']->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Section Number</label>
                        <input type="text" id="editCategorySection" class="form-control" placeholder="e.g. 1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Section Name <span class="text-danger">*</span></label>
                        <input type="text" id="editCategoryName" class="form-control" placeholder="Section name">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveCategoryBtn">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Section Modal --}}
    <div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-trash me-2"></i>Delete Section</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="deleteCategoryId">
                    <p>Are you sure you want to delete section <strong id="deleteCategoryName"></strong>?</p>
                    <div id="deleteCategoryPolicyWarning" class="alert alert-warning d-none">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This section has <strong id="deleteCategoryPolicyCount"></strong> assigned to it.
                        <div class="mt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="deleteCategoryOption" id="optionNullify" value="nullify" checked>
                                <label class="form-check-label" for="optionNullify">Remove section from policies (keep policies)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="deleteCategoryOption" id="optionDeletePolicies" value="delete_policies">
                                <label class="form-check-label text-danger" for="optionDeletePolicies">Delete the section AND all its policies</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteCategoryBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    @php
        // Calculate column indices for CCBRT table
        $ccbrtOrderCol = $canManage ? 3 : 2;
        $ccbrtActionsCol = $canManage ? 4 : 3;

        // CCBRT table options (jQuery DataTables 1.x)
        $ccbrtOptions = [
            'pageLength' => 25,
            'lengthMenu' => [[10, 25, 50, 100], [10, 25, 50, 100]],
            'order' => [[$ccbrtOrderCol, 'desc']],
            'columnDefs' => [
                [
                    'orderable' => false,
                    'targets' => [0, $ccbrtActionsCol],
                ],
            ],
            'language' => [
                'search' => 'Search:',
                'lengthMenu' => 'Show _MENU_ policies per page',
                'info' => 'Showing _START_ to _END_ of _TOTAL_ CCBRT policies',
                'infoEmpty' => 'No CCBRT policies found',
                'infoFiltered' => '(filtered from _MAX_ total)',
                'zeroRecords' => 'No matching policies found',
                'emptyTable' => 'No CCBRT policies available',
                'paginate' => ['first' => 'First', 'last' => 'Last', 'next' => 'Next', 'previous' => 'Previous'],
            ],
            'autoWidth' => false,
        ];
    @endphp

    <script>
        $(document).ready(function() {
            // CCBRT Policies table – init only when it has data rows
            var $ccbrt = $('#ccbrtPoliciesTable');
            if ($ccbrt.length && $ccbrt.find('tbody tr').length > 0 && $ccbrt.find('tbody tr td[colspan]').length === 0) {
                $ccbrt.DataTable(@json($ccbrtOptions));
            }
        });
    </script>

    {{-- DataTable for Other Organization Policies (7 cols with canManage, 6 without) --}}
    @php
        if ($canManage) {
            // 7 columns: #, Policy, Entity, Department, Status, Created, Actions
            $otherOrgOptions = [
                'pageLength' => 25,
                'lengthMenu' => [[10, 25, 50, 100], [10, 25, 50, 100]],
                'order' => [[5, 'desc']],
                'columnDefs' => [
                    [
                        'orderable' => false,
                        'targets' => [0, 6],
                    ],
                ],
                'language' => [
                    'search' => 'Search:',
                    'lengthMenu' => 'Show _MENU_ policies per page',
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ organization policies',
                    'infoEmpty' => 'No organization policies found',
                    'infoFiltered' => '(filtered from _MAX_ total)',
                    'zeroRecords' => 'No matching policies found',
                    'emptyTable' => 'No organization policies available',
                    'paginate' => ['first' => 'First', 'last' => 'Last', 'next' => 'Next', 'previous' => 'Previous'],
                ],
                'autoWidth' => false,
            ];
        } else {
            // 6 columns: #, Policy, Entity, Department, Created, Actions
            $otherOrgOptions = [
                'pageLength' => 25,
                'lengthMenu' => [[10, 25, 50, 100], [10, 25, 50, 100]],
                'order' => [[4, 'desc']],
                'columnDefs' => [
                    [
                        'orderable' => false,
                        'targets' => [0, 5],
                    ],
                ],
                'language' => [
                    'search' => 'Search:',
                    'lengthMenu' => 'Show _MENU_ policies per page',
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ organization policies',
                    'infoEmpty' => 'No organization policies found',
                    'infoFiltered' => '(filtered from _MAX_ total)',
                    'zeroRecords' => 'No matching policies found',
                    'emptyTable' => 'No organization policies available',
                    'paginate' => ['first' => 'First', 'last' => 'Last', 'next' => 'Next', 'previous' => 'Previous'],
                ],
                'autoWidth' => false,
            ];
        }
    @endphp

    @php
        $otherOrgOptionsJs = isset($otherOrgOptions) ? $otherOrgOptions : [];
    @endphp
    <script>
        $(document).ready(function() {
            var $other = $('#otherOrgPoliciesTable');
            if ($other.length && $other.find('tbody tr').length > 0 && $other.find('tbody tr td[colspan]').length === 0) {
                $other.DataTable(@json($otherOrgOptionsJs));
            }


        });
    </script>

    {{-- DataTables for Archived Policies (jQuery DT, init only when table has data to avoid column count error) --}}
    @if ($canManage)
        @php
            // Archived CCBRT Policies Table (5 columns: #, Title, Status, Created, Actions)
            $archivedCcbrtOptions = [
                'pageLength' => 25,
                'lengthMenu' => [[10, 25, 50, 100], [10, 25, 50, 100]],
                'order' => [[3, 'desc']],
                'columnDefs' => [['orderable' => false, 'targets' => [0, 4]]],
                'language' => [
                    'search' => 'Search:',
                    'lengthMenu' => 'Show _MENU_ per page',
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ archived',
                    'infoEmpty' => 'No archived policies found',
                    'emptyTable' => 'No archived policies available',
                    'paginate' => ['first' => 'First', 'last' => 'Last', 'next' => 'Next', 'previous' => 'Previous'],
                ],
                'autoWidth' => false,
            ];

            // Archived Other Organization Policies Table (8 columns: #, Title, Entity, Departments, Content Type, Status, Created, Actions)
            $archivedOtherOrgOptions = [
                'pageLength' => 25,
                'lengthMenu' => [[10, 25, 50, 100], [10, 25, 50, 100]],
                'order' => [[6, 'desc']],
                'columnDefs' => [['orderable' => false, 'targets' => [0, 7]]],
                'language' => [
                    'search' => 'Search:',
                    'lengthMenu' => 'Show _MENU_ per page',
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ archived',
                    'infoEmpty' => 'No archived policies found',
                    'emptyTable' => 'No archived policies available',
                    'paginate' => ['first' => 'First', 'last' => 'Last', 'next' => 'Next', 'previous' => 'Previous'],
                ],
                'autoWidth' => false,
            ];

            $draftOtherOrgOptions = [
                'pageLength' => 25,
                'lengthMenu' => [[10, 25, 50, 100], [10, 25, 50, 100]],
                'order' => [[5, 'desc']],
                'columnDefs' => [['orderable' => false, 'targets' => [0, 6]]],
                'language' => [
                    'search' => 'Search:',
                    'lengthMenu' => 'Show _MENU_ per page',
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ drafts',
                    'infoEmpty' => 'No drafts found',
                    'emptyTable' => 'No draft policies available',
                    'paginate' => ['first' => 'First', 'last' => 'Last', 'next' => 'Next', 'previous' => 'Previous'],
                ],
                'autoWidth' => false,
            ];
        @endphp

        <script>
            $(document).ready(function() {
                var $draftOther = $('#draftOtherOrgPoliciesTable');
                if ($draftOther.length && $draftOther.find('tbody tr').length > 0 && $draftOther.find('tbody tr td[colspan]').length === 0) {
                    $draftOther.DataTable(@json($draftOtherOrgOptions));
                }
                var $archivedCcbrt = $('#archivedCcbrtPoliciesTable');
                if ($archivedCcbrt.length && $archivedCcbrt.find('tbody tr').length > 0 && $archivedCcbrt.find('tbody tr td[colspan]').length === 0) {
                    $archivedCcbrt.DataTable(@json($archivedCcbrtOptions));
                }
                var $archivedOther = $('#archivedOtherOrgPoliciesTable');
                if ($archivedOther.length && $archivedOther.find('tbody tr').length > 0 && $archivedOther.find('tbody tr td[colspan]').length === 0) {
                    $archivedOther.DataTable(@json($archivedOtherOrgOptions));
                }
            });
        </script>
    @endif

    <script>
        $(document).ready(function() {
            const manageCategoriesPanel = document.getElementById('manageCategoriesPanel');
            const pendingCategoryId = sessionStorage.getItem('policyCategoryFocusId');
            const shouldOpenCategoryTree = sessionStorage.getItem('policyManageCategoriesOpen') === '1';

            if (manageCategoriesPanel && (pendingCategoryId || shouldOpenCategoryTree)) {
                const focusPendingCategory = function() {
                    if (pendingCategoryId) {
                        const categoryButton = manageCategoriesPanel.querySelector('.btn-edit-category[data-id="' + pendingCategoryId + '"]');
                        const categoryRow = categoryButton?.closest('.cat-tree-item')?.querySelector('.cat-tree-row');

                        if (categoryRow) {
                            categoryRow.classList.add('cat-tree-new');
                            categoryRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            window.setTimeout(function() {
                                categoryRow.classList.remove('cat-tree-new');
                            }, 2500);
                        }
                    }

                    sessionStorage.removeItem('policyCategoryFocusId');
                    sessionStorage.removeItem('policyManageCategoriesOpen');
                };

                if (manageCategoriesPanel.classList.contains('show')) {
                    focusPendingCategory();
                } else {
                    manageCategoriesPanel.addEventListener('shown.bs.collapse', focusPendingCategory, { once: true });
                    bootstrap.Collapse.getOrCreateInstance(manageCategoriesPanel, { toggle: false }).show();
                }
            }

            // View Organization Policy PDF in modal (other_organization)
            var pdfModal = document.getElementById('viewOrgPolicyPdfModal');
            var pdfIframe = document.getElementById('viewOrgPolicyPdfIframe');
            var pdfTitleEl = document.getElementById('viewOrgPolicyPdfTitle');
            if (pdfModal && pdfIframe) {
                $(document).on('click', '.view-org-policy-pdf', function() {
                    pdfTitleEl.textContent = $(this).data('doc-title') || 'Document';
                    pdfIframe.src = $(this).data('doc-url') || '';
                    new bootstrap.Modal(pdfModal).show();
                });
                pdfModal.addEventListener('hidden.bs.modal', function() { pdfIframe.src = ''; });
            }

            // Helper: populate and show the policy view modal directly
            function populateAndShowPolicyModal(titleStr, content, description, policyType, policyId, viewCount) {
                var vm = document.getElementById('viewPolicyModal');
                if (!vm) return;
                document.getElementById('modalPolicyTitle').textContent = titleStr;
                document.getElementById('modalPolicyContent').innerHTML = content || 'No content available.';
                var descSection = document.getElementById('modalPolicyDescriptionSection');
                if (description && description !== 'N/A' && description !== '') {
                    document.getElementById('modalPolicyDescription').textContent = description;
                    if (descSection) descSection.style.display = 'block';
                } else {
                    if (descSection) descSection.style.display = 'none';
                }
                var orgMetaEl = document.getElementById('modalPolicyOrgMeta');
                var viewCountEl = document.getElementById('modalPolicyViewCount');
                if (orgMetaEl) {
                    if (policyType === 'other_organization') {
                        orgMetaEl.style.display = 'block';
                        if (viewCountEl) viewCountEl.textContent = viewCount || 0;
                        if (policyId) {
                            fetch('{{ url("policies") }}/' + policyId + '/record-view?type=other_organization', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            }).then(function(r) { return r.json(); }).then(function(data) {
                                if (data.view_count !== undefined && viewCountEl) viewCountEl.textContent = data.view_count;
                            }).catch(function() {});
                        }
                    } else {
                        orgMetaEl.style.display = 'none';
                    }
                }
                bootstrap.Modal.getOrCreateInstance(vm).show();
            }

            // Clickable rows
            document.querySelectorAll('.policy-clickable-row').forEach(function(row) {
                row.addEventListener('click', function() {
                    var isPdf = this.getAttribute('data-is-pdf') === '1';
                    var pdfUrl = this.getAttribute('data-pdf-url') || '';
                    var title = this.getAttribute('data-policy-title') || '';
                    if (isPdf && pdfUrl) {
                        var pdfModal = document.getElementById('viewOrgPolicyPdfModal');
                        if (pdfModal) {
                            var el = document.getElementById('viewOrgPolicyPdfTitle');
                            var iframe = document.getElementById('viewOrgPolicyPdfIframe');
                            if (el) el.textContent = title;
                            if (iframe) iframe.src = pdfUrl;
                            bootstrap.Modal.getOrCreateInstance(pdfModal).show();
                        } else {
                            window.open(pdfUrl, '_blank');
                        }
                    } else {
                        populateAndShowPolicyModal(
                            title,
                            this.getAttribute('data-policy-content') || '',
                            this.getAttribute('data-policy-description') || '',
                            this.getAttribute('data-policy-type') || 'ccbrt',
                            this.getAttribute('data-policy-id') || '',
                            parseInt(this.getAttribute('data-policy-view-count') || '0', 10)
                        );
                    }
                });
            });

            // View Policy Modal (keep for any remaining triggers)
            const viewModal = document.getElementById('viewPolicyModal');
            if (viewModal) {
                viewModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    if (!button) return; // triggered programmatically via populateAndShowPolicyModal
                    const title = button.getAttribute('data-policy-title');
                    const content = button.getAttribute('data-policy-content');
                    const description = button.getAttribute('data-policy-description');
                    const policyType = button.getAttribute('data-policy-type') || 'ccbrt';
                    const policyId = button.getAttribute('data-policy-id');
                    const viewCountEl = document.getElementById('modalPolicyViewCount');
                    const orgMetaEl = document.getElementById('modalPolicyOrgMeta');

                    document.getElementById('modalPolicyTitle').textContent = title;
                    document.getElementById('modalPolicyContent').innerHTML = content ||
                        'No content available.';

                    if (description && description !== 'N/A') {
                        document.getElementById('modalPolicyDescription').textContent = description;
                        document.getElementById('modalPolicyDescriptionSection').style.display = 'block';
                    } else {
                        document.getElementById('modalPolicyDescriptionSection').style.display = 'none';
                    }

                    // Organization policy: show view count and record this view
                    if (orgMetaEl) {
                        if (policyType === 'other_organization') {
                            orgMetaEl.style.display = 'block';
                            const initialCount = parseInt(button.getAttribute('data-policy-view-count') || '0', 10);
                            if (viewCountEl) viewCountEl.textContent = initialCount;
                            // Increment view count via API
                            if (policyId) {
                                fetch('{{ url("policies") }}/' + policyId + '/record-view?type=other_organization', {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest'
                                    }
                                }).then(function(r) { return r.json(); }).then(function(data) {
                                    if (data.view_count !== undefined && viewCountEl) viewCountEl.textContent = data.view_count;
                                }).catch(function() {});
                            }
                        } else {
                            orgMetaEl.style.display = 'none';
                        }
                    }
                });
            }

            // Archive Policy Modal
            const archiveModal = document.getElementById('archivePolicyModal');
            if (archiveModal) {
                archiveModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const policyId = button.getAttribute('data-policy-id');
                    const policyType = button.getAttribute('data-policy-type') || 'ccbrt';
                    const policyTitle = button.getAttribute('data-policy-title');
                    const modalTitle = archiveModal.querySelector('#archivePolicyTitle');
                    const form = archiveModal.querySelector('#archivePolicyForm');

                    modalTitle.textContent = policyTitle;
                    form.action = `/policies/${policyId}/archive?type=${policyType}`;
                });
            }

            // Restore Policy Modal
            const restoreModal = document.getElementById('restorePolicyModal');
            if (restoreModal) {
                restoreModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const policyId = button.getAttribute('data-policy-id');
                    const policyType = button.getAttribute('data-policy-type') || 'ccbrt';
                    const policyTitle = button.getAttribute('data-policy-title');
                    const modalTitle = restoreModal.querySelector('#restorePolicyTitle');
                    const form = restoreModal.querySelector('#restorePolicyForm');

                    modalTitle.textContent = policyTitle;
                    form.action = `/policies/${policyId}/restore?type=${policyType}`;
                });
            }

            // Delete Policy Modal
            const deleteModal = document.getElementById('deletePolicyModal');
            if (deleteModal) {
                deleteModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const policyId = button.getAttribute('data-policy-id');
                    const policyType = button.getAttribute('data-policy-type') || 'ccbrt';
                    const policyTitle = button.getAttribute('data-policy-title');
                    const form = deleteModal.querySelector('#deletePolicyForm');
                    const titleSpan = deleteModal.querySelector('#deletePolicyTitle');

                    titleSpan.textContent = policyTitle;
                    form.action = `/policies/${policyId}?type=${policyType}`;
                });
            }
        });

        // ── Category Add ──
        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function findDirectChildList(categoryItem) {
            return Array.from(categoryItem?.children || []).find(function(child) {
                return child.classList?.contains('cat-tree-children');
            }) || null;
        }

        function updateParentCategoryIcon(categoryId) {
            if (!categoryId) {
                return;
            }

            const parentButton = document.querySelector('.btn-edit-category[data-id="' + categoryId + '"]');
            const icon = parentButton?.closest('.cat-tree-item')?.querySelector('.cat-tree-icon');

            if (icon) {
                icon.innerHTML = '<i class="fas fa-folder-open text-warning" style="font-size:.85rem"></i>';
            }
        }

        function buildCategoryTreeItem(category) {
            const listItem = document.createElement('li');
            listItem.className = 'cat-tree-item';
            listItem.dataset.categoryId = String(category.id);
            listItem.innerHTML = `
                <div class="cat-tree-row cat-tree-new d-flex align-items-center gap-2 py-1 px-2 rounded">
                    <span class="cat-tree-icon flex-shrink-0">
                        <i class="fas fa-file-alt text-muted" style="font-size:.8rem"></i>
                    </span>
                    <span class="flex-grow-1 fw-semibold" style="font-size:.875rem">${escapeHtml(category.name)}</span>
                    ${category.section_number ? `<span class="badge bg-light text-secondary border" style="font-size:.7rem">${escapeHtml(category.section_number)}</span>` : ''}
                    <span class="badge bg-success" title="0 direct, 0 total">0</span>
                    <div class="d-flex gap-1 flex-shrink-0">
                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 btn-edit-category"
                            data-id="${escapeHtml(category.id)}"
                            data-name="${escapeHtml(category.name)}"
                            data-section="${escapeHtml(category.section_number || '')}"
                            data-parent-id="${escapeHtml(category.parent_id || '')}"
                            title="Edit">
                            <i class="fas fa-pencil-alt fa-xs"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 btn-delete-category"
                            data-id="${escapeHtml(category.id)}"
                            data-name="${escapeHtml(category.name)}"
                            data-count="0"
                            title="Delete">
                            <i class="fas fa-trash fa-xs"></i>
                        </button>
                    </div>
                </div>`;

            return listItem;
        }

        function insertCategoryIntoTree(category) {
            const manageCategoriesPanel = document.getElementById('manageCategoriesPanel');
            const panelBody = manageCategoriesPanel?.querySelector('.card-body');
            if (!panelBody || !category?.id) {
                return null;
            }

            let rootList = panelBody.querySelector('.cat-tree-root');
            if (!rootList) {
                panelBody.innerHTML = '';
                rootList = document.createElement('ul');
                rootList.className = 'cat-tree-root';
                panelBody.appendChild(rootList);
            }

            let targetList = rootList;
            if (category.parent_id) {
                const parentButton = rootList.querySelector('.btn-edit-category[data-id="' + category.parent_id + '"]');
                const parentItem = parentButton?.closest('.cat-tree-item');
                if (parentItem) {
                    let childList = findDirectChildList(parentItem);
                    if (!childList) {
                        childList = document.createElement('ul');
                        childList.className = 'cat-tree-children';
                        parentItem.appendChild(childList);
                    }
                    updateParentCategoryIcon(category.parent_id);
                    targetList = childList;
                }
            }

            const listItem = buildCategoryTreeItem(category);
            targetList.appendChild(listItem);
            return listItem;
        }

        function formatCategoryOptionLabel(category, depth) {
            return `${'— '.repeat(depth)}${category.section_number ? category.section_number + '. ' : ''}${category.name}`;
        }

        function insertCategoryOption(select, category) {
            if (!select || !category?.id) {
                return;
            }

            const parentId = category.parent_id ? String(category.parent_id) : '';
            const parentOption = parentId ? select.querySelector('option[value="' + parentId + '"]') : null;
            const depth = parentOption ? Number(parentOption.dataset.depth || 0) + 1 : 0;

            const option = document.createElement('option');
            option.value = String(category.id);
            option.dataset.parentId = parentId;
            option.dataset.sectionNumber = category.section_number || '';
            option.dataset.depth = String(depth);
            option.textContent = formatCategoryOptionLabel(category, depth);

            const options = Array.from(select.options);
            let insertAfter = options[options.length - 1] || null;

            if (parentOption) {
                const parentDepth = Number(parentOption.dataset.depth || 0);
                const parentIndex = options.indexOf(parentOption);
                insertAfter = parentOption;

                for (let index = parentIndex + 1; index < options.length; index += 1) {
                    const currentOption = options[index];
                    const currentDepth = Number(currentOption.dataset.depth || 0);
                    if (currentDepth <= parentDepth) {
                        break;
                    }
                    insertAfter = currentOption;
                }
            }

            if (insertAfter?.parentElement === select) {
                insertAfter.insertAdjacentElement('afterend', option);
            } else {
                select.appendChild(option);
            }
        }

        function focusCategoryTreeItem(listItem) {
            const manageCategoriesPanel = document.getElementById('manageCategoriesPanel');
            const categoryRow = listItem?.querySelector('.cat-tree-row');
            if (!categoryRow) {
                return;
            }

            const focusRow = function() {
                categoryRow.classList.add('cat-tree-new');
                categoryRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                window.setTimeout(function() {
                    categoryRow.classList.remove('cat-tree-new');
                }, 2500);
            };

            if (manageCategoriesPanel?.classList.contains('show')) {
                focusRow();
                return;
            }

            manageCategoriesPanel?.addEventListener('shown.bs.collapse', focusRow, { once: true });
            bootstrap.Collapse.getOrCreateInstance(manageCategoriesPanel, { toggle: false }).show();
        }

        function openEditCategoryModal(button) {
            document.getElementById('editCategoryId').value      = button.dataset.id;
            document.getElementById('editCategoryName').value    = button.dataset.name;
            document.getElementById('editCategorySection').value = button.dataset.section;
            document.getElementById('editCategoryParent').value  = button.dataset.parentId || '';
            new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
        }

        function openDeleteCategoryModal(button) {
            const id    = button.dataset.id;
            const name  = button.dataset.name;
            const count = parseInt(button.dataset.count, 10) || 0;

            document.getElementById('deleteCategoryId').value = id;
            document.getElementById('deleteCategoryName').textContent = name;

            const warning = document.getElementById('deleteCategoryPolicyWarning');
            const countEl = document.getElementById('deleteCategoryPolicyCount');
            if (count > 0) {
                countEl.textContent = count + (count === 1 ? ' policy' : ' policies');
                warning.classList.remove('d-none');
            } else {
                warning.classList.add('d-none');
            }

            document.getElementById('optionNullify').checked = true;
            new bootstrap.Modal(document.getElementById('deleteCategoryModal')).show();
        }

        function updateAddCategorySectionNumber() {
            const parentSelect = document.getElementById('addCategoryParent');
            const parentId     = parentSelect?.value || '';
            const sectionInput = document.getElementById('addCategorySection');
            const hint         = document.getElementById('addSectionHint');
            const spinner      = document.getElementById('addSectionSpinner');

            if (spinner) spinner.classList.remove('d-none');
            if (sectionInput) sectionInput.value = '';

            // Update hint text
            if (hint) {
                if (parentId) {
                    const selectedText = parentSelect.options[parentSelect.selectedIndex].text.trim();
                    hint.textContent = 'Next child section under: ' + selectedText;
                } else {
                    hint.textContent = 'Will be added as a top-level category.';
                }
            }

            const url = new URL('/policy-categories/next-section', window.location.origin);
            if (parentId) url.searchParams.set('parent_id', parentId);

            fetch(url.toString())
                .then(r => { if (!r.ok) throw new Error(); return r.json(); })
                .then(data => {
                    if (sectionInput) sectionInput.value = data.next;
                })
                .catch(() => {
                    if (sectionInput) sectionInput.value = '';
                })
                .finally(() => {
                    if (spinner) spinner.classList.add('d-none');
                });
        }

        document.getElementById('addCategoryModal')?.addEventListener('show.bs.modal', function() {
            document.getElementById('addCategoryParent').value = '';
            updateAddCategorySectionNumber();
            document.getElementById('addCategoryName').value = '';
            document.getElementById('addSectionError').classList.add('d-none');
        });

        document.getElementById('addCategoryParent')?.addEventListener('change', updateAddCategorySectionNumber);

        document.getElementById('saveNewCategoryBtn')?.addEventListener('click', function() {
            const name    = document.getElementById('addCategoryName').value.trim();
            const section = document.getElementById('addCategorySection').value.trim();
            const parentId = document.getElementById('addCategoryParent').value || null;
            const errEl   = document.getElementById('addSectionError');
            errEl.classList.add('d-none');
            if (!name) { alert('Section name is required.'); return; }

            fetch('/policy-categories', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ name: name, parent_id: parentId, section_number: section })
            })
            .then(async r => {
                const payload = await r.json().catch(() => ({}));
                if (!r.ok) {
                    throw payload;
                }
                return payload;
            })
            .then(data => {
                if (data.success) {
                    const createdCategory = data.category || {};
                    const insertedTreeItem = insertCategoryIntoTree(createdCategory);

                    insertCategoryOption(document.getElementById('addCategoryParent'), createdCategory);
                    insertCategoryOption(document.getElementById('editCategoryParent'), createdCategory);

                    bootstrap.Modal.getInstance(document.getElementById('addCategoryModal'))?.hide();
                    document.getElementById('addCategoryParent').value = '';
                    document.getElementById('addCategoryName').value = '';
                    document.getElementById('addSectionError').classList.add('d-none');
                    updateAddCategorySectionNumber();

                    focusCategoryTreeItem(insertedTreeItem);
                }
                else if (data.errors?.section_number) {
                    errEl.textContent = data.errors.section_number[0];
                    errEl.classList.remove('d-none');
                } else { alert(data.error || data.message || 'Failed to add section.'); }
            })
            .catch(error => {
                if (error?.errors?.section_number) {
                    errEl.textContent = error.errors.section_number[0];
                    errEl.classList.remove('d-none');
                    return;
                }

                if (error?.errors?.name) {
                    alert(error.errors.name[0]);
                    return;
                }

                alert(error?.error || error?.message || 'Failed to add section.');
            });
        });

        // ── Category Edit ──
        document.addEventListener('click', function(event) {
            const editButton = event.target.closest('.btn-edit-category');
            if (editButton) {
                openEditCategoryModal(editButton);
                return;
            }

            const deleteButton = event.target.closest('.btn-delete-category');
            if (deleteButton) {
                openDeleteCategoryModal(deleteButton);
            }
        });

        document.getElementById('saveCategoryBtn')?.addEventListener('click', function() {
            const id      = document.getElementById('editCategoryId').value;
            const name    = document.getElementById('editCategoryName').value.trim();
            const section = document.getElementById('editCategorySection').value.trim();
            const parentId = document.getElementById('editCategoryParent').value || null;
            if (!name) { alert('Section name is required.'); return; }

            fetch(`/policy-categories/${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ name: name, parent_id: parentId, section_number: section })
            })
            .then(async r => {
                const payload = await r.json().catch(() => ({}));
                if (!r.ok) {
                    throw payload;
                }
                return payload;
            })
            .then(data => {
                if (data.success) { location.reload(); }
                else if (data.errors?.section_number) { alert('Section number: ' + data.errors.section_number[0]); }
                else { alert(data.error || data.message || 'Failed to update section.'); }
            })
            .catch(error => {
                if (error?.errors?.section_number) {
                    alert('Section number: ' + error.errors.section_number[0]);
                    return;
                }

                if (error?.errors?.name) {
                    alert(error.errors.name[0]);
                    return;
                }

                alert(error?.error || error?.message || 'Failed to update section.');
            });
        });

        // ── Category Delete ──
        document.getElementById('confirmDeleteCategoryBtn')?.addEventListener('click', function() {
            const id             = document.getElementById('deleteCategoryId').value;
            const deletePolicies = document.getElementById('optionDeletePolicies')?.checked || false;

            fetch(`/policy-categories/${id}`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ delete_policies: deletePolicies })
            })
            .then(async r => {
                const payload = await r.json().catch(() => ({}));
                if (!r.ok) {
                    throw payload;
                }
                return payload;
            })
            .then(data => {
                if (data.success) { location.reload(); }
                else { alert(data.error || 'Failed to delete section.'); }
            })
            .catch(error => {
                alert(error?.error || error?.message || 'Failed to delete section.');
            });
        });
    </script>

    <script>
    // ── Org-policy search ──────────────────────────────────────────────
    (() => {
        const searchInput  = document.getElementById('orgPolicySearch');
        const clearBtn     = document.getElementById('orgPolicySearchClear');
        const noResults    = document.getElementById('orgSearchNoResults');
        const accordion    = document.getElementById('orgPolicyCategoryAccordion');
        if (!searchInput || !accordion) return;

        // Collect original open/close state to restore when cleared
        let originalState = null;

        const bsCollapse = (el, show) => {
            if (!el) return;
            if (show) {
                el.classList.add('show');
                const btn = document.querySelector(`[data-bs-target="#${el.id}"]`);
                if (btn) btn.classList.remove('collapsed');
            } else {
                el.classList.remove('show');
                const btn = document.querySelector(`[data-bs-target="#${el.id}"]`);
                if (btn) btn.classList.add('collapsed');
            }
        };

        // Returns true if the group or any descendant matches the query
        const matchGroup = (groupEl, q) => {
            const catName    = (groupEl.dataset.catName    || '').toLowerCase();
            const catSection = (groupEl.dataset.catSection || '').toLowerCase();
            if (catName.includes(q) || catSection.includes(q)) return true;

            // Check child groups recursively
            const childGroups = groupEl.querySelectorAll(':scope .accordion-item.org-category-group');
            for (const child of childGroups) {
                if ((child.dataset.catName || '').includes(q) || (child.dataset.catSection || '').includes(q)) return true;
            }

            // Check policy rows in this group (direct + descendants)
            const rows = groupEl.querySelectorAll('tr.policy-clickable-row');
            for (const row of rows) {
                if ((row.dataset.policyTitle || '').toLowerCase().includes(q)) return true;
            }
            return false;
        };

        const applySearch = (q) => {
            q = q.trim().toLowerCase();
            clearBtn.classList.toggle('d-none', !q);

            if (!q) {
                // Restore: show all groups, hide all rows visible again, close all accordions
                accordion.querySelectorAll('.accordion-item.org-category-group').forEach(g => {
                    g.style.display = '';
                });
                accordion.querySelectorAll('tr.policy-clickable-row').forEach(r => {
                    r.style.display = '';
                });
                accordion.querySelectorAll('.accordion-collapse').forEach(c => {
                    bsCollapse(c, false);
                });
                if (noResults) noResults.classList.add('d-none');
                return;
            }

            let anyVisible = false;

            // Process each ROOT group (depth-0)
            const rootGroups = accordion.querySelectorAll(':scope > .accordion-item.org-category-group');
            rootGroups.forEach(rootGroup => {
                const rootMatches = matchGroup(rootGroup, q);
                rootGroup.style.display = rootMatches ? '' : 'none';
                if (!rootMatches) return;

                anyVisible = true;
                // Expand root
                const rootBody = rootGroup.querySelector(':scope > .accordion-collapse');
                bsCollapse(rootBody, true);

                // Handle direct policy rows in root
                const directRows = rootGroup.querySelectorAll(':scope > .accordion-collapse > .accordion-body > .org-category-table-wrap tr.policy-clickable-row');
                const rootSectionMatch = (rootGroup.dataset.catName || '').includes(q) || (rootGroup.dataset.catSection || '').includes(q);
                directRows.forEach(row => {
                    const titleMatch = (row.dataset.policyTitle || '').toLowerCase().includes(q);
                    row.style.display = (rootSectionMatch || titleMatch) ? '' : 'none';
                });

                // Handle child groups
                const childGroups = rootGroup.querySelectorAll('.accordion-item.org-category-group');
                childGroups.forEach(childGroup => {
                    const childSectionMatch = (childGroup.dataset.catName || '').includes(q) || (childGroup.dataset.catSection || '').includes(q);
                    const childHasRowMatch  = Array.from(childGroup.querySelectorAll('tr.policy-clickable-row'))
                                                .some(r => (r.dataset.policyTitle || '').toLowerCase().includes(q));

                    if (!childSectionMatch && !childHasRowMatch) {
                        childGroup.style.display = 'none';
                        return;
                    }

                    childGroup.style.display = '';
                    const childBody = childGroup.querySelector(':scope > .accordion-collapse');
                    bsCollapse(childBody, true);

                    // Show/hide rows in child
                    const childRows = childGroup.querySelectorAll(':scope > .accordion-collapse > .accordion-body > .org-category-table-wrap tr.policy-clickable-row');
                    childRows.forEach(row => {
                        const titleMatch = (row.dataset.policyTitle || '').toLowerCase().includes(q);
                        row.style.display = (childSectionMatch || titleMatch) ? '' : 'none';
                    });
                });
            });

            if (noResults) noResults.classList.toggle('d-none', anyVisible);
        };

        searchInput.addEventListener('input', () => applySearch(searchInput.value));
        clearBtn.addEventListener('click', () => {
            searchInput.value = '';
            applySearch('');
            searchInput.focus();
        });
    })();
    </script>
@endpush
