<?php

namespace App\Http\Controllers;

use App\Models\SmtpAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SmtpAccountController extends Controller
{
    public function index()
    {
        $accounts = SmtpAccount::orderBy('name')->get();
        return view('admin.smtp-accounts.index', compact('accounts'));
    }

    public function create()
    {
        return view('admin.smtp-accounts.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['is_active'] = $request->boolean('is_active', true);

        SmtpAccount::create($data);

        return redirect()->route('admin.smtp-accounts.index')
            ->with('success', 'SMTP account added successfully.');
    }

    public function edit(SmtpAccount $smtp_account)
    {
        return view('admin.smtp-accounts.edit', ['account' => $smtp_account]);
    }

    public function update(Request $request, SmtpAccount $smtp_account)
    {
        $data = $this->validateData($request, $smtp_account);
        $data['is_active'] = $request->boolean('is_active', false);

        // Keep existing password if field left blank
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $smtp_account->update($data);

        return redirect()->route('admin.smtp-accounts.index')
            ->with('success', 'SMTP account updated.');
    }

    public function destroy(SmtpAccount $smtp_account)
    {
        $smtp_account->delete();
        return redirect()->route('admin.smtp-accounts.index')
            ->with('success', 'SMTP account deleted.');
    }

    public function toggle(SmtpAccount $smtp_account)
    {
        $smtp_account->update(['is_active' => !$smtp_account->is_active]);
        return back()->with('success', $smtp_account->is_active ? 'Account enabled.' : 'Account disabled.');
    }

    public function test(Request $request, SmtpAccount $smtp_account)
    {
        $data = $request->validate([
            'test_to' => 'required|email',
        ]);

        // Apply this account's config
        config([
            'mail.default'                 => 'smtp',
            'mail.mailers.smtp.host'       => $smtp_account->host,
            'mail.mailers.smtp.port'       => $smtp_account->port,
            'mail.mailers.smtp.username'   => $smtp_account->username,
            'mail.mailers.smtp.password'   => $smtp_account->password,
            'mail.mailers.smtp.encryption' => $smtp_account->encryption === 'none' ? null : $smtp_account->encryption,
            'mail.from.address'            => $smtp_account->from_address,
            'mail.from.name'               => $smtp_account->from_name,
        ]);
        Mail::purge('smtp');

        try {
            Mail::html(
                '<p>This is a test email from SMTP account <strong>' . e($smtp_account->name) . '</strong>.</p>',
                function ($msg) use ($data, $smtp_account) {
                    $msg->to($data['test_to'])
                        ->subject('SMTP Test — ' . $smtp_account->name)
                        ->from($smtp_account->from_address, $smtp_account->from_name);
                }
            );
            return back()->with('success', "Test email sent from {$smtp_account->name} to {$data['test_to']}.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed: ' . $e->getMessage());
        }
    }

    private function validateData(Request $request, ?SmtpAccount $account = null): array
    {
        $rules = [
            'name'         => 'required|string|max:100',
            'host'         => 'required|string|max:255',
            'port'         => 'required|integer|min:1|max:65535',
            'username'     => 'required|string|max:255',
            'password'     => $account ? 'nullable|string|max:255' : 'required|string|max:255',
            'encryption'   => 'required|in:tls,ssl,none',
            'from_address' => 'required|email',
            'from_name'    => 'required|string|max:100',
        ];

        return $request->validate($rules);
    }
}
