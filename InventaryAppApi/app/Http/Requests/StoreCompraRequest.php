<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompraRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * La autorización granular se maneja ahora en la Policy del controlador.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'productos' => 'required|array|min:1',
            'productos.*.producto_id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.precio_unitario' => 'required|numeric|min:0',
            'proveedor_id' => 'required|exists:proveedors,id',
            'numero_factura' => 'required|string|max:50',
            'sucursal_id' => 'nullable|exists:sucursals,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'productos.required' => 'Debe seleccionar al menos un producto.',
            'productos.min' => 'Debe seleccionar al menos un producto.',
            'productos.*.producto_id.required' => 'El producto es requerido.',
            'productos.*.producto_id.exists' => 'El producto seleccionado no existe o no es válido.',
            'productos.*.cantidad.required' => 'La cantidad es requerida.',
            'productos.*.cantidad.min' => 'La cantidad debe ser al menos 1.',
            'productos.*.precio_unitario.required' => 'El precio unitario es requerido.',
            'productos.*.precio_unitario.min' => 'El precio unitario no puede ser menor a 0.',
            'proveedor_id.required' => 'Debe seleccionar un proveedor.',
            'proveedor_id.exists' => 'El proveedor seleccionado no existe.',
            'numero_factura.required' => 'El número de factura es obligatorio.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        if (!$this->has('sucursal_id') && $this->header('X-Sucursal-Id')) {
            $this->merge([
                'sucursal_id' => $this->header('X-Sucursal-Id'),
            ]);
        }
    }
}