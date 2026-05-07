<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
<script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>
<script src="{{ asset('assets/js/feather.min.js') }}"></script>
<script src="{{ asset('assets/plugins/slimscroll/jquery.slimscroll.min.js') }}"></script>
<script src="{{ asset('assets/plugins/apexchart/apexcharts.min.js') }}"></script>
<script src="{{ asset('assets/plugins/apexchart/chart-data.js') }}"></script>
<script src="{{ asset('assets/js/script.js') }}"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
{{-- <script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script> --}}
<script src="{{ asset('assets/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/plugins/datatables/datatables.min.js') }}"></script>


<!-- jQuery (required) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- WaitMe JS -->
<script src="https://cdn.jsdelivr.net/npm/waitme@1.19.0/waitMe.min.js"></script>

<!-- DataTables core -->
<script src="https://cdn.datatables.net/2.1.2/js/dataTables.js"></script>

<!-- Buttons (for export) + Excel (needs JSZip) -->
<script src="https://cdn.datatables.net/buttons/3.1.0/js/dataTables.buttons.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/buttons/3.1.0/js/buttons.html5.min.js"></script>

@stack('scripts')

{{-- Global AJAX Session Expired Handler --}}
<script>
    // Function to show session expired modal (simple version)
    function showSessionExpiredModal(message) {
        // Check if modal is already showing
        if (document.getElementById('sessionExpiredModal')) {
            return;
        }

        const defaultMessage = 'Your session has expired due to inactivity.';
        const modalMessage = message || defaultMessage;

        // Create modal HTML with faded background overlay
        const modalHTML = `
            <div class="modal fade show" id="sessionExpiredModal" tabindex="-1" aria-labelledby="sessionExpiredModalLabel" aria-hidden="false" style="display: flex; align-items: center; justify-content: center; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999; background-color: rgba(0, 0, 0, 0.5); backdrop-filter: blur(2px);">
                <div class="modal-dialog" style="margin: 0; max-width: 400px; width: 90%; z-index: 10000;">
                    <div class="modal-content" style="border-radius: 8px; border: 1px solid #ddd; box-shadow: 0 4px 20px rgba(0,0,0,0.3); background: white;">
                        <div class="modal-body text-center p-4">
                            <h5 class="modal-title mb-3" id="sessionExpiredModalLabel" style="color: #333; font-weight: 600;">Your session has expired</h5>
                            <p class="mb-4" style="color: #666; font-size: 14px; line-height: 1.5;">${modalMessage}</p>
                            <button type="button" class="btn btn-primary px-4" id="refreshSessionBtn" style="background: #007A33; border: none; border-radius: 6px; padding: 10px 24px; font-weight: 500; color: white;">
                                OK
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remove existing modal if any
        const existingModal = document.getElementById('sessionExpiredModal');
        if (existingModal) {
            existingModal.remove();
        }

        // Remove existing backdrop if any
        const existingBackdrop = document.getElementById('sessionExpiredBackdrop');
        if (existingBackdrop) {
            existingBackdrop.remove();
        }

        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHTML);

        // Handle OK button click
        const refreshBtn = document.getElementById('refreshSessionBtn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
                window.location.reload();
            });
        }

        // Close modal when clicking outside
        document.getElementById('sessionExpiredModal').addEventListener('click', function(e) {
            if (e.target === this) {
                window.location.reload();
            }
        });
    }

    // Idle Timeout Detection
    (function() {
        // Set idle timeout to 120 seconds (2 minutes)
        const idleTimeoutSeconds = 120;
        // Convert to milliseconds
        const idleTimeoutMs = idleTimeoutSeconds * 1000;
        
        let idleTimer;
        let isIdle = false;
        let lastActivity = Date.now();
        
        // Events that indicate user activity
        const activityEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
        
        // Reset idle timer on user activity
        function resetIdleTimer() {
            lastActivity = Date.now();
            isIdle = false;
            clearTimeout(idleTimer);
            
            // Set new timeout
            idleTimer = setTimeout(function() {
                checkSessionStatus();
            }, idleTimeoutMs);
        }
        
        // Check session status with backend
        function checkSessionStatus() {
            // Only check if user is still on the page and not already showing modal
            if (document.hidden || document.getElementById('sessionExpiredModal')) {
                return;
            }
            
            // Make AJAX request to check session
            $.ajax({
                url: '{{ route("session.check") }}',
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.valid) {
                        // Session still valid, reset timer
                        resetIdleTimer();
                    } else {
                        // Session expired
                        showSessionExpiredModal(response.message || 'Your session has expired due to inactivity. Please log in again.');
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 401 || xhr.status === 419) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            showSessionExpiredModal(response.message || 'Your session has expired due to inactivity. Please log in again.');
                        } catch (e) {
                            showSessionExpiredModal('Your session has expired due to inactivity. Please log in again.');
                        }
                    } else {
                        // Other error, reset timer anyway (might be network issue)
                        resetIdleTimer();
                    }
                }
            });
        }
        
        // Listen for user activity
        activityEvents.forEach(function(event) {
            document.addEventListener(event, resetIdleTimer, true);
        });
        
        // Also check when page becomes visible again
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                // Page is visible again, check if we've been idle too long
                const timeSinceLastActivity = Date.now() - lastActivity;
                if (timeSinceLastActivity >= idleTimeoutMs) {
                    checkSessionStatus();
                } else {
                    resetIdleTimer();
                }
            }
        });
        
        // Initialize timer on page load
        $(document).ready(function() {
            resetIdleTimer();
        });
        
        // Also check periodically (every 5 minutes) as a backup
        setInterval(function() {
            if (!document.hidden && !document.getElementById('sessionExpiredModal')) {
                const timeSinceLastActivity = Date.now() - lastActivity;
                if (timeSinceLastActivity >= idleTimeoutMs) {
                    checkSessionStatus();
                }
            }
        }, 5 * 60 * 1000); // Check every 5 minutes
    })();

    // AJAX Session Expired Handler
    $(document).ajaxComplete(function(event, xhr) {
        // Check if response indicates session expired (401 Unauthorized or 419 CSRF Token Mismatch)
        if (xhr.status === 401 || xhr.status === 419) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.session_expired || xhr.status === 419) {
                    showSessionExpiredModal(response.message || 'For your security, your session ended. Refresh to continue — your selections will be preserved.');
                }
            } catch (e) {
                // If 419 or 401, show session expired modal anyway
                if (xhr.status === 419 || xhr.status === 401) {
                    showSessionExpiredModal('For your security, your session ended. Refresh to continue — your selections will be preserved.');
                }
            }
        }
        
        // Handle 403 Forbidden errors with popup
        if (xhr.status === 403) {
            try {
                const response = JSON.parse(xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Access Denied',
                    text: response.message || 'You do not have permission to access this resource.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#007A33'
                });
            } catch (e) {
                Swal.fire({
                    icon: 'error',
                    title: 'Access Denied',
                    text: 'You do not have permission to access this resource.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#007A33'
                });
            }
        }
    });

    // Global Flash Message Handler (SweetAlert)
    $(document).ready(function() {
        @if (session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '{{ session('error') }}',
                confirmButtonText: 'OK',
                confirmButtonColor: '#007A33'
            });
        @endif

        @if (session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: '{{ session('success') }}',
                confirmButtonText: 'OK',
                confirmButtonColor: '#007A33',
                timer: 3000,
                timerProgressBar: true
            });
        @endif
    });
</script>

{{-- Session Expired Modal Handler --}}
@if(session('session_expired'))
<script>
    $(document).ready(function() {
        const message = '{{ session("session_expired_message", "For your security, your session ended. Refresh to continue — your selections will be preserved.") }}';
        showSessionExpiredModal(message);
    });
</script>
@php
    // Clear the session expired flag after showing
    session()->forget('session_expired');
    session()->forget('session_expired_message');
@endphp
@endif
