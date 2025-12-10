/**
 * Common JavaScript for Requisition Create Form
 * Shared functionality across all user types
 */

// File validation
function initFileValidation() {
    const jobDescriptionFile = document.getElementById('job_description_file');
    const fileErrorMessage = document.getElementById('file-error-message');
    const fileSuccessMessage = document.getElementById('file-success-message');

    if (jobDescriptionFile) {
        jobDescriptionFile.addEventListener('change', function(e) {
            const file = e.target.files[0];

            if (fileErrorMessage) fileErrorMessage.style.display = 'none';
            if (fileSuccessMessage) fileSuccessMessage.style.display = 'none';

            if (!file) return;

            const maxSize = 2 * 1024 * 1024; // 2MB
            const fileSize = file.size;
            const fileName = file.name;
            const fileExtension = fileName.split('.').pop().toLowerCase();

            if (fileExtension !== 'pdf' || file.type !== 'application/pdf') {
                e.target.value = '';
                if (fileErrorMessage) {
                    fileErrorMessage.innerHTML = '<i class="fas fa-exclamation-circle me-1"></i>Invalid file format. Please upload a PDF file only.';
                    fileErrorMessage.style.display = 'block';
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid File Format',
                        text: 'Please upload a PDF file only. The selected file is not a PDF.',
                    });
                }
                return;
            }

            if (fileSize > maxSize) {
                e.target.value = '';
                const fileSizeMB = (fileSize / (1024 * 1024)).toFixed(2);
                if (fileErrorMessage) {
                    fileErrorMessage.innerHTML = `<i class="fas fa-exclamation-circle me-1"></i>File size (${fileSizeMB} MB) exceeds 2 MB.`;
                    fileErrorMessage.style.display = 'block';
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'File Too Large',
                        html: `The file size (${fileSizeMB} MB) exceeds the maximum allowed size of 2 MB.`,
                    });
                }
                return;
            }

            const fileSizeMB = (fileSize / (1024 * 1024)).toFixed(2);
            if (fileSuccessMessage) {
                fileSuccessMessage.innerHTML = `<i class="fas fa-check-circle me-1"></i>File "${fileName}" (${fileSizeMB} MB) is valid and ready to upload.`;
                fileSuccessMessage.style.display = 'block';
            }
        });
    }
}

// Set button loading state
function setButtonLoading(button, isLoading) {
    if (!button) return;
    
    if (isLoading) {
        // Store original HTML if not already stored
        if (!button.hasAttribute('data-original-html')) {
            button.setAttribute('data-original-html', button.innerHTML);
        }
        // Disable button and show loading state
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Submitting...';
        button.classList.add('btn-loading');
    } else {
        // Restore original HTML
        const originalHtml = button.getAttribute('data-original-html');
        if (originalHtml) {
            button.innerHTML = originalHtml;
            button.removeAttribute('data-original-html');
        }
        button.disabled = false;
        button.classList.remove('btn-loading');
    }
}

// Form submit validation
function initFormValidation() {
    const requisitionForm = document.getElementById('requisitionForm');
    const submitBtn = document.getElementById('submitRequisitionBtn');

    if (!requisitionForm || !submitBtn) return;

    submitBtn.disabled = false;
    submitBtn.type = 'submit';

    requisitionForm.addEventListener('submit', function(e) {
        // Check HTML5 validation first
        if (!requisitionForm.checkValidity()) {
            requisitionForm.reportValidity();
            e.preventDefault();
            return false;
        }

        // At least one condition must be selected
        const conditionCheckboxes = document.querySelectorAll('input[name="conditions[]"]:checked');
        if (conditionCheckboxes.length === 0) {
            e.preventDefault();
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Condition Required',
                    text: 'Please select at least one condition before submitting.',
                });
            } else {
                alert('Please select at least one condition before submitting.');
            }
            return false;
        }

        // Double-check file validation
        const fileInput = document.getElementById('job_description_file');
        if (fileInput && fileInput.files.length > 0) {
            const file = fileInput.files[0];
            const maxSize = 2 * 1024 * 1024;
            const fileSize = file.size;
            const fileName = file.name;
            const fileExtension = fileName.split('.').pop().toLowerCase();

            if (fileExtension !== 'pdf' || file.type !== 'application/pdf') {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid File Format',
                        text: 'Please upload a PDF file only.',
                    });
                } else {
                    alert('Please upload a PDF file only.');
                }
                return false;
            }

            if (fileSize > maxSize) {
                e.preventDefault();
                const fileSizeMB = (fileSize / (1024 * 1024)).toFixed(2);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'File Too Large',
                        html: `The file size (${fileSizeMB} MB) exceeds 2 MB.`,
                    });
                } else {
                    alert(`The file size (${fileSizeMB} MB) exceeds 2 MB.`);
                }
                return false;
            }
        }
        
        // All validations passed - show loading state
        setButtonLoading(submitBtn, true);
        
        // Disable cancel button if it exists
        const cancelBtn = document.querySelector('a[href*="requisitions.index"]');
        if (cancelBtn) {
            cancelBtn.style.pointerEvents = 'none';
            cancelBtn.style.opacity = '0.6';
        }
        
        // Form will submit normally - loading state will remain until page reloads
    });
}

