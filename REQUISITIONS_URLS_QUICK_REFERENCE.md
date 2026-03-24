# Requisitions Module - Quick URL Reference

## 🔗 All Requisition URLs at a Glance

### Core Navigation URLs

```
GET /requisitions
├─ Purpose: Show all requisitions created by current user
├─ Route Name: requisitions.index
├─ Controller: RequisitionController::index()
├─ View: requisitions/index.blade.php
├─ Users: Creators, Line Managers, HEC Members
└─ Shows: Title, Department, Status, Links to View

GET /requisitions/pending
├─ Purpose: Show pending approvals for current user
├─ Route Name: requisitions.pending
├─ Controller: RequisitionController::pending()
├─ View: requisitions/pending.blade.php
├─ Users: Payroll, HEC, CFO, CEO, HR
└─ Shows: Requisitions awaiting their approval

GET /requisitions/create
├─ Purpose: Display new requisition form
├─ Route Name: requisitions.create
├─ Controller: RequisitionController::create()
├─ View: requisitions/create.blade.php
├─ Users: Line Managers, HEC Members
└─ Shows: Form with position type, dept, budget fields
```

### Form Submission URLs

```
POST /requisitions
├─ Purpose: Save new requisition
├─ Route Name: requisitions.store
├─ Controller: RequisitionController::store()
├─ Generates: Unique access_id (RRF-0001)
└─ Redirects to: requisitions.show

GET  /requisitions/employees
├─ Purpose: AJAX - Get employees by department
├─ Route Name: requisitions.employees
├─ Controller: RequisitionController::getEmployeesByDepartment()
└─ Returns: JSON list of staff

GET  /requisitions/line-managers
├─ Purpose: AJAX - Get line managers by department
├─ Route Name: requisitions.line-managers
├─ Controller: RequisitionController::getLineManagersByDepartment()
└─ Returns: JSON list of line managers

GET  /requisitions/job-titles
├─ Purpose: AJAX - Get job titles by department
├─ Route Name: requisitions.job-titles
├─ Controller: RequisitionController::getJobTitlesByDepartment()
└─ Returns: JSON list of job titles
```

### Detail & Edit URLs

```
GET /requisitions/{accessId}
├─ Example: /requisitions/RRF-0001
├─ Purpose: View complete requisition details
├─ Route Name: requisitions.show
├─ Controller: RequisitionController::show($id)
├─ View: requisitions/show.blade.php
├─ Parameter: Can be access_id (RRF-0001) or numeric id
└─ Shows: All sections, workflow history, approval forms

GET /requisitions/{accessId}/edit
├─ Example: /requisitions/RRF-0001/edit
├─ Purpose: Edit rejected requisition (within 30 days)
├─ Route Name: requisitions.edit
├─ Controller: RequisitionController::edit($id)
├─ View: requisitions/edit.blade.php
├─ Users: Original creator only
└─ Shows: Pre-populated form with old data

PUT /requisitions/{accessId}
├─ Example: PUT /requisitions/RRF-0001
├─ Purpose: Save edited requisition
├─ Route Name: requisitions.update
├─ Controller: RequisitionController::update($id)
└─ Redirects to: requisitions.show

GET /requisitions/{accessId}/download-jd
├─ Example: /requisitions/RRF-0001/download-jd
├─ Purpose: Download job description PDF
├─ Route Name: requisitions.download-jd
├─ Controller: RequisitionController::downloadJobDescription()
└─ Returns: PDF file
```

### Approval/Review URLs

```
POST /requisitions/{accessId}/payroll-review
├─ Example: /requisitions/RRF-0001/payroll-review
├─ Purpose: Submit payroll accountant review
├─ Route Name: requisitions.payroll-review
├─ Controller: RequisitionController::payrollReview($id)
├─ Form Data: decision, comment, attachment
└─ Updates: payroll_reviewed_at, status

POST /requisitions/{accessId}/hec-review
├─ Example: /requisitions/RRF-0001/hec-review
├─ Purpose: Submit HEC review
├─ Route Name: requisitions.hec-review
├─ Controller: RequisitionController::hecReview($id)
├─ Form Data: hec_financial_decision, comment, funding_source
└─ Updates: hec_reviewed_at, status

POST /requisitions/{accessId}/cfo-review
├─ Example: /requisitions/RRF-0001/cfo-review
├─ Purpose: Submit CFO review
├─ Route Name: requisitions.cfo-review
├─ Controller: RequisitionController::cfoReview($id)
├─ Form Data: cfo_decision, cfo_financing_code, comment
└─ Updates: cfo_reviewed_at, status

POST /requisitions/{accessId}/ceo-review
├─ Example: /requisitions/RRF-0001/ceo-review
├─ Purpose: Submit CEO review
├─ Route Name: requisitions.ceoReview($id)
├─ Controller: RequisitionController::ceoReview($id)
├─ Form Data: ceo_decision, comment
└─ Updates: ceo_reviewed_at, status

POST /requisitions/{accessId}/hr-review
├─ Example: /requisitions/RRF-0001/hr-review
├─ Purpose: Submit HR final review
├─ Route Name: requisitions.hr-review
├─ Controller: RequisitionController::hrReview($id)
├─ Form Data: hr_decision, comment, attachment
└─ Updates: hr_reviewed_at, status

POST /requisitions/{accessId}/resubmit
├─ Example: /requisitions/RRF-0001/resubmit
├─ Purpose: Resubmit edited requisition
├─ Route Name: requisitions.resubmit
├─ Controller: RequisitionController::resubmit($id)
├─ Form Data: requisition_data
└─ Resets: Status to pending_payroll, clears rejections
```

