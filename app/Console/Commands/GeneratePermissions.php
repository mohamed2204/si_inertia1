<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Artisan;

class GeneratePermissions extends Command
{
    protected $signature = 'permissions:generate';

    protected $description = 'Generate permissions from Filament resources';

    public function handle(): void
    {
        $resources = config('filament.resources');
        //dd($resources);

        foreach ($resources as $resource) {

            $modelClass = $resource::getModel(); // App\Models\Matiere
            $model = class_basename($modelClass); // Matiere
            $resource = Str::snake(class_basename($modelClass));

            $this->info('Resource : ' . $resource);

            $policyName = "{$model}Policy";

            // Vérifier si existe déjà
            if (!class_exists("App\\Policies\\{$policyName}")) {

                Artisan::call('make:policy', [
                    'name' => $policyName,
                    '--model' => $modelClass, // IMPORTANT : ici on met la classe complète
                ]);

                $this->addPolicyToProvider($modelClass, $policyName);

                $this->info("Policy créée : {$policyName}");


            }

            $this->generateCrudPermissions($resource);
            $this->generateCustomPermissions($resource);
        }

        $this->info('Permissions generated successfully');
    }

    protected function generateCrudPermissions(string $resource): void
    {
        foreach (config('permissions.actions') as $action) {

            $permission = "{$action}_{$resource}";
            $this->info("Permission créée : " . $permission);
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }

    protected function generateCustomPermissions(string $resource): void
    {
        $custom = config("permissions.custom_actions.$resource", []);

        foreach ($custom as $action) {

            $permission = "{$action}_{$resource}";
           $this->info("Permission créée : " . $permission);
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }

    protected function addPolicyToProvider($model, $policy): void
    {
        $providerPath = app_path('Providers/AuthServiceProvider.php');

        $content = file_get_contents($providerPath);

        $entry = "\\{$model}::class => \\{$policy}::class,";

        if (!str_contains($content, $entry)) {

            $content = str_replace(
                'protected $policies = [',
                "protected \$policies = [\n        {$entry}",
                $content
            );

            file_put_contents($providerPath, $content);
        }
    }

}

