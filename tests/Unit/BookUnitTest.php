<?php

use App\Models\Book;
use App\Http\Resources\BookResource;
use Illuminate\Http\Request;

it('transforms the book into an array', function () {
    //libro de prueba
    $book = new Book([
        'id' => 1,
        'title' => 'El Quijote',
        'description' => 'Libro clásico',
        'ISBN' => '1234567890123',
        'total_copies' => 10,
        'available_copies' => 5,
        'is_available' => true
    ]);

    $resource = new BookResource($book);
    $result = $resource->toArray(new Request());

    //Revisar la transformacion
    expect($result)->toBeArray()
        ->and($result['title'])->toBe('El Quijote')
        ->and($result['is_available'])->toBe('Disponible'); 

    // para no disponible
    $book->is_available = false;
    $resultNegative = $resource->toArray(new Request());
    expect($resultNegative['is_available'])->toBe('No Disponible');
});