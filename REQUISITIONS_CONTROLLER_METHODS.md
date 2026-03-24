# Requisitions Module - Controller Methods & Data Flow

## 🎯 RequisitionController Methods Overview

**Class:** `App\Http\Controllers\RequisitionController`  
**File:** `app/Http/Controllers/RequisitionController.php` (3,180 lines)

---

## 📋 Main Methods

### 1️⃣ `index()` - Display User's Requisitions

```php
public function index()
```

**Route:** `GET /requisitions` → `requisitions.index`  
**View:** `requisitions/index.blade.php`

**Flow:**
```
1. Get current user
2. Check permissions:
   - 'access requisitions form' permission
   - OR hasRole('line-manager')
   - OR hasAnyRole(['coo', 'cms', 'cfo', 'crhdo', 'chief_accountant'])
3. Query requisitions WHERE user_id = auth()->id()
4. Load relations:
   - user.department
   - user.roles
   - jobTitle
   - workflow.histories
5. Order by created_at DESC
6. Return view with $requisitions
```

**Variables Passed to View:**
- `$requisitions` - Collection of user's requisitions
- `$isHecMember` - Boolean flag for HEC members

**Display Columns:**
- Reference (access_id) - linked to show()
- Position Type
- Department (dept_name)
- Initiated By (initiator_name + initiator_type)
- Status (getStatusLabel() + badge color)
- Submitted Date (created_at)
- Action button → show()

---

### 2️⃣ `pending()` - Display Pending Approvals

```php
public function pending(Request $request)
```

**Route:** `GET /requisitions/pending` → `requisitions.pending`  
**View:** `requisitions/pending.blade.php`

**Flow:**
```
1. Get current user
2. Check user role:
   IF hasRole('hr'):
      - Get requisitions where:
        * workflow.work_flow_completed = 0
        * workflow_histories.attended_by = user.id
        * workflow_histories.requisition_status IN [1, 3, -1]
      
   ELSE:
      - Get requisitions where:
        * workflow.work_flow_completed = 0
        * workflow_histories.attended_by = user.id
        * workflow_histories.requisition_status IN [0, 1]
        * workflow_histories.decision_date IS NULL

3. Load relations with histories
4. Order by created_at DESC
5. Group by month/status
6. Return view with $requisitions
```

**Access Control:**
- Payroll Accountant: See pending payroll reviews
- HEC Members: See pending HEC reviews
- CFO: See pending CFO approvals
- CEO: See pending CEO approvals
- HR: See all step reviews

**Display:**
- Grouped by approval step/month
- Reference ID → show()
- Department
- Initiated By
- Submitted Date
- Current Status
- Action button → show() to review

---

### 3️⃣ `create()` - Display New Requisition Form

```php
public function create()
```

**Route:** `GET /requisitions/create` → `requisitions.create`  
**View:** `requisitions/create.blade.php`

**Flow:**
```
1. Get current user
2. Check permissions:
   - 'create new requisition' permission
   - OR hasRole('line-manager')
   - OR hasAnyRole(['coo', 'cms', 'cfo', 'crhdo', 'chief_accountant'])
   
3. Determine user type:
   - isHecMember = hasAnyRole(['coo', 'cms', ...])
   - isLineManager = hasRole('line-manager')

4. Get departments:
   IF isHecMember:
      - Get all departments mapped to user:
        * WHERE hec_member_id = user.id
        * Plus fallback by HEC role level mapping
   ELSE (Line Manager):
      - Get only user's own department:
        * WHERE deptId = user.deptId

5. For each department, get:
   - Job Titles: WHERE deptId = department.id
   - Users (staff): WHERE deptId = department.id AND status = 'active'
   - Line Managers: WHERE role('line-manager') AND deptId = department.id

6. Return view with $departments, $jobTitles, $users, ...
```

**Variables Passed to View:**
- `$jobTitles` - Available job titles
- `$departments` - Departments user can create requisitions for
- `$users` - Staff members (for replacement/renewal)
- `$isHecMember` - Boolean
- `$isLineManager` - Boolean
- `$lineManagers` - Available line managers

**Form Sections Displayed:**
1. **Position Type Selection** (radio buttons)
   - New Position
   - Replacement
   - Contract Renewal/Extension

2. **Position Details**
   - Department (dropdown for HEC, auto for line manager)
   - Responsibility Centre
   - Reporting Line
   - Employee Name(s) - conditional on position type

3. **Position Requirements**
   - Job Title
   - Contract Type
   - Required Start Date
   - Job Description (file upload)

4. **Justification**
   - Condition checkboxes (medical, safety, legal, financial, income)
   - Elaborate reason (textarea)

5. **Budget Information**
   - Budget Approved (checkbox)
   - Max Monthly Budget
   - Funding Available (radio)
   - Donor Code
   - Activity Code

