<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SyncPermissions extends Command
{
    // Le nom de la commande à taper dans le terminal
    protected $signature = 'app:permissions:sync';
    protected $description = 'Synchronise les permissions personnalisées dans la base de données';

    public function handle()
    {
        // 1. Liste de vos permissions spécifiques au projet
        $customPermissions = [
            'initialiser_notes',
            'attribuer_notes_masse',
            'vider_feuille_notes',
            'voir_stats_avancement',
            'exporter_notes_excel',
            'validate_note',
        ];

        $this->info('Début de la synchronisation...');

        // 2. Création des permissions
        foreach ($customPermissions as $permissionName) {
            // firstOrCreate évite les erreurs si la permission existe déjà
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
            $this->line("- Permission : {$permissionName} [OK]");
        }

        // 3. Attribution automatique à l'Admin (pour ne pas être bloqué)
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo($customPermissions);

        $this->line('Terminé ! Toutes les permissions sont prêtes et l\'admin y a accès.');
    }
}


