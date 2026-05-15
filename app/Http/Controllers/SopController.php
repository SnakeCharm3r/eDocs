<?php

namespace App\Http\Controllers;

use App\Models\Sop;
use App\Models\User;
use App\Models\Division;
use App\Models\Departments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RealRashid\SweetAlert\Facades\Alert;

class SopController extends Controller
{
    /**
     * Check if user is Quality Assurance, Admin, or Super Admin (can manage SOPs)
     */
    private function isQualityAssurance(): bool
    {
        $user = auth()->user();
        return $user->hasRole('quality_assurance') || $user->hasRole('admin') || $user->hasRole('super-admin');
    }

    /**
     * Check if user is Line Manager
     */
    private function isLineManager(): bool
    {
        return auth()->user()->hasRole('line-manager');
    }

    /**
     * Check if user sees all SOPs (QA, CEO, or HEC: COO, CFO, CMS, CCDRO)
     */
    private function canSeeAllSOPs(): bool
    {
        $user = auth()->user();
        return $this->isQualityAssurance()
            || $user->hasRole('ceo')
            || $user->hasAnyRole(['coo', 'cfo', 'cms', 'ccdro', 'view-all-sops'])
            || $user->hasPermissionTo('view all sops');
    }

    private function mergeNextReviewDate(Request $request): void
    {
        if (!$request->filled('next_review_date') && $request->filled('expiry_date')) {
            $request->merge(['next_review_date' => $request->input('expiry_date')]);
        }
    }

    private function applyVisibleReviewDateFilter($query): void
    {
        $today = now()->startOfDay()->toDateString();

        $query->where(function ($q) use ($today) {
            $q->where(function ($inner) {
                $inner->whereNull('next_review_date')
                    ->whereNull('expiry_date');
            })->orWhereRaw('COALESCE(next_review_date, expiry_date) >= ?', [$today]);
        });
    }

