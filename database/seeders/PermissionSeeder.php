<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // Define permissions
        $permissions = [
            'view dashboard',
            'view forms',
            'view ict access form',
            'view hr clearance form',
            'view data security agreement',
            'view change management',
            'manage change request categories',
            'view requests',
            'view my requests',
            'approve requests',
            'view departments',
            'view nhif',
            'view hmis',
            'view remarks',
            'view user category',
            'view employment type',
            'manage users',
            'assign roles',
            'manage roles',
            'assign permissions',
            'manage permissions',
            'view settings',
            'view logs',
            'create vendor',
            'edit vendor',
            'delete vendor',
            'View vendor',
            'job title',
            'department',
            'edit-announcement',
            'delete-announcement',
            'create-announcement',
            'create employment type',
            'Manage Category',
            
            // On-Call Request Permissions
            'view oncall requests',
            'create oncall requests',
            'edit oncall requests',
            'delete oncall requests',
            'approve oncall requests',
            'reject oncall requests',
            'view oncall reports',
            'view oncall settings',
            'manage oncall settings',
            
            // Locum Request Permissions
            'view locum requests',
            'create locum requests',
            'edit locum requests',
            'delete locum requests',
            'approve locum requests',
            'reject locum requests',
            'view locum reports',
            'view locum settings',
            'manage locum settings',
            
            // HR Forms Permissions
            'view hr forms',
            'access bank details form',
            'access heslb form',
            'access nhif registration',
            'access clearance form',
            'access requisitions form',
            'access other documents',
            
            // General Request Permissions
            'view general requests',
            'approve general requests',
            
            // IT Requests Permissions
            'view it requests',
            'create it requests',
            
            // Staff & Employee Permissions
            'view staff details',
            'view employee details',
            
            // Biometric Attendance Permissions
            'view biometric attendance',
            'manage biometric attendance',
            
            // Business Contracts Permissions
            'view contracts',
            'view vendors',
            'view entities',
            'view procureents',
            
            // Announcements Permissions
            'view announcements',
            
            // Policies & SOPs Permissions
            'view policies',
            'view sops',
            
            // Maintenance Mode Permission
            'manage maintenance mode',
            
            // SAP Access Permission
            'view sap access',
            
            // User Management Permissions
            'edit users',
            'delete users',
            'activate users',
            'deactivate users',
            'reset user password',
            'assign user roles',
            'edit employee details',
            'view employee forms',
            
            // Form Management Permissions
            'approve forms',
            'reject forms',
            'view form details',
            'edit forms',
            'delete forms',
            
            // Signature Permissions
            'view signatures',
            'manage signatures',
            'approve signatures',
            'reject signatures',
            
            // Platform & Unit Permissions
            'manage platforms',
            'manage units',
            'assign platform managers',
            'assign unit incharge',
            
            // HR Document Permissions
            'view hr documents',
            'upload hr documents',
            'delete hr documents',
            'download hr documents',
            
            // ID Card Permissions
            'view id card requests',
            'create id card requests',
            
            // Clearance Form Permissions
            'view clearance forms',
            'create clearance forms',
            'approve clearance forms',
            'reject clearance forms',
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
