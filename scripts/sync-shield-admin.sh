#!/bin/bash

echo "🔄 Génération permissions Shield..."
php artisan shield:generate

echo "👑 Attribution permissions au rôle admin..."
php artisan tinker --execute="
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

\$role = Role::firstOrCreate(['name' => 'admin']);
\$role->syncPermissions(Permission::all());
"

php artisan optimize:clear

echo "✅ Admin mis à jour avec toutes les permissions."