---

### 4️⃣ `store()` - Save New Requisition

```php
public function store(Request $request)
```

**Route:** `POST /requisitions` → `requisitions.store`  
**Redirects to:** `requisitions.show`

**Flow:**
```
1. Validate request data
   - All required fields
   - File validation (job description)
   - Budget amounts and numeric fields

2. Create Requisition:
   - Boot method auto-generates access_id (RRF-0001)
   - Set fields from request
   - Set initiator_type & initiator_name
   - Set status = 'draft'
   - Save to database

3. Handle file uploads:
   - Job description → storage/job_descriptions/

4. Create Workflow:
   - Create Workflow record linked to requisition
   - Set work_flow_completed = 0
   - Create WorkFlowHistory entries for each approval step

5. Set current_step = 'payroll_review' (first step)

6. Send email notifications:
   - To payroll accountant: New requisition pending their review
   - To creator: Confirmation of submission

7. Redirect to:
   route('requisitions.show', $requisition->access_id)
   with success message
```

**Access Control:**
- Only users checked in `create()` can access

**Validation Rules (Simplified):**
```php
'position_type' => 'required|in:new_position,replacement,contract_renewal',
'department_id' => 'required|exists:departments,id',
'job_title_id' => 'required|exists:job_titles,id',
'contract_type' => 'required|in:minimal_1_year,...',
'required_start_date' => 'required|date|after:today',
'max_monthly_budget' => 'required|numeric|min:0',
'job_description_path' => 'file|mimes:pdf,doc,docx|max:5120',
```

---

### 5️⃣ `show()` - Display Full Requisition Details

```php
public function show(Request $request, $id)
```

**Route:** `GET /requisitions/{accessId}` → `requisitions.show`  
**View:** `requisitions/show.blade.php`  
**Parameter:** `$id` can be access_id (RRF-0001) or numeric ID

**Flow:**
```
1. Query requisition by ID:
   - Load user.department.hec
   - Load user.department.hecMember
   - Load department
   - Load jobTitle
   - Load workflow.histories.forwardedBy
   - Load workflow.histories.attendedBy

2. Check access control:
   - isCreator? = requisition.user_id == auth()->id()
   - isApprover? = In workflow.histories.attended_by
   - isLineManager? & relevant department?
   - isHecMember? & manages department or is approver?

3. If NOT (creator OR approver OR line manager OR HEC):
   - abort(403, 'Access Denied')

4. Additional queries:
   - Join users table for created_by info
   - Join workflow, work_flow_histories
   - Join departments
   - Join job_titles
   - Join hecs for HEC level info

5. Determine approver actions:
   - Get current user's pending approval form (if applicable)
   - Determine which review form to display:
     * payroll_review form if status = 'pending_payroll'
     * hec_review form if status = 'pending_hec'
     * cfo_review form if status = 'pending_cfo'
     * etc.

6. Format workflow history:
   - Group by step
   - Show decision made at each step
   - Show comments from approvers
   - Show forward chain (who forwarded to whom)

7. Return view with all data
```

**Variables Passed to View:**
- `$requisition` - Full requisition with all relations
- `$isCreator` - Boolean
- `$isApprover` - Boolean
- `$isLineManager` - Boolean
- `$isHecMember` - Boolean
- `$canViewAsLineManager` - Boolean
- `$canViewAsHecMember` - Boolean

**Display Sections:**
1. Request Summary (basic info)
2. Section A: Position Details (read-only)
3. Section B: Position Requirements (read-only)
4. Section C: Justification (read-only)
5. Section D: Budget Information (read-only)
6. Workflow History (timeline of approvals)
7. Approval/Review Forms (if current user is next approver):
   - Payroll Review Form
   - HEC Review Form
   - CFO Review Form
   - CEO Review Form
   - HR Review Form
8. Rejection Alert (if status = 'rejected_for_editing')
9. Edit/Resubmit Buttons (if within 30-day window)

---

### 6️⃣ `edit()` - Display Edit Form for Rejected Requisition

```php
public function edit(Request $request, $id)
```

**Route:** `GET /requisitions/{accessId}/edit` → `requisitions.edit`  
**View:** `requisitions/edit.blade.php`

**Flow:**
```
1. Get requisition by ID
2. Check access:
   - Only creator can edit
   - Only if status = 'rejected_for_editing'
   - Only if within 30-day window (can_edit_until > now())
   - abort(403) if not allowed

3. Load all the same data as create():
   - Departments
   - Job Titles
   - Staff
   - Line Managers

4. Pre-populate form with existing data:
   - Loop through $requisition fields
   - Set old() values or requisition data
   - Display existing file names

5. Return edit view with pre-filled form
```

**Access Control:**
- Only requisition creator
- Only if rejected_for_editing status
- Only if canBeEdited() = true (within 30 days)

