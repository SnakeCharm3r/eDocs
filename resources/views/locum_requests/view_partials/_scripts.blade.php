<script>
    function waitForJQuery(callback) {
        if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
            callback();
        } else {
            setTimeout(function() {
                waitForJQuery(callback);
            }, 100);
        }
    }

    function initLocumScripts() {
        waitForJQuery(function() {
            $(document).ready(function() {
                // Wait a bit more to ensure DataTables library is loaded
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
        // -------------------------------
        // DataTables initialization
        // -------------------------------
        if ($.fn.DataTable && $('#locumApproverTable').length && !$.fn.DataTable.isDataTable('#locumApproverTable')) {
            const table = $('#locumApproverTable').DataTable({
                paging: true,
                pageLength: 10,
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "All"]
                ],
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
                order: [
                    [1, 'asc']
                ], // Sort by # column
                columnDefs: [{
                        orderable: false,
                        targets: [0, 6]
                    } // Disable sorting on checkbox (0) and action (6) columns
                ]
            });

            // Update select-all checkbox and selection counter
            function updateSelectAllCheckbox(dt) {
                const visibleCheckboxes = dt.rows({
                    search: 'applied'
                }).nodes().to$().find('input[type="checkbox"][name="request_ids[]"]');
                const checkedCount = visibleCheckboxes.filter(':checked').length;
                const totalCount = visibleCheckboxes.length;
                $('#select-all').prop('checked', totalCount > 0 && checkedCount === totalCount);

                // Update selection counter
                updateSelectionCounter(checkedCount);
            }

            // Update selection counter
            function updateSelectionCounter(count) {
                const counter = $('#selection-counter');
                const countSpan = $('#selected-count');
                if (count > 0) {
                    counter.show();
                    countSpan.text(count);
                } else {
                    counter.hide();
                }
            }

            table.on('draw', function() {
                updateSelectAllCheckbox(table);
            });

            // Update select-all and counter when individual checkboxes change
            $(document).on('change', '#locumApproverTable tbody input[type="checkbox"][name="request_ids[]"]',
                function() {
                    updateSelectAllCheckbox(table);
                });
        }
    }

    function initEventHandlers() {
        // -------------------------------
        // Select-all (scoped to table) - works with DataTables
        // -------------------------------
        $(document).on('change', '.select-all', function() {
            const isChecked = this.checked;
            if ($.fn.DataTable && $('#locumApproverTable').length && $.fn.DataTable.isDataTable(
                    '#locumApproverTable')) {
                const table = $('#locumApproverTable').DataTable();
                table.rows({
                    search: 'applied'
                }).nodes().to$().find('input[type="checkbox"][name="request_ids[]"]').prop('checked',
                    isChecked);
                // Update counter
                const checkedCount = isChecked ? table.rows({
                    search: 'applied'
                }).count() : 0;
                updateSelectionCounter(checkedCount);
            } else {
                const table = this.closest('table');
                const boxes = table.querySelectorAll('tbody input[type="checkbox"][name="request_ids[]"]');
                let checkedCount = 0;
                boxes.forEach(cb => {
                    if (cb.closest('tr').style.display !== 'none') {
                        cb.checked = isChecked;
                        if (isChecked) checkedCount++;
                    }
                });
                updateSelectionCounter(checkedCount);
            }
        });

        // Helper function to update selection counter (global scope)
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

        // -------------------------------
        // Loading animation for buttons
        // -------------------------------
        window.setButtonLoading = function(button, isLoading) {
            if (!button) return;
            const btnText = button.querySelector('.btn-text');
            const spinner = button.querySelector('.js-spinner');
            const loadingLabel = button.getAttribute('data-loading-label') || 'Processing...';

            if (isLoading) {
                button.disabled = true;
                button.classList.add('btn-loading');
                if (btnText) {
                    // Preserve original HTML (including icons) before changing
                    if (!button.hasAttribute('data-original-html')) {
                        button.setAttribute('data-original-html', btnText.innerHTML);
                    }
                    // Update text while preserving structure
                    btnText.innerHTML = loadingLabel;
                }
                if (spinner) {
                    spinner.classList.remove('d-none');
                }
            } else {
                button.disabled = false;
                button.classList.remove('btn-loading');
                if (btnText) {
                    // Restore original HTML (including icons)
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

        // -------------------------------
        // Open modal & load Worked Days data (using event delegation for DataTables)
        // -------------------------------
        $(document).on('click', '.view-worked-days', function() {
            const id = this.getAttribute('data-id');

            $.ajax({
                url: '{{ route('locum-requests.worked-days', ':id') }}'.replace(':id', id),
                method: 'GET',
                success: function(data) {
                    // header field (ccbrt)
                    if (data.ccbrt_code) $(`#ccbrt-code-${id}`).text(data.ccbrt_code);

                    // ---------------------------
                    // Compact BioTime summary
                    // ---------------------------
                    let bioTimeTotalHours = 0;
                    let bioTimeWorkedDays = 0;

                    const btMap = data.bioTimeData || {};
                    Object.keys(btMap).forEach(date => {
                        const h = parseFloat(btMap[date]?.hours || '0') || 0;
                        if (h > 0) {
                            bioTimeWorkedDays += 1;
                            bioTimeTotalHours += h;
                        }
                    });

                    $(`#biotime-worked-days-${id}`).text(String(bioTimeWorkedDays));
                    $(`#bioTime-total-hours-${id}`).text(bioTimeTotalHours.toFixed(2));

                    // ---------------------------
                    // DAILY ROLLUP TABLE
                    // ---------------------------
                    const rollupBody = $(`#day-rollup-body-${id}`);
                    const fallbackBody = $(`#worked-days-table-body-${id}`);
                    const targetBody = rollupBody.length ? rollupBody : fallbackBody;

                    targetBody.empty();

                    const wdMap = data.workedDays || {};

                    // union of dates from biotime + worked_days so we show whole month
                    const dateSet = new Set([
                        ...Object.keys(btMap || {}),
                        ...Object.keys(wdMap || {}),
                    ]);
                    const allDates = Array.from(dateSet).sort();

                    if (allDates.length === 0) {
                        targetBody.append(
                            '<tr><td colspan="5" class="text-muted">No worked days or BioTime data available.</td></tr>'
                        );
                        $(`#no-data-${id}`).show();
                    } else {
                        $(`#no-data-${id}`).hide();

                        allDates.forEach(dateIso => {
                            const info = wdMap[dateIso] || {};
                            const entries = Array.isArray(info.entries) ? info.entries : [];
                            const workedFlag = (info.worked === '1' || info.worked === 1 ||
                                entries.length > 0);

                            // Worked badge
                            const workedBadge = workedFlag ?
                                '<span class="badge bg-success">Yes</span>' :
                                '<span class="badge bg-secondary">No</span>';

                            // Biotime cell
                            const bt = btMap[dateIso] || {
                                worked: 0,
                                hours: '0.00'
                            };
                            const biotimeBadge = (parseFloat(bt.hours || '0') > 0) ?
                                '<span class="badge bg-success me-1">Yes</span>' :
                                '<span class="badge bg-secondary me-1">No</span>';
                            const biotimeCell =
                                `${biotimeBadge} <span class="text-muted">(${(bt.hours || '0.00')} hrs)</span>`;

                            // HOURS (each entry on its own line WITH SHIFT NAME) + TOTAL
                            let totalHours = 0;
                            const hoursLines = [];
                            entries.forEach(e => {
                                const h = Number(e?.hours || 0);
                                const shift = (e?.shift_name || '—').toString();
                                if (!Number.isNaN(h) && h > 0) {
                                    totalHours += h;
                                    hoursLines.push(
                                        `<div>${shift} — ${h.toFixed(2)}</div>`);
                                }
                            });
                            if (hoursLines.length === 0) hoursLines.push('<div>—</div>');
                            const totalHtml =
                                `<div class="mt-1 pt-1 border-top fw-semibold">Total: ${totalHours.toFixed(2)}</div>`;
                            const hoursHtml =
                                `<div class="lh-sm">${hoursLines.join('')}${totalHtml}</div>`;

                            // PLATFORMS column: "Location — Unit — In-Charge" per entry line
                            const platformLines = [];
                            entries.forEach(e => {
                                const loc = (e?.platform_name || '—').toString();
                                const unit = (e?.unit_name || '—').toString();
                                const inc = (e?.unit_incharge_name || '—')
                                    .toString();
                                platformLines.push(
                                    `<div>${loc} — ${unit} — ${inc}</div>`);
                            });
                            if (platformLines.length === 0) platformLines.push(
                                '<div>—</div>');
                            const platformsHtml =
                                `<div class="lh-sm">${platformLines.join('')}</div>`;

                            // Date label (e.g. "01 September")
                            const dateLabel = new Date(dateIso).toLocaleDateString(
                                'en-GB', {
                                    day: '2-digit',
                                    month: 'long'
                                });

                            // Build row
                            if (targetBody.is(fallbackBody)) {
                                targetBody.append(`
                    <tr>
                      <td>${dateLabel}</td>
                      <td>${workedBadge}</td>
                      <td>${hoursHtml}</td>
                      <td>${biotimeCell}</td>
                    </tr>
                  `);
                            } else {
                                targetBody.append(`
                    <tr>
                      <td>${dateLabel}</td>
                      <td>${workedBadge}</td>
                      <td>${hoursHtml}</td>
                      <td>${platformsHtml}</td>
                      <td>${biotimeCell}</td>
                    </tr>
                  `);
                            }
                        });
                    }
                },
                error: function(xhr) {
                    let msg = 'Failed to load details';
                    try {
                        if (xhr.responseJSON?.message) msg = xhr.responseJSON.message;
                        else if (xhr.responseText) {
                            const parsed = JSON.parse(xhr.responseText);
                            if (parsed?.message) msg = parsed.message;
                        }
                    } catch (_) {}
                    // Check if SweetAlert2 is available
                    if (typeof Swal !== 'undefined' && Swal.fire) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: `Failed to load details: ${msg} (HTTP ${xhr.status})`,
                            confirmButtonText: 'OK'
                        });
                    } else {
                        alert(`Error: Failed to load details: ${msg} (HTTP ${xhr.status})`);
                    }
                }
            });
        });

        // -------------------------------
        // Approve / Reject / Bulk reject (AJAX) - using event delegation
        // -------------------------------
        // Prevent form submission immediately when page loads
        $(document).on('submit', '.approve-form, .reject-form', function(e) {
            // CRITICAL: Prevent default form submission immediately
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            const form = this;

            // Double-check: return false to ensure form doesn't submit
            if (!form) {
                return false;
            }

            if (form.classList.contains('reject-form')) {
                const rr = form.querySelector('textarea[name="rejection_reason"]');
                if (!rr || !rr.value.trim()) {
                    // Check if SweetAlert2 is available
                    if (typeof Swal !== 'undefined' && Swal.fire) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Validation Error',
                            text: 'Please provide a reason for rejection.',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        alert('Validation Error: Please provide a reason for rejection.');
                    }
                    if (rr) rr.focus();
                    return;
                }
            }

            // Get submit button and set loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            const cancelBtn = form.querySelector('button[type="button"]');
            const modal = form.closest('.modal');

            if (submitBtn) window.setButtonLoading(submitBtn, true);
            if (cancelBtn) cancelBtn.disabled = true;
            if (modal) {
                const closeBtn = modal.querySelector('.btn-close');
                if (closeBtn) closeBtn.style.pointerEvents = 'none';
            }

            // Determine the correct URL
            let submitUrl = $(form).attr('action') || form.action;
            // Ensure it's a string, not an object
            if (typeof submitUrl !== 'string') {
                submitUrl = form.getAttribute('action') || '';
            }

            const fd = new FormData(form);
            $.ajax({
                url: submitUrl,
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                dataType: 'json', // Explicitly expect JSON response
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json' // Ensure server returns JSON
                },
                success: function(resp) {
                    // Response should already be parsed as JSON due to dataType: 'json'
                    // Ensure resp is an object, not a string
                    let responseData = resp;
                    if (typeof resp === 'string') {
                        try {
                            responseData = JSON.parse(resp);
                        } catch (parseErr) {
                            console.error('Failed to parse JSON response:', parseErr, resp);
                            responseData = {
                                icon: 'success',
                                title: 'Success',
                                text: resp
                            };
                        }
                    }

                    // Validate response structure
                    if (!responseData || typeof responseData !== 'object') {
                        console.error('Invalid response format:', responseData);
                        // Determine default text based on action type
                        let defaultText = 'Successfully approved.';
                        if (form.classList.contains('reject-form')) {
                            defaultText = 'Successfully rejected.';
                        }
                        responseData = {
                            icon: 'success',
                            title: 'Success',
                            text: defaultText
                        };
                    }

                    // Determine default text based on action type
                    let defaultText = 'Successfully approved.';
                    if (form.classList.contains('reject-form') || form.classList.contains(
                            'bulk-reject-form')) {
                        defaultText = 'Successfully rejected.';
                    }

                    // Extract values from response
                    const icon = (responseData.icon && typeof responseData.icon === 'string') ?
                        responseData.icon : 'success';
                    const title = (responseData.title && typeof responseData.title === 'string') ?
                        responseData.title : 'Success';
                    const text = (responseData.text && typeof responseData.text === 'string') ?
                        responseData.text :
                        (responseData.message && typeof responseData.message === 'string') ?
                        responseData.message :
                        defaultText;

                    // Keep modal open and button in loading state until success message is shown
                    // Don't close modal yet - keep it visible so user can see the loading state
                    const modal = form.closest('.modal');

                    // Show SweetAlert notification - ensure Swal is available
                    // Keep button in loading state until SweetAlert is shown and page reloads
                    if (typeof Swal !== 'undefined' && Swal.fire) {
                        Swal.fire({
                            icon: icon,
                            title: title,
                            text: text,
                            timer: 2000,
                            showConfirmButton: false,
                            allowOutsideClick: false,
                            allowEscapeKey: false
                        }).then(() => {
                            // Close modal and remove loading state right before page reload
                            if (modal) {
                                try {
                                    if (typeof bootstrap !== 'undefined' && bootstrap
                                        .Modal) {
                                        const bsModal = bootstrap.Modal.getInstance(modal);
                                        if (bsModal) {
                                            bsModal.hide();
                                        } else {
                                            $(modal).modal('hide');
                                        }
                                    } else {
                                        $(modal).modal('hide');
                                    }
                                } catch (modalErr) {
                                    console.warn('Could not close modal:', modalErr);
                                    $(modal).modal('hide');
                                }
                            }

                            // Remove loading state before reload
                            if (submitBtn) window.setButtonLoading(submitBtn, false);
                            if (cancelBtn) cancelBtn.disabled = false;
                            if (modal) {
                                const closeBtn = modal.querySelector('.btn-close');
                                if (closeBtn) closeBtn.style.pointerEvents = '';
                            }
                            window.location.reload();
                        });
                    } else {
                        // Fallback if SweetAlert is not available
                        alert(title + ': ' + text);

                        // Close modal
                        if (modal) {
                            try {
                                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                                    const bsModal = bootstrap.Modal.getInstance(modal);
                                    if (bsModal) {
                                        bsModal.hide();
                                    } else {
                                        $(modal).modal('hide');
                                    }
                                } else {
                                    $(modal).modal('hide');
                                }
                            } catch (modalErr) {
                                $(modal).modal('hide');
                            }
                        }

                        // Remove loading state before reload
                        if (submitBtn) window.setButtonLoading(submitBtn, false);
                        if (cancelBtn) cancelBtn.disabled = false;
                        if (modal) {
                            const closeBtn = modal.querySelector('.btn-close');
                            if (closeBtn) closeBtn.style.pointerEvents = '';
                        }
                        window.location.reload();
                    }
                },
                error: function(xhr, status, error) {
                    // Restore button states
                    if (submitBtn) window.setButtonLoading(submitBtn, false);
                    if (cancelBtn) cancelBtn.disabled = false;
                    if (modal) {
                        const closeBtn = modal.querySelector('.btn-close');
                        if (closeBtn) closeBtn.style.pointerEvents = '';
                    }

                    let message = 'Unknown error occurred';

                    // Try to parse error response
                    if (xhr.responseJSON) {
                        message = xhr.responseJSON.message || xhr.responseJSON.text || xhr
                            .responseJSON.error || message;
                        if (xhr.responseJSON.errors) {
                            const errorMessages = Object.values(xhr.responseJSON.errors).flat();
                            if (errorMessages.length > 0) {
                                message += '\n' + errorMessages.join('\n');
                            }
                        }
                    } else if (xhr.responseText) {
                        try {
                            const parsed = JSON.parse(xhr.responseText);
                            message = parsed.message || parsed.text || parsed.error || message;
                        } catch (parseErr) {
                            // If responseText is not JSON, use it directly (but truncate if too long)
                            message = xhr.responseText.length > 200 ?
                                xhr.responseText.substring(0, 200) + '...' :
                                xhr.responseText;
                        }
                    }

                    // Show error notification
                    if (typeof Swal !== 'undefined' && Swal.fire) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to perform action: ' + message,
                            confirmButtonText: 'OK'
                        });
                    } else {
                        alert('Error: ' + message);
                    }
                }
            });

            // Ensure we return false to prevent any form submission
            return false;
        });

        // -------------------------------
        // Bulk approve form validation and loading state
        // -------------------------------
        $(document).on('submit', '#bulk-approve-form', function(e) {
            e.preventDefault();
            const form = this;

            // Check if any checkboxes are selected
            const checked = form.querySelectorAll('input[name="request_ids[]"]:checked');
            if (checked.length === 0) {
                if (typeof Swal !== 'undefined' && Swal.fire) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Selection',
                        text: 'Please select at least one request to approve.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#2fb565'
                    });
                } else {
                    alert('Please select at least one request to approve.');
                }
                return false;
            }

            const submitBtn = form.querySelector('button[type="submit"][name="action"][value="approve"]');
            if (submitBtn) {
                window.setButtonLoading(submitBtn, true);
                const rejectBtn = document.querySelector('#reject-btn');
                if (rejectBtn) rejectBtn.disabled = true;
            }

            // Submit the form - use the correct route URL
            const formAction = $(form).attr('action') || '{{ route('locum-requests.bulk-approve') }}';
            const fd = new FormData(form);

            // Ensure action field is included (button value might not be included in FormData)
            if (!fd.has('action')) {
                fd.append('action', 'approve');
            }

            $.ajax({
                url: formAction,
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                success: function(resp) {
                    const icon = resp.icon || 'success';
                    const title = resp.title || 'Success';
                    const text = resp.text || resp.message || 'Successfully approved.';

                    if (typeof Swal !== 'undefined' && Swal.fire) {
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
                    const rejectBtn = document.querySelector('#reject-btn');
                    if (rejectBtn) rejectBtn.disabled = false;

                    let message = xhr.responseJSON?.message || xhr.responseJSON?.text ||
                        'Unknown error';
                    if (xhr.responseJSON?.errors) {
                        message += '\n' + Object.values(xhr.responseJSON.errors).flat().join('\n');
                    }

                    // Check if SweetAlert2 is available
                    if (typeof Swal !== 'undefined' && Swal.fire) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to approve requests: ' + message,
                            confirmButtonText: 'OK'
                        });
                    } else {
                        alert('Error: Failed to approve requests: ' + message);
                    }
                }
            });
        });

        // -------------------------------
        // Bulk reject: collect selected IDs with improved validation
        // -------------------------------
        // Reject button click handler - Use SweetAlert instead of modal
        // -------------------------------
        $(document).on('click', '#reject-btn', function() {
            const checked = document.querySelectorAll('input[name="request_ids[]"]:checked');
            const ids = Array.from(checked).map(cb => cb.value);
            if (ids.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Selection',
                    html: '<p>Please select at least one request to reject.</p><p class="text-muted small mt-2">Use the checkboxes in the table to select requests.</p>',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#dc3545'
                });
                return;
            }

            // Use SweetAlert for rejection with loading state
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
                cancelButtonColor: '#6c757d',
                reverseButtons: true,
                focusConfirm: false,
                allowOutsideClick: false,
                allowEscapeKey: true,
                preConfirm: () => {
                    const reason = document.getElementById('swal-rejection-reason').value.trim();
                    if (!reason) {
                        if (typeof Swal !== 'undefined' && Swal.showValidationMessage) {
                            Swal.showValidationMessage('Please provide a reason for rejection.');
                        }
                        return false;
                    }
                    return reason;
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    // Show loading state
                    Swal.fire({
                        title: 'Rejecting...',
                        html: '<div class="text-center"><div class="spinner-border text-danger" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-3">Please wait while we process your request.</p></div>',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Prepare form data
                    const formData = new FormData();
                    formData.append('_token', document.querySelector('meta[name="csrf-token"]')
                        .content);
                    formData.append('rejection_reason', result.value);
                    ids.forEach(id => {
                        formData.append('request_ids[]', id);
                    });

                    // Submit rejection
                    $.ajax({
                        url: '{{ route('locum-requests.bulk-reject') }}',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        dataType: 'json',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .content,
                            'Accept': 'application/json'
                        },
                        success: function(resp) {
                            let responseData = resp;
                            if (typeof resp === 'string') {
                                try {
                                    responseData = JSON.parse(resp);
                                } catch (e) {
                                    responseData = {
                                        icon: 'success',
                                        title: 'Success',
                                        text: resp
                                    };
                                }
                            }

                            const icon = responseData.icon || 'success';
                            const title = responseData.title || 'Success';
                            const text = responseData.text || responseData.message ||
                                'Successfully rejected.';

                            // Check if SweetAlert2 is available
                            if (typeof Swal !== 'undefined' && Swal.fire) {
                                Swal.fire({
                                    icon: icon,
                                    title: title,
                                    text: text,
                                    timer: 2000,
                                    showConfirmButton: false,
                                    allowOutsideClick: false,
                                    allowEscapeKey: false
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                alert(title + ': ' + text);
                                window.location.reload();
                            }
                        },
                        error: function(xhr) {
                            let message = 'Unknown error occurred';
                            if (xhr.responseJSON) {
                                message = xhr.responseJSON.message || xhr.responseJSON
                                    .text || xhr.responseJSON.error || message;
                                if (xhr.responseJSON.errors) {
                                    const errorMessages = Object.values(xhr.responseJSON
                                        .errors).flat();
                                    if (errorMessages.length > 0) {
                                        message += '\n' + errorMessages.join('\n');
                                    }
                                }
                            } else if (xhr.responseText) {
                                try {
                                    const parsed = JSON.parse(xhr.responseText);
                                    message = parsed.message || parsed.text || parsed
                                        .error || message;
                                } catch (e) {
                                    message = xhr.responseText.length > 200 ?
                                        xhr.responseText.substring(0, 200) + '...' :
                                        xhr.responseText;
                                }
                            }

                            // Check if SweetAlert2 is available
                            if (typeof Swal !== 'undefined' && Swal.fire) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Failed to reject requests: ' + message,
                                    confirmButtonText: 'OK'
                                });
                            } else {
                                alert('Error: Failed to reject requests: ' + message);
                            }
                        }
                    });
                }
            });
        });
    }

    // Initialize scripts
    initLocumScripts();
</script>
