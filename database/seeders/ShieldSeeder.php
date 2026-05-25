<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use BezhanSalleh\FilamentShield\Support\Utils;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $rolesWithPermissions = '[{"name":"super_admin","guard_name":"web","permissions":["view_classement","view_any_classement","create_classement","update_classement","restore_classement","restore_any_classement","replicate_classement","reorder_classement","delete_classement","delete_any_classement","force_delete_classement","force_delete_any_classement","view_eleve","view_any_eleve","create_eleve","update_eleve","restore_eleve","restore_any_eleve","replicate_eleve","reorder_eleve","delete_eleve","delete_any_eleve","force_delete_eleve","force_delete_any_eleve","view_grade","view_any_grade","create_grade","update_grade","restore_grade","restore_any_grade","replicate_grade","reorder_grade","delete_grade","delete_any_grade","force_delete_grade","force_delete_any_grade","view_matiere","view_any_matiere","create_matiere","update_matiere","restore_matiere","restore_any_matiere","replicate_matiere","reorder_matiere","delete_matiere","delete_any_matiere","force_delete_matiere","force_delete_any_matiere","view_note","view_any_note","create_note","update_note","restore_note","restore_any_note","replicate_note","reorder_note","delete_note","delete_any_note","force_delete_note","force_delete_any_note","view_phase","view_any_phase","create_phase","update_phase","restore_phase","restore_any_phase","replicate_phase","reorder_phase","delete_phase","delete_any_phase","force_delete_phase","force_delete_any_phase","view_programme::matiere","view_any_programme::matiere","create_programme::matiere","update_programme::matiere","restore_programme::matiere","restore_any_programme::matiere","replicate_programme::matiere","reorder_programme::matiere","delete_programme::matiere","delete_any_programme::matiere","force_delete_programme::matiere","force_delete_any_programme::matiere","view_promotion","view_any_promotion","create_promotion","update_promotion","restore_promotion","restore_any_promotion","replicate_promotion","reorder_promotion","delete_promotion","delete_any_promotion","force_delete_promotion","force_delete_any_promotion","view_promotion::specialite","view_any_promotion::specialite","create_promotion::specialite","update_promotion::specialite","restore_promotion::specialite","restore_any_promotion::specialite","replicate_promotion::specialite","reorder_promotion::specialite","delete_promotion::specialite","delete_any_promotion::specialite","force_delete_promotion::specialite","force_delete_any_promotion::specialite","view_promotion::specialite::phase","view_any_promotion::specialite::phase","create_promotion::specialite::phase","update_promotion::specialite::phase","restore_promotion::specialite::phase","restore_any_promotion::specialite::phase","replicate_promotion::specialite::phase","reorder_promotion::specialite::phase","delete_promotion::specialite::phase","delete_any_promotion::specialite::phase","force_delete_promotion::specialite::phase","force_delete_any_promotion::specialite::phase","view_role","view_any_role","create_role","update_role","restore_role","restore_any_role","replicate_role","reorder_role","delete_role","delete_any_role","force_delete_role","force_delete_any_role","view_specialite","view_any_specialite","create_specialite","update_specialite","restore_specialite","restore_any_specialite","replicate_specialite","reorder_specialite","delete_specialite","delete_any_specialite","force_delete_specialite","force_delete_any_specialite","view_user","view_any_user","create_user","update_user","restore_user","restore_any_user","replicate_user","reorder_user","delete_user","delete_any_user","force_delete_user","force_delete_any_user","page_CreerPromotion","page_CreerPromotionComplete","page_DirectionDashboard","page_ImportData","page_ImportNotesEleves","page_MissingGradesReport","page_ModifierPromotion","page_ParcoursDashboard","page_PromotionPhases","page_PromotionPhasesForm","page_PublipostageFoxpro","page_SaisieNotesMassive","page_StatistiquesSchool","widget_ElevesStats","widget_GlobalStats","widget_PhaseCalendarWidget","widget_PhasesActives","widget_PromotionsStats","widget_RoadmapWidget","widget_StatsOverview","access_eleve_import","access_import_notes","access_publipostage_foxpro","access_promotion_create","access_promotion_edit"]},{"name":"Professeur","guard_name":"web","permissions":["view_any_eleve","access_eleve_import"]},{"name":"admin","guard_name":"web","permissions":["view_classement","view_any_classement","create_classement","update_classement","restore_classement","restore_any_classement","replicate_classement","reorder_classement","delete_classement","delete_any_classement","force_delete_classement","force_delete_any_classement","view_eleve","view_any_eleve","create_eleve","update_eleve","restore_eleve","restore_any_eleve","replicate_eleve","reorder_eleve","delete_eleve","delete_any_eleve","force_delete_eleve","force_delete_any_eleve","view_grade","view_any_grade","create_grade","update_grade","restore_grade","restore_any_grade","replicate_grade","reorder_grade","delete_grade","delete_any_grade","force_delete_grade","force_delete_any_grade","view_matiere","view_any_matiere","create_matiere","update_matiere","restore_matiere","restore_any_matiere","replicate_matiere","reorder_matiere","delete_matiere","delete_any_matiere","force_delete_matiere","force_delete_any_matiere","view_note","view_any_note","create_note","update_note","restore_note","restore_any_note","replicate_note","reorder_note","delete_note","delete_any_note","force_delete_note","force_delete_any_note","view_phase","view_any_phase","create_phase","update_phase","restore_phase","restore_any_phase","replicate_phase","reorder_phase","delete_phase","delete_any_phase","force_delete_phase","force_delete_any_phase","view_programme::matiere","view_any_programme::matiere","create_programme::matiere","update_programme::matiere","restore_programme::matiere","restore_any_programme::matiere","replicate_programme::matiere","reorder_programme::matiere","delete_programme::matiere","delete_any_programme::matiere","force_delete_programme::matiere","force_delete_any_programme::matiere","view_promotion","view_any_promotion","create_promotion","update_promotion","restore_promotion","restore_any_promotion","replicate_promotion","reorder_promotion","delete_promotion","delete_any_promotion","force_delete_promotion","force_delete_any_promotion","view_promotion::specialite","view_any_promotion::specialite","create_promotion::specialite","update_promotion::specialite","restore_promotion::specialite","restore_any_promotion::specialite","replicate_promotion::specialite","reorder_promotion::specialite","delete_promotion::specialite","delete_any_promotion::specialite","force_delete_promotion::specialite","force_delete_any_promotion::specialite","view_promotion::specialite::phase","view_any_promotion::specialite::phase","create_promotion::specialite::phase","update_promotion::specialite::phase","restore_promotion::specialite::phase","restore_any_promotion::specialite::phase","replicate_promotion::specialite::phase","reorder_promotion::specialite::phase","delete_promotion::specialite::phase","delete_any_promotion::specialite::phase","force_delete_promotion::specialite::phase","force_delete_any_promotion::specialite::phase","view_role","view_any_role","create_role","update_role","restore_role","restore_any_role","replicate_role","reorder_role","delete_role","delete_any_role","force_delete_role","force_delete_any_role","view_specialite","view_any_specialite","create_specialite","update_specialite","restore_specialite","restore_any_specialite","replicate_specialite","reorder_specialite","delete_specialite","delete_any_specialite","force_delete_specialite","force_delete_any_specialite","view_user","view_any_user","create_user","update_user","restore_user","restore_any_user","replicate_user","reorder_user","delete_user","delete_any_user","force_delete_user","force_delete_any_user","page_CreerPromotion","page_CreerPromotionComplete","page_DirectionDashboard","page_ImportData","page_ImportNotesEleves","page_MissingGradesReport","page_ModifierPromotion","page_ParcoursDashboard","page_PromotionPhases","page_PromotionPhasesForm","page_PublipostageFoxpro","page_SaisieNotesMassive","page_StatistiquesSchool","widget_ElevesStats","widget_GlobalStats","widget_PhaseCalendarWidget","widget_PhasesActives","widget_PromotionsStats","widget_RoadmapWidget","widget_StatsOverview","access_eleve_import","access_import_notes","access_publipostage_foxpro","access_promotion_create","access_promotion_edit"]}]';
        $directPermissions = '[]';

        static::makeRolesWithPermissions($rolesWithPermissions);
        static::makeDirectPermissions($directPermissions);

        $this->command->info('Shield Seeding Completed.');
    }

    protected static function makeRolesWithPermissions(string $rolesWithPermissions): void
    {
        if (! blank($rolePlusPermissions = json_decode($rolesWithPermissions, true))) {
            /** @var Model $roleModel */
            $roleModel = Utils::getRoleModel();
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($rolePlusPermissions as $rolePlusPermission) {
                $role = $roleModel::firstOrCreate([
                    'name' => $rolePlusPermission['name'],
                    'guard_name' => $rolePlusPermission['guard_name'],
                ]);

                if (! blank($rolePlusPermission['permissions'])) {
                    $permissionModels = collect($rolePlusPermission['permissions'])
                        ->map(fn ($permission) => $permissionModel::firstOrCreate([
                            'name' => $permission,
                            'guard_name' => $rolePlusPermission['guard_name'],
                        ]))
                        ->all();

                    $role->syncPermissions($permissionModels);
                }
            }
        }
    }

    public static function makeDirectPermissions(string $directPermissions): void
    {
        if (! blank($permissions = json_decode($directPermissions, true))) {
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($permissions as $permission) {
                if ($permissionModel::whereName($permission)->doesntExist()) {
                    $permissionModel::create([
                        'name' => $permission['name'],
                        'guard_name' => $permission['guard_name'],
                    ]);
                }
            }
        }
    }
}
