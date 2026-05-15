{{--
    Section tree dropdown picker
  Props:
    $catTreeAll      – Collection keyed by id
    $catTreeByParent – Collection grouped by parent_id (0 = roots)
    $selectedCatId   – currently selected id (can be null)
    $pickerId        – unique DOM id prefix
    $hiddenId        – id of the hidden <input> to write selected value into
--}}
@php
    /** Initial label: breadcrumb path of the already-selected node */
    $initLabel = '-- Select Section --';
    if ($selectedCatId && $catTreeAll->has((int) $selectedCatId)) {
        $parts = [];
        $cur = $catTreeAll->get((int) $selectedCatId);
        while ($cur) {
            array_unshift($parts, ($cur->section_number ? $cur->section_number . '. ' : '') . $cur->name);
            $cur = $cur->parent_id ? $catTreeAll->get($cur->parent_id) : null;
        }
        $initLabel = implode(' › ', $parts);
    }

    /** Encode the tree for JS as {id, name, section, children:[…]} */
    $buildJsTree = function (int $parentId) use (&$buildJsTree, $catTreeByParent): array {
        return ($catTreeByParent->get($parentId) ?? collect())->map(function ($c) use (&$buildJsTree) {
            return [
                'id'       => $c->id,
                'name'     => $c->name,
                'section'  => $c->section_number ?? '',
                'children' => $buildJsTree($c->id),
            ];
        })->values()->all();
    };
    $jsTree = $buildJsTree(0);
@endphp

{{-- Trigger button styled like a native <select> --}}
<div class="cat-dd" id="{{ $pickerId }}" data-hidden="{{ $hiddenId }}" style="position:relative;">
    <button type="button" class="cat-dd-trigger form-select form-select-sm text-start d-flex align-items-center">
        <span class="cat-dd-label text-truncate" style="flex:1">{{ $initLabel }}</span>
    </button>
    {{-- Dropdown menu (positioned absolute, populated by JS) --}}
    <div class="cat-dd-menu border rounded bg-white shadow-sm"
         style="display:none; position:absolute; z-index:1055; width:100%; max-height:280px; overflow-y:auto; top:calc(100% + 2px); left:0;"></div>
</div>

@once
<style>
.cat-dd-trigger {
    cursor: pointer;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right .75rem center;
    background-size: 16px 12px;
    padding-right: 2.25rem !important;
}
.cat-dd-row {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 7px 12px;
    cursor: pointer;
    font-size: .875rem;
    border-bottom: 1px solid #f2f2f2;
    user-select: none;
}
.cat-dd-row:last-child  { border-bottom: none; }
.cat-dd-row:hover       { background: #f0faf1; }
.cat-dd-row.is-selected { background: #d4edda; font-weight: 600; }
.cat-dd-row .cat-dd-name { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.cat-dd-toggle {
    flex-shrink: 0;
    background: none;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    width: 22px; height: 22px;
    display: flex; align-items: center; justify-content: center;
    font-size: .6rem; color: #6c757d; cursor: pointer; padding: 0;
}
.cat-dd-toggle:hover { background: #e9ecef; }
</style>
@endonce

@push('scripts')
<script>
(function () {
    const TREE = @json($jsTree);

    // Flat lookup id → node, injecting _parentId for trail building
    const ALL = {};
    (function flatten(nodes, pid) {
        nodes.forEach(function (n) {
            n._parentId = pid;
            ALL[n.id] = n;
            flatten(n.children, n.id);
        });
    }(TREE, null));

    document.querySelectorAll('.cat-dd').forEach(function (picker) {
        const hidden  = document.getElementById(picker.dataset.hidden);
        const trigger = picker.querySelector('.cat-dd-trigger');
        const label   = picker.querySelector('.cat-dd-label');
        const menu    = picker.querySelector('.cat-dd-menu');

        const expanded = new Set(); // ids that are expanded
        let isOpen = false;

        function nodeLabel(n) {
            return (n.section ? n.section + '. ' : '') + n.name;
        }
        function buildPath(id) {
            const path = [];
            let cur = ALL[id];
            while (cur) { path.unshift(cur); cur = cur._parentId ? ALL[cur._parentId] : null; }
            return path;
        }

        /* render */
        function renderMenu() {
            menu.innerHTML = '';
            const currentVal = hidden ? parseInt(hidden.value) || 0 : 0;
            renderNodes(TREE, 0, currentVal);
        }

        function renderNodes(nodes, depth, currentVal) {
            nodes.forEach(function (node) {
                const row = document.createElement('div');
                row.className = 'cat-dd-row' + (currentVal === node.id ? ' is-selected' : '');
                row.style.paddingLeft = (12 + depth * 22) + 'px';

                const icon = document.createElement('i');
                icon.className = node.children.length
                    ? 'fas fa-folder text-warning'
                    : 'fas fa-file-alt text-secondary';
                icon.style.cssText = 'width:16px;flex-shrink:0';
                row.appendChild(icon);

                const nameEl = document.createElement('span');
                nameEl.className = 'cat-dd-name';
                nameEl.textContent = nodeLabel(node);
                row.appendChild(nameEl);

                if (node.children.length) {
                    const toggle = document.createElement('button');
                    toggle.type = 'button';
                    toggle.className = 'cat-dd-toggle';
                    toggle.title = expanded.has(node.id) ? 'Collapse' : 'Show sub sections';
                    toggle.innerHTML = expanded.has(node.id)
                        ? '<i class="fas fa-chevron-down"></i>'
                        : '<i class="fas fa-chevron-right"></i>';
                    toggle.addEventListener('click', function (e) {
                        e.stopPropagation();
                        expanded.has(node.id) ? expanded.delete(node.id) : expanded.add(node.id);
                        renderMenu();
                    });
                    row.appendChild(toggle);
                }

                row.addEventListener('click', function (e) {
                    e.stopPropagation();
                    selectNode(node);
                });

                menu.appendChild(row);

                if (node.children.length && expanded.has(node.id)) {
                    renderNodes(node.children, depth + 1, currentVal);
                }
            });
        }

        /* select */
        function selectNode(node) {
            if (hidden) {
                hidden.value = node.id;
                hidden.dispatchEvent(new Event('input', { bubbles: true }));
                hidden.dispatchEvent(new Event('change', { bubbles: true }));
            }
            label.textContent = buildPath(node.id).map(nodeLabel).join(' › ');
            close();
        }

        /* open / close */
        function open(e) {
            if (e) e.stopPropagation();
            renderMenu();
            menu.style.display = '';
            isOpen = true;
        }
        function close() {
            menu.style.display = 'none';
            isOpen = false;
        }

        trigger.addEventListener('click', function (e) { isOpen ? close() : open(e); });
        menu.addEventListener('click',    function (e) { e.stopPropagation(); });
        document.addEventListener('click', close);

        // init: label + auto-expand ancestors of pre-selected value
        if (hidden && hidden.value) {
            const node = ALL[parseInt(hidden.value)];
            if (node) {
                label.textContent = buildPath(node.id).map(nodeLabel).join(' › ');
                buildPath(node.id).forEach(function (n) {
                    if (n.children && n.children.length) expanded.add(n.id);
                });
            }
        }
    });
}());
</script>
@endpush