**Template:**
- Same form as create() but with old() values and existing data

---

### 7️⃣ `update()` - Save Edited Requisition

```php
public function update(Request $request, $id)
```

**Route:** `PUT /requisitions/{accessId}` → `requisitions.update`  
**Redirects to:** `requisitions.show`

**Flow:**
```
1. Get requisition
2. Check permissions (creator only)
3. Validate new data (same rules as store)
4. Update requisition fields
5. Handle new file uploads
6. Keep status as 'rejected_for_editing'
7. Clear rejection flags
8. Redirect to show() with success message
```

---

### 8️⃣ `resubmit()` - Resubmit Edited Requisition

```php
public function resubmit(Request $request, $id)
```

**Route:** `POST /requisitions/{accessId}/resubmit` → `requisitions.resubmit`  
**Redirects to:** `requisitions.show`

**Flow:**
```
1. Get requisition
2. Check permissions (creator only)
3. Validate canBeEdited() = true

4. Reset workflow:
   - Delete existing workflow_histories
   - Create new histories for fresh workflow
   - Reset current_step = 'payroll_review'

5. Update requisition:
   - Set status = 'pending_payroll'
   - Clear rejection_reason
   - Clear rejection_stage
   - Clear rejected_by, rejected_at

6. Send notifications:
   - To payroll: Resubmitted requisition awaiting review
   - To creator: Confirmation of resubmission

7. Redirect to show()
```

---

### 9️⃣ `payrollReview()` - Payroll Accountant Approval

```php
public function payrollReview(Request $request, $id)
```

**Route:** `POST /requisitions/{accessId}/payroll-review` → `requisitions.payroll-review`  
**Access:** Only payroll_accountant role

**Flow:**
```
1. Get requisition
2. Validate user is payroll accountant
3. Get decision from request:
   - 'approve' → move to next step
   - 'reject' → set to rejected_for_editing
   - 'more_info' → request more info

4. Update requisition:
   - payroll_reviewed_at = now()
   - payroll_comment = request.comment
   - payroll_accountant_id = auth()->id()

5. Update workflow:
   IF approved:
      - Mark payroll step complete
      - Set current_step = 'hec_review'
      - Set status = 'pending_hec'
      - Create new WorkFlowHistory entry
   
   ELSEIF rejected:
      - Set status = 'rejected_for_editing'
      - Set rejection_reason = request.reason
      - Set rejection_stage = 'payroll'
      - Set can_edit_until = now()->addDays(30)
      - Send email to creator

6. Send notifications

7. Redirect to show() with message
```

---

### 🔟 `hecReview()` - HEC Review

```php
public function hecReview(Request $request, $id)
```

**Route:** `POST /requisitions/{accessId}/hec-review` → `requisitions.hec-review`  
**Access:** HEC members only (coo, cms, cfo, crhdo)

**Similar to payrollReview but:**
- Gets HEC financial decision
- Gets proposed funding source
- Updates hec_financial_decision field
- Sets current_step = 'cfo_review' on approval
- May trigger CFO auto-approval if conditions met

---

### 1️⃣1️⃣ `cfoReview()` - CFO Approval

```php
public function cfoReview(Request $request, $id)
```

**Route:** `POST /requisitions/{accessId}/cfo-review` → `requisitions.cfo-review`  
**Access:** CFO role only

**Similar flow:**
- Gets CFO decision (approved/rejected/more_info)
- Gets financing code
- Updates cfo_financing_confirmation
- Sets current_step = 'ceo_review' on approval

---

### 1️⃣2️⃣ `ceoReview()` - CEO Approval

```php
public function ceoReview(Request $request, $id)
```

**Route:** `POST /requisitions/{accessId}/ceo-review` → `requisitions.ceo-review`  
**Access:** CEO role only

**Similar flow:**
- Gets CEO decision
- Sets current_step = 'hr_review' on approval

---

### 1️⃣3️⃣ `hrReview()` - HR Final Approval

```php
public function hrReview(Request $request, $id)
```

**Route:** `POST /requisitions/{accessId}/hr-review` → `requisitions.hr-review`  
**Access:** HR role only

**Similar flow:**
- Gets HR decision: 'approved' or 'filed'
- Sets status = 'approved' or 'filed'
- Sets work_flow_completed = 1
- Creates final workflow history
- Sends completion email to creator

---

### AJAX Helper Methods

#### `getEmployeesByDepartment()`
**Route:** `GET /requisitions/employees` → `requisitions.employees`  
**Returns:** JSON list of staff in department

```
Used by: create.blade.php
When: User selects "replacement" position type
Returns: [{id, name, username, contract_end_date}, ...]
```