    /**
     * Display a listing of SOPs
     * - QA, CEO, and HEC (COO, CFO, CMS, CCDRO) see all SOPs (including archived)
     * - Line managers see only active SOPs for the department they manage (user's deptId)
     * - Staff see only active SOPs for their department
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $isQA = $this->isQualityAssurance();
        $isLineManager = $this->isLineManager();
        $canSeeAll = $this->canSeeAllSOPs();
        $isRequesterOnly = $user->hasRole('requester') && !$isQA && !$isLineManager;

        $query = Sop::with(['departments', 'division', 'divisions', 'ownerDepartment.head', 'creator', 'updatedBy']);

        // Filter by status - QA/CEO/HEC can see all statuses; others only active and not expired by date
        if ($canSeeAll) {
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
        } else {
            $query->where('status', 'active');
            // Hide expired SOPs from other users until QA updates (renews) them
            $this->applyVisibleReviewDateFilter($query);
            // Line manager / Staff: view SOPs where their dept is owner OR their dept is in visibility list OR apply-to-all-entities OR SOP's division(s) include user's department's division(s)
            $userDeptId = $user->deptId ? (int) $user->deptId : null;
            $userDivisionIds = [];
            if ($userDeptId) {
                $userDept = Departments::find($userDeptId);
                if ($userDept) {
                    $userDivisionIds = $userDept->divisions()->pluck('divisions.id')->toArray();
                }
            }
            $restrictToOwnDepartment = $user->hasRole('line-manager')
                || (!$user->hasAnyRole(['super-admin', 'admin']));
            if ($restrictToOwnDepartment) {
                if ($userDeptId) {
                    $query->where(function ($q) use ($userDeptId, $userDivisionIds) {
                        $q->where('owner_department_id', $userDeptId)
                            ->orWhereHas('departments', function ($dq) use ($userDeptId) {
                                $dq->where('departments.id', $userDeptId);
                            })
                            ->orWhere(function ($aq) {
                                $aq->whereNull('division_id')->where('global', true);
                            })
                            ->orWhere(function ($divQ) use ($userDivisionIds) {
                                if (!empty($userDivisionIds)) {
                                    $divQ->whereIn('division_id', $userDivisionIds)
                                        ->orWhereHas('divisions', function ($dq) use ($userDivisionIds) {
                                            $dq->whereIn('divisions.id', $userDivisionIds);
                                        });
                                } else {
                                    $divQ->whereRaw('1 = 0');
                                }
                            });
                    });
                } else {
                    $query->where(function ($q) {
                        $q->whereNull('division_id')->where('global', true);
                    });
                }
            }
        }

        // Filter by owner department: include apply-to-all-entities SOPs (division_id null + global) in every folder
        $userDeptId = $user->deptId ? (int) $user->deptId : null;
        if ($request->filled('owner_department_id')) {
            $requestedOwnerId = (int) $request->owner_department_id;
            if ($canSeeAll) {
                $query->where(function ($q) use ($requestedOwnerId) {
                    $q->where('owner_department_id', $requestedOwnerId)
                        ->orWhere(function ($aq) {
                            $aq->whereNull('division_id')->where('global', true);
                        });
                });
            } elseif ($userDeptId && $requestedOwnerId === $userDeptId) {
                $query->where(function ($q) use ($userDeptId) {
                    $q->where('owner_department_id', $userDeptId)
                        ->orWhereHas('departments', function ($dq) use ($userDeptId) {
                            $dq->where('departments.id', $userDeptId);
                        })
                        ->orWhere(function ($aq) {
                            $aq->whereNull('division_id')->where('global', true);
                        });
                });
            }
        }

        // Filter by division/entity (include SOPs with that division in pivot or division_id, and apply-to-all-entities)
        if ($request->filled('division_id')) {
            $requestedDivisionId = (int) $request->division_id;
            $query->where(function ($q) use ($requestedDivisionId) {
                $q->where('division_id', $requestedDivisionId)
                    ->orWhereHas('divisions', function ($dq) use ($requestedDivisionId) {
                        $dq->where('divisions.id', $requestedDivisionId);
                    })
                    ->orWhere(function ($aq) {
                        $aq->whereNull('division_id')->where('global', true);
                    });
            });
        }

        // Filter by department
        if ($request->filled('department_id')) {
            $query->whereHas('departments', function ($q) use ($request) {
                $q->where('departments.id', $request->department_id);
            });
        }

        // Filter by title
        if ($request->filled('title')) {
            $query->where('title', 'like', '%' . $request->title . '%');
        }

        // Filter by expiry status
        if ($request->filled('expiry_status')) {
            if ($request->expiry_status === 'expiring_soon') {
                $query->expiringSoon(30);
            } elseif ($request->expiry_status === 'expired') {
                $query->expired();
            }
        }

        $sops = $query->orderBy('created_at', 'desc')->get();
        $divisions = Division::all();
        $departments = Departments::all();

        // Folder-style cards: all departments with SOP counts (0 if none); QA/CEO/HEC see all depts; others see their dept only
        $departmentCards = collect();
        if ($canSeeAll) {
            $sopCountsByDept = Sop::whereNotNull('owner_department_id')
                ->selectRaw('owner_department_id, count(*) as sop_count')
                ->groupBy('owner_department_id')
                ->pluck('sop_count', 'owner_department_id');
            $departmentCards = Departments::with('head')->orderBy('dept_name')->get()->map(function ($dept) use ($sopCountsByDept) {
                $lineManagerName = null;
                if ($dept->head) {
                    $h = $dept->head;
                    $lineManagerName = trim(($h->fname ?? '') . ' ' . ($h->lname ?? '')) ?: $h->username ?? null;
                }
                return (object)[
                    'department_id' => $dept->id,
                    'department_name' => $dept->dept_name,
                    'sop_count' => (int) ($sopCountsByDept[$dept->id] ?? 0),
                    'line_manager_name' => $lineManagerName,
                ];
            });
        } else {
            // Line managers / staff: show their department as one folder with SOP count (owned + visible to all in entity)
            $userDeptId = $user->deptId ? (int) $user->deptId : null;
            if ($userDeptId) {
                $countQuery = Sop::where('status', 'active');
                $this->applyVisibleReviewDateFilter($countQuery);
                $count = $countQuery
                    ->where(function ($q) use ($userDeptId) {
                        $q->where('owner_department_id', $userDeptId)
                            ->orWhereHas('departments', function ($dq) use ($userDeptId) {
                                $dq->where('departments.id', $userDeptId);
                            });
                    })
                    ->count();
                $dept = Departments::with('head')->find($userDeptId);
                $lineManagerName = null;
                if ($dept && $dept->head) {
                    $h = $dept->head;
                    $lineManagerName = trim(($h->fname ?? '') . ' ' . ($h->lname ?? '')) ?: $h->username ?? null;
                }
                $departmentCards = collect([(object)[
                    'department_id' => $userDeptId,
                    'department_name' => $dept ? $dept->dept_name : 'Unknown',
                    'sop_count' => $count,
                    'line_manager_name' => $lineManagerName,
                ]]);
            }
        }

        // Expiring soon count (QA/CEO/HEC see all; line managers see their dept only)
        $expiringSoonCount = 0;
        if ($canSeeAll || $isLineManager) {
            $expiringSoonQuery = Sop::active()->expiringSoon(30);
            if ($isLineManager && !$canSeeAll && $user->deptId) {
                $expiringSoonQuery->where('owner_department_id', $user->deptId);
            }
            $expiringSoonCount = $expiringSoonQuery->count();
        }

        // When a folder is open: selected department name for heading (QA/CEO/HEC use request; line manager only their dept)
        $selectedDepartmentName = null;
        if ($request->filled('owner_department_id')) {
            if ($canSeeAll) {
                $dept = Departments::find($request->owner_department_id);
                $selectedDepartmentName = $dept ? $dept->dept_name : null;
            } elseif ($userDeptId && (int) $request->owner_department_id === $userDeptId) {
                $dept = Departments::find($userDeptId);
                $selectedDepartmentName = $dept ? $dept->dept_name : null;
            }
        }

        return view('sops.index', compact('sops', 'departments', 'divisions', 'isQA', 'isLineManager', 'canSeeAll', 'isRequesterOnly', 'expiringSoonCount', 'departmentCards', 'selectedDepartmentName'));
    }

    /**
     * Show the form for creating a new SOP
     * Only Quality Assurance can create SOPs
     */
    public function create()
    {
        if (!$this->isQualityAssurance()) {
            Alert::error('Unauthorized', 'Only Quality Assurance can upload SOPs.');
            return redirect()->route('sops.index');
        }

        $divisions = Division::all();
        $departments = Departments::all();

        return view('sops.create', compact('divisions', 'departments'));
    }

