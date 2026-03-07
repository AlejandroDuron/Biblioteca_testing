<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;

class LoanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Estudiante', 'Docente', 'Bibliotecario']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Estudiante', 'Docente']);
    }

    public function update(User $user, Loan $loan): bool
    {
        return $user->hasAnyRole(['Estudiante', 'Docente']);
    }
}