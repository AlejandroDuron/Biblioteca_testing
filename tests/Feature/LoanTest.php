<?php

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'Bibliotecario']);
    Role::create(['name' => 'Estudiante']);
    Role::create(['name' => 'Docente']);
});

//Pruebas de distintos escenarios de prestar libros
//Prueba 1:
it('only students and teachers can borrow a book', function (string $role) {
    //Preparacion
    $user = User::factory()->create();
    $user->assignRole($role);

    $book = Book::factory()->create([
        'available_copies' => 5,
        'is_available' => true
    ]);

    //Ejecucion
    $response = $this->actingAs($user)->post('/api/v1/loans', [
        'book_id' => $book->id,
        'requester_name' => $user->name
    ]);

    //Verificacion
    $response->assertStatus(201);

    //ver si el loan se creo correctamente
    $this->assertDatabaseHas('loans', [
        'book_id' => $book->id,
        'requester_name' => $user->name,
    ]);

    //para ver si el libro que se presto, se actualizo correctamente
    $this->assertDatabaseHas('books', [
        'id' => $book->id,
        'available_copies' => 4,
        'is_available' => true,
    ]);

    $response->assertJsonFragment([
        'book_id' => $book->id,
        'requester_name' => $user->name,
    ]);

})->with(['Estudiante', 'Docente']);

//Prueba 2:
it('librarian cannot borrow a book', function () {
    //Preparacion
    $user = User::factory()->create();

    $user->assignRole('Bibliotecario');

    $book = Book::factory()->create();

    //Ejecucion
    $response = $this->actingAs($user)->post('/api/v1/loans', [
        'book_id' => $book->id,
        'requester_name' => $user->name
    ]);

    $response->assertStatus(403);

    //para validar que no se haya creado ningun regsitro 
    $this->assertDatabaseCount('loans', 0);
});

//Prueba 3:
it('a non-existent book cannot be loaned', function () {
    //Preparacion
    $user = User::factory()->create();
    $user->assignRole('Estudiante');

    //Ejecucion
    $response = $this->actingAs($user)->postJson('/api/v1/loans', [
        'book_id' => 7,
        'requester_name' => $user->name
    ]);

    //Verificacion
    $response->assertStatus(422);
    $this->assertDatabaseCount('loans', 0);
});

//Prueba 4:
it('a not available book cannot be loaned', function () {
    //Preparacion
    $user = User::factory()->create();
    $user->assignRole('Docente');

    $book = Book::factory()->create([
        'available_copies' => 0,
        'is_available' => false
    ]);

    //Ejecucion
    $response = $this->actingAs($user)->postJson('/api/v1/loans', [
        'book_id' => $book->id,
        'requester_name' => $user->name
    ]);

    //Verificacion
    $response->assertStatus(422);
    $this->assertDatabaseCount('loans', 0);
});

//Prueba 5:
it('a loan cannot be made without the requester’s name', function () {
    //Preparacion
    $user = User::factory()->create();
    $user->assignRole('Estudiante');

    $book = Book::factory()->create();

    //Ejecucion
    $response = $this->actingAs($user)->postJson('/api/v1/loans', [
        'book_id' => $book->id
    ]);

    //Verificacion
    $response->assertStatus(422);
    $this->assertDatabaseCount('loans', 0);
});

//Pruebas de distintos escenarios de devolver libros
//Prueba 6:
it('only students and teachers can return a loan', function (string $role) {
    //Preparacion
    $user = User::factory()->create();
    $user->assignRole($role);

    $book = Book::factory()->create([
        'available_copies' => 3,
        'is_available' => true
    ]);

    $loan = Loan::create([
        'book_id' => $book->id,
        'requester_name' => 'Juan Martinez'
    ]);

    //Ejecucion
    $response = $this->actingAs($user)->post("/api/v1/loans/{$loan->id}/return");

    //Verificacion
    $response->assertStatus(200);

    $this->assertNotNull($loan->fresh()->return_at);

    $this->assertDatabaseHas('books', [
        'id' => $book->id,
        'available_copies' => 4,
        'is_available' => true,
    ]);

    $response->assertJsonFragment([
        'id' => $loan->id,
        'requester_name' => 'Juan Martinez'
    ]);

})->with(['Estudiante', 'Docente']);

//Prueba 7:
it('librarian cannot return a loan', function () {
    //Preparacion
    $user = User::factory()->create();
    $user->assignRole('Bibliotecario');

    $book = Book::factory()->create();

    $loan = Loan::create([
        'book_id' => $book->id,
        'requester_name' => 'Hugo Flores'
    ]);

    //Ejecucion
    $response = $this->actingAs($user)->post("/api/v1/loans/{$loan->id}/return");

    //Verificacion
    $response->assertStatus(403);

    $this->assertDatabaseHas('loans', [
        'id' => $loan->id,
        'return_at' => null
    ]);
});

//Prueba 8:
it('cannot return an already returned loan', function () {
    //Preparacion
    $user = User::factory()->create();
    $user->assignRole('Docente');

    $book = Book::factory()->create();

    $loan = Loan::create([
        'book_id' => $book->id,
        'requester_name' => 'Maria Guevara',
        'return_at' => '2026-03-01 15:30:45'
    ]);

    //Ejecucion
    $response = $this->actingAs($user)->post("/api/v1/loans/{$loan->id}/return");

    //Verificacion
    $response->assertStatus(422);
    $response->assertJson([
        'message' => 'Loan already returned'
    ]);

    $this->assertDatabaseHas('loans', [
        'id' => $loan->id,
        'requester_name' => 'Maria Guevara',
        'return_at' => '2026-03-01 15:30:45'
    ]);
});

//Prueba 9:
it('a non-existent loan cannot be returned', function () {
    //Preparacion
    $user = User::factory()->create();
    $user->assignRole('Estudiante');

    $nonExistentLoanId = 934;

    //Ejecucion
    $response = $this->actingAs($user)->post("/api/v1/loans/{$nonExistentLoanId}/return");

    //Verificacion
    $response->assertStatus(404);
});

//Pruebas de distintos escenarios de devolver libros
//Prueba 10:
it('show the history of loaned books', function (string $role) {
    $user = User::factory()->create();
    $user->assignRole($role);

    $book = Book::factory()->create();

    $loan = Loan::create([
        'book_id' => $book->id,
        'requester_name' => 'Ana Fernandez',
        'return_at' => '2026-02-01 15:30:45'
    ]);

    $response = $this->actingAs($user)->get('/api/v1/loans');

    $response->assertStatus(200);

    $response->assertJsonFragment([
        'id' => $loan->id,
        'requester_name' => 'Ana Fernandez',
        'return_at' => '2026-02-01 15:30:45'
    ]);

})->with(['Estudiante', 'Docente', 'Bibliotecario']);

//Prueba 11: 
it('unauthorized users cannot view the history of loaned books', function () {
    // Preparación
    $user = User::factory()->create();

    // Ejecución
    $response = $this->actingAs($user)->get('/api/v1/loans');

    // Verificación
    $response->assertStatus(403);

    $response->assertForbidden();
});
