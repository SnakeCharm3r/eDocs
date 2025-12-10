/**
 * JavaScript for Line Manager Requisition Create Form
 * Line managers can select staff directly from their department
 */

(function () {
    "use strict";

    const isLineManager = window.isLineManager || false;
    if (!isLineManager) return;

    const backgroundInputs = document.querySelectorAll(
        'input[name="background"]'
    );
    const contractRenewalFields = document.getElementById(
        "contractRenewalFields"
    );
    const replacementEmployeeName = document.getElementById(
        "replacementEmployeeName"
    );
    const contractEmployeeSelect = document.getElementById(
        "contract_employee_id"
    );
    const replacementEmployeeSelect = document.getElementById(
        "replacement_employee_id"
    );

    /**
     * Load all staff from line manager's department
     */
    function loadAllStaffForLineManager(targetSelectId) {
        const targetSelect = document.getElementById(targetSelectId);
        if (!targetSelect) return;

        targetSelect.innerHTML =
            '<option value="">Loading staff from your department...</option>';
        targetSelect.disabled = true;

        fetch(
            window.allStaffInDepartmentRoute ||
                "/requisitions/all-staff-in-department",
            {
                method: "GET",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    Accept: "application/json",
                },
            }
        )
            .then((r) => r.json())
            .then((data) => {
                targetSelect.innerHTML =
                    '<option value="">-- Select Employee --</option>';

                if (data.users && data.users.length > 0) {
                    data.users.forEach((user) => {
                        const option = document.createElement("option");
                        option.value = user.id;
                        option.textContent =
                            user.name +
                            (user.username ? ` (${user.username})` : "") +
                            (user.job_title ? ` - ${user.job_title}` : "");

                        const oldValue =
                            targetSelect.getAttribute("data-old-value");
                        if (oldValue && Number(oldValue) === Number(user.id)) {
                            option.selected = true;
                        }

                        targetSelect.appendChild(option);
                    });
                } else {
                    targetSelect.innerHTML =
                        '<option value="">No staff found in your department</option>';
                }

                targetSelect.disabled = false;
            })
            .catch(() => {
                targetSelect.innerHTML =
                    '<option value="">Error loading staff</option>';
                targetSelect.disabled = false;
            });
    }

    /**
     * Toggle fields based on background selection for Line Managers
     */
    function toggleFields() {
        const selected = document.querySelector(
            'input[name="background"]:checked'
        );
        const departmentField = document.getElementById("departmentField");
        const departmentDisplay = document.getElementById("department_display");
        const departmentHidden = document.getElementById("department_hidden");
        const departmentHelpText =
            document.getElementById("departmentHelpText");
        const newPositionNameField = document.getElementById(
            "newPositionNameField"
        );
        const newJobTitleInput = document.getElementById("new_job_title");

        if (!selected) {
            if (replacementEmployeeName)
                replacementEmployeeName.style.display = "none";
            if (contractRenewalFields)
                contractRenewalFields.style.display = "none";
            if (newPositionNameField)
                newPositionNameField.style.display = "none";
            if (replacementEmployeeSelect)
                replacementEmployeeSelect.removeAttribute("required");
            if (contractEmployeeSelect)
                contractEmployeeSelect.removeAttribute("required");
            if (departmentDisplay) {
                departmentDisplay.style.backgroundColor = "#f8f9fa";
                departmentDisplay.readOnly = true;
            }
            if (departmentHelpText) {
                departmentHelpText.innerHTML =
                    '<i class="fas fa-info-circle me-1"></i>Your department is automatically assigned';
            }
            return;
        }

        const value = selected.value;

        if (value === "new_position") {
            if (replacementEmployeeName)
                replacementEmployeeName.style.display = "none";
            if (contractRenewalFields)
                contractRenewalFields.style.display = "none";
            if (replacementEmployeeSelect)
                replacementEmployeeSelect.removeAttribute("required");
            if (contractEmployeeSelect)
                contractEmployeeSelect.removeAttribute("required");
            if (newPositionNameField) {
                newPositionNameField.style.display = "block";
                if (newJobTitleInput) {
                    newJobTitleInput.setAttribute("required", "required");
                }
            }
            // Show department field (it's already visible, just ensure it's styled correctly)
            if (departmentDisplay) {
                departmentDisplay.style.backgroundColor = "#f8f9fa";
                departmentDisplay.readOnly = true;
            }
            if (departmentHelpText) {
                departmentHelpText.innerHTML =
                    '<i class="fas fa-info-circle me-1"></i>Your department is automatically assigned';
            }
            return;
        }

        if (value === "replacement") {
            if (replacementEmployeeName)
                replacementEmployeeName.style.display = "block";
            if (contractRenewalFields)
                contractRenewalFields.style.display = "none";
            if (newPositionNameField)
                newPositionNameField.style.display = "none";
            if (replacementEmployeeSelect) {
                replacementEmployeeSelect.setAttribute("required", "required");
                // Staff dropdown is already populated from backend, no need to load
            }
            if (contractEmployeeSelect)
                contractEmployeeSelect.removeAttribute("required");
            if (newJobTitleInput) {
                newJobTitleInput.removeAttribute("required");
            }
            return;
        }

        if (value === "contract_renewal") {
            if (replacementEmployeeName)
                replacementEmployeeName.style.display = "none";
            if (contractRenewalFields)
                contractRenewalFields.style.display = "block";
            if (replacementEmployeeSelect)
                replacementEmployeeSelect.removeAttribute("required");
            if (newPositionNameField)
                newPositionNameField.style.display = "none";
            if (contractEmployeeSelect) {
                contractEmployeeSelect.setAttribute("required", "required");
            }
            if (newJobTitleInput) {
                newJobTitleInput.removeAttribute("required");
            }
            // Contract renewal employee dropdown is already populated from backend
            return;
        }
    }

    /**
     * Auto-populate contract end date when employee is selected
     */
    function autoFillContractEndDate(employeeSelect, dateInput) {
        if (!employeeSelect || !dateInput) return;

        employeeSelect.addEventListener("change", function () {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.value) {
                const contractEndDate = selectedOption.getAttribute(
                    "data-contract-end-date"
                );
                if (contractEndDate) {
                    dateInput.value = contractEndDate;
                } else {
                    dateInput.value = "";
                }
            } else {
                dateInput.value = "";
            }
        });
    }

    // Initialize
    document.addEventListener("DOMContentLoaded", function () {
        // Background change handlers
        backgroundInputs.forEach((input) => {
            input.addEventListener("change", toggleFields);
        });

        // Auto-fill contract end date for contract renewal
        const contractEndDateInput =
            document.getElementById("contract_end_date");
        if (contractEmployeeSelect && contractEndDateInput) {
            autoFillContractEndDate(
                contractEmployeeSelect,
                contractEndDateInput
            );
        }

        // Initial state
        toggleFields();

        // Preserve old values
        if (window.oldReplacementEmployeeId && replacementEmployeeSelect) {
            replacementEmployeeSelect.setAttribute(
                "data-old-value",
                window.oldReplacementEmployeeId
            );
        }
        if (window.oldContractEmployeeId && contractEmployeeSelect) {
            contractEmployeeSelect.setAttribute(
                "data-old-value",
                window.oldContractEmployeeId
            );
        }
    });
})();
