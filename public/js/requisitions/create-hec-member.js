/**
 * JavaScript for HEC Member Requisition Create Form
 * HEC members select department, then line manager, then staff
 */

(function () {
    "use strict";

    const isHecMember = window.isHecMember || false;
    if (!isHecMember) return;

    const backgroundInputs = document.querySelectorAll(
        'input[name="background"]'
    );
    const departmentSelect = document.getElementById("department");
    const lineManagerSelect = document.getElementById("line_manager");
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
     * Load staff from selected department for HEC members
     */
    function loadStaffForHec(targetSelectId) {
        const targetSelect = document.getElementById(targetSelectId);
        const departmentSelect = document.getElementById("department");

        if (!targetSelect || !departmentSelect) return;

        const departmentId = departmentSelect.value;
        if (!departmentId) {
            targetSelect.innerHTML =
                '<option value="">Select department first</option>';
            targetSelect.disabled = true;
            return;
        }

        targetSelect.innerHTML =
            '<option value="">Loading staff from selected department...</option>';
        targetSelect.disabled = true;

        fetch(
            (window.staffByDepartmentRoute ||
                "/requisitions/staff-by-department") +
                `?department_id=${departmentId}`,
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
                        '<option value="">No staff found for this department</option>';
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
     * Load line managers by department
     */
    window.loadDepartmentData = function () {
        const departmentId = departmentSelect ? departmentSelect.value : null;

        if (!departmentId || !lineManagerSelect) {
            if (lineManagerSelect) {
                lineManagerSelect.innerHTML =
                    '<option value="">-- Select Department First --</option>';
                lineManagerSelect.disabled = true;
            }
            return;
        }

        lineManagerSelect.innerHTML =
            '<option value="">Loading line managers...</option>';
        lineManagerSelect.disabled = true;

        fetch(
            (window.lineManagersByDepartmentRoute ||
                "/requisitions/line-managers-by-department") +
                `?department_id=${departmentId}`,
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
                lineManagerSelect.innerHTML =
                    '<option value="">-- Select Line Manager --</option>';

                if (data.lineManagers && data.lineManagers.length > 0) {
                    data.lineManagers.forEach((manager) => {
                        const option = document.createElement("option");
                        option.value = manager.id;
                        let label = manager.name;
                        if (manager.employee_id) {
                            label += ` (${manager.employee_id})`;
                        }
                        if (manager.contract_end_date) {
                            const d = new Date(manager.contract_end_date);
                            label += ` - Contract expired: ${d.toLocaleDateString()}`;
                        } else {
                            label += " - Contract expired";
                        }
                        option.textContent = label;
                        if (manager.job_title_id) {
                            option.dataset.jobTitleId = manager.job_title_id;
                        }
                        lineManagerSelect.appendChild(option);
                    });

                    if (data.lineManagers.length === 1) {
                        lineManagerSelect.value = data.lineManagers[0].id;
                        updateJobTitleFromLineManager(data.lineManagers[0]);
                        lineManagerSelect.dispatchEvent(new Event("change"));
                    }
                } else {
                    lineManagerSelect.innerHTML =
                        '<option value="">No line managers found</option>';
                }
                lineManagerSelect.disabled = false;
            })
            .catch(() => {
                lineManagerSelect.innerHTML =
                    '<option value="">Error loading line managers</option>';
                lineManagerSelect.disabled = false;
            });
    };

    /**
     * Update job title from selected line manager
     */
    function updateJobTitleFromLineManager(manager) {
        const jobTitleHiddenField = document.getElementById(
            "job_title_from_line_manager"
        );
        const jobTitleDisplay = document.getElementById(
            "line_manager_job_title_display"
        );
        const jobTitleText = document.getElementById(
            "line_manager_job_title_text"
        );

        if (manager.job_title_id) {
            if (jobTitleHiddenField) {
                jobTitleHiddenField.value = manager.job_title_id;
            }

            const deptId = document.getElementById("department")?.value;
            if (!deptId) return;

            fetch(
                (window.jobTitlesByDepartmentRoute ||
                    "/requisitions/job-titles-by-department") +
                    `?department_id=${deptId}`,
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
                    if (data.jobTitles && data.jobTitles.length > 0) {
                        const jt = data.jobTitles.find(
                            (j) => Number(j.id) === Number(manager.job_title_id)
                        );
                        if (jt && jobTitleDisplay && jobTitleText) {
                            jobTitleText.textContent = jt.job_title;
                            jobTitleDisplay.style.display = "block";
                        }
                    }
                })
                .catch(() => {
                    if (jobTitleText) {
                        jobTitleText.textContent =
                            "Job Title ID: " + manager.job_title_id;
                    }
                    if (jobTitleDisplay) {
                        jobTitleDisplay.style.display = "block";
                    }
                });
        } else {
            if (jobTitleHiddenField) jobTitleHiddenField.value = "";
            if (jobTitleDisplay) jobTitleDisplay.style.display = "none";
        }
    }

    /**
     * Toggle fields based on background selection for HEC Members
     */
    function toggleFields() {
        const selected = document.querySelector(
            'input[name="background"]:checked'
        );
        const lineManagerField = document.getElementById("lineManagerField");
        const lineManagerSelect = document.getElementById("line_manager");
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
            if (lineManagerField) lineManagerField.style.display = "block";
            if (lineManagerSelect) {
                lineManagerSelect.removeAttribute("required");
            }
            if (newJobTitleInput) {
                newJobTitleInput.removeAttribute("required");
            }
            return;
        }

        const value = selected.value;

        if (value === "new_position") {
            if (replacementEmployeeName)
                replacementEmployeeName.style.display = "none";
            if (contractRenewalFields)
                contractRenewalFields.style.display = "none";
            if (newPositionNameField) {
                newPositionNameField.style.display = "block";
                if (newJobTitleInput) {
                    newJobTitleInput.setAttribute("required", "required");
                }
            }
            // Hide line manager field for new position
            if (lineManagerField) lineManagerField.style.display = "none";
            if (lineManagerSelect) {
                lineManagerSelect.removeAttribute("required");
                lineManagerSelect.value = "";
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
            }
            if (lineManagerField) lineManagerField.style.display = "block";
            if (lineManagerSelect) {
                lineManagerSelect.setAttribute("required", "required");
            }
            if (newJobTitleInput) {
                newJobTitleInput.removeAttribute("required");
            }
            if (departmentSelect && departmentSelect.value) {
                loadStaffForHec("replacement_employee_id");
            }
            return;
        }

        if (value === "contract_renewal") {
            if (replacementEmployeeName)
                replacementEmployeeName.style.display = "none";
            if (contractRenewalFields)
                contractRenewalFields.style.display = "block";
            if (newPositionNameField)
                newPositionNameField.style.display = "none";
            if (contractEmployeeSelect) {
                contractEmployeeSelect.setAttribute("required", "required");
            }
            if (lineManagerField) lineManagerField.style.display = "block";
            if (lineManagerSelect) {
                lineManagerSelect.setAttribute("required", "required");
            }
            if (newJobTitleInput) {
                newJobTitleInput.removeAttribute("required");
            }
            if (departmentSelect && departmentSelect.value) {
                loadStaffForHec("contract_employee_id");
            }
            return;
        }
    }

    // Initialize
    document.addEventListener("DOMContentLoaded", function () {
        // Background change handlers
        backgroundInputs.forEach((input) => {
            input.addEventListener("change", toggleFields);
        });

        // Department change handler
        if (departmentSelect) {
            departmentSelect.addEventListener("change", function () {
                window.loadDepartmentData();

                const selectedBackground = document.querySelector(
                    'input[name="background"]:checked'
                );
                if (!selectedBackground) return;

                if (selectedBackground.value === "replacement") {
                    loadStaffForHec("replacement_employee_id");
                } else if (selectedBackground.value === "contract_renewal") {
                    loadStaffForHec("contract_employee_id");
                }
            });
        }

        // Line manager change handler
        if (lineManagerSelect) {
            lineManagerSelect.addEventListener("change", function () {
                const opt = this.options[this.selectedIndex];
                if (opt && opt.value) {
                    const jobTitleId = opt.dataset.jobTitleId;
                    if (jobTitleId) {
                        updateJobTitleFromLineManager({
                            id: opt.value,
                            job_title_id: jobTitleId,
                        });
                    }
                } else {
                    const jobTitleHiddenField = document.getElementById(
                        "job_title_from_line_manager"
                    );
                    const jobTitleDisplay = document.getElementById(
                        "line_manager_job_title_display"
                    );
                    if (jobTitleHiddenField) jobTitleHiddenField.value = "";
                    if (jobTitleDisplay) jobTitleDisplay.style.display = "none";
                }
            });
        }

        // Initial state
        toggleFields();
        const initialBackground = document.querySelector(
            'input[name="background"]:checked'
        );
        if (initialBackground) {
            if (
                initialBackground.value === "replacement" &&
                departmentSelect &&
                departmentSelect.value
            ) {
                loadStaffForHec("replacement_employee_id");
            } else if (
                initialBackground.value === "contract_renewal" &&
                departmentSelect &&
                departmentSelect.value
            ) {
                loadStaffForHec("contract_employee_id");
            }
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
