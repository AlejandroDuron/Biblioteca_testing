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

// Detalle libro

it('returns book when user is authorized', function () {
    $user = User::factory()->create();
    $book = Book::factory()->create();

    $response = $this->actingAs($user)->get("/api/v1/books/{$book->id}");

    $response->assertStatus(200)
             ->assertJsonFragment(['id' => $book->id]);
});

it('returns 404 when book does not exist', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/api/v1/books/99999');

    $response->assertStatus(404);
});

// 
// Crear libro

it('creates book with valid data', function () {
    $bibliotecario = User::factory()->create();
    $bibliotecario->assignRole('Bibliotecario');

    $response = $this->actingAs($bibliotecario)->post('/api/v1/books', [
        'title'            => 'Nuevo Libro',
        'ISBN'             => '1234567890123',
        'description'      => 'Una descripcion valida',
        'total_copies'     => 5,
        'available_copies' => 3,
        'is_available'     => true,
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('books', ['title' => 'Nuevo Libro']);
});

it('fails when available_copies is not integer', function () {
    $bibliotecario = User::factory()->create();
    $bibliotecario->assignRole('Bibliotecario');

    $response = $this->actingAs($bibliotecario)
                    ->withHeaders(['Accept' => 'application/json'])
                    ->post('/api/v1/books', [
        'title'            => 'Libro inválido',
        'ISBN'             => '9999999999999',
        'description'      => 'descripción',
        'total_copies'     => 5,
        'available_copies' => 'bla-bla-fatima', 
    ]);

    $response->assertStatus(422);
});

it('only librarian can create a book', function () {
    $bibliotecario = User::factory()->create();
    $bibliotecario->assignRole('Bibliotecario');

    $response = $this->actingAs($bibliotecario)->post('/api/v1/books', [
        'title'            => 'Libro para el bibliotecario',
        'ISBN'             => '1111111111111',
        'description'      => 'descripción',
        'total_copies'     => 5,
        'available_copies' => 3,
    ]);

    $response->assertStatus(201);
});

it('unauthorized users cannot create book', function () {
    $estudiante = User::factory()->create();
    $estudiante->assignRole('Estudiante');

    $response = $this->actingAs($estudiante)->post('/api/v1/books', [
        'title'            => 'No debería crearse',
        'ISBN'             => '2222222222222',
        'description'      => 'descripción',
        'total_copies'     => 5,
        'available_copies' => 3,
    ]);

    $response->assertStatus(403);
});

// Actualizar libro

it('updates only provided fields', function () {
    $bibliotecario = User::factory()->create();
    $bibliotecario->assignRole('Bibliotecario');

    $book = Book::factory()->create([
        'title' => 'Título Original',
        'ISBN'  => '3333333333333',
    ]);

    $this->actingAs($bibliotecario)->patch("/api/v1/books/{$book->id}", [
        'title' => 'Título Actualizado',
    ]);

    // El título cambia
    $this->assertDatabaseHas('books', ['title' => 'Título Actualizado']);
    // El ISBN no cambia
    $this->assertDatabaseHas('books', ['ISBN' => '3333333333333']);
});

it('returns error when trying to update while not being librarian', function () {
    $docente = User::factory()->create();
    $docente->assignRole('Docente');
    $book = Book::factory()->create();

    $response = $this->actingAs($docente)->patch("/api/v1/books/{$book->id}", [
        'title' => 'Intento fallido',
    ]);

    $response->assertStatus(403);
});

it('error message when trying to update a non existent book', function () {
    $bibliotecario = User::factory()->create();
    $bibliotecario->assignRole('Bibliotecario');

    $response = $this->actingAs($bibliotecario)->patch('/api/v1/books/99999', [
        'title' => 'No existe',
    ]);

    $response->assertStatus(404);
});

// Eliminar libro

it('deletes book when authorized', function () {
    $bibliotecario = User::factory()->create();
    $bibliotecario->assignRole('Bibliotecario');
    $book = Book::factory()->create();

    $response = $this->actingAs($bibliotecario)->delete("/api/v1/books/{$book->id}");

    $response->assertStatus(204);
    $this->assertDatabaseMissing('books', ['id' => $book->id]);
});

it('deleted book cannot be retrieved', function () {
    $bibliotecario = User::factory()->create();
    $bibliotecario->assignRole('Bibliotecario');
    $book = Book::factory()->create();

    $this->actingAs($bibliotecario)->delete("/api/v1/books/{$book->id}");

    $response = $this->actingAs($bibliotecario)->get("/api/v1/books/{$book->id}");
    $response->assertStatus(404);
});

it('returns error if book is already deleted', function () {
    $bibliotecario = User::factory()->create();
    $bibliotecario->assignRole('Bibliotecario');
    $book = Book::factory()->create();

    $this->actingAs($bibliotecario)->delete("/api/v1/books/{$book->id}");

    // probamos un segundo delete
    $response = $this->actingAs($bibliotecario)->delete("/api/v1/books/{$book->id}");
    $response->assertStatus(404);
});

it('only librarian can delete', function () {
    $estudiante = User::factory()->create();
    $estudiante->assignRole('Estudiante');
    $book = Book::factory()->create();

    $response = $this->actingAs($estudiante)->delete("/api/v1/books/{$book->id}");

    $response->assertStatus(403);
});