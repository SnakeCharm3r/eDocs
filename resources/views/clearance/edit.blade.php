@extends('layouts.template')
@include('includes.loader')

@section('breadcrumb')
    @include('sweetalert::alert')
    <div class="page-wrapper">
        <div class="content container">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="page-header mb-4"
                            style="padding: 15px; background-color: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                            <h3 class="page-title mb-0">Employee Exit Clearance Form</h3>
                        </div>

                        <div class="form-container p-4 border rounded shadow-sm">
                            <form action="{{ route('clearance.update', $clearform->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="userId" value="{{ $user->id }}">

                                @php
                                    $role = auth()->user()->roles->pluck('name')->first();
                                @endphp

                                <!-- Section I: Staff Details -->
                                <h4 class="section-title mb-4">Section I: Staff Details</h4>
                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <label><strong>First Name:</strong></label>
                                        <input type="text" class="form-control" value="{{ $user->fname }}" readonly>
                                    </div>
                                    <div class="col-md-4">
                                        <label><strong>Last Name:</strong></label>
                                        <input type="text" class="form-control" value="{{ $user->lname }}" readonly>
                                    </div>
                                    <div class="col-md-4">
                                        <label><strong>Job Title:</strong></label>
                                        <input type="text" class="form-control" value="{{ $user->job_title }}" readonly>
                                    </div>
                                    <div class="col-md-4">
                                        <label><strong>Department:</strong></label>
                                        <input type="text" class="form-control" value="{{ $user->dept_name }}" readonly>
                                    </div>
                                    <div class="col-md-4">
                                        <label><strong>Date:</strong></label>
                                        <input type="date" class="form-control" name="date"
                                            value="{{ $clearform->date }}" {{ $role == 'requester' ? '' : 'readonly' }} required>
                                    </div>
                                </div>

                                <!-- Line Manager Section -->
                                @if($role == 'line-manager')
                                    <h4 class="section-title mb-4">Line Manager Clearance</h4>
                                    <div class="form-group">
                                        <label>Line Manager Notes / Confirmation:</label>
                                        <textarea class="form-control" name="line_manager_notes">{{ $clearform->line_manager_notes }}</textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Status:</label>
                                        <select class="form-control" name="line_manager_status">
                                            <option value="pending" {{ $clearform->line_manager_status == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="completed" {{ $clearform->line_manager_status == 'completed' ? 'selected' : '' }}>Completed</option>
                                        </select>
                                    </div>
                                @endif

                                <!-- Finance Section -->
                                @if($role == 'finance officer')
                                    <h4 class="section-title mb-4">Finance Clearance</h4>
                                    <table class="table mb-4">
                                        <tbody>
                                            <tr>
                                                <td>Repaid advance on Salary?</td>
                                                <td>
                                                    <label>
                                                        <input type="radio" name="repaid_salary_advance" value="Yes" {{ $clearform->repaid_salary_advance == 'Yes' ? 'checked' : '' }}> Yes
                                                    </label>
                                                    <label>
                                                        <input type="radio" name="repaid_salary_advance" value="N/A" {{ $clearform->repaid_salary_advance == 'N/A' ? 'checked' : '' }}> N/A
                                                    </label>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Staff informed Finance of outstanding loan balances?</td>
                                                <td>
                                                    <label>
                                                        <input type="radio" name="loan_balances_informed" value="Yes" {{ $clearform->loan_balances_informed == 'Yes' ? 'checked' : '' }}> Yes
                                                    </label>
                                                    <label>
                                                        <input type="radio" name="loan_balances_informed" value="N/A" {{ $clearform->loan_balances_informed == 'N/A' ? 'checked' : '' }}> N/A
                                                    </label>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Repaid any outstanding imprest?</td>
                                                <td>
                                                    <label>
                                                        <input type="radio" name="repaid_outstanding_imprest" value="Yes" {{ $clearform->repaid_outstanding_imprest == 'Yes' ? 'checked' : '' }}> Yes
                                                    </label>
                                                    <label>
                                                        <input type="radio" name="repaid_outstanding_imprest" value="N/A" {{ $clearform->repaid_outstanding_imprest == 'N/A' ? 'checked' : '' }}> N/A
                                                    </label>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                @endif

                                <!-- IT Section -->
                                @if($role == 'it')
                                    <h4 class="section-title mb-4">ICT Clearance</h4>
                                    <table class="table mb-4">
                                        <tbody>
                                            <tr>
                                                <td>Laptop/iPad & Accessories returned?</td>
                                                <td>
                                                    <label><input type="radio" name="laptop_returned" value="Yes" {{ $clearform->laptop_returned == 'Yes' ? 'checked' : '' }}> Yes</label>
                                                    <label><input type="radio" name="laptop_returned" value="No" {{ $clearform->laptop_returned == 'No' ? 'checked' : '' }}> No</label>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Email Account Disabled?</td>
                                                <td>
                                                    <label><input type="radio" name="email_account_disabled" value="Yes" {{ $clearform->email_account_disabled == 'Yes' ? 'checked' : '' }}> Yes</label>
                                                    <label><input type="radio" name="email_account_disabled" value="No" {{ $clearform->email_account_disabled == 'No' ? 'checked' : '' }}> No</label>
                                                </td>
                                            </tr>
                                            <!-- Add more ICT fields as needed -->
                                        </tbody>
                                    </table>
                                @endif

                                <!-- HR Section -->
                                @if($role == 'hr')
                                    <h4 class="section-title mb-4">HR Final Clearance</h4>
                                    <div class="form-group">
                                        <label>Attach HR Confirmation Sheet:</label>
                                        <input type="file" class="form-control" name="hr_confirmation_sheet">
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Close Clearance</button>
                                    </div>
                                @endif

                                <!-- Submit Button for other roles -->
                                @if($role != 'hr')
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Submit Section</button>
                                    </div>
                                @endif

                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
<style>
    .form-check-input { width: 20px; height: 20px; margin: 0; }
    .section-title { font-size: 1.25rem; font-weight: bold; margin-top: 20px; }
    .table td { vertical-align: middle; }
</style>
@endsection
