<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(?int $agencyId = null): void
    {
        // Reset cached roles and permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. Define and Create Permissions (Global)
        $permissions = [
            // Listings
            'listings.view_own', 'listings.view_team', 'listings.view_all',
            'listings.create', 'listings.edit', 'listings.delete', 'listings.manage',
            
            // Contacts
            'contacts.view_own', 'contacts.view_team', 'contacts.view_all',
            'contacts.create', 'contacts.edit', 'contacts.delete', 'contacts.manage',
            
            // Pipeline / Deals
            'pipeline.view_own', 'pipeline.view_team', 'pipeline.view_all',
            'pipeline.create', 'pipeline.edit', 'pipeline.delete', 'pipeline.manage',
            
            // Campaigns / Marketing
            'campaigns.view_own', 'campaigns.view_team', 'campaigns.view_all',
            'campaigns.create', 'campaigns.edit', 'campaigns.delete', 'campaigns.manage',
            
            // Transactions / Compliance
            'transactions.view_own', 'transactions.view_team', 'transactions.view_all',
            'transactions.create', 'transactions.edit', 'transactions.delete', 'transactions.manage',
            
            // Commissions
            'commission.view_own', 'commission.view_team', 'commission.view_all',
            'commission.create', 'commission.edit', 'commission.delete', 'commission.manage',
            
            // Dashboards
            'dashboard.view', 'dashboard.manage',
            
            // Training
            'training.view', 'training.manage',
            
            // Agency settings
            'agency.view', 'agency.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // 2. Set Spatie Permission team context if agencyId is provided
        if ($agencyId !== null) {
            setPermissionsTeamId($agencyId);
        }

        // 3. Create Tenant-scoped Roles and Assign Permissions
        
        // Agent Role
        $agent = Role::firstOrCreate([
            'name' => 'agent', 
            'guard_name' => 'web',
            'agency_id' => $agencyId
        ]);
        $agent->syncPermissions([
            'listings.view_own', 'listings.create', 'listings.edit',
            'contacts.view_own', 'contacts.create', 'contacts.edit',
            'pipeline.view_own', 'pipeline.create', 'pipeline.edit',
            'dashboard.view', 'training.view',
        ]);

        // Senior Agent Role
        $seniorAgent = Role::firstOrCreate([
            'name' => 'senior_agent', 
            'guard_name' => 'web',
            'agency_id' => $agencyId
        ]);
        $seniorAgent->syncPermissions([
            'listings.view_team', 'listings.create', 'listings.edit',
            'contacts.view_team', 'contacts.create', 'contacts.edit',
            'pipeline.view_team', 'pipeline.create', 'pipeline.edit',
            'dashboard.view', 'training.view',
        ]);

        // Branch Manager
        $branchManager = Role::firstOrCreate([
            'name' => 'branch_manager', 
            'guard_name' => 'web',
            'agency_id' => $agencyId
        ]);
        $branchManager->syncPermissions([
            'listings.view_team', 'listings.create', 'listings.edit', 'listings.delete',
            'contacts.view_team', 'contacts.create', 'contacts.edit', 'contacts.delete',
            'pipeline.view_team', 'pipeline.create', 'pipeline.edit', 'pipeline.delete',
            'dashboard.view', 'training.view',
        ]);

        // Marketing Manager
        $marketingManager = Role::firstOrCreate([
            'name' => 'marketing_manager', 
            'guard_name' => 'web',
            'agency_id' => $agencyId
        ]);
        $marketingManager->syncPermissions([
            'listings.view_all', 'listings.edit',
            'campaigns.view_all', 'campaigns.create', 'campaigns.edit', 'campaigns.delete', 'campaigns.manage',
            'dashboard.view',
        ]);

        // Admin / PA
        $admin = Role::firstOrCreate([
            'name' => 'admin', 
            'guard_name' => 'web',
            'agency_id' => $agencyId
        ]);
        $admin->syncPermissions([
            'listings.view_all', 'listings.create', 'listings.edit',
            'contacts.view_all', 'contacts.create', 'contacts.edit',
            'pipeline.view_all', 'pipeline.create', 'pipeline.edit',
            'transactions.view_all', 'transactions.create', 'transactions.edit', 'transactions.manage',
            'dashboard.view', 'dashboard.manage',
        ]);

        // Principal Role (Full Access)
        $principal = Role::firstOrCreate([
            'name' => 'principal', 
            'guard_name' => 'web',
            'agency_id' => $agencyId
        ]);
        $principal->syncPermissions($permissions);

        // Super Admin (Platform level)
        $superAdmin = Role::firstOrCreate([
            'name' => 'super_admin', 
            'guard_name' => 'web',
            'agency_id' => $agencyId
        ]);
        $superAdmin->syncPermissions($permissions);
    }
}
