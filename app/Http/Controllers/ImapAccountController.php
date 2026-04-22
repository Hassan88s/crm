<?php

namespace App\Http\Controllers;

use App\Models\ImapAccount;
use Illuminate\Http\Request;

class ImapAccountController extends Controller
{
    public function index()
    {
        $accounts = ImapAccount::orderBy('name')->get();
        return view('admin.imap-accounts.index', compact('accounts'));
    }

    public function create()
    {
        return view('admin.imap-accounts.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['is_active'] = $request->boolean('is_active', true);

        ImapAccount::create($data);

        return redirect()->route('admin.imap-accounts.index')
            ->with('success', 'IMAP account added successfully.');
    }

    public function edit(ImapAccount $imap_account)
    {
        return view('admin.imap-accounts.edit', ['account' => $imap_account]);
    }

    public function update(Request $request, ImapAccount $imap_account)
    {
        $data = $this->validateData($request, $imap_account);
        $data['is_active'] = $request->boolean('is_active', false);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $imap_account->update($data);

        return redirect()->route('admin.imap-accounts.index')
            ->with('success', 'IMAP account updated.');
    }

    public function destroy(ImapAccount $imap_account)
    {
        $imap_account->delete();
        return redirect()->route('admin.imap-accounts.index')
            ->with('success', 'IMAP account deleted.');
    }

    public function toggle(ImapAccount $imap_account)
    {
        $imap_account->update(['is_active' => !$imap_account->is_active]);
        return back()->with('success', $imap_account->is_active ? 'Account enabled.' : 'Account disabled.');
    }

    public function test(ImapAccount $imap_account)
    {
        $imap = $imap_account->openConnection('INBOX');
        if (!$imap) {
            return back()->with('error', 'IMAP connect failed: ' . \imap_last_error());
        }
        $total = @\imap_num_msg($imap);
        @\imap_close($imap);

        $imap_account->update(['last_fetched_at' => now()]);

        return back()->with('success', "Connected to {$imap_account->name}. Found {$total} message(s) in INBOX.");
    }

    private function validateData(Request $request, ?ImapAccount $account = null): array
    {
        return $request->validate([
            'name'       => 'required|string|max:100',
            'host'       => 'required|string|max:255',
            'port'       => 'required|integer|min:1|max:65535',
            'username'   => 'required|string|max:255',
            'password'   => $account ? 'nullable|string|max:255' : 'required|string|max:255',
            'encryption' => 'required|in:ssl,tls,starttls,none',
            'color'      => 'nullable|string|max:20',
        ]);
    }
}
