<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuditLogController extends Controller
{
    //
    // Web Routes
    //

    public function indexPage()
    {
        return view('admin.audit-logs');
    }

    //
    // API Routes
    //

    public function list(Request $request): JsonResponse
    {
        $search = $request->input('search');
        $perPage = 25;

        $query = AuditLog::query()
            ->where('event_ts', '>=', now()->subDays(7));

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('event_name', 'like', "%{$search}%")
                  ->orWhere('event_ext', 'like', "%{$search}%")
                  ->orWhere('source', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%")
                  ->orWhere('order_id', 'like', "%{$search}%")
                  ->orWhere('offer_id', 'like', "%{$search}%");
            });
        }

        $logs = $query->orderBy('id', 'desc')
            ->paginate($perPage);

        return response()->json($logs);
    }
}
