<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    /**
     * Obtener configuraciones públicas (logo, nombre de la empresa).
     */
    public function publicSettings()
    {
        $keys = [
            'empresa_nombre',
            'empresa_logo',
            'empresa_correo',
            'empresa_telefono',
            'empresa_direccion',
            'empresa_moneda',
            'venta_serie',
            'color_primary',
            'color_secondary',
            'color_accent',
            'inventory_valuation_method'
        ];
        $settings = \Illuminate\Support\Facades\DB::table('settings')->whereIn('key', $keys)->select('key', 'value')->get();
        return response()->json([
            'success' => true,
            'message' => 'Configuraciones públicas obtenidas con éxito',
            'data' => $settings
        ], 200);
    }

    /**
     * Obtener todas las configuraciones.
     */
    public function index()
    {
        $settings = DB::table('settings')->get();
        return response()->json([
            'success' => true,
            'message' => 'Configuraciones obtenidas con éxito',
            'data' => $settings
        ], 200);
    }

    /**
     * Actualizar configuraciones en lote de forma segura con validaciones de formato.
     */
    public function updateBulk(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string|max:100',
            'settings.*.value' => 'nullable|string|max:1000',
        ]);

        $settings = $request->input('settings', []);

        // Validaciones específicas de formato por clave
        $rules = [];
        $dataToValidate = [];

        foreach ($settings as $setting) {
            $key = $setting['key'] ?? '';
            $val = $setting['value'] ?? null;
            $dataToValidate[$key] = $val;

            switch ($key) {
                case 'empresa_nombre':
                    $rules[$key] = 'required|string|min:3|max:100|regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s.,&\'()-]+$/';
                    break;
                case 'empresa_correo':
                    $rules[$key] = 'required|email:filter|max:150';
                    break;
                case 'empresa_telefono':
                    $rules[$key] = ['required', 'string', 'regex:/^[0-9]{4}-[0-9]{4}$/'];
                    break;
                case 'empresa_direccion':
                    $rules[$key] = 'required|string|min:5|max:250';
                    break;
                case 'empresa_moneda':
                    $rules[$key] = 'required|string|in:USD,MXN,EUR,COP,CLP,ARS';
                    break;
                case 'venta_serie':
                    $rules[$key] = ['required', 'string', 'regex:/^[A-Z0-9\-]{2,10}$/'];
                    break;
                case 'iva_percentage':
                    $rules[$key] = 'nullable|numeric|between:0,100';
                    break;
                case 'iva_enabled':
                    $rules[$key] = 'nullable|in:true,false,1,0';
                    break;
                case 'inventory_valuation_method':
                    $rules[$key] = 'required|in:promedio,fifo,lifo';
                    break;
                case 'color_primary':
                case 'color_secondary':
                case 'color_accent':
                    $rules[$key] = ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'];
                    break;
            }
        }

        $validator = \Illuminate\Support\Facades\Validator::make($dataToValidate, $rules, [
            'empresa_nombre.required' => 'El nombre de la empresa es obligatorio.',
            'empresa_nombre.min' => 'El nombre de la empresa debe tener al menos 3 caracteres.',
            'empresa_nombre.regex' => 'El nombre de la empresa contiene caracteres no válidos.',
            'empresa_correo.required' => 'El correo electrónico es obligatorio.',
            'empresa_correo.email' => 'El correo electrónico debe tener un formato válido (ej: contacto@empresa.com).',
            'empresa_telefono.required' => 'El teléfono de la empresa es obligatorio.',
            'empresa_telefono.regex' => 'El teléfono debe tener el formato 0000-0000.',
            'empresa_direccion.required' => 'La dirección es obligatoria.',
            'empresa_direccion.min' => 'La dirección debe tener al menos 5 caracteres.',
            'empresa_moneda.in' => 'La moneda seleccionada no es válida.',
            'venta_serie.required' => 'La serie de venta es obligatoria.',
            'venta_serie.regex' => 'La serie de venta debe estar en mayúsculas y contener entre 2 y 10 caracteres alfanuméricos (ej: F001).',
            'iva_percentage.between' => 'El porcentaje de IVA debe estar comprendido entre 0% y 100%.',
            'inventory_valuation_method.in' => 'El método de valuación de inventario no es válido.',
            'color_primary.regex' => 'El color primario debe tener un formato hexadecimal válido (ej: #1976D2).',
            'color_secondary.regex' => 'El color secundario debe tener un formato hexadecimal válido (ej: #26A69A).',
            'color_accent.regex' => 'El color terciario debe tener un formato hexadecimal válido (ej: #9C27B0).',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            foreach ($settings as $setting) {
                DB::table('settings')
                    ->updateOrInsert(
                        ['key' => $setting['key']],
                        ['value' => $setting['value']]
                    );
            }
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Configuraciones actualizadas con éxito',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar configuraciones: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Subir logo global de la empresa.
     */
    public function uploadGlobalLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048'
        ], [
            'logo.required' => 'Debe seleccionar un archivo de imagen.',
            'logo.image' => 'El archivo seleccionado debe ser una imagen válida.',
            'logo.mimes' => 'El formato de imagen debe ser jpeg, png, jpg, gif, svg o webp.',
            'logo.max' => 'El tamaño máximo de la imagen es de 2MB.'
        ]);

        if ($request->hasFile('logo')) {
            // Borrar logo anterior si existe
            $oldLogo = DB::table('settings')->where('key', 'empresa_logo')->value('value');
            if ($oldLogo) {
                if (Storage::disk('public')->exists($oldLogo)) {
                    Storage::disk('public')->delete($oldLogo);
                }
            }

            // Guardar logo nuevo
            $path = $request->file('logo')->store('general', 'public');
            DB::table('settings')->updateOrInsert(
                ['key' => 'empresa_logo'],
                ['value' => $path, 'type' => 'string', 'group' => 'general']
            );

            // Copiar al frontend para que esté por defecto en las compilaciones / exportaciones
            $frontendPath = base_path('../InventaryApp/src/assets/logo.png');
            if (file_exists(dirname($frontendPath))) {
                copy($request->file('logo')->getRealPath(), $frontendPath);
            }

            // Eliminar carpeta storage/app/private si fue creada por el sistema
            $privateDir = storage_path('app/private');
            if (\Illuminate\Support\Facades\File::isDirectory($privateDir)) {
                \Illuminate\Support\Facades\File::deleteDirectory($privateDir);
            }

            return response()->json([
                'success' => true,
                'message' => 'Logo de la empresa subido correctamente',
                'logo_path' => $path
            ], 200);
        }

        return response()->json(['success' => false, 'message' => 'No se recibió ninguna imagen'], 400);
    }
}
