<?php

namespace App\Http\Controllers\Api;

use App\Models\SmtpAccount;
use Illuminate\Http\Request;

class SmtpAccountController extends ApiController
{
    public function index(Request $request)
    {
        $q = SmtpAccount::query();
        if ($request->has('active')) $q->where('is_active', (bool) $request->query('active'));
        return $this->paginate($q->orderBy('name'), $request);
    }

    public function show(SmtpAccount $smtpAccount)
    {
        return $this->ok($smtpAccount->makeHidden(['password']));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, true);
        return $this->ok(SmtpAccount::create($data)->makeHidden(['password']), 201);
    }

    public function update(Request $request, SmtpAccount $smtpAccount)
    {
        $data = $this->validated($request, false);
        $smtpAccount->update($data);
        return $this->ok($smtpAccount->makeHidden(['password']));
    }

    public function destroy(SmtpAccount $smtpAccount)
    {
        $smtpAccount->delete();
        return $this->ok(['deleted' => true]);
    }

    public function toggle(SmtpAccount $smtpAccount)
    {
        $smtpAccount->update(['is_active' => !$smtpAccount->is_active]);
        return $this->ok($smtpAccount->makeHidden(['password']));
    }

    private function validated(Request $r, bool $creating): array
    {
        $rules = [
            'name'         => ($creating ? 'required' : 'sometimes') . '|string|max:100',
            'host'         => ($creating ? 'required' : 'sometimes') . '|string|max:255',
            'port'         => ($creating ? 'required' : 'sometimes') . '|integer',
            'username'     => ($creating ? 'required' : 'sometimes') . '|string|max:255',
            'password'     => ($creating ? 'required' : 'sometimes') . '|string',
            'encryption'   => 'nullable|in:tls,ssl,none',
            'from_address' => 'nullable|email',
            'from_name'    => 'nullable|string|max:255',
            'is_active'    => 'boolean',
        ];
        return $r->validate($rules);
    }
}
