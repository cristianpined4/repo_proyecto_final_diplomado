<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('usuario'); // Route parameter is 'usuario' according to apiResource('/usuarios')
        return [
            'name' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $id,
            'password' => 'nullable|string|min:6',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio',
            'string' => 'El campo :attribute debe ser una cadena de texto',
            'max' => 'El campo :attribute no puede ser mayor a :max caracteres',
            'unique' => 'El campo :attribute ya existe',
            'min' => 'El campo :attribute debe ser mayor o igual a :min',
        ];
    }
}
