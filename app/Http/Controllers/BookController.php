<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function __construct() {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Book::class);
        $books = Book::when($request->has('title'), function ($query) use ($request) {
            $query->where('title', 'like', '%'.$request->input('title').'%');
        })->when($request->has('isbn'), function ($query) use ($request) {
            $query->where('ISBN', 'like', '%'.$request->input('isbn').'%');
        })->when($request->has('is_available'), function ($query) use ($request) {
            $query->where('is_available', $request->boolean('is_available'));
        })
            ->paginate();

        return response()->json(BookResource::collection($books));
    }

    public function show(Book $book)
    {
        $this->authorize('view', $book);
        return response()->json(new BookResource($book));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Book::class);
        
        $validated = $request->validate([
            'title' => 'required|string',
            'ISBN' => 'required|string',
            'is_available' => 'boolean',
            'description'      => 'required|string',     
            'total_copies'     => 'required|integer', 
            'available_copies' => 'integer'
        ]);

        $book = Book::create($validated);

        return response()->json(new BookResource($book), 201);
    }

    public function update(Request $request, Book $book)
    {
        $this->authorize('update', $book);
        
        $validated = $request->validate([
            'title' => 'sometimes|string',
            'ISBN' => 'sometimes|string',
            'is_available' => 'boolean',
            'available_copies' => 'integer'
        ]);

        $book->update($validated);

        return response()->json(new BookResource($book));
    }

    public function destroy(Book $book)
    {
        $this->authorize('delete', $book);
        
        $book->delete();

        return response()->json(null, 204);
    }
}