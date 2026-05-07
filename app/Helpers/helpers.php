<?php

if (!function_exists('getRequisitionStatusLabel')) {
    function getRequisitionStatusLabel($status)
    {
        switch ($status) {
            case -1:
                return ['label' => 'Pending Review', 'class' => 'bg-secondary text-white'];
            case 0:
                return ['label' => 'Pending Payroll Review', 'class' => 'bg-warning text-dark'];
            case 1:
                return ['label' => 'Pending HEC Review', 'class' => 'bg-secondary text-white'];
            case 2:
                return ['label' => 'Pending CFO & CEO Review', 'class' => 'bg-warning text-dark'];
            case 3:
                return ['label' => 'Completed', 'class' => 'bg-success text-white'];
            case 4:
                return ['label' => 'Stored by HR', 'class' => 'bg-dark text-white'];
            case 5:
                return ['label' => 'Rejected', 'class' => 'bg-danger text-white'];
            default:
                return ['label' => 'Unknown', 'class' => 'bg-danger text-white'];
        }
    }
}
