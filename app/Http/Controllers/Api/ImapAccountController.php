<?php

namespace App\Http\Controllers\Api;

use App\Models\ImapAccount;
use Illuminate\Http\Request;

class ImapAccountController extends ApiController
{
    public function index(Request $request)
    {
        $q = ImapAccount::query();
        if ($request->has('active')) $q->where('is_active', (bool) $request->query('active'));
        return $this->paginate($q->orderBy('name'), $request);
    }

    public function show(ImapAccount $imapAccount)
    {
        return $this->ok($imapAccount->makeHidden(['password']));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, true);
        return $this->ok(ImapAccount::create($data)->makeHidden(['password']), 201);
    }

    public function update(Request $request, ImapAccount $imapAccount)
    {
        $data = $this->validated($request, false);
        $imapAccount->update($data);
        return $this->ok($imapAccount->makeHidden(['password']));
    }

    public function destroy(ImapAccount $imapAccount)
    {
        $imapAccount->delete();
        return $this->ok(['deleted' => true]);
    }

    public function toggle(ImapAccount $imapAccount)
    {
        $imapAccount->update(['is_active' => !$imapAccount->is_active]);
        return $this->ok($imapAccount->makeHidden(['password']));
    }

    private function validated(Request $r, bool $creating): array
    {
        return $r->validate([
            'name'       => ($creating ? 'required' : 'sometimes') . '|string|max:100',
            'host'       => ($creating ? 'required' : 'sometimes') . '|string|max:255',
            'port'       => ($creating ? 'required' : 'sometimes') . '|integer',
            'username'   => ($creating ? 'required' : 'sometimes') . '|string|max:255',
            'password'   => ($creating ? 'required' : 'sometimes') . '|string',
            'encryption' => 'nullable|in:ssl,tls,starttls,none',
            'color'      => 'nullable|string|max:32',
            'is_active'  => 'boolean',
        ]);
    }
}
