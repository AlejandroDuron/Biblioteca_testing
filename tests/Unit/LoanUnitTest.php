<?php

use App\Http\Requests\StoreLoanRequest;

it('a loan cannot be made without the requester’s name', function () {
    $request = new StoreLoanRequest();
    $rules = $request->rules();

    // Verificar
    expect($rules['requester_name'])->toEqual(['string', 'required', 'max:255']);
});