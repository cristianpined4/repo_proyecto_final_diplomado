<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id') ?? $this->route('cliente') ?? $this->id;

        return [
            'nombre' => 'required|string',
            'correo' => ['nullable', 'email', 'max:255', Rule::unique('clientes', 'correo')->ignore($id)],
            'dui' => ['nullable', 'string', Rule::unique('clientes', 'dui')->ignore($id)],
            'nit' => ['nullable', 'string', Rule::unique('clientes', 'nit')->ignore($id)],
            'nrc' => ['nullable', 'string', Rule::unique('clientes', 'nrc')->ignore($id)],
            'telefono' => 'nullable|string',
            'direccion' => 'nullable|string',
            'status' => 'nullable|in:0,1,true,false',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio',
            'string' => 'El campo :attribute debe ser una cadena de texto',
            'unique' => 'El campo :attribute ya está registrado por otro cliente',
            'in' => 'El campo :attribute debe ser uno de los valores permitidos',
        ];
    }
}
