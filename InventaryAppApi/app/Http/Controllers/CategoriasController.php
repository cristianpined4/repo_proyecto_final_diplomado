<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Categoria;
use Illuminate\Support\Facades\DB;

class CategoriasController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->input('limit', 15);
        $search = $request->input('search', '');
        $sortBy = $request->input('sortBy', 'name');
        $descending = $request->input('descending', 'false') === 'true' ? 'desc' : 'asc';

        $allowedSorts = ['id', 'name', 'status', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'name';
        }

        $query = Categoria::query();

        if ($request->filled('status')) {
            $statusVal = ($request->input('status') === '1' || $request->input('status') === 'true' || $request->input('status') === 1) ? 1 : 0;
            $query->where('status', $statusVal);
        }

        if (!empty($search)) {
            $query->where('name', 'ILIKE', "%{$search}%");
        }

        $query->orderBy($sortBy, $descending);

        if ($limit > 0) {
            $categorias = $query->paginate($limit);
        } else {
            $categorias = $query->get();
        }

        return response()->json(['data' => $categorias, 'status' => true], 200);
    }

    public function store(Request $r)
    {

        $validated = $r->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|boolean',
        ]);

        try {
            DB::beginTransaction();
            $categoria = new Categoria();
            $categoria->name = $r->name;
            $categoria->status = $r->status;

            $categoria->save();

            DB::commit();
            return response()->json(['data' => $categoria, 'status' => true], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['message' => $th->getMessage(), 'status' => false], 500);
        }
    }

    public function update(Request $r, $id)
    {

        $validated = $r->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|boolean',
        ]);
        try {
            DB::beginTransaction();

            $categoria = Categoria::find($id);

            if (!$categoria) {
                return response()->json(['message' => 'Categoria no encontrada', 'status' => false], 404);
            }

            $categoria->name = $r->name;
            $categoria->status = $r->status;

            $categoria->save();

            DB::commit();

            return response()->json(['data' => $categoria, 'status' => true], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['message' => $th->getMessage(), 'status' => false], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $categoria = Categoria::with('productos')->find($id);
            if (!$categoria) {
                return response()->json(['message' => 'Categoria no encontrada', 'status' => false], 404);
            }
            /* evitar eliminar la categoria si tiene productos usados en las ventas o compras */
            if ($categoria->productos()->count() > 0) {
                return response()->json(['message' => 'No se puede eliminar la categoria porque esta siendo usada en un producto', 'status' => false], 400);
            }
            $categoria->delete();
            DB::commit();
            return response()->json(['message' => 'Categoria eliminada', 'status' => true], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['message' => $th->getMessage(), 'status' => false], 500);
        }
    }
}
