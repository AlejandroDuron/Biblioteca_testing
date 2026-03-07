<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

//Pruebas de distintos escenarios de incio de sesion
// Prueba 1: 
it('can log in', function () {
    // Preparacion
    $user = User::factory()->create([
        'password' => bcrypt('test123'),
    ]);

    // Ejecucion
    $response = $this->post('/api/v1/login', [
        'email' => $user->email,
        'password' => 'test123',
    ]);

    // Verificacion
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'access_token',
        'token_type',
        'user',
    ]);

    $this->assertAuthenticatedAs($user);
});

//Prueba 2:
it('it cannot log in with invalid password', function () {
    // Preparacion
    $user = User::factory()->create([
        'password' => bcrypt('test123'),
    ]);

    // Ejecucion
    $response = $this->post('/api/v1/login', [
        'email' => $user->email,
        'password' => 'password567',
    ]);

    // Verificacion
    $response->assertStatus(422);

    $response->assertJson([
        'message' => 'Invalid credentials'
    ]);

    $this->assertGuest();
});

//Prueba 3:
it('it cannot log in with invalid email', function () {
    //Ejecucion
    $response = $this->post('/api/v1/login', [
        'email' => 'emailincorrecto@correo.com',
        'password' => 'escuela123',
    ]);

    // Verificacion
    $response->assertStatus(422);

    $response->assertJson([
        'message' => 'Invalid credentials'
    ]);

    $this->assertGuest();
});

//Pruebas de distintos escenarios de cierre de sesion
//Prueba 4:
it('can log out', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->post('/api/v1/logout');

    $response->assertStatus(200);
    $response->assertJson([
        'message' => 'Logged out successfully'
    ]);

    expect($user->tokens()->count())->toBe(0);
});

//Prueba 5:
it('unauthenticated users cannot log out', function () {
    $response = $this->postJson('/api/v1/logout');

    $response->assertStatus(401);
});

//Pruebas de distintos escenarios de mostrar el perfil del usuario
//Prueba 6:
it('it shows user profile', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/api/v1/profile');

    //Verificacion
    $response->assertStatus(200);
    $response->assertJson([
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]
    ]);
});

//Prueba 7:
it('should not show users without active session', function () {
    $response = $this->getJson('/api/v1/profile');

    $response->assertStatus(401);
});