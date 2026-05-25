<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
class SyncRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-roles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */


    public function handle(): void
    {
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $prof = Role::firstOrCreate(['name' => 'professeur']);

        // admin = tout
        $admin->syncPermissions(Permission::all());

        // manager
        $manager->syncPermissions(
            Permission::where('name', 'like', '%view%')
                ->orWhere('name', 'like', '%validate%')
                ->get()
        );

        // professeur
        $prof->syncPermissions(
            Permission::where('name', 'like', '%note%')
                ->orWhere('name', 'like', '%view_matiere%')
                ->get()
        );

        $this->info('Roles synced');

        app()[\Spatie\Permission\PermissionRegistrar::class]
            ->forgetCachedPermissions();

        $this->callSilent('cache:clear');
    }

}
