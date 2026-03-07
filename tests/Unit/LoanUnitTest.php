<?php

use App\Models\Loan;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

//Prueba de autorizacion para prestar libros
it('only students and teachers can borrow books', function (string $role) {
    $user = User::factory()->create();
    $user->assignRole($role);
    
    expect($user->can('create', Loan::class))->toBeTrue();

})->with(['Estudiante', 'Docente']);

//Prueba de autorizacion para devolver libros
it('only students and teachers can return a loan', function (string $role) {
    $user = User::factory()->create();
    $user->assignRole($role);
    
    expect($user->can('update', Loan::class))->toBeTrue();

})->with(['Estudiante', 'Docente']);