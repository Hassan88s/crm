<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

abstract class ApiController extends Controller
{
    protected function paginate(Builder $query, Request $request, int $default = 25): array
    {
        $per = min(200, max(1, (int) $request->query('per_page', $default)));
        $p   = $query->paginate($per)->withQueryString();
        return [
            'data' => $p->items(),
            'meta' => [
                'current_page' => $p->currentPage(),
                'last_page'    => $p->lastPage(),
                'per_page'     => $p->perPage(),
                'total'        => $p->total(),
            ],
        ];
    }

    protected function ok($data = null, int $status = 200)
    {
        return response()->json($data === null ? ['ok' => true] : $data, $status);
    }

    protected function notFound(string $what = 'Resource')
    {
        return response()->json(['error' => 'not_found', 'message' => "$what not found"], 404);
    }

    protected function badRequest(string $message)
    {
        return response()->json(['error' => 'bad_request', 'message' => $message], 400);
    }
}
