<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ShieldSync extends Command
{
    protected $signature = 'shield:sync {--admin}';
    protected $description = 'Synchronise les permissions Filament Shield';

    public function handle(): int
    {
        $this->info('🔄 Génération des permissions Shield...');
        $this->call('shield:generate');

        if ($this->option('admin')) {
            $this->info('👑 Attribution des permissions au rôle admin...');
            $role = Role::firstOrCreate(['name' => 'admin']);
            $role->syncPermissions(Permission::all());
        }

        $this->call('optimize:clear');

        $this->info('✅ Synchronisation terminée.');
        return self::SUCCESS;
    }
}
