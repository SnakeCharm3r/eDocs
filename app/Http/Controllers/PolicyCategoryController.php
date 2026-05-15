<?php

namespace App\Http\Controllers;

use App\Models\PolicyCategory;
use App\Models\Policy;
use App\Models\OtherOrganizationPolicy;
use Illuminate\Http\Request;

class PolicyCategoryController extends Controller
{
    private function canManage(): bool
    {
        $user = auth()->user();
        return $user->hasRole('coo') || $user->hasRole('super-admin');
    }

    private function nextSectionValue(?PolicyCategory $parent = null): string
    {
        if ($parent) {
            $prefix = trim((string) $parent->section_number);
            $maxSuffix = PolicyCategory::where('parent_id', $parent->id)
                ->get()
                ->map(function (PolicyCategory $category) use ($prefix) {
                    $section = trim((string) $category->section_number);
                    if ($prefix === '' || !str_starts_with($section, $prefix . '.')) {
                        return null;
                    }

                    $suffix = substr($section, strlen($prefix) + 1);
                    return ctype_digit($suffix) ? (int) $suffix : null;
                })
                ->filter(static fn($value) => $value !== null)
                ->max();

            return $prefix !== ''
                ? $prefix . '.' . (($maxSuffix ?? 0) + 1)
                : (string) (($maxSuffix ?? 0) + 1);
        }

        $max = PolicyCategory::whereNull('parent_id')
            ->get()
            ->map(function (PolicyCategory $category) {
                $section = trim((string) $category->section_number);
                return ctype_digit($section) ? (int) $section : null;
            })
            ->filter(static fn($value) => $value !== null)
            ->max();

        return (string) (($max ?? 0) + 1);
    }

    private function wouldCreateCycle(PolicyCategory $category, ?int $parentId): bool
    {
        while ($parentId) {
            if ($parentId === $category->id) {
                return true;
            }

            $parentId = PolicyCategory::whereKey($parentId)->value('parent_id');
        }

        return false;
    }

    public function nextSectionNumber(Request $request)
    {
        $parent = null;
        if ($request->filled('parent_id')) {
            $parent = PolicyCategory::find($request->integer('parent_id'));
        }

        return response()->json(['next' => $this->nextSectionValue($parent)]);
    }

    public function store(Request $request)
    {
        if (!$this->canManage()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = validator($request->all(), [
            'name'           => 'required|string|max:255',
            'parent_id'      => 'nullable|exists:policy_categories,id',
            'section_number' => 'nullable|string|max:20|unique:policy_categories,section_number',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        if (blank($data['section_number'] ?? null)) {
            $parent = !empty($data['parent_id']) ? PolicyCategory::find($data['parent_id']) : null;
            $data['section_number'] = $this->nextSectionValue($parent);
        }

        $data['sort_order'] = \App\Models\PolicyCategory::max('sort_order') + 1;
        $category = \App\Models\PolicyCategory::create($data);

        return response()->json(['success' => true, 'category' => $category->load('parent')]);
    }

    public function update(Request $request, PolicyCategory $category)
    {
        if (!$this->canManage()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = validator($request->all(), [
            'name'           => 'required|string|max:255',
            'parent_id'      => 'nullable|exists:policy_categories,id',
            'section_number' => 'nullable|string|max:20|unique:policy_categories,section_number,' . $category->id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        if (!empty($data['parent_id']) && (int) $data['parent_id'] === $category->id) {
            return response()->json(['error' => 'A category cannot be its own parent.'], 422);
        }

        if ($this->wouldCreateCycle($category, !empty($data['parent_id']) ? (int) $data['parent_id'] : null)) {
            return response()->json(['error' => 'A category cannot be moved under one of its descendants.'], 422);
        }

        if (blank($data['section_number'] ?? null)) {
            $parent = !empty($data['parent_id']) ? PolicyCategory::find($data['parent_id']) : null;
            $data['section_number'] = $this->nextSectionValue($parent);
        }

        $category->update($data);

        return response()->json(['success' => true, 'category' => $category->load('parent')]);
    }

    public function destroy(Request $request, PolicyCategory $category)
    {
        if (!$this->canManage()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($request->boolean('delete_policies')) {
            Policy::where('policy_category_id', $category->id)->delete();
            OtherOrganizationPolicy::where('policy_category_id', $category->id)->delete();
        } else {
            // Keep policies but remove the category reference
            Policy::where('policy_category_id', $category->id)->update(['policy_category_id' => null]);
            OtherOrganizationPolicy::where('policy_category_id', $category->id)->update(['policy_category_id' => null]);
        }

        PolicyCategory::where('parent_id', $category->id)->update(['parent_id' => $category->parent_id]);

        $category->delete();

        return response()->json(['success' => true]);
    }
}