    /**
     * Store a newly created SOP
     * Only Quality Assurance can store SOPs
     */
    public function store(Request $request)
    {
        if (!$this->isQualityAssurance()) {
            Alert::error('Unauthorized', 'Only Quality Assurance can upload SOPs.');
            return redirect()->route('sops.index');
        }

        $applyToAllEntities = $request->has('apply_to_all_entities') && $request->apply_to_all_entities == 1;
        if ($applyToAllEntities) {
            $request->merge([
                'division_id' => null,
                'division_ids' => [],
                'owner_department_id' => null,
                'visible_to_all_departments' => 1,
            ]);
        } else {
            // Normalize: support both division_ids[] (multi) and division_id (single)
            $divisionIds = $request->filled('division_ids') && is_array($request->division_ids)
                ? array_values(array_map('intval', array_filter($request->division_ids)))
                : ($request->filled('division_id') ? [(int) $request->division_id] : []);
            $request->merge(['division_ids' => $divisionIds]);
            if (count($divisionIds) === 1) {
                $request->merge(['division_id' => $divisionIds[0]]);
            } else {
                $request->merge(['division_id' => $divisionIds[0] ?? null]);
            }
        }

        // If form was submitted but no file received, PHP likely rejected the upload (post_max_size / upload_max_filesize)
        if ($request->filled('title') && !$request->hasFile('pdf')) {
            return back()->withErrors([
                'pdf' => [
                    'The server did not receive the file. This usually means PHP upload limits are too low. ' .
                    'On the server, edit php.ini: set upload_max_filesize = 5M and post_max_size = 8M (or higher), then restart PHP or the web server. ' .
                    'If using Nginx, also set client_max_body_size 8M; in Apache, no extra step needed.',
                ],
            ])->withInput();
        }

        $this->mergeNextReviewDate($request);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'document_code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'division_id' => 'nullable|exists:divisions,id',
            'division_ids' => 'nullable|array',
            'division_ids.*' => 'exists:divisions,id',
            'owner_department_id' => 'nullable|required_unless:apply_to_all_entities,1|exists:departments,id',
            'pdf' => 'required|file|mimes:pdf,doc,docx|max:5120',
            'departments' => 'nullable|array',
            'departments.*' => 'exists:departments,id',
            'global' => 'nullable|boolean',
            'visible_to_all_departments' => 'nullable|boolean',
            'apply_to_all_entities' => 'nullable|boolean',
            'effective_date' => 'required|date',
            'next_review_date' => 'required|date|after:effective_date',
            'version' => 'nullable|string|max:20',
        ], [
            'pdf.required' => 'Please select a PDF, DOC or DOCX file to upload.',
            'pdf.file' => 'The selected file could not be uploaded. Please try again.',
            'pdf.uploaded' => 'The server did not accept the file. On the server, increase PHP limits: set upload_max_filesize and post_max_size to at least 5M in php.ini, then restart PHP or the web server.',
            'pdf.mimes' => 'The file must be a PDF, DOC or DOCX document.',
            'pdf.max' => 'The file must not be larger than 5 MB.',
        ]);

        if (!$applyToAllEntities && empty($request->division_ids) && !$request->filled('division_id')) {
            return back()->withErrors(['division_ids' => ['Select at least one entity/division or check Apply to all entities.']])->withInput();
        }

        DB::beginTransaction();
        try {
            $visibleToAll = $request->has('visible_to_all_departments') && $request->visible_to_all_departments == 1;
            $divisionIds = $request->division_ids ?? ($request->filled('division_id') ? [(int) $request->division_id] : []);

            // Document code: use first division when single or multiple
            $divisionIdForCode = $applyToAllEntities ? null : (count($divisionIds) > 0 ? $divisionIds[0] : $request->division_id);
            $documentCode = $request->filled('document_code')
                ? $request->document_code
                : Sop::generateDocumentCode($divisionIdForCode);

            // Handle file upload: ensure directory exists, then store (unique name to avoid conflicts on live)
            $file = $request->file('pdf');
            $fileExtension = $file->getClientOriginalExtension();
            $originalName = basename($file->getClientOriginalName());
            Storage::disk('public')->makeDirectory('sops');
            $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $fileExtension;
            $uniqueName = time() . '_' . $safeName;
            $filePath = $file->storeAs('sops', $uniqueName, 'public');
            if ($filePath === false) {
                throw new \RuntimeException('The PDF could not be saved. Please ensure the storage folder (storage/app/public/sops) is writable.');
            }

            $ownerDeptId = $request->filled('owner_department_id') ? (int) $request->owner_department_id : null;
            // division_id: first when one entity, null when multiple or apply-to-all
            $sopDivisionId = $applyToAllEntities ? null : (count($divisionIds) === 1 ? $divisionIds[0] : null);
            $sop = Sop::create([
                'title' => $request->title,
                'document_code' => $documentCode,
                'description' => $request->description,
                'division_id' => $sopDivisionId,
                'owner_department_id' => $ownerDeptId,
                'global' => $visibleToAll || $applyToAllEntities,
                'pdf_path' => $filePath,
                'file_type' => strtolower($fileExtension),
                'effective_date' => $request->effective_date,
                'next_review_date' => $request->next_review_date,
                'expiry_date' => $request->next_review_date,
                'version' => $request->version ?? '1.0',
                'status' => 'active',
                'created_by' => auth()->id(),
            ]);

            // Sync division pivot (multiple entities)
            if ($applyToAllEntities) {
                $sop->divisions()->sync([]);
            } else {
                $sop->divisions()->sync($divisionIds);
            }

            // Visibility: apply-to-all-entities = no departments (index shows to all); else entity-wide or owner only
            if ($applyToAllEntities) {
                $sop->departments()->sync([]);
            } elseif ($visibleToAll) {
                $allDeptIds = [];
                foreach ($divisionIds as $did) {
                    $division = Division::find($did);
                    if ($division) {
                        $allDeptIds = array_merge($allDeptIds, $division->departments()->pluck('departments.id')->toArray());
                    }
                }
                $allDeptIds = array_unique($allDeptIds);
                if (empty($allDeptIds) && $ownerDeptId) {
                    $allDeptIds = [$ownerDeptId];
                }
                $syncData = [];
                foreach ($allDeptIds as $deptId) {
                    $syncData[$deptId] = ['created_by' => auth()->id()];
                }
                $sop->departments()->sync($syncData);
            } else {
                $sop->departments()->sync($ownerDeptId ? [$ownerDeptId => ['created_by' => auth()->id()]] : []);
            }

            DB::commit();

            // Notify: all entities = all users; visible to all in selected entity/entities = users in those entities; else owner department
            if ($applyToAllEntities) {
                $recipients = User::whereNotNull('email')->where('email', '!=', '')->get();
            } elseif ($visibleToAll && !empty($divisionIds)) {
                $deptIds = [];
                foreach ($divisionIds as $did) {
                    $division = Division::find($did);
                    if ($division) {
                        $deptIds = array_merge($deptIds, $division->departments()->pluck('departments.id')->toArray());
                    }
                }
                $deptIds = array_unique($deptIds);
                if (empty($deptIds) && $ownerDeptId) {
                    $deptIds = [$ownerDeptId];
                }
                $recipients = !empty($deptIds)
                    ? User::whereIn('deptId', $deptIds)->whereNotNull('email')->where('email', '!=', '')->get()
                    : collect();
            } else {
                $recipients = $ownerDeptId
                    ? User::where('deptId', $ownerDeptId)->whereNotNull('email')->where('email', '!=', '')->get()
                    : collect();
            }
            foreach ($recipients as $recipient) {
                try {
                    \Illuminate\Support\Facades\Mail::to($recipient->email)->queue(new \App\Mail\SopNotification($sop, $recipient, true));
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            Alert::success('Success', 'SOP created successfully.');
            return redirect()->route('sops.index');
        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();
            if (str_contains($message, 'Permission denied') || str_contains($message, 'failed to open stream') || str_contains($message, 'writable')) {
                $message = 'The PDF could not be saved. Please ensure the server has write permission to storage/app/public/sops. Contact your administrator.';
            }
            report($e);
            Alert::error('Error', 'Failed to create SOP: ' . $message);
            return back()->withInput();
        }
    }

    /**
     * Display the specified SOP
     */
    public function show(string $id)
    {
        $sop = Sop::with(['departments', 'division', 'divisions', 'ownerDepartment', 'creator', 'updatedBy', 'archivedBy'])->findOrFail($id);

        // QA/CEO/HEC can view any SOP; others only active and not expired
        if (!$this->canSeeAllSOPs() && $sop->status !== 'active') {
            Alert::error('Unauthorized', 'This SOP is not available.');
            return redirect()->route('sops.index');
        }
        // Hide expired SOPs from other users until QA updates (renews) them
        if (!$this->canSeeAllSOPs() && $sop->current_review_date && $sop->current_review_date->isPast()) {
            Alert::error('Not available', 'This SOP has expired and is not visible until it is renewed or updated.');
            return redirect()->route('sops.index');
        }

        // Line managers and staff: may view SOPs where their dept is owner OR in visibility list OR apply-to-all-entities OR SOP's division(s) include user's department's division(s)
        if (!$this->canSeeAllSOPs() && !auth()->user()->hasAnyRole(['super-admin', 'admin'])) {
            $userDeptId = auth()->user()->deptId ? (int) auth()->user()->deptId : null;
            $isAllEntities = $sop->division_id === null && $sop->global;
            $isOwnerDept = $userDeptId && (int) $sop->owner_department_id === $userDeptId;
            $isVisibleToDept = $userDeptId && $sop->departments->contains('id', $userDeptId);
            $userDivisionIds = [];
            if ($userDeptId) {
                $userDept = Departments::find($userDeptId);
                if ($userDept) {
                    $userDivisionIds = $userDept->divisions()->pluck('divisions.id')->toArray();
                }
            }
            $sopDivisionIds = $sop->divisions->isNotEmpty() ? $sop->divisions->pluck('id')->toArray() : ($sop->division_id ? [$sop->division_id] : []);
            $isVisibleByDivision = !empty($userDivisionIds) && !empty($sopDivisionIds) && count(array_intersect($userDivisionIds, $sopDivisionIds)) > 0;
            if (!$isAllEntities && (!$userDeptId || (!$isOwnerDept && !$isVisibleToDept && !$isVisibleByDivision))) {
                Alert::error('Unauthorized', 'You can only view SOPs for your department.');
                return redirect()->route('sops.index');
            }
        }

        // Record view count
        $sop->increment('view_count');
        $sop->refresh();

        $isQA = $this->isQualityAssurance();

        return view('sops.show', compact('sop', 'isQA'));
    }

    /**
     * Show the form for editing the specified SOP
     * Only Quality Assurance can edit SOPs
     */
    public function edit(string $id)
    {
        if (!$this->isQualityAssurance()) {
            Alert::error('Unauthorized', 'Only Quality Assurance can edit SOPs.');
            return redirect()->route('sops.index');
        }

        $sop = Sop::with(['departments', 'ownerDepartment', 'divisions'])->findOrFail($id);
        $divisions = Division::all();
        $departments = Departments::all();

        return view('sops.edit', compact('sop', 'divisions', 'departments'));
    }

    /**
     * Update the specified SOP
     * Only Quality Assurance can update SOPs
     */
    public function update(Request $request, $id)
    {
        if (!$this->isQualityAssurance()) {
            Alert::error('Unauthorized', 'Only Quality Assurance can update SOPs.');
            return redirect()->route('sops.index');
        }

        $applyToAllEntities = $request->has('apply_to_all_entities') && $request->apply_to_all_entities == 1;
        if ($applyToAllEntities) {
            $request->merge([
                'division_id' => null,
                'division_ids' => [],
                'owner_department_id' => null,
                'visible_to_all_departments' => 1,
            ]);
        } else {
            $divisionIds = $request->filled('division_ids') && is_array($request->division_ids)
                ? array_values(array_map('intval', array_filter($request->division_ids)))
                : ($request->filled('division_id') ? [(int) $request->division_id] : []);
            $request->merge(['division_ids' => $divisionIds]);
            $request->merge(['division_id' => count($divisionIds) === 1 ? ($divisionIds[0] ?? null) : ($divisionIds[0] ?? null)]);
        }

        $this->mergeNextReviewDate($request);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'division_id' => 'nullable|exists:divisions,id',
            'division_ids' => 'nullable|array',
            'division_ids.*' => 'exists:divisions,id',
            'owner_department_id' => 'nullable|required_unless:apply_to_all_entities,1|exists:departments,id',
            'departments' => 'nullable|array',
            'departments.*' => 'exists:departments,id',
            'pdf' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
            'effective_date' => 'required|date',
            'next_review_date' => 'required|date|after:effective_date',
            'version' => 'nullable|string|max:20',
            'global' => 'nullable|boolean',
            'visible_to_all_departments' => 'nullable|boolean',
            'apply_to_all_entities' => 'nullable|boolean',
        ]);

        if (!$applyToAllEntities && empty($request->division_ids) && !$request->filled('division_id')) {
            return back()->withErrors(['division_ids' => ['Select at least one entity/division or check Apply to all entities.']])->withInput();
        }

        DB::beginTransaction();
        try {
            $sop = Sop::findOrFail($id);
            $visibleToAll = $request->has('visible_to_all_departments') && $request->visible_to_all_departments == 1;
            $ownerDeptId = $request->filled('owner_department_id') ? (int) $request->owner_department_id : null;
            $divisionIds = $request->division_ids ?? ($request->filled('division_id') ? [(int) $request->division_id] : []);
            $sopDivisionId = $applyToAllEntities ? null : (count($divisionIds) === 1 ? ($divisionIds[0] ?? null) : null);

            $updateData = [
                'title' => $request->title,
                'document_code' => $sop->document_code,
                'description' => $request->description,
                'division_id' => $sopDivisionId,
                'owner_department_id' => $ownerDeptId,
                'effective_date' => $request->effective_date,
                'next_review_date' => $request->next_review_date,
                'expiry_date' => $request->next_review_date,
                'version' => $request->version ?? $sop->version,
                'global' => $visibleToAll || $applyToAllEntities,
                'updated_by' => auth()->id(),
            ];

            // Handle PDF upload: ensure directory exists, store with unique name
            if ($request->hasFile('pdf')) {
                if ($sop->pdf_path && Storage::exists('public/' . $sop->pdf_path)) {
                    Storage::delete('public/' . $sop->pdf_path);
                }
                $file = $request->file('pdf');
                $fileExtension = $file->getClientOriginalExtension();
                $originalName = basename($file->getClientOriginalName());
                Storage::disk('public')->makeDirectory('sops');
                $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $fileExtension;
                $uniqueName = time() . '_' . $safeName;
                $filePath = $file->storeAs('sops', $uniqueName, 'public');
                if ($filePath === false) {
                    throw new \RuntimeException('The PDF could not be saved. Please ensure the storage folder (storage/app/public/sops) is writable.');
                }
                $updateData['pdf_path'] = $filePath;
                $updateData['file_type'] = strtolower($fileExtension);
            }

            $sop->update($updateData);

            // Sync division pivot (multiple entities)
            if ($applyToAllEntities) {
                $sop->divisions()->sync([]);
            } else {
                $sop->divisions()->sync($divisionIds);
            }

            // Visibility: apply-to-all-entities = no departments; else entity-wide or owner only
            if ($applyToAllEntities) {
                $sop->departments()->sync([]);
            } elseif ($visibleToAll) {
                $allDeptIds = [];
                foreach ($divisionIds as $did) {
                    $division = Division::find($did);
                    if ($division) {
                        $allDeptIds = array_merge($allDeptIds, $division->departments()->pluck('departments.id')->toArray());
                    }
                }
                $allDeptIds = array_unique($allDeptIds);
                if (empty($allDeptIds) && $ownerDeptId) {
                    $allDeptIds = [$ownerDeptId];
                }
                $syncData = [];
                foreach ($allDeptIds as $deptId) {
                    $syncData[$deptId] = ['created_by' => auth()->id()];
                }
                $sop->departments()->sync($syncData);
            } else {
                $sop->departments()->sync($ownerDeptId ? [$ownerDeptId => ['created_by' => auth()->id()]] : []);
            }

            DB::commit();

            // Notify: all entities = all users; visible to all in selected entity/entities = users in those entities; else owner department
            if ($applyToAllEntities) {
                $recipients = User::whereNotNull('email')->where('email', '!=', '')->get();
            } elseif ($visibleToAll && !empty($divisionIds)) {
                $deptIds = [];
                foreach ($divisionIds as $did) {
                    $division = Division::find($did);
                    if ($division) {
                        $deptIds = array_merge($deptIds, $division->departments()->pluck('departments.id')->toArray());
                    }
                }
                $deptIds = array_unique($deptIds);
                if (empty($deptIds) && $ownerDeptId) {
                    $deptIds = [$ownerDeptId];
                }
                $recipients = !empty($deptIds)
                    ? User::whereIn('deptId', $deptIds)->whereNotNull('email')->where('email', '!=', '')->get()
                    : collect();
            } else {
                $recipients = $ownerDeptId
                    ? User::where('deptId', $ownerDeptId)->whereNotNull('email')->where('email', '!=', '')->get()
                    : collect();
            }
            foreach ($recipients as $recipient) {
                try {
                    \Illuminate\Support\Facades\Mail::to($recipient->email)->queue(new \App\Mail\SopNotification($sop, $recipient, false));
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            Alert::success('Success', 'SOP updated successfully.');
            return redirect()->route('sops.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Alert::error('Error', 'Failed to update SOP: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Archive the specified SOP
     * Only Quality Assurance can archive SOPs
     */
    public function archive($id)
    {
        if (!$this->isQualityAssurance()) {
            Alert::error('Unauthorized', 'Only Quality Assurance can archive SOPs.');
            return redirect()->route('sops.index');
        }

        $sop = Sop::findOrFail($id);
        $sop->archive(auth()->id());

        Alert::success('Success', 'SOP archived successfully.');
        return redirect()->route('sops.index');
    }

    /**
     * Restore an archived SOP
     * Only Quality Assurance can restore SOPs
     */
    public function restore($id)
    {
        if (!$this->isQualityAssurance()) {
            Alert::error('Unauthorized', 'Only Quality Assurance can restore SOPs.');
            return redirect()->route('sops.index');
        }

        $sop = Sop::findOrFail($id);
        $sop->update([
            'status' => 'active',
            'archived_at' => null,
            'archived_by' => null,
            'updated_by' => auth()->id(),
        ]);

        Alert::success('Success', 'SOP restored successfully.');
        return redirect()->route('sops.index');
    }

    /**
     * Remove the specified SOP from storage
     * Only Quality Assurance can delete SOPs
     */
    public function destroy(string $id)
    {
        if (!$this->isQualityAssurance()) {
            Alert::error('Unauthorized', 'Only Quality Assurance can delete SOPs.');
            return redirect()->route('sops.index');
        }

        $sop = Sop::findOrFail($id);

        // Delete the file
        if ($sop->pdf_path && Storage::exists('public/' . $sop->pdf_path)) {
            Storage::delete('public/' . $sop->pdf_path);
        }

        $sop->delete();

        Alert::success('Success', 'SOP deleted successfully.');
        return redirect()->route('sops.index');
    }

    /**
     * Get departments by entity/division (AJAX)
     */
    public function getDepartmentsByDivision($divisionId)
    {
        $division = Division::findOrFail($divisionId);
        $departments = $division->departments()->get(['departments.id', 'departments.dept_name']);

        return response()->json($departments);
    }

    /**
     * Show SOPs for the authenticated user's department
     */
    public function mySops()
    {
        $user = auth()->user();
        $sops = Sop::with(['departments', 'division'])
            ->active()
            ->forDepartment($user->deptId)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('sops.my-sops', compact('sops'));
    }

    /**
     * Show expiring SOPs (QA/CEO/HEC see all; line managers and others see only their department)
     */
    public function expiring()
    {
        $user = auth()->user();
        $isQA = $this->isQualityAssurance();
        $isLineManager = $this->isLineManager();
        $canSeeAll = $this->canSeeAllSOPs();

        $query = Sop::with(['departments', 'division', 'ownerDepartment'])
            ->active()
            ->expiringSoon(60);

        // QA/CEO/HEC see all; line managers and others see only SOPs for their department (owner_department_id)
        if (!$canSeeAll) {
            $userDeptId = $user->deptId ? (int) $user->deptId : null;
            if ($userDeptId) {
                $query->where('owner_department_id', $userDeptId);
            } else {
                $query->whereRaw('1 = 0'); // No department = no SOPs
            }
        }

        $sops = $query->orderByRaw('COALESCE(next_review_date, expiry_date) asc')->get();

        return view('sops.expiring', compact('sops', 'isQA', 'isLineManager'));
    }
}
