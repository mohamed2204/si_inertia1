<?php

namespace App\Policies;

use App\Models\DesignationItem;
use App\Models\User;

class DesignationItemPolicy
{
    /**
     * Détermine si l'utilisateur peut modifier une ligne de saisie spécifique.
     */
    public function update(User $user, DesignationItem $designationItem): bool
    {
        if ($user->hasRole('super_admin')) return true;

        $labId = $designationItem->sous_departement_id;

        // 1. Vérifier s'il est le responsable titulaire (ConfigResponsable)
        $isResponsable = \App\Models\ConfigResponsable::where('user_id', $user->id)
            ->where('sous_departement_id', $labId)
            ->exists();

        // 2. Vérifier s'il est remplaçant actif (ConfigRemplacant)
        $isRemplacant = \App\Models\ConfigRemplacant::where('user_id', $user->id)
            ->where('sous_departement_id', $labId)
            ->where('is_active', true)
            ->exists();

        return $isResponsable || $isRemplacant;
    }

    public function delete(User $user, DesignationItem $designationItem): bool
    {
        // On applique la même logique que pour l'update
        return $this->update($user, $designationItem);
    }
}