---

## 📊 URL Route Mapping

### By HTTP Method

**GET Requests** (Display pages)
```
GET /requisitions ................... index (List all my requisitions)
GET /requisitions/pending ........... pending (List approvals for me)
GET /requisitions/create ............ create (New form)
GET /requisitions/employees ......... getEmployeesByDepartment (AJAX)
GET /requisitions/line-managers .... getLineManagersByDepartment (AJAX)
GET /requisitions/job-titles ....... getJobTitlesByDepartment (AJAX)
GET /requisitions/{accessId} ........ show (View details)
GET /requisitions/{accessId}/edit .. edit (Edit form - rejected items)
GET /requisitions/{accessId}/download-jd ... downloadJobDescription
```

**POST Requests** (Submit data)
```
POST /requisitions ........................... store (Create new)
POST /requisitions/{accessId}/payroll-review ... payrollReview
POST /requisitions/{accessId}/hec-review ...... hecReview
POST /requisitions/{accessId}/cfo-review ...... cfoReview
POST /requisitions/{accessId}/ceo-review ...... ceoReview
POST /requisitions/{accessId}/hr-review ....... hrReview
POST /requisitions/{accessId}/resubmit ........ resubmit
```

**PUT Requests** (Update data)
```
PUT /requisitions/{accessId} ....... update (Save edited requisition)
```

---

## 🎯 How Links Are Generated in Views

### From index.blade.php (List View)
```blade
<!-- View/Details link -->
<a href="{{ route('requisitions.show', $req->access_id) }}">
    {{ $req->access_id }}
</a>

<!-- Edit link (if creator) -->
<a href="{{ route('requisitions.edit', $req->access_id) }}">
    Edit
</a>

<!-- Download JD -->
<a href="{{ route('requisitions.download-jd', $req->access_id) }}">
    Download
</a>
```

### From show.blade.php (Details View)
```blade
<!-- Approval forms (if user is approver) -->
<form action="{{ route('requisitions.payroll-review', $requisition->access_id) }}" method="POST">
    ...
</form>

<!-- Edit button (if rejected & within 30 days) -->
<a href="{{ route('requisitions.edit', $requisition->access_id) }}">
    Edit Requisition
</a>

<!-- Resubmit button (after editing) -->
<form action="{{ route('requisitions.resubmit', $requisition->access_id) }}" method="POST">
    ...
</form>
```

---

## 📋 Current URL Structure

### Base URL: `https://yourdomain/requisitions`

#### Common Scenarios:

**Scenario 1: User Views Their Own Requisitions**
```
1. Visit: /requisitions
   ↓ (Shows list of all requisitions created by user)
2. Click on: RRF-0001
   ↓ (Takes to: /requisitions/RRF-0001)
3. View all details, status, workflow
```

**Scenario 2: Approver Reviews Pending Items**
```
1. Visit: /requisitions/pending
   ↓ (Shows requisitions awaiting their approval)
2. Click on: RRF-0001
   ↓ (Takes to: /requisitions/RRF-0001)
3. View all details and approval form area
4. Submit approval form at: POST /requisitions/RRF-0001/payroll-review
```

**Scenario 3: Create New Requisition**
```
1. Visit: /requisitions/create
   ↓ (Shows new requisition form)
2. Fill form and submit to: POST /requisitions
   ↓ (Creates record with access_id: RRF-0001)
3. Redirects to: /requisitions/RRF-0001
```

**Scenario 4: Edit Rejected Requisition**
```
1. From /requisitions/RRF-0001 (show view)
   ↓ (See rejection alert, click "Edit")
2. Visit: /requisitions/RRF-0001/edit
   ↓ (Shows pre-filled form)
3. Modify and submit to: PUT /requisitions/RRF-0001
   ↓ (Updates record)
4. Redirects to: /requisitions/RRF-0001
5. Then click "Resubmit" button
   ↓ (Submits to: POST /requisitions/RRF-0001/resubmit)
```

---

## 🔑 Key Parameters