#### `getLineManagersByDepartment()`
**Route:** `GET /requisitions/line-managers` → `requisitions.line-managers`  
**Returns:** JSON list of line managers in department

```
Used by: create.blade.php (for HEC members)
When: Department selected
Returns: [{id, name, username, job_title_id}, ...]
```

#### `getJobTitlesByDepartment()`
**Route:** `GET /requisitions/job-titles` → `requisitions.job-titles`  
**Returns:** JSON list of job titles in department

```
Used by: create.blade.php
When: Department selected
Returns: [{id, title}, ...]
```

#### `downloadJobDescription()`
**Route:** `GET /requisitions/{accessId}/download-jd` → `requisitions.download-jd`  
**Returns:** PDF file download

```
Retrieves job description file and triggers download
```

---

## 🔄 Data Flow Diagram: Creating a Requisition

```
User (Line Manager/HEC)
         ↓
  visits /requisitions/create
         ↓
  RequisitionController::create()
         ├─ Check permissions
         ├─ Get departments
         ├─ Get job titles
         ├─ Get staff
         └─ Return create.blade.php
         ↓
  User fills form
         ↓
  Submits POST /requisitions
         ↓
  RequisitionController::store()
         ├─ Validate form
         ├─ Create Requisition record
         ├─ Auto-generate access_id (RRF-0001)
         ├─ Upload files
         ├─ Create Workflow record
         ├─ Create WorkFlowHistory entries
         ├─ Send email to payroll
         └─ Redirect to /requisitions/RRF-0001
         ↓
  RequisitionController::show()
         ├─ Check access
         ├─ Load all relations
         ├─ Check current approval step
         └─ Returns show.blade.php
         ↓
  User sees confirmation & details
```

---

## 🔄 Data Flow Diagram: Approval Process

```
Requisition at status: pending_payroll
         ↓
  Payroll Accountant
  visits /requisitions/RRF-0001
         ↓
  RequisitionController::show()
         └─ Shows payroll review form
         ↓
  Payroll fills form & submits
  POST /requisitions/RRF-0001/payroll-review
         ↓
  RequisitionController::payrollReview()
         ├─ Validate decision
         ├─ Update requisition fields
         ├─ Update status → pending_hec
         ├─ Set current_step → hec_review
         ├─ Create WorkFlowHistory
         ├─ Send email to HEC
         └─ Redirect to show
         ↓
  Now in HEC's approval queue
         ↓
  HEC Member
  visits /requisitions/pending
         └─ Sees RRF-0001 awaiting HEC review
         ↓
  Clicks to view /requisitions/RRF-0001
         ↓
  RequisitionController::show()
         └─ Shows HEC review form
         ↓
  [Process repeats through CFO → CEO → HR]
```

---

## 📊 Status State Machine

```
START (not in DB) 
    ↓ create()
DRAFT (initial)
    ↓ store() 
PENDING_PAYROLL
    ├─ payrollReview(approve)
    │  ↓
    │ PENDING_HEC
    │  ├─ hecReview(approve)
    │  │  ↓
    │  │ PENDING_CFO
    │  │  ├─ cfoReview(approve)
    │  │  │  ↓
    │  │  │ PENDING_CEO
    │  │  │  ├─ ceoReview(approve)
    │  │  │  │  ↓
    │  │  │  │ PENDING_HR
    │  │  │  │  ├─ hrReview(approved)
    │  │  │  │  │  ↓
    │  │  │  │  │ APPROVED ✅
    │  │  │  │  │
    │  │  │  │  └─ hrReview(filed)
    │  │  │  │     ↓
    │  │  │  │     FILED
    │  │  │  │
    │  │  │  └─ ceoReview(reject)
    │  │  │     ↓
    │  │  │  REJECTED_FOR_EDITING (30-day window)
    │  │  │     ├─ edit/update
    │  │  │     └─ resubmit() → PENDING_PAYROLL
    │  │  │
    │  │  └─ cfoReview(reject)
    │  │     ↓
    │  │  REJECTED_FOR_EDITING
    │  │
    │  └─ hecReview(reject)
    │     ↓
    │  REJECTED_FOR_EDITING
    │
    └─ payrollReview(reject)
       ↓
    REJECTED_FOR_EDITING (30-day window)
       ├─ Can edit & resubmit within 30 days
       └─ Expires after 30 days (shows as EXPIRED)
```

---

## ✅ Summary

The Requisitions module has:
- **13 controller methods** handling different phases
- **18 routes** across GET/POST/PUT verbs
- **5-level approval chain** (Payroll → HEC → CFO → CEO → HR)
- **Rejection/Edit workflow** with 30-day edit window
- **Role-based access** with different views per role
- **Automatic workflow creation** on submission
- **Email notifications** at each step
- **AJAX helpers** for dynamic form population

All methods work together to provide a complete recruitment requisition management system.
