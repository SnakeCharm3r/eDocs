# Requisitions Module - Complete Analysis

## 📋 Overview
The Requisitions module manages recruitment requisitions with a multi-stage approval workflow. It handles new positions, replacements, and contract renewals.

---

## 🛣️ Routes Definition

**File:** [routes/web.php](routes/web.php#L579-L597)

```php
Route::prefix('requisitions')->name('requisitions.')->group(function () {
    Route::get('/', [RequisitionController::class, 'index'])->name('index');
    Route::get('/pending', [RequisitionController::class, 'pending'])->name('pending');
    Route::get('/create', [RequisitionController::class, 'create'])->name('create');
    Route::post('/', [RequisitionController::class, 'store'])->name('store');
    Route::get('/employees', [RequisitionController::class, 'getEmployeesByDepartment'])->name('employees');
    Route::get('/line-managers', [RequisitionController::class, 'getLineManagersByDepartment'])->name('line-managers');
    Route::get('/job-titles', [RequisitionController::class, 'getJobTitlesByDepartment'])->name('job-titles');
    Route::get('/{accessId}', [RequisitionController::class, 'show'])->name('show');
    Route::get('/{accessId}/download-jd', [RequisitionController::class, 'downloadJobDescription'])->name('download-jd');
    Route::post('/{accessId}/payroll-review', [RequisitionController::class, 'payrollReview'])->name('payroll-review');
    Route::post('/{accessId}/hec-review', [RequisitionController::class, 'hecReview'])->name('hec-review');
    Route::post('/{accessId}/cfo-review', [RequisitionController::class, 'cfoReview'])->name('cfo-review');
    Route::post('/{accessId}/ceo-review', [RequisitionController::class, 'ceoReview'])->name('ceo-review');
    Route::post('/{accessId}/hr-review', [RequisitionController::class, 'hrReview'])->name('hr-review');
    Route::get('/{accessId}/edit', [RequisitionController::class, 'edit'])->name('edit');
    Route::put('/{accessId}', [RequisitionController::class, 'update'])->name('update');
    Route::post('/{accessId}/resubmit', [RequisitionController::class, 'resubmit'])->name('resubmit');
});
```

---

## 📍 Main Routes & Their Views

### 1. **GET /requisitions** → Display All User's Requisitions
| Aspect | Details |
|--------|---------|
| **Route Name** | `requisitions.index` |
| **Controller Method** | `RequisitionController::index()` |
| **View File** | [resources/views/requisitions/index.blade.php](resources/views/requisitions/index.blade.php) |
| **Purpose** | Show all requisitions initiated by the current user |
| **Access Control** | Users with 'access requisitions form' permission, line managers, HEC members |

#### Controller Logic:
```php
public function index()
{
    // Only show requisitions created by current user
    $requisitions = Requisition::where('user_id', auth()->id())
        ->with(['user.department', 'user.roles', 'jobTitle', 'workflow.histories'])
        ->orderBy('created_at', 'desc')
        ->get();
    
    return view('requisitions.index', compact('requisitions'));
}
```

#### View Display:
- **Page Title:** "Recruitment Requisitions"
- **Content:** DataTable with columns:
  - Reference (access_id) - **linked to `requisitions.show`**
  - Position Type (badge)
  - Department
  - Initiated By
  - Status (badge with color coding)
  - Submitted Date
  - Actions (View button → `requisitions.show`)

#### Link to Details Page:
```blade
<a href="{{ route('requisitions.show', $req->access_id) }}" 
   class="fw-semibold text-primary">
    {{ $req->access_id }}
</a>
```

**Status Badges:**
- `draft` → Secondary (gray)
- `pending_payroll` → Info (blue)
- `pending_hec` → Warning (yellow)
- `pending_cfo` → Warning
- `pending_ceo` → Warning
- `pending_hr` → Info (blue)
- `approved` → Success (green)
- `rejected` → Danger (red)
- `rejected_for_editing` → Warning (editable) or Secondary (expired)
- `filed` → Dark

---

### 2. **GET /requisitions/pending** → Display Approvers' Pending Items

| Aspect | Details |
|--------|---------|
| **Route Name** | `requisitions.pending` |
| **Controller Method** | `RequisitionController::pending()` |
| **View File** | [resources/views/requisitions/pending.blade.php](resources/views/requisitions/pending.blade.php) |
| **Purpose** | Show requisitions awaiting approval from the current user |
| **Access Control** | Payroll accountant, HEC members, CFO, CEO, HR |

#### Controller Logic (Simplified):
```php
public function pending(Request $request)
{
    $user = auth()->user();
    
    // Different logic based on user role
    if ($user->hasRole('payroll_accountant')) {
        // Get requisitions pending payroll review
    } elseif ($user->hasAnyRole(['coo', 'cms', 'cfo', 'ccdro'])) {
        // Get requisitions pending HEC review
    } elseif ($user->hasRole('hr')) {
        // Get requisitions pending HR review
    }
    
    return view('requisitions.pending', compact('requisitions'));
}
```

#### View Display:
- **Page Title:** "Pending Requests"
- **Subtitle:** Shows approval stage (e.g., "Payroll Review: 5", "HEC Review: 3")
- **Content:** DataTable with columns:
  - Reference (access_id) - **linked to `requisitions.show`**
  - Position Type
  - Department
  - Initiated By
  - Submitted Date
  - Status
  - Actions (Review button → `requisitions.show`)

---

### 3. **GET /requisitions/create** → Create New Requisition Form

| Aspect | Details |
|--------|---------|
| **Route Name** | `requisitions.create` |
| **Controller Method** | `RequisitionController::create()` |
| **View File** | [resources/views/requisitions/create.blade.php](resources/views/requisitions/create.blade.php) |
| **Purpose** | Display form to create a new requisition |
| **Access Control** | Users with 'create new requisition' permission, line managers, HEC members |

#### Form Sections:
**Section A: Position Details**
- Position Type (radio buttons):
  - New Position
  - Replacement
  - Contract Renewal/Extension
- Department (dropdown for HEC members, auto-filled for line managers)
- Responsibility Centre
- Reporting Line
- Employee Name(s) (if replacement/renewal)

**Section B: Position Requirements**
- Job Title
- Contract Type (Employment, Consultant, Volunteer, Work Exposure)
- Required Start Date
- Job Description (file upload)

**Section C: Justification**
- Multiple condition checkboxes
- Detailed reason explanation

**Section D: Budget**
- Budget Approved
- Max Monthly Budget
- Funding Available
- Donor Code
- Activity Code

---

### 4. **POST /requisitions** → Store New Requisition

| Aspect | Details |
|--------|---------|
| **Route Name** | `requisitions.store` |
| **Controller Method** | `RequisitionController::store()` |
| **Processes** | - Validates form data<br/>- Creates Requisition record<br/>- Generates unique access_id (RRF-0001, etc.)<br/>- Creates Workflow record<br/>- Sends notifications |
| **Redirects To** | `requisitions.show` with success message |

#### Access ID Generation:
Auto-incremented format: `RRF-0001`, `RRF-0002`, etc.

---

### 5. **GET /requisitions/{accessId}** → View Full Requisition Details

| Aspect | Details |
|--------|---------|
| **Route Name** | `requisitions.show` |
| **Controller Method** | `RequisitionController::show($id)` |
| **View File** | [resources/views/requisitions/show.blade.php](resources/views/requisitions/show.blade.php) |
| **Purpose** | Display complete requisition with all details and approval workflows |
| **Access Control** | Creator, approvers in workflow, HEC members, HR |
| **Parameter** | `$accessId` (e.g., `RRF-0001`) - can also be ID |

#### Access Control Logic:
- **Creator:** Can always view
- **Workflow Approvers:** Users in workflow histories
- **Line Manager:** Can view completed forms from their department
- **HEC Member:** Can view if they're assigned to review or manage the department

#### Display Includes:
1. **Request Summary**
   - Reference (access_id)
   - Submitted date
   - Department
   - Initiated by (with signature)
   - Position type

2. **Section A: Position Details** (read-only)
   - Department
   - Employee info (if applicable)
   - Reporting line

3. **Section B-D: Requirements, Justification, Budget** (read-only)

4. **Workflow History** (accordion view)
   - Shows all approval steps in order
   - Each step shows: Status, Attended By, Decision, Comments

5. **Approval/Review Forms** (if current user is next approver)
   - Payroll Review Form
   - HEC Review Form
   - CFO Review Form
   - CEO Review Form
   - HR Review Form

6. **Rejection Alert** (if rejected)
   - Shows rejection stage and reason
   - Shows days remaining to edit (if within 30 days)
   - Edit button (if editing allowed)

---

### 6. **GET /requisitions/{accessId}/edit** → Edit Rejected Requisition

| Aspect | Details |
|--------|---------|
| **Route Name** | `requisitions.edit` |
| **Controller Method** | `RequisitionController::edit($id)` |
| **View File** | [resources/views/requisitions/edit.blade.php](resources/views/requisitions/edit.blade.php) |
| **Purpose** | Edit requisition that was rejected |
| **Access Control** | Original creator only |
| **Time Limit** | 30 days from rejection date |

---

### 7. **POST /requisitions/{accessId}/payroll-review** → Payroll Accountant Review

| Aspect | Details |
|--------|---------|
| **Route Name** | `requisitions.payroll-review` |
| **Controller Method** | `RequisitionController::payrollReview($id)` |
| **Processes** | - Updates payroll_reviewed_at<br/>- Records payroll_comment<br/>- Updates workflow status<br/>- Creates workflow history entry |

---

### 8. **POST /requisitions/{accessId}/hec-review** → HEC Review

| Aspect | Details |
|--------|---------|
| **Route Name** | `requisitions.hec-review` |
| **Controller Method** | `RequisitionController::hecReview($id)` |
| **Processes** | - Validates HEC financial decision<br/>- Updates hec_reviewed_at<br/>- Records comments<br/>- Updates workflow |

---

### 9. **POST /requisitions/{accessId}/resubmit** → Resubmit After Rejection

| Aspect | Details |
|--------|---------|
| **Route Name** | `requisitions.resubmit` |
| **Controller Method** | `RequisitionController::resubmit($id)` |
| **Processes** | - Resets status to 'pending_payroll'<br/>- Clears rejection flags<br/>- Resets workflow<br/>- Sends new notifications |

---

## 📊 Requisition Model

**File:** [app/Models/Requisition.php](app/Models/Requisition.php)

### Key Attributes:
- `id` - Primary key
- `access_id` - Unique identifier (RRF-0001)
- `user_id` - Requisition creator
- `position_type` - new_position|replacement|contract_renewal
- `department_id` - Target department
- `status` - Current workflow status
- `current_step` - Current approval stage

### Status Values:
```
draft → pending_payroll → pending_hec → pending_cfo → pending_ceo → pending_hr → approved/filed
```

### Relationships:
- `user()` - Belongs to User (creator)
- `department()` - Belongs to Department
- `jobTitle()` - Belongs to JobTitle
- `workflow()` - Has one Workflow (approval chain)

### Status/Display Methods:
```php
getStatusLabel()           // Returns human-readable status
getStatusBadgeClass()      // Returns CSS class for badge color
getPositionTypeLabel()     // Returns position type label
canBeEdited()              // Check if within 30-day edit window
getDaysUntilExpiration()   // Remaining edit days
```

---

## 🔄 Approval Workflow

```
Creator submits requisition
    ↓
Payroll Accountant reviews (financial data validation)
    ↓
HEC reviews (needs analysis, funding source)
    ↓
CFO reviews (financing confirmation)
    ↓
CEO reviews (strategic approval)
    ↓
HR processes (final approval)
    ↓
Filed or Approved
```

Each step can:
- ✅ **Approve** → moves to next stage
- ❌ **Reject** → returns to creator for 30-day editing window
- ℹ️ **Request More Info** → sends back before approval

---

## 🔐 Access Control Matrix

| User Type | Can Create | Can View Own | View Pending | Edit Own | Edit Rejected |
|-----------|-----------|-------------|-------------|---------|---------------|
| Line Manager | ✅ | ✅ | ✗ | ✅ | ✅ (within 30d) |
| HEC Member | ✅ | ✅ | ✅ | ✅ | ✅ (within 30d) |
| HR | ✗ | ✅ | ✅ | ✗ | ✗ |
| Payroll | ✗ | ✗ | ✅ | ✗ | ✗ |
| CFO | ✗ | ✗ | ✅ | ✗ | ✗ |
| CEO | ✗ | ✗ | ✅ | ✗ | ✗ |

---

## 📱 View Files in Directory

**Location:** `resources/views/requisitions/`

| File | Purpose |
|------|---------|
| `index.blade.php` | List all user's requisitions (3180 lines) |
| `pending.blade.php` | List pending approvals for current approver (202 lines) |
| `create.blade.php` | Form to create new requisition (1031 lines) |
| `edit.blade.php` | Form to edit rejected requisition |
| `show.blade.php` | Full details and approval interface (1280 lines) |
| `partials/` | Sub-components (dropdowns, etc.) |

---

## 🎯 Key Features

1. **Multi-Stage Approval Workflow**
   - 5-level approval chain (Payroll → HEC → CFO → CEO → HR)
   - Each approver can approve, reject, or request more info

2. **Rejection & Edit Window**
   - Rejected requisitions can be edited within 30 days
   - Auto-expires after 30 days
   - Clear visual feedback on remaining time

3. **Role-Based Access**
   - Line managers see only their department's positions
   - HEC members manage multiple departments
   - Approvers see only items awaiting their approval

4. **Position Types**
   - New Position
   - Replacement (existing employee replacement)
   - Contract Renewal/Extension

5. **Budget Tracking**
   - Max monthly budget specification
   - Funding source tracking (Donor Code, Activity Code)
   - CFO financing confirmation with codes

6. **Workflow Transparency**
   - Complete history of all decisions
   - Comments from each approver
   - Signature/approval timestamps

---

## 🔗 Navigation Links

**From Dashboard/Menu:**
- `/requisitions` → View my requisitions
- `/requisitions/pending` → View approvals awaiting me
- `/requisitions/create` → Create new requisition

**From List Views:**
- Reference ID → `requisitions.show` (view full details)
- View button → `requisitions.show` (view full details)

**From Details Page:**
- Edit button → `requisitions.edit` (if rejected and within 30 days)
- Resubmit button → `requisitions.resubmit` (after editing)
- Approval forms → `requisitions.payroll-review`, etc. (if you're the approver)

---

## 📝 Database Connection

The requisitions are tracked via:
- `requisitions` table - Main requisition data
- `workflows` table - Approval chain
- `work_flow_histories` table - Individual approval steps

Query for all requisitions awaiting approval:
```sql
SELECT r.* FROM requisitions r
JOIN workflows w ON w.requisition_id = r.id
JOIN work_flow_histories wfh ON wfh.work_flow_id = w.id
WHERE wfh.attended_by = ? AND wfh.requisition_status IS NULL
```

---

## ✨ Summary

The Requisitions module provides a complete recruitment request management system with:
1. Multi-stage approval workflows
2. Role-based access control
3. Clear status tracking
4. Edit windows for rejected submissions
5. Complete audit trails
6. Budget and financial tracking

The main entry point is **`/requisitions`** which shows the user's own requisitions, with links to individual requisition details at **`/requisitions/{reference-id}`** (e.g., `/requisitions/RRF-0001`).
