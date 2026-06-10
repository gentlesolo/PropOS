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

        // 3. Seed second demo agency (Apex Realty Group — ZAR / Violet brand)
        $this->call(SecondAgencySeeder::class);

        // 4. Seed portal platforms
        $this->call(PortalSeeder::class);

        // 5. Seed training modules
        $this->call(TrainingModuleSeeder::class);

        // 6. Seed tenants, leases, and rent payment history
        $this->call(TenantLeaseSeeder::class);

        // 7. Seed invoices, expenses, budgets, tax configs, and mandates
        $this->call(FinancialAccountingSeeder::class);

        // 8. Seed tasks across all types, priorities, and statuses
        $this->call(TaskSeeder::class);

        // 9. Seed offers with counter/accepted/rejected scenarios
        $this->call(OffersSeeder::class);

        // 10. Seed contracts: OTP, mandates, lease agreements, addendum
        $this->call(ContractsSeeder::class);

        // 11. Seed email templates (all categories) and delivery logs
        $this->call(EmailTemplatesSeeder::class);

        // 12. Seed WhatsApp and SMS messaging inbox conversations
        $this->call(MessagingInboxSeeder::class);

        // 13. Seed default notification templates into agency settings
        $this->call(NotificationTemplatesSeeder::class);

        // 14. Seed demo in-app notifications
        $this->call(NotificationSeeder::class);
    }
}
