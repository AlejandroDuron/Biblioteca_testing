<?php

namespace App\Policies;

use App\Models\User;

class LoanPolicy
{
    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Estudiante', 'Docente']);
    }
}