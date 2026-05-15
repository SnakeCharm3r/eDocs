@php
    $nodeChildren = $categoryChildren->get($cat->id, collect());
    $directCount  = $groupedPolicies->get($cat->id, collect())->count();
    $totalCount   = $categoryPolicyTotal($cat->id);
@endphp
<li class="cat-tree-item">
    <div class="cat-tree-row d-flex align-items-center gap-2 py-1 px-2 rounded">
        {{-- Folder / leaf icon --}}
        <span class="cat-tree-icon flex-shrink-0">
            @if($nodeChildren->isNotEmpty())
                <i class="fas fa-folder-open text-warning" style="font-size:.85rem"></i>
            @else
                <i class="fas fa-file-alt text-muted" style="font-size:.8rem"></i>
            @endif
        </span>

        {{-- Section badge (left of name) --}}
        @if($cat->section_number)
            <span class="badge bg-light text-secondary border flex-shrink-0" style="font-size:.7rem;min-width:2rem;text-align:center">{{ $cat->section_number }}</span>
        @endif

        {{-- Name --}}
        <span class="flex-grow-1 fw-semibold" style="font-size:.875rem">{{ $cat->name }}</span>

        {{-- Policy count (total incl. descendants) --}}
        <span class="badge bg-success" title="{{ $directCount }} direct, {{ $totalCount }} total">{{ $totalCount }}</span>

        {{-- Actions --}}
        <div class="d-flex gap-1 flex-shrink-0">
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
                data-count="{{ $directCount }}"
                title="Delete">
                <i class="fas fa-trash fa-xs"></i>
            </button>
        </div>
    </div>

    @if($nodeChildren->isNotEmpty())
        <ul class="cat-tree-children">
            @foreach($nodeChildren as $cat)
                @include('policies._category_tree_node', [
                    'cat'                 => $cat,
                    'groupedPolicies'     => $groupedPolicies,
                    'categoryChildren'    => $categoryChildren,
                    'categoryPolicyTotal' => $categoryPolicyTotal,
                ])
            @endforeach
        </ul>
    @endif
</li>
