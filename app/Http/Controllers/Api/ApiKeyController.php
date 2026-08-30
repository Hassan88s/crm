<?php

namespace App\Http\Controllers\Api;

use App\Models\ApiKey;
use Illuminate\Http\Request;

class ApiKeyController extends ApiController
{
    public function me(Request $request)
    {
        return $this->ok($request->attributes->get('api_key'));
    }
}
