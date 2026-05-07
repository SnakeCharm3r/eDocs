<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Departments;
use App\Models\HecProfile;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class RecruitmentRequisitionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles if they don't exist
        $roles = [
            'hod' => 'Head of Department',
            'hec-cfo' => 'HEC - Chief Financial Officer',
            'hec-coo' => 'HEC - Chief Operating Officer',
            'hec-cms' => 'HEC - Chief Medical Specialist',
            'hec-ccd' => 'HEC - CCDRO',
            'cfo' => 'CFO (Finance Head)',
            'ceo' => 'Chief Executive Officer',
            'hr' => 'Human Resources',
            'payroll_accountant' => 'Payroll Accountant',
        ];

        foreach ($roles as $roleName => $description) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        // Create sample HOD user
        $hod = User::firstOrCreate(
            ['email' => 'hod@example.com'],
            [
                'fname' => 'John',
                'lname' => 'Doe',
                //'name' => 'John Doe',
                'username' => 'hod',
                'password' => Hash::make('password'),
                'deptId' => Departments::first()->id ?? 1,
                'employment_typeId' => 1,
            ]
        );
        $hod->assignRole('hod');

        // Create sample HEC members
        $hecCfo = User::firstOrCreate(
            ['email' => 'hec.cfo@example.com'],
            [
                'fname' => 'Jane',
                'lname' => 'Smith',
                'name' => 'Jane Smith',
                'username' => 'hec_cfo',
                'password' => Hash::make('password'),
            ]
        );
        $hecCfo->assignRole(['hec-cfo', 'cfo']);

        $hecCoo = User::firstOrCreate(
            ['email' => 'hec.coo@example.com'],
            [
                'fname' => 'Michael',
                'lname' => 'Johnson',
                'name' => 'Michael Johnson',
                'username' => 'hec_coo',
                'password' => Hash::make('password'),
            ]
        );
        $hecCoo->assignRole('hec-coo');

        $hecCms = User::firstOrCreate(
            ['email' => 'hec.cms@example.com'],
            [
                'fname' => 'Sarah',
                'lname' => 'Williams',
                'name' => 'Sarah Williams',
                'username' => 'hec_cms',
                'password' => Hash::make('password'),
            ]
        );
        $hecCms->assignRole('hec-cms');

        // Create CFO user (can be same as HEC-CFO or separate)
        $cfo = User::firstOrCreate(
            ['email' => 'cfo@example.com'],
            [
                'fname' => 'Robert',
                'lname' => 'Brown',
                'name' => 'Robert Brown',
                'username' => 'cfo',
                'password' => Hash::make('password'),
            ]
        );
        $cfo->assignRole('cfo');

        // Create CEO user
        $ceo = User::firstOrCreate(
            ['email' => 'ceo@example.com'],
            [
                'fname' => 'David',
                'lname' => 'Miller',
                'name' => 'David Miller',
                'username' => 'ceo',
                'password' => Hash::make('password'),
            ]
        );
        $ceo->assignRole('ceo');

        // Create HR user
        $hr = User::firstOrCreate(
            ['email' => 'hr@example.com'],
            [
                'fname' => 'Emily',
                'lname' => 'Davis',
                'name' => 'Emily Davis',
                'username' => 'hr',
                'password' => Hash::make('password'),
            ]
        );
        $hr->assignRole('hr');

        // Create Payroll Accountant
        $payrollAccountant = User::firstOrCreate(
            ['email' => 'payroll@example.com'],
            [
                'fname' => 'Lisa',
                'lname' => 'Anderson',
                'name' => 'Lisa Anderson',
                'username' => 'payroll',
                'password' => Hash::make('password'),
            ]
        );
        $payrollAccountant->assignRole('payroll_accountant');

        // Create HEC Profiles
        if ($hecCfo) {
            HecProfile::firstOrCreate(
                ['user_id' => $hecCfo->id],
                ['is_cfo' => true, 'is_coo' => false, 'is_cms' => false, 'is_ccdro' => false]
            );
        }

        if ($hecCoo) {
            HecProfile::firstOrCreate(
                ['user_id' => $hecCoo->id],
                ['is_cfo' => false, 'is_coo' => true, 'is_cms' => false, 'is_ccdro' => false]
            );
        }

        if ($hecCms) {
            HecProfile::firstOrCreate(
                ['user_id' => $hecCms->id],
                ['is_cfo' => false, 'is_coo' => false, 'is_cms' => true, 'is_ccdro' => false]
            );
        }

        // Assign HEC members to departments (example)
        $departments = Departments::take(3)->get();
        if ($departments->count() >= 3) {
            $departments[0]->update(['hec_member_id' => $hecCfo->id]);
            $departments[1]->update(['hec_member_id' => $hecCoo->id]);
            $departments[2]->update(['hec_member_id' => $hecCms->id]);
        }

        $this->command->info('Recruitment Requisition roles and users created successfully!');
        $this->command->info('Sample users created:');
        $this->command->info('- HOD: hod@example.com / password');
        $this->command->info('- HEC-CFO: hec.cfo@example.com / password');
        $this->command->info('- HEC-COO: hec.coo@example.com / password');
        $this->command->info('- HEC-CMS: hec.cms@example.com / password');
        $this->command->info('- CFO: cfo@example.com / password');
        $this->command->info('- CEO: ceo@example.com / password');
        $this->command->info('- HR: hr@example.com / password');
        $this->command->info('- Payroll Accountant: payroll@example.com / password');
    }
}
