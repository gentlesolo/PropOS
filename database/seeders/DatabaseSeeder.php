<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Run Roles & Permissions Seeder
        $this->call(RoleAndPermissionSeeder::class);

        // 2. Run Demo Agency Seeder
        $this->call(DemoAgencySeeder::class);

        // 3. Seed portal platforms
        $this->call(PortalSeeder::class);

        // 4. Seed training modules
        $this->call(TrainingModuleSeeder::class);

        // 5. Seed tenants, leases, and rent payment history
        $this->call(TenantLeaseSeeder::class);

        // 6. Seed invoices, expenses, budgets, tax configs, and mandates
        $this->call(FinancialAccountingSeeder::class);

        // 7. Seed tasks across all types, priorities, and statuses
        $this->call(TaskSeeder::class);
    }
}
