@php
    $depth = $depth ?? 0;
    $directPolicies = $groupedPolicies->get($cat->id, collect());
    $visibleChildren = $categoryChildren->get($cat->id, collect())->filter(function ($childCategory) use ($categoryHasVisiblePolicies) {
        return $categoryHasVisiblePolicies($childCategory->id);
    });
    $totalPolicies = $categoryPolicyTotal($cat->id);
    $hasChildren   = $visibleChildren->isNotEmpty();
@endphp

@if($totalPolicies > 0)
    <div class="accordion-item org-category-group org-cat-depth-{{ $depth }}"
         data-category-id="{{ $cat->id }}"
         data-cat-name="{{ strtolower($cat->name) }}"
         data-cat-section="{{ strtolower($cat->section_number ?? '') }}">
        <h2 class="accordion-header d-flex align-items-center" id="orgCatHead{{ $cat->id }}">
            <button class="accordion-button collapsed flex-grow-1 py-2" type="button" data-bs-toggle="collapse"
                data-bs-target="#orgCatBody{{ $cat->id }}" aria-expanded="false" aria-controls="orgCatBody{{ $cat->id }}">
                {{-- Icon: folder if has children, file otherwise --}}
                <i class="fas fa-{{ $hasChildren ? 'folder text-warning' : 'file-alt text-secondary' }} me-2 flex-shrink-0" style="font-size:.82rem;width:14px;text-align:center"></i>
                {{-- Section number badge --}}
                @if($cat->section_number)
                    <span class="badge bg-light text-secondary border fw-normal me-2 flex-shrink-0" style="font-size:.7rem;letter-spacing:.02em">{{ $cat->section_number }}</span>
                @endif
                {{-- Name --}}
                <span class="flex-grow-1">{{ $cat->name }}</span>
                {{-- Policy count --}}
                <span class="badge bg-success ms-2 flex-shrink-0">{{ $totalPolicies }}</span>
            </button>
            {{-- Admin quick-actions (COO / superadmin only) --}}
            @if($isSuperAdmin || $isCOO)
            <div class="org-cat-admin-btns">
                <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 btn-edit-category"
                    data-id="{{ $cat->id }}"
                    data-name="{{ $cat->name }}"
                    data-section="{{ $cat->section_number }}"
                    data-parent-id="{{ $cat->parent_id }}"
                    title="Edit">
                    <i class="fas fa-pencil-alt fa-xs"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 btn-delete-category"
                    data-id="{{ $cat->id }}"
                    data-name="{{ $cat->name }}"
                    data-count="{{ $directPolicies->count() }}"
                    title="Delete">
                    <i class="fas fa-trash fa-xs"></i>
                </button>
            </div>
            @endif
        </h2>
        <div id="orgCatBody{{ $cat->id }}" class="accordion-collapse collapse" aria-labelledby="orgCatHead{{ $cat->id }}">
            <div class="accordion-body org-category-body p-0">
                @if($directPolicies->isNotEmpty())
                    <div class="org-category-table-wrap table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>Policy</th>
                                    <th>Entity</th>
                                    <th>Department</th>
                                    @if ($canManage)
                                        <th>Status</th>
                                    @endif
                                    <th>Created</th>
                                    <th class="text-center" style="width: 130px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($directPolicies as $policy)
                                    @include('policies._org_policy_row', ['policy' => $policy, 'loop' => $loop])
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if($visibleChildren->isNotEmpty())
                    <div class="org-category-children-section {{ $directPolicies->isNotEmpty() ? 'has-parent-policies' : 'without-parent-policies' }}">
                        @if($directPolicies->isNotEmpty())
                            <div class="org-category-children-label">
                                <i class="fas fa-diagram-project"></i>
                                <span>Sub sections</span>
                            </div>
                        @endif
                        <div class="accordion org-category-children-accordion" id="orgNestedAccordion{{ $cat->id }}">
                            @foreach($visibleChildren as $cat)
                                @include('policies._org_category_group', [
                                    'cat'                        => $cat,
                                    'depth'                      => $depth + 1,
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
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif