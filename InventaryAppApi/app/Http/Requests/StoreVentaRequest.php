<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVentaRequest extends FormRequest
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
            'productos.*.precio_venta' => 'required|numeric|min:0',
            'cliente_id' => 'required|exists:clientes,id',
            'sucursal_id' => 'nullable|exists:sucursals,id',
            'metodo_pago' => 'nullable|string|in:efectivo,tarjeta_debito,tarjeta_credito',
            'monto_recibido' => 'nullable|numeric|min:0',
            'vuelto' => 'nullable|numeric|min:0',
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
            'productos.*.precio_venta.required' => 'El precio de venta es requerido.',
            'productos.*.precio_venta.min' => 'El precio de venta no puede ser menor a 0.',
            'cliente_id.required' => 'Debe seleccionar un cliente.',
            'cliente_id.exists' => 'El cliente seleccionado no existe.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Asegurar que sucursal_id venga del header si no se envía
        if (!$this->has('sucursal_id') && $this->header('X-Sucursal-Id')) {
            $this->merge([
                'sucursal_id' => $this->header('X-Sucursal-Id'),
            ]);
        }
    }
}