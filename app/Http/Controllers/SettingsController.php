<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class SettingsController extends Controller
{
    public function index()
    {
        $envValues = $this->readEnvValues([
            'APP_NAME', 'APP_TIMEZONE',
            'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME',
            'MAIL_PASSWORD', 'MAIL_ENCRYPTION',
            'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME',
            'IMAP_HOST', 'IMAP_PORT', 'IMAP_USERNAME',
            'IMAP_PASSWORD', 'IMAP_ENCRYPTION',
            'OPENAI_API_KEY',
            'APOLLO_API_KEY',
            'HERMES_API_URL',
            'HERMES_API_KEY',
        ]);

        return view('admin.settings', [
            'envName'          => $envValues['APP_NAME']          ?? config('app.name'),
            'envTimezone'      => $envValues['APP_TIMEZONE']      ?? config('app.timezone', 'UTC'),
            'mailHost'         => $envValues['MAIL_HOST']         ?? '',
            'mailPort'         => $envValues['MAIL_PORT']         ?? '587',
            'mailUsername'     => $envValues['MAIL_USERNAME']     ?? '',
            'mailPassword'     => $envValues['MAIL_PASSWORD']     ?? '',
            'mailEncryption'   => $envValues['MAIL_ENCRYPTION']   ?? 'tls',
            'mailFromAddress'  => $envValues['MAIL_FROM_ADDRESS'] ?? '',
            'mailFromName'     => $envValues['MAIL_FROM_NAME']    ?? '',
            'imapHost'         => $envValues['IMAP_HOST']         ?? '',
            'imapPort'         => $envValues['IMAP_PORT']         ?? '993',
            'imapUsername'     => $envValues['IMAP_USERNAME']     ?? '',
            'imapPassword'     => $envValues['IMAP_PASSWORD']     ?? '',
            'imapEncryption'   => $envValues['IMAP_ENCRYPTION']   ?? 'ssl',
            'openaiApiKey'     => $envValues['OPENAI_API_KEY']    ?? '',
            'apolloApiKey'     => $envValues['APOLLO_API_KEY']    ?? '',
            'hermesApiUrl'     => $envValues['HERMES_API_URL']    ?? '',
            'hermesApiKey'     => $envValues['HERMES_API_KEY']    ?? '',
            'apiKeys'          => ApiKey::orderByDesc('id')->get(),
            'newApiKey'        => session('new_api_key'),
        ]);
    }

    public function updateHermes(Request $request)
    {
        $data = $request->validate([
            'hermes_api_url' => 'nullable|url|max:255',
            'hermes_api_key' => 'nullable|string|max:255',
        ]);
        $this->setEnv('HERMES_API_URL', $data['hermes_api_url'] ?? '');
        $this->setEnv('HERMES_API_KEY', $data['hermes_api_key'] ?? '');
        \Artisan::call('config:clear');
        return back()->with('success_hermes', 'Hermes settings saved.');
    }

    public function testHermes(Request $request)
    {
        $url = rtrim($request->input('hermes_api_url') ?: env('HERMES_API_URL', ''), '/');
        $key = $request->input('hermes_api_key') ?: env('HERMES_API_KEY', '');
        if (!$url || !$key) {
            return response()->json(['ok' => false, 'error' => 'URL and key required.'], 422);
        }
        try {
            $resp = \Illuminate\Support\Facades\Http::timeout(15)->get($url . '/health');
            if (!$resp->ok()) {
                return response()->json(['ok' => false, 'error' => 'HTTP ' . $resp->status()], 502);
            }
            return response()->json(['ok' => true, 'details' => $resp->json()]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 502);
        }
    }

    public function storeApiKey(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
        ]);
        [, $plain] = ApiKey::generate($data['name']);
        return back()
            ->with('success_apikeys', 'API key created. Copy it now — you will not see it again.')
            ->with('new_api_key', $plain);
    }

    public function revokeApiKey(ApiKey $apiKey)
    {
        $apiKey->update(['revoked_at' => now()]);
        return back()->with('success_apikeys', 'API key revoked.');
    }

    public function destroyApiKey(ApiKey $apiKey)
    {
        $apiKey->delete();
        return back()->with('success_apikeys', 'API key deleted.');
    }

    public function updateGeneral(Request $request)
    {
        $data = $request->validate([
            'app_name' => 'required|string|max:100',
            'timezone' => 'required|string',
        ]);

        $this->setEnv('APP_NAME', '"' . $data['app_name'] . '"');
        $this->setEnv('APP_TIMEZONE', $data['timezone']);

        \Artisan::call('config:clear');
        \Artisan::call('view:clear');

        return back()->with('success_general', 'General settings saved.');
    }

    public function updateSmtp(Request $request)
    {
        $data = $request->validate([
            'mail_host'         => 'required|string',
            'mail_port'         => 'required|integer',
            'mail_username'     => 'required|string',
            'mail_password'     => 'required|string',
            'mail_encryption'   => 'required|in:tls,ssl,none',
            'mail_from_address' => 'required|email',
            'mail_from_name'    => 'required|string|max:100',
        ]);

        $this->setEnv('MAIL_MAILER',       'smtp');
        $this->setEnv('MAIL_HOST',         $data['mail_host']);
        $this->setEnv('MAIL_PORT',         $data['mail_port']);
        $this->setEnv('MAIL_USERNAME',     $data['mail_username']);
        $this->setEnv('MAIL_PASSWORD',     '"' . $data['mail_password'] . '"');
        $this->setEnv('MAIL_ENCRYPTION',   $data['mail_encryption'] === 'none' ? '' : $data['mail_encryption']);
        $this->setEnv('MAIL_FROM_ADDRESS', $data['mail_from_address']);
        $this->setEnv('MAIL_FROM_NAME',    '"' . $data['mail_from_name'] . '"');

        \Artisan::call('config:clear');

        return back()->with('success_smtp', 'SMTP settings saved successfully.');
    }

    public function testSmtp(Request $request)
    {
        $request->validate(['test_email' => 'required|email']);

        // Re-configure mailer on the fly with current .env values
        $envValues = $this->readEnvValues([
            'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME',
            'MAIL_PASSWORD', 'MAIL_ENCRYPTION',
            'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME',
        ]);

        config([
            'mail.default'                 => 'smtp',
            'mail.mailers.smtp.host'       => $envValues['MAIL_HOST']         ?? '',
            'mail.mailers.smtp.port'       => (int) ($envValues['MAIL_PORT']  ?? 587),
            'mail.mailers.smtp.username'   => $envValues['MAIL_USERNAME']     ?? '',
            'mail.mailers.smtp.password'   => $envValues['MAIL_PASSWORD']     ?? '',
            'mail.mailers.smtp.encryption' => $envValues['MAIL_ENCRYPTION']   ?? 'tls',
            'mail.from.address'            => $envValues['MAIL_FROM_ADDRESS'] ?? '',
            'mail.from.name'               => $envValues['MAIL_FROM_NAME']    ?? '',
        ]);

        try {
            Mail::raw('✅ SMTP test from PulseCore — your email settings are working correctly!', function ($msg) use ($request, $envValues) {
                $msg->to($request->test_email)
                    ->subject('PulseCore SMTP Test')
                    ->from($envValues['MAIL_FROM_ADDRESS'] ?? 'noreply@pulsecore.app', $envValues['MAIL_FROM_NAME'] ?? 'PulseCore');
            });

            return back()->with('success_smtp', '✅ Test email sent to ' . $request->test_email);
        } catch (\Exception $e) {
            return back()->with('error_smtp', '❌ SMTP Error: ' . $e->getMessage());
        }
    }

    public function updateEmail(Request $request)
    {
        $data = $request->validate([
            'admin_email'      => 'required|email',
            'current_password' => 'required|string',
        ]);

        if (!Hash::check($data['current_password'], Auth::user()->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        User::where('id', Auth::id())->update(['email' => $data['admin_email']]);
        $this->setEnv('ADMIN_EMAIL', $data['admin_email']);
        \Artisan::call('config:clear');

        return back()->with('success_email', 'Admin email updated successfully.');
    }

    public function updateImap(Request $request)
    {
        $data = $request->validate([
            'imap_host'       => 'required|string',
            'imap_port'       => 'required|integer',
            'imap_username'   => 'required|string',
            'imap_password'   => 'required|string',
            'imap_encryption' => 'required|in:ssl,tls,starttls,none',
        ]);

        $this->setEnv('IMAP_HOST',       $data['imap_host']);
        $this->setEnv('IMAP_PORT',       $data['imap_port']);
        $this->setEnv('IMAP_USERNAME',   $data['imap_username']);
        $this->setEnv('IMAP_PASSWORD',   '"' . $data['imap_password'] . '"');
        $this->setEnv('IMAP_ENCRYPTION', $data['imap_encryption']);

        \Artisan::call('config:clear');

        return back()->with('success_imap', 'IMAP settings saved successfully.');
    }

    public function testImap(Request $request)
    {
        $request->validate(['test_imap' => 'nullable']);

        $env = $this->readEnvValues([
            'IMAP_HOST', 'IMAP_PORT', 'IMAP_USERNAME', 'IMAP_PASSWORD', 'IMAP_ENCRYPTION',
        ]);

        $host       = $env['IMAP_HOST']       ?? '';
        $port       = $env['IMAP_PORT']       ?? '993';
        $username   = $env['IMAP_USERNAME']   ?? '';
        $password   = $env['IMAP_PASSWORD']   ?? '';
        $encryption = $env['IMAP_ENCRYPTION'] ?? 'ssl';

        if (!$host || !$username) {
            return back()->with('error_imap', '❌ Please fill in IMAP host and username first.');
        }

        $flags = match ($encryption) {
            'ssl'      => '/imap/ssl/novalidate-cert',
            'tls'      => '/imap/tls/novalidate-cert',
            'starttls' => '/imap/starttls/novalidate-cert',
            default    => '/imap/notls',
        };

        $mailbox = '{' . $host . ':' . $port . $flags . '}INBOX';

        $conn = @\imap_open($mailbox, $username, $password, 0, 1);

        if ($conn) {
            $count = \imap_num_msg($conn);
            \imap_close($conn);
            return back()->with('success_imap', "✅ Connected! {$count} message(s) in inbox.");
        }

        return back()->with('error_imap', '❌ IMAP Error: ' . \imap_last_error());
    }

    public function updateOpenAI(Request $request)
    {
        $data = $request->validate([
            'openai_api_key' => 'required|string|min:10',
            'apollo_api_key' => 'nullable|string|min:5',
        ]);

        $this->setEnv('OPENAI_API_KEY', $data['openai_api_key']);

        if (!empty($data['apollo_api_key'])) {
            $this->setEnv('APOLLO_API_KEY', $data['apollo_api_key']);
        }

        \Artisan::call('config:clear');

        return back()->with('success_openai', 'API keys saved.');
    }

    public function checkOpenAIUsage()
    {
        $env    = $this->readEnvValues(['OPENAI_API_KEY']);
        $apiKey = $env['OPENAI_API_KEY'] ?? '';

        if (!$apiKey) {
            return response()->json(['error' => 'No API key configured.'], 422);
        }

        // ── 1. Make a tiny API call to capture rate-limit headers ────────
        $payload = json_encode([
            'model'      => 'gpt-4o-mini',
            'messages'   => [['role' => 'user', 'content' => 'Hi']],
            'max_tokens' => 1,
        ]);

        $headers = [];
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_HEADERFUNCTION => function ($ch, $line) use (&$headers) {
                $parts = explode(':', $line, 2);
                if (count($parts) === 2) {
                    $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
                return strlen($line);
            },
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if (!$response) {
            return response()->json(['error' => 'Connection failed: ' . $curlErr], 500);
        }

        $json = json_decode($response, true);

        // Key invalid?
        if ($httpCode === 401) {
            return response()->json(['error' => $json['error']['message'] ?? 'Invalid API key.'], 422);
        }
        if ($httpCode === 429) {
            // Rate limited — still has useful headers
        }

        // ── 2. Extract rate limit headers ────────────────────────────────
        $rateLimits = [
            'requests_limit'     => (int) ($headers['x-ratelimit-limit-requests'] ?? 0),
            'requests_remaining' => (int) ($headers['x-ratelimit-remaining-requests'] ?? 0),
            'requests_reset'     => $headers['x-ratelimit-reset-requests'] ?? '',
            'tokens_limit'       => (int) ($headers['x-ratelimit-limit-tokens'] ?? 0),
            'tokens_remaining'   => (int) ($headers['x-ratelimit-remaining-tokens'] ?? 0),
            'tokens_reset'       => $headers['x-ratelimit-reset-tokens'] ?? '',
        ];

        // ── 3. Extract model/usage from the tiny call response ───────────
        $model       = $json['model'] ?? 'gpt-4o-mini';
        $promptTok   = $json['usage']['prompt_tokens'] ?? 0;
        $completTok  = $json['usage']['completion_tokens'] ?? 0;
        $totalTok    = $json['usage']['total_tokens'] ?? 0;

        // ── 4. Local CRM usage stats ─────────────────────────────────────
        $now = now();
        $monthStart = $now->copy()->startOfMonth();

        // Count AI operations from our CRM tables
        $classifiedThisMonth = \App\Models\EmailReply::where('classified_at', '>=', $monthStart)->count();
        $classifiedTotal     = \App\Models\EmailReply::whereNotNull('classified_at')->count();

        // Estimate tokens: ~300 tokens per classification, ~800 per AI draft, ~400 per verify
        $estTokensClassify = $classifiedThisMonth * 300;

        // Rough cost estimate (gpt-4o-mini: $0.15/1M input, $0.60/1M output)
        // Average ~250 input + 30 output per call
        $estCostClassify = ($classifiedThisMonth * 250 * 0.15 / 1000000) + ($classifiedThisMonth * 30 * 0.60 / 1000000);

        $data = [
            'key_valid'    => true,
            'key_preview'  => substr($apiKey, 0, 7) . '...' . substr($apiKey, -4),
            'model'        => $model,
            'rate_limits'  => $rateLimits,
            'test_call'    => [
                'prompt_tokens'     => $promptTok,
                'completion_tokens' => $completTok,
                'total_tokens'      => $totalTok,
            ],
            'crm_usage' => [
                'classifications_this_month' => $classifiedThisMonth,
                'classifications_total'      => $classifiedTotal,
                'est_tokens_this_month'      => $estTokensClassify,
                'est_cost_this_month'        => round($estCostClassify, 6),
            ],
        ];

        return response()->json($data);
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($data['current_password'], Auth::user()->password)) {
            return back()->withErrors(['current_password_pwd' => 'Current password is incorrect.']);
        }

        User::where('id', Auth::id())->update(['password' => Hash::make($data['new_password'])]);

        return back()->with('success_password', 'Password updated successfully.');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function readEnvValues(array $keys): array
    {
        $result = [];
        $lines  = file(base_path('.env'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) continue;
            [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
            if (in_array(trim($k), $keys)) {
                $result[trim($k)] = trim(trim($v), '"');
            }
        }
        return $result;
    }

    private function setEnv(string $key, string $value): void
    {
        $path    = base_path('.env');
        $current = file_get_contents($path);

        if (preg_match("/^{$key}=/m", $current)) {
            $current = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $current);
        } else {
            $current .= "\n{$key}={$value}";
        }

        file_put_contents($path, $current);
    }
}
