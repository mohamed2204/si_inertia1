<?php
namespace App\Policies;

use App\Models\Designation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\DB;

class DesignationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasGlobalPermission('designations', 'lecture');
    }

    public function create(User $user): bool
    {
        if (! $user->hasGlobalPermission('designations', 'modification')) {
            return false;
        }

        if ($user->hasAbsoluteView()) {
            return true;
        }

        // Doit avoir au moins un labo en écriture ou total pour pouvoir créer
        $groupIds = $user->groups()->pluck('groups.id')->toArray();
        return DB::table('group_sous_departement')
            ->whereIn('group_id', $groupIds)
            ->whereIn('niveau_acces', ['ecriture', 'total'])
            ->exists();
    }

    public function update(User $user, Designation $designation): bool
    {
        if (! $user->hasGlobalPermission('designations', 'modification')) {
            return false;
        }

        $labLevel = $user->getAccessLevelForLab($designation->sous_departement_id);

        return in_array($labLevel, ['ecriture', 'total']);
    }

    public function delete(User $user, Designation $designation): bool
    {
        if (! $user->hasGlobalPermission('designations', 'suppression')) {
            return false;
        }

        $labLevel = $user->getAccessLevelForLab($designation->sous_departement_id);

        return $labLevel === 'total';
    }

    /**
     * Determine whether the user can view any models.
     */
    // public function viewAny(User $user): bool
    // {
    //     return $user->can('view_any_designation');
    // }

    // /**
    //  * Determine whether the user can view the model.
    //  */
    // public function view(User $user, Designation $designation): bool
    // {
    //     return $user->can('view_designation');
    // }

    // /**
    //  * Determine whether the user can create models.
    //  */
    // public function create(User $user): bool
    // {
    //     return $user->can('create_designation');
    // }

    // /**
    //  * Determine whether the user can update the model.
    //  */
    // public function update(User $user, Designation $designation): bool
    // {
    //     return $user->can('update_designation');
    // }

    // /**
    //  * Determine whether the user can delete the model.
    //  */
    // public function delete(User $user, Designation $designation): bool
    // {
    //     return $user->can('delete_designation');
    // }

    // /**
    //  * Determine whether the user can bulk delete.
    //  */
    // public function deleteAny(User $user): bool
    // {
    //     return $user->can('delete_any_designation');
    // }

    // /**
    //  * Determine whether the user can permanently delete.
    //  */
    // public function forceDelete(User $user, Designation $designation): bool
    // {
    //     return $user->can('force_delete_designation');
    // }

    // /**
    //  * Determine whether the user can permanently bulk delete.
    //  */
    // public function forceDeleteAny(User $user): bool
    // {
    //     return $user->can('force_delete_any_designation');
    // }

    // /**
    //  * Determine whether the user can restore.
    //  */
    // public function restore(User $user, Designation $designation): bool
    // {
    //     return $user->can('restore_designation');
    // }

    // /**
    //  * Determine whether the user can bulk restore.
    //  */
    // public function restoreAny(User $user): bool
    // {
    //     return $user->can('restore_any_designation');
    // }

    // /**
    //  * Determine whether the user can replicate.
    //  */
    // public function replicate(User $user, Designation $designation): bool
    // {
    //     return $user->can('replicate_designation');
    // }

    // /**
    //  * Determine whether the user can reorder.
    //  */
    // public function reorder(User $user): bool
    // {
    //     return $user->can('reorder_designation');
    // }
}
