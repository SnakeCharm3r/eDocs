/**
 * JavaScript for Regular Users (HOD) Requisition Create Form
 * Regular users select job title, then staff from that job title
 */

(function () {
    "use strict";

    const isHecMember = window.isHecMember || false;
    const isLineManager = window.isLineManager || false;

    // Only run for regular users (not HEC or Line Manager)
    if (isHecMember || isLineManager) return;

    const backgroundInputs = document.querySelectorAll(
        'input[name="background"]'
    );
    const jobTitleSelect = document.getElementById("job_title");
    const newJobTitleInput = document.getElementById("new_job_title");
    const existingJobTitleRow = document.getElementById("existingJobTitleRow");
    const newPositionFields = document.getElementById("newPositionFields");
    const replacementEmployeeName = document.getElementById(
        "replacementEmployeeName"
    );
    const contractRenewalFields = document.getElementById(
        "contractRenewalFields"
    );
    const replacementEmployeeSelect = document.getElementById(
        "replacement_employee_id"
    );
    const contractEmployeeSelect = document.getElementById(
        "contract_employee_id"
    );

    /**
     * Load users by job title
     */
    function loadUsersByJobTitle(jobTitleId, targetSelect) {
        if (!jobTitleId || !targetSelect) {
            if (targetSelect) {
                targetSelect.innerHTML =
                    '<option value="">-- Select Job Title First --</option>';
            }
            return;
        }

        targetSelect.innerHTML =
            '<option value="">Loading staff from selected job title...</option>';
        targetSelect.disabled = true;

        fetch(
            (window.usersByJobTitleRoute ||
                "/requisitions/users-by-job-title") +
                `?job_title_id=${jobTitleId}`,
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
                            (user.username ? ` (${user.username})` : "");

                        // Add contract end date as data attribute
                        if (user.contract_end_date) {
                            option.setAttribute(
                                "data-contract-end-date",
                                user.contract_end_date
                            );
                        }

                        const oldValue =
                            targetSelect.getAttribute("data-old-value");
                        if (oldValue && Number(oldValue) === Number(user.id)) {
                            option.selected = true;
                        }

                        targetSelect.appendChild(option);
                    });
                } else {
                    targetSelect.innerHTML =
                        '<option value="">No staff found for this job title</option>';
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

    /**
     * Handle job title change
     */
    function handleJobTitleChange() {
        if (!jobTitleSelect) return;

        const jobTitleId = jobTitleSelect.value;

        if (!jobTitleId) {
            if (replacementEmployeeSelect) {
                replacementEmployeeSelect.innerHTML =
                    '<option value="">-- Select Job Title First --</option>';
            }
            if (contractEmployeeSelect) {
                contractEmployeeSelect.innerHTML =
                    '<option value="">-- Select Job Title First --</option>';
            }
            return;
        }

        const selectedBackground = document.querySelector(
            'input[name="background"]:checked'
        );
        if (!selectedBackground) return;
        const backgroundValue = selectedBackground.value;

        if (
            backgroundValue === "replacement" &&
            replacementEmployeeName &&
            replacementEmployeeName.style.display !== "none"
        ) {
            if (replacementEmployeeSelect) {
                loadUsersByJobTitle(jobTitleId, replacementEmployeeSelect);
            }
        }

        if (
            backgroundValue === "contract_renewal" &&
            contractRenewalFields &&
            contractRenewalFields.style.display !== "none"
        ) {
            if (contractEmployeeSelect) {
                loadUsersByJobTitle(jobTitleId, contractEmployeeSelect);
            }
        }
    }

    /**
     * Toggle fields based on background selection
     */
    function toggleFields() {
        const selected = document.querySelector(
            'input[name="background"]:checked'
        );
        if (!selected) {
            if (existingJobTitleRow) existingJobTitleRow.style.display = "none";
            if (newPositionFields) newPositionFields.style.display = "none";
            if (replacementEmployeeName)
                replacementEmployeeName.style.display = "none";
            if (contractRenewalFields)
                contractRenewalFields.style.display = "none";
            return;
        }

        const value = selected.value;

        if (value === "new_position") {
            if (newPositionFields) newPositionFields.style.display = "block";
            if (existingJobTitleRow) existingJobTitleRow.style.display = "none";
            if (replacementEmployeeName)
                replacementEmployeeName.style.display = "none";
            if (contractRenewalFields)
                contractRenewalFields.style.display = "none";
            if (jobTitleSelect) jobTitleSelect.removeAttribute("required");
            if (newJobTitleInput)
                newJobTitleInput.setAttribute("required", "required");
            return;
        }

        if (value === "replacement") {
            if (replacementEmployeeName)
                replacementEmployeeName.style.display = "block";
            if (existingJobTitleRow)
                existingJobTitleRow.style.display = "block";
            if (newPositionFields) newPositionFields.style.display = "none";
            if (contractRenewalFields)
                contractRenewalFields.style.display = "none";
            if (jobTitleSelect)
                jobTitleSelect.setAttribute("required", "required");
            if (newJobTitleInput) newJobTitleInput.removeAttribute("required");
            if (replacementEmployeeSelect) {
                replacementEmployeeSelect.setAttribute("required", "required");
            }
            if (jobTitleSelect && jobTitleSelect.value) {
                loadUsersByJobTitle(
                    jobTitleSelect.value,
                    replacementEmployeeSelect
                );
            }
            return;
        }

        if (value === "contract_renewal") {
            if (contractRenewalFields)
                contractRenewalFields.style.display = "block";
            if (existingJobTitleRow)
                existingJobTitleRow.style.display = "block";
            if (newPositionFields) newPositionFields.style.display = "none";
            if (replacementEmployeeName)
                replacementEmployeeName.style.display = "none";
            if (jobTitleSelect)
                jobTitleSelect.setAttribute("required", "required");
            if (newJobTitleInput) newJobTitleInput.removeAttribute("required");
            if (contractEmployeeSelect) {
                contractEmployeeSelect.setAttribute("required", "required");
            }
            if (jobTitleSelect && jobTitleSelect.value) {
                loadUsersByJobTitle(
                    jobTitleSelect.value,
                    contractEmployeeSelect
                );
            }
            return;
        }

        // Default: show existing job title
        if (existingJobTitleRow) existingJobTitleRow.style.display = "block";
        if (newPositionFields) newPositionFields.style.display = "none";
        if (replacementEmployeeName)
            replacementEmployeeName.style.display = "none";
        if (contractRenewalFields) contractRenewalFields.style.display = "none";
        if (jobTitleSelect) jobTitleSelect.setAttribute("required", "required");
        if (newJobTitleInput) newJobTitleInput.removeAttribute("required");
    }

    // Initialize
    document.addEventListener("DOMContentLoaded", function () {
        // Background change handlers
        backgroundInputs.forEach((input) => {
            input.addEventListener("change", function () {
                toggleFields();
                if (jobTitleSelect && jobTitleSelect.value) {
                    handleJobTitleChange();
                }
            });
        });

        // Job title change handlers
        if (jobTitleSelect) {
            jobTitleSelect.addEventListener("change", handleJobTitleChange);
            jobTitleSelect.addEventListener("input", handleJobTitleChange);
        }

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
        if (jobTitleSelect && jobTitleSelect.value) {
            handleJobTitleChange();
        }

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
