@push('scripts')
<script>
    function editVendor(id, name, type, contactPerson, contactPhone, contactEmail, address, rating, ownerName, registrationNumber, taxNumber, industry, status) {
        // Set form action
        const form = document.getElementById('editVendorForm');
        const route = '{{ route("procurements.vendors.update", ":id") }}'.replace(':id', id);
        form.action = route;

        // Populate form fields
        document.getElementById('edit_name').value = name || '';
        document.getElementById('edit_type').value = type || 'internal';
        document.getElementById('edit_contact_person').value = contactPerson || '';
        document.getElementById('edit_contact_email').value = contactEmail || '';
        document.getElementById('edit_contact_phone').value = contactPhone || '';
        document.getElementById('edit_address').value = address || '';
        document.getElementById('edit_rating').value = rating || '';
        document.getElementById('edit_owner_name').value = ownerName || '';
        document.getElementById('edit_registration_number').value = registrationNumber || '';
        document.getElementById('edit_tax_number').value = taxNumber || '';
        document.getElementById('edit_industry').value = industry || '';
        document.getElementById('edit_status').value = status || 'active';

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('editVendorModal'));
        modal.show();
    }

    // Handle form submission
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('editVendorForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(form);
                const url = form.action;
                
                fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success || data.message) {
                        // Close modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('editVendorModal'));
                        modal.hide();
                        
                        // Show success message
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: data.message || 'Vendor updated successfully!',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            alert(data.message || 'Vendor updated successfully!');
                            location.reload();
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An error occurred while updating the vendor.'
                        });
                    } else {
                        alert('An error occurred while updating the vendor.');
                    }
                });
            });
        }
    });

    // Filter functions
    function clearFilters() {
        document.getElementById('monthFilter').value = '';
        document.getElementById('statusFilter').value = '';
        document.getElementById('showEntries').value = '10';
        
        // Trigger DataTable refresh if available
        if (window.DataTable) {
            const table = DataTable.get(document.getElementById('vendorsTable'));
            if (table) {
                table.search('').draw();
            }
        }
    }
</script>
@endpush

