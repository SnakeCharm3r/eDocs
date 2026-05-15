<tr class="policy-clickable-row {{ $policy->isArchived() ? 'table-secondary' : ($policy->isDraft() ? 'table-warning' : '') }}"
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
                <br><small class="text-muted">{{ $policy->document_code }}</small>
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
        @if ($policy->is_global)
            <span class="badge bg-secondary">All Users</span>
        @else
            @if ($policy->departments && $policy->departments->isNotEmpty())
                @foreach ($policy->departments->take(2) as $department)
                    <span class="badge bg-success mb-1">{{ $department->dept_name }}</span>
                @endforeach
                @if ($policy->departments->count() > 2)
                    <span class="badge bg-light text-dark">+{{ $policy->departments->count() - 2 }} more</span>
                @endif
            @else
                <span class="badge bg-warning text-dark">No Department</span>
            @endif
        @endif
    </td>
    @if ($canManage)
        <td>
            <span class="badge {{ $policy->isActive() ? 'bg-success' : ($policy->isDraft() ? 'bg-warning text-dark' : 'bg-secondary') }}">
                {{ ucfirst($policy->status) }}
            </span>
        </td>
    @endif
    <td data-order="{{ $policy->created_at->format('Y-m-d H:i:s') }}">
        {{ $policy->created_at->format('d M Y') }}
        <br>
        <small class="text-muted">{{ $policy->created_at->diffForHumans() }}</small>
    </td>
    <td class="text-end" onclick="event.stopPropagation()">
        <div class="d-flex gap-1 justify-content-end flex-wrap">
            @if ($canManageOtherOrg)
                <a href="{{ route('policies.edit', $policy->id) }}?type=other_organization"
                    class="btn btn-sm btn-outline-warning" title="Edit">
                    <i class="fas fa-edit text-success"></i>
                </a>
                @if ($policy->isActive())
                    <button type="button" class="btn btn-sm btn-outline-secondary" title="Archive"
                        data-bs-toggle="modal" data-bs-target="#archivePolicyModal"
                        data-policy-id="{{ $policy->id }}" data-policy-type="other_organization"
                        data-policy-title="{{ $policy->title }}">
                        <i class="fas fa-archive text-success"></i>
                    </button>
                @elseif ($policy->isArchived())
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
            @endif
        </div>
    </td>
</tr>
