<?php

namespace App\Http\Controllers;

use App\Models\CombineOperation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CombineOperationController extends Controller
{
    //
    // Web Routes
    //

    public function indexPage()
    {
        return view('admin.combine-operations');
    }

    public function detailPage(int $id)
    {
        return view('admin.combine-operation-detail', ['id' => $id]);
    }

    //
    // API Routes
    //

    public function list(Request $request): JsonResponse
    {
        $search = $request->input('search');
        $perPage = 25;

        $query = CombineOperation::query()
            ->with(['shop:id,name', 'user:id,email'])
            ->orderBy('id', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                  ->orWhere('order_id_numeric', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%")
                  ->orWhere('original_shipping_method', 'like', "%{$search}%");
            });
        }

        $operations = $query->paginate($perPage);

        return response()->json($operations);
    }

    public function get(int $id): JsonResponse
    {
        $operation = CombineOperation::with(['shop:id,name,shop_domain', 'user:id,email', 'logs', 'auditLog'])
            ->findOrFail($id);

        return response()->json($operation);
    }
}