### Primary Parameter: `{accessId}`
- **Type:** String or Integer
- **Format:** `RRF-0001` (preferred) or database `id`
- **Example:** `/requisitions/RRF-0001` or `/requisitions/15`
- **Used in:** show, edit, all review routes

### Query Parameters (optional)
- None currently defined in the routes
- Pagination handled via DataTables JavaScript

### Form Data Parameters

**Payroll Review:**
```
decision (approve|reject|more_info)
comment
attachment (optional file)
```

**HEC Review:**
```
hec_financial_decision
proposed_funding_source
comment
```

**CFO Review:**
```
cfo_decision (approved|rejected|more_info)
cfo_financing_code
comment
```

**CEO Review:**
```
ceo_decision (approved|rejected|more_info)
comment
```

**HR Review:**
```
hr_decision (approved|filed)
comment
hr_attachment_path
```

---

## 🔐 Access Control by Route

| Route | Line Manager | HEC Member | HR | Payroll | CFO | CEO |
|-------|--------------|------------|----|---------|----|-----|
| GET /requisitions | ✅ Own | ✅ Dept | ✅ All | ❌ | ❌ | ❌ |
| GET /pending | ❌ | ✅ | ✅ | ✅ | ✅ | ✅ |
| GET /create | ✅ Dept | ✅ Any | ❌ | ❌ | ❌ | ❌ |
| POST / (store) | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| GET /{id} (show) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| POST /{id}/payroll | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ |
| POST /{id}/hec | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| POST /{id}/cfo | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| POST /{id}/ceo | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| POST /{id}/hr | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ |
| GET /{id}/edit | ✅ Creator | ✅ Creator | ❌ | ❌ | ❌ | ❌ |
| PUT /{id} | ✅ Creator | ✅ Creator | ❌ | ❌ | ❌ | ❌ |

---

## 📞 Related Models & Tables

### Database Tables
- `requisitions` - Main requisition data
- `workflows` - Approval workflow record
- `work_flow_histories` - Individual approval step records
- `users` - User data (approvers, creator)
- `departments` - Department info
- `job_titles` - Job title reference

### Related Models
- `Requisition` - Main model (app/Models/Requisition.php)
- `Workflow` - Approval chain
- `WorkFlowHistory` - Step tracking
- `User` - Creators and approvers
- `Departments` - Target department

---

## ✨ Route Groups Summary

All routes are grouped under:
```php
Route::prefix('requisitions')->name('requisitions.')->group(...)
```

This means:
- All URLs start with `/requisitions`
- All route names start with `requisitions.`
- Example: `route('requisitions.show')` → `/requisitions/{id}`

---

## 🚀 Example: Complete User Flow

```
User logs in
  ↓
Clicks "Requisitions" in menu
  ↓
Navigates to: GET /requisitions
  ↓
Sees list of their requisitions (DataTable)
  ↓
Clicks on "RRF-0001" reference ID
  ↓
Navigates to: GET /requisitions/RRF-0001
  ↓
Views complete requisition details (show.blade.php)
  ↓
If they're an approver, they see approval form
  ↓
Completes form and clicks "Approve"
  ↓
Submits to: POST /requisitions/RRF-0001/payroll-review
  ↓
Workflow updated, redirects back to show page
  ↓
User sees status change and success message
```

---

## 📌 File Structure Reference

```
app/Http/Controllers/
├── RequisitionController.php ........... 3180 lines
    ├── index() - List my requisitions
    ├── pending() - List pending approvals
    ├── create() - Show form
    ├── store() - Save new
    ├── show() - View details
    ├── edit() - Edit rejected
    ├── update() - Save edited
    ├── payrollReview() - Payroll approval
    ├── hecReview() - HEC approval
    ├── cfoReview() - CFO approval
    ├── ceoReview() - CEO approval
    ├── hrReview() - HR approval
    ├── resubmit() - Resubmit after edit
    └── ... 15+ methods

resources/views/requisitions/
├── index.blade.php ................... 223 lines - List view
├── pending.blade.php ................. 202 lines - Pending view
├── create.blade.php ................. 1031 lines - Create form
├── edit.blade.php ................... Similar to create
├── show.blade.php .................. 1280 lines - Details view
└── partials/ ........................ Reusable components

app/Models/
└── Requisition.php .................. 336 lines - Main model
    ├── Relations to User, Department, Workflow
    └── Methods: getStatusLabel(), canBeEdited(), etc.
```

---

## 🎓 Summary

The requisitions module uses:
- **18 total routes** (GET/POST/PUT)
- **Main entry:** `/requisitions` and `/requisitions/pending`
- **Detail page:** `/requisitions/{accessId}` (e.g., `/requisitions/RRF-0001`)
- **Multi-stage approval** via POST endpoints for each level
- **Edit capability** within 30 days of rejection
- **Role-based access** with different views per user type

All links are generated dynamically using Laravel route helpers like:
```php
route('requisitions.show', $id)
route('requisitions.payroll-review', $id)
```
