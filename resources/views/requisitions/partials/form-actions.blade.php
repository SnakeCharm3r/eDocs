{{-- Form Actions --}}
<div class="card shadow-sm mb-4" style="border-left: 4px solid #007A33;">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <a href="{{ route('requisitions.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-times me-1"></i> Cancel
            </a>
            <button type="submit" class="btn btn-success" id="submitRequisitionBtn" style="background-color: #007A33; border-color: #007A33;">
                <i class="fas fa-save me-1"></i> Submit Requisition
            </button>
        </div>
    </div>
</div>

