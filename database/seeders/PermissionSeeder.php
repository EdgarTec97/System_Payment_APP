<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // User Management
            ['name' => 'users.view', 'display_name' => 'Ver Usuarios', 'description' => 'Puede ver la lista de usuarios', 'module' => 'users'],
            ['name' => 'users.create', 'display_name' => 'Crear Usuarios', 'description' => 'Puede crear nuevos usuarios', 'module' => 'users'],
            ['name' => 'users.edit', 'display_name' => 'Editar Usuarios', 'description' => 'Puede editar usuarios existentes', 'module' => 'users'],
            ['name' => 'users.delete', 'display_name' => 'Eliminar Usuarios', 'description' => 'Puede eliminar usuarios', 'module' => 'users'],
            ['name' => 'users.verify', 'display_name' => 'Verificar Usuarios', 'description' => 'Puede verificar/desverificar usuarios', 'module' => 'users'],
            
            // Product Management
            ['name' => 'products.view', 'display_name' => 'Ver Productos', 'description' => 'Puede ver la lista de productos', 'module' => 'products'],
            ['name' => 'products.create', 'display_name' => 'Crear Productos', 'description' => 'Puede crear nuevos productos', 'module' => 'products'],
            ['name' => 'products.edit', 'display_name' => 'Editar Productos', 'description' => 'Puede editar productos existentes', 'module' => 'products'],
            ['name' => 'products.delete', 'display_name' => 'Eliminar Productos', 'description' => 'Puede eliminar productos', 'module' => 'products'],
            
            // Order Management
            ['name' => 'orders.view', 'display_name' => 'Ver Órdenes', 'description' => 'Puede ver órdenes', 'module' => 'orders'],
            ['name' => 'orders.create', 'display_name' => 'Crear Órdenes', 'description' => 'Puede crear nuevas órdenes', 'module' => 'orders'],
            ['name' => 'orders.edit', 'display_name' => 'Editar Órdenes', 'description' => 'Puede editar órdenes existentes', 'module' => 'orders'],
            ['name' => 'orders.delete', 'display_name' => 'Eliminar Órdenes', 'description' => 'Puede eliminar órdenes canceladas', 'module' => 'orders'],
            ['name' => 'orders.status', 'display_name' => 'Cambiar Estado Órdenes', 'description' => 'Puede cambiar el estado de las órdenes', 'module' => 'orders'],
            
            // Dashboard & Reports
            ['name' => 'dashboard.admin', 'display_name' => 'Dashboard Admin', 'description' => 'Acceso al dashboard administrativo', 'module' => 'dashboard'],
            ['name' => 'dashboard.support', 'display_name' => 'Dashboard Soporte', 'description' => 'Acceso al dashboard de soporte', 'module' => 'dashboard'],
            ['name' => 'reports.view', 'display_name' => 'Ver Reportes', 'description' => 'Puede ver reportes del sistema', 'module' => 'reports'],
            
            // Profile Management
            ['name' => 'profile.view', 'display_name' => 'Ver Perfil', 'description' => 'Puede ver su propio perfil', 'module' => 'profile'],
            ['name' => 'profile.edit', 'display_name' => 'Editar Perfil', 'description' => 'Puede editar su propio perfil', 'module' => 'profile'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->insert(array_merge($permission, [
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Assign permissions to roles
        $this->assignPermissionsToRoles();
    }

    private function assignPermissionsToRoles()
    {
        // Get role IDs
        $adminRole = DB::table('roles')->where('name', 'admin')->first();
        $supportRole = DB::table('roles')->where('name', 'support')->first();
        $basicRole = DB::table('roles')->where('name', 'basic')->first();

        // Get all permissions
        $allPermissions = DB::table('permissions')->get();

        // Admin gets all permissions
        foreach ($allPermissions as $permission) {
            DB::table('permission_role')->insert([
                'permission_id' => $permission->id,
                'role_id' => $adminRole->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Support permissions
        $supportPermissions = [
            'products.view', 'products.create', 'products.edit', 'products.delete',
            'orders.view', 'orders.create', 'orders.edit', 'orders.status',
            'dashboard.support', 'profile.view', 'profile.edit'
        ];

        foreach ($supportPermissions as $permissionName) {
            $permission = $allPermissions->where('name', $permissionName)->first();
            if ($permission) {
                DB::table('permission_role')->insert([
                    'permission_id' => $permission->id,
                    'role_id' => $supportRole->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Basic user permissions
        $basicPermissions = [
            'products.view', 'orders.view', 'orders.create',
            'profile.view', 'profile.edit'
        ];

        foreach ($basicPermissions as $permissionName) {
            $permission = $allPermissions->where('name', $permissionName)->first();
            if ($permission) {
                DB::table('permission_role')->insert([
                    'permission_id' => $permission->id,
                    'role_id' => $basicRole->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}

