<?php

use App\Models\Book;
use App\Models\User;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);


beforeEach(function () {
    Role::create(['name' => 'Bibliotecario']);
    Role::create(['name' => 'Estudiante']);
    Role::create(['name' => 'Docente']);
});


// Listar libros

it('returns only existing records', closure: function () {
    $user = User::factory()->create();
    Book::factory()->count(3)->create();

    $response = $this->actingAs($user)->get('/api/v1/books');

    $response->assertStatus(200);
    $this->assertDatabaseCount('books', 3);
});

it('paginates correctly', function () {
    $user = User::factory()->create();
    Book::factory()->count(20)->create();


    $response = $this->actingAs($user)->get('/api/v1/books');
    $response->assertStatus(200);

    expect($response->json())->toHaveCount(15);
});

it('respects filter by title', function () {
    $user = User::factory()->create();
    Book::factory()->create(['title' => 'La Odisea']);
    Book::factory()->create(['title' => 'Homero']);

    $response = $this->actingAs($user)->get('/api/v1/books?title=Odisea');

    $response->assertStatus(200);
    expect($response->json())->toHaveCount(1);
    expect($response->json('0.title'))->toBe('La Odisea');
});

it('respects filter by is_available', function () {
    $user = User::factory()->create();
    Book::factory()->create(['is_available' => true]);
    Book::factory()->create(['is_available' => false]);

    $response = $this->actingAs($user)->get('/api/v1/books?is_available=1');

    $response->assertStatus(200);
    expect($response->json())->toHaveCount(1);
    expect($response->json('0.is_available'))->toBe('Disponible');
});

it('returns error when not authorized', function () {
    $response = $this->withHeaders(['Accept' => 'application/json'])
                     ->get('/api/v1/books');

    $response->assertStatus(401);
});