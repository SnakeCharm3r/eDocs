<script>
    function waitForJQuery(callback) {
        if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
            callback();
        } else {
            setTimeout(function() { waitForJQuery(callback); }, 100);
        }
    }

    function initOncallScripts() {
        waitForJQuery(function() {
            $(document).ready(function() {
                if (typeof $.fn.DataTable === 'undefined') {
                    setTimeout(function() {
                        initDataTables();
                        initEventHandlers();
                    }, 200);
                } else {
                    initDataTables();
                    initEventHandlers();
                }
            });
        });
    }

    function initDataTables() {
        if ($.fn.DataTable && $('#oncallApproverTable').length && !$.fn.DataTable.isDataTable('#oncallApproverTable')) {
            const table = $('#oncallApproverTable').DataTable({
                paging: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                searching: true,
                ordering: true,
                info: true,
                autoWidth: false,
                responsive: true,
                pagingType: 'simple_numbers',
                language: {
                    paginate: {
                        previous: 'Previous',
                        next: 'Next'
                    }
                },
                order: [[1, 'asc']],
                columnDefs: [{
                    orderable: false,
                    targets: [0, 8]
                }]
            });

            function updateSelectAllCheckbox(dt) {
                const visibleCheckboxes = dt.rows({ search: 'applied' }).nodes().to$().find('input[type="checkbox"][name="request_ids[]"]');
                const checkedCount = visibleCheckboxes.filter(':checked').length;
                const totalCount = visibleCheckboxes.length;
                $('#select-all').prop('checked', totalCount > 0 && checkedCount === totalCount);
                updateSelectionCounter(checkedCount);
            }

            table.on('draw', function() {
                updateSelectAllCheckbox(table);
            });

            $(document).on('change', '#oncallApproverTable tbody input[type="checkbox"][name="request_ids[]"]', function() {
                updateSelectAllCheckbox(table);
            });
        }
    }

    function initEventHandlers() {
        // Button loading animation function
        window.setButtonLoading = function(button, isLoading) {
            if (!button) return;
            const btnText = button.querySelector('.btn-text');
            const spinner = button.querySelector('.js-spinner');
            const loadingLabel = button.getAttribute('data-loading-label') || 'Processing...';

            if (isLoading) {
                button.disabled = true;
                button.classList.add('btn-loading');
                if (btnText) {
                    if (!button.hasAttribute('data-original-html')) {
                        button.setAttribute('data-original-html', btnText.innerHTML);
                    }
                    btnText.innerHTML = loadingLabel;
                }
                if (spinner) {
                    spinner.classList.remove('d-none');
                }
            } else {
                button.disabled = false;
                button.classList.remove('btn-loading');
                if (btnText) {
                    const originalHtml = button.getAttribute('data-original-html');
                    if (originalHtml) {
                        btnText.innerHTML = originalHtml;
                        button.removeAttribute('data-original-html');
                    }
                }
                if (spinner) {
                    spinner.classList.add('d-none');
                }
            }
        };

        // Select-all checkbox
        $(document).on('change', '.select-all', function() {
            const isChecked = this.checked;
            if ($.fn.DataTable && $('#oncallApproverTable').length && $.fn.DataTable.isDataTable('#oncallApproverTable')) {
                const table = $('#oncallApproverTable').DataTable();
                table.rows({ search: 'applied' }).nodes().to$().find('input[type="checkbox"][name="request_ids[]"]').prop('checked', isChecked);
                updateSelectionCounter(isChecked ? table.rows({ search: 'applied' }).count() : 0);
            }
        });

        // Update selection counter
        window.updateSelectionCounter = function(count) {
            const counter = $('#selection-counter');
            const countSpan = $('#selected-count');
            if (count > 0) {
                counter.show();
                countSpan.text(count);
            } else {
                counter.hide();
            }
        };

        // Bulk approve form
        $(document).on('submit', '#bulk-approve-form', function(e) {
            e.preventDefault();
            const form = this;
            const checked = form.querySelectorAll('input[name="request_ids[]"]:checked');
            
            if (checked.length === 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Selection',
                        text: 'Please select at least one request to approve.',
                        confirmButtonText: 'OK'
                    });
                } else {
                    alert('Please select at least one request to approve.');
                }
                return false;
            }

            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                window.setButtonLoading(submitBtn, true);
            }

            // Get the form action URL properly
            const formAction = $(form).attr('action') || form.getAttribute('action') || '{{ route('oncall_requests.bulk-approve') }}';
            
            const fd = new FormData(form);
            // Ensure action is set
            if (!fd.has('action')) {
                fd.append('action', 'approve');
            }
            
            $.ajax({
                url: formAction,
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                success: function(resp) {
                    const icon = resp.icon || 'success';
                    const title = resp.title || 'Success';
                    const text = resp.text || resp.message || 'Successfully approved.';
                    
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: icon,
                            title: title,
                            text: text,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => window.location.reload());
                    } else {
                        alert(title + ': ' + text);
                        window.location.reload();
                    }
                },
                error: function(xhr) {
                    if (submitBtn) window.setButtonLoading(submitBtn, false);
                    let message = xhr.responseJSON?.message || xhr.responseJSON?.text || 'Unknown error';
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to approve requests: ' + message,
                            confirmButtonText: 'OK'
                        });
                    } else {
                        alert('Error: ' + message);
                    }
                }
            });
        });

        // Bulk reject
        $(document).on('click', '#reject-btn', function() {
            const checked = document.querySelectorAll('input[name="request_ids[]"]:checked');
            const ids = Array.from(checked).map(cb => cb.value);
            
            if (ids.length === 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Selection',
                        text: 'Please select at least one request to reject.',
                        confirmButtonText: 'OK'
                    });
                } else {
                    alert('Please select at least one request to reject.');
                }
                return;
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Reject Selected Requests',
                    html: `
                        <div class="mb-3">
                            <label for="swal-rejection-reason" class="form-label text-start d-block mb-2">
                                <strong>Reason for Rejection</strong>
                            </label>
                            <textarea id="swal-rejection-reason" class="form-control" rows="4"
                                placeholder="Please provide a reason for rejection..."
                                style="min-height: 100px; resize: vertical;"></textarea>
                        </div>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-times-circle me-1"></i> Reject',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#dc3545',
                    preConfirm: () => {
                        const reason = document.getElementById('swal-rejection-reason').value.trim();
                        if (!reason) {
                            Swal.showValidationMessage('Please provide a reason for rejection.');
                            return false;
                        }
                        return reason;
                    }
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        Swal.fire({
                            title: 'Rejecting...',
                            html: '<div class="text-center"><div class="spinner-border text-danger" role="status"></div><p class="mt-3">Please wait...</p></div>',
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            didOpen: () => { Swal.showLoading(); }
                        });

                        const formData = new FormData();
                        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                        formData.append('rejection_reason', result.value);
                        ids.forEach(id => {
                            formData.append('request_ids[]', id);
                        });

                        $.ajax({
                            url: '{{ route('oncall_requests.bulk-reject') }}',
                            method: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            success: function(resp) {
                                const icon = resp.icon || 'success';
                                const title = resp.title || 'Success';
                                const text = resp.text || resp.message || 'Successfully rejected.';
                                Swal.fire({
                                    icon: icon,
                                    title: title,
                                    text: text,
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => window.location.reload());
                            },
                            error: function(xhr) {
                                let message = xhr.responseJSON?.message || xhr.responseJSON?.text || 'Unknown error';
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Failed to reject requests: ' + message,
                                    confirmButtonText: 'OK'
                                });
                            }
                        });
                    }
                });
            } else {
                const reason = prompt('Please provide a reason for rejection:');
                if (reason) {
                    // Fallback form submission
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route('oncall_requests.bulk-reject') }}';
                    form.innerHTML = `
                        <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}">
                        <input type="hidden" name="rejection_reason" value="${reason}">
                        ${ids.map(id => `<input type="hidden" name="request_ids[]" value="${id}">`).join('')}
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            }
        });
    }

    // Initialize scripts
    initOncallScripts();
</script>

