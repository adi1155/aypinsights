<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\DailyClosing;
use App\Models\DashboardPreference;
use App\Models\ExecutiveNotification;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ExecutiveDashboardSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view daily closing', 'view ap dashboard', 'view ar dashboard',
            'view expense dashboard', 'view payroll dashboard', 'view attendance dashboard', 'view production dashboard', 'manage users', 'manage companies',
            'view audit logs', 'export reports',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $roles = [
            'CEO' => $permissions,
            'CFO' => $permissions,
            'Director' => array_slice($permissions, 0, 8),
            'Finance' => [
                'view daily closing', 'view ap dashboard', 'view ar dashboard',
                'view expense dashboard', 'view payroll dashboard', 'view attendance dashboard',
                'view production dashboard', 'export reports',
            ],
            'Branch Manager' => [
                'view daily closing', 'view ar dashboard', 'view expense dashboard',
                'view attendance dashboard', 'view production dashboard', 'export reports',
            ],
        ];

        foreach ($roles as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($perms);
        }

        $company = Company::firstOrCreate(
            ['erpnext_name' => 'GMP Foods (Pvt.) Ltd'],
            ['abbr' => 'GMP', 'default_currency' => 'PKR']
        );

        foreach (['North Region', 'South Region', 'Head Office', 'Export Division'] as $branchName) {
            Branch::firstOrCreate(
                ['erpnext_name' => $branchName],
                ['company_id' => $company->id, 'name' => $branchName]
            );
        }

        $users = [
            ['name' => 'CEO Executive', 'email' => 'ceo@ayp-insights.local', 'role' => 'CEO'],
            ['name' => 'CFO Finance', 'email' => 'cfo@ayp-insights.local', 'role' => 'CFO'],
            ['name' => 'Finance Manager', 'email' => 'finance@ayp-insights.local', 'role' => 'Finance'],
            ['name' => 'Branch Manager', 'email' => 'branch@ayp-insights.local', 'role' => 'Branch Manager'],
        ];

        foreach ($users as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'company' => 'GMP Foods (Pvt.) Ltd',
                    'branch' => 'Head Office',
                    'is_active' => true,
                ]
            );
            $user->syncRoles([$data['role']]);
            DashboardPreference::firstOrCreate(['user_id' => $user->id], ['theme' => 'dark']);
        }

        for ($i = 7; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $opening = rand(10000000, 14000000);
            $receipts = rand(800000, 2500000);
            $payments = rand(600000, 2200000);
            DailyClosing::updateOrCreate(
                ['closing_date' => $date, 'company' => 'GMP Foods (Pvt.) Ltd', 'branch' => null],
                [
                    'opening_balance' => $opening,
                    'receipts' => $receipts,
                    'payments' => $payments,
                    'closing_balance' => $opening + $receipts - $payments,
                    'bank_balance' => rand(35000000, 42000000),
                    'cash_in_hand' => rand(200000, 800000),
                    'pending_deposits' => rand(100000, 400000),
                    'outstanding_cheques' => rand(150000, 500000),
                    'daily_profit_loss' => $receipts - $payments - rand(50000, 200000),
                ]
            );
        }

        ExecutiveNotification::create([
            'type' => 'alert',
            'severity' => 'red',
            'title' => 'Critical AP Overdue',
            'message' => 'Steel Works Ltd overdue exceeds PKR 1.5M threshold.',
        ]);
        ExecutiveNotification::create([
            'type' => 'info',
            'severity' => 'amber',
            'title' => 'AR Collection Follow-up',
            'message' => 'City Wholesale account requires executive escalation.',
        ]);
    }
}
