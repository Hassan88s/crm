<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class RequirementsController extends Controller
{
    public function index()
    {
        $checks = [];

        // ── PHP Version ──────────────────────────────────────────────────────
        $phpVersion  = PHP_VERSION;
        $phpRequired = '8.2.0';
        $checks[]    = [
            'group'   => 'PHP',
            'label'   => 'PHP Version',
            'value'   => $phpVersion,
            'pass'    => version_compare($phpVersion, $phpRequired, '>='),
            'note'    => ">= {$phpRequired} required",
        ];

        // ── PHP Extensions ───────────────────────────────────────────────────
        $extensions = [
            'curl'      => ['label' => 'cURL',          'note' => 'Required for HTTP scraping and OpenAI API calls'],
            'imap'      => ['label' => 'IMAP',           'note' => 'Required for inbox email reading'],
            'mbstring'  => ['label' => 'Multibyte String','note' => 'Required for text processing'],
            'openssl'   => ['label' => 'OpenSSL',        'note' => 'Required for HTTPS connections'],
            'pdo'       => ['label' => 'PDO',            'note' => 'Required for database access'],
            'pdo_mysql' => ['label' => 'PDO MySQL',      'note' => 'Required for MySQL/MariaDB database'],
            'xml'       => ['label' => 'XML',            'note' => 'Required by Laravel and DomCrawler'],
            'dom'       => ['label' => 'DOM',            'note' => 'Required for HTML parsing'],
            'fileinfo'  => ['label' => 'FileInfo',       'note' => 'Required for file type detection'],
            'json'      => ['label' => 'JSON',           'note' => 'Required everywhere'],
            'tokenizer' => ['label' => 'Tokenizer',      'note' => 'Required by Laravel'],
            'ctype'     => ['label' => 'CType',          'note' => 'Required by Laravel'],
            'bcmath'    => ['label' => 'BCMath',         'note' => 'Required for precise calculations'],
            'zip'       => ['label' => 'Zip',            'note' => 'Required for file extraction (optional)'],
            'gd'        => ['label' => 'GD / Imagick',   'note' => 'Required for image processing (optional)'],
        ];

        foreach ($extensions as $ext => $meta) {
            $loaded   = extension_loaded($ext);
            $checks[] = [
                'group' => 'PHP Extensions',
                'label' => $meta['label'],
                'value' => $loaded ? 'Enabled' : 'Not installed',
                'pass'  => $loaded,
                'note'  => $meta['note'],
                'warn'  => in_array($ext, ['zip', 'gd']) && !$loaded, // optional = warn not fail
            ];
        }

        // ── PHP INI Settings ─────────────────────────────────────────────────
        $iniChecks = [
            ['key' => 'allow_url_fopen',    'label' => 'allow_url_fopen',    'expected' => '1',    'note' => 'Required for stream-based HTTP requests'],
            ['key' => 'file_uploads',       'label' => 'file_uploads',       'expected' => '1',    'note' => 'Required for CSV import and photo uploads'],
            ['key' => 'display_errors',     'label' => 'display_errors (off)','expected' => '0',   'note' => 'Should be off in production'],
        ];

        foreach ($iniChecks as $ini) {
            $val      = ini_get($ini['key']);
            $expected = $ini['expected'];
            $pass     = ($val == $expected);
            $checks[] = [
                'group' => 'PHP INI',
                'label' => $ini['label'],
                'value' => $val ?: '(not set)',
                'pass'  => $pass,
                'note'  => $ini['note'],
                'warn'  => $ini['key'] === 'display_errors', // warn not fail
            ];
        }

        // upload_max_filesize
        $uploadMax = ini_get('upload_max_filesize');
        $checks[] = [
            'group' => 'PHP INI',
            'label' => 'upload_max_filesize',
            'value' => $uploadMax,
            'pass'  => $this->parseBytes($uploadMax) >= $this->parseBytes('2M'),
            'note'  => '>= 2M recommended for CSV imports',
        ];

        // post_max_size
        $postMax = ini_get('post_max_size');
        $checks[] = [
            'group' => 'PHP INI',
            'label' => 'post_max_size',
            'value' => $postMax,
            'pass'  => $this->parseBytes($postMax) >= $this->parseBytes('8M'),
            'note'  => '>= 8M recommended',
        ];

        // max_execution_time
        $maxExec = (int) ini_get('max_execution_time');
        $checks[] = [
            'group' => 'PHP INI',
            'label' => 'max_execution_time',
            'value' => $maxExec === 0 ? 'unlimited' : "{$maxExec}s",
            'pass'  => $maxExec === 0 || $maxExec >= 60,
            'note'  => '>= 60 seconds recommended for scraping',
        ];

        // memory_limit
        $memLimit = ini_get('memory_limit');
        $checks[] = [
            'group' => 'PHP INI',
            'label' => 'memory_limit',
            'value' => $memLimit,
            'pass'  => $this->parseBytes($memLimit) >= $this->parseBytes('128M') || $this->parseBytes($memLimit) === -1,
            'note'  => '>= 128M recommended',
        ];

        // ── Composer Packages ────────────────────────────────────────────────
        $packages = [
            ['class' => \GuzzleHttp\Client::class,                     'label' => 'guzzlehttp/guzzle',      'note' => 'HTTP client for scraping'],
            ['class' => \Symfony\Component\DomCrawler\Crawler::class,  'label' => 'symfony/dom-crawler',    'note' => 'HTML parser for speaker extraction'],
            ['class' => \Symfony\Component\CssSelector\CssSelectorConverter::class, 'label' => 'symfony/css-selector', 'note' => 'CSS selector support for DomCrawler'],
        ];

        foreach ($packages as $pkg) {
            $exists   = class_exists($pkg['class']);
            $checks[] = [
                'group' => 'Composer Packages',
                'label' => $pkg['label'],
                'value' => $exists ? 'Installed' : 'Missing',
                'pass'  => $exists,
                'note'  => $pkg['note'],
            ];
        }

        // ── Database ─────────────────────────────────────────────────────────
        try {
            DB::connection()->getPdo();
            $driver  = DB::connection()->getDriverName();
            $dbName  = DB::connection()->getDatabaseName();
            $checks[] = [
                'group' => 'Database',
                'label' => 'Database Connection',
                'value' => strtoupper($driver) . ' — ' . $dbName,
                'pass'  => true,
                'note'  => 'MySQL 5.7+ or MariaDB 10.3+ required',
            ];
        } catch (\Exception $e) {
            $checks[] = [
                'group' => 'Database',
                'label' => 'Database Connection',
                'value' => 'Failed',
                'pass'  => false,
                'note'  => $e->getMessage(),
            ];
        }

        // ── File Permissions ─────────────────────────────────────────────────
        $paths = [
            ['path' => storage_path(),              'label' => 'storage/'],
            ['path' => storage_path('logs'),        'label' => 'storage/logs/'],
            ['path' => storage_path('app/public'),  'label' => 'storage/app/public/'],
            ['path' => base_path('bootstrap/cache'),'label' => 'bootstrap/cache/'],
        ];

        foreach ($paths as $p) {
            $writable = is_writable($p['path']);
            $checks[] = [
                'group' => 'File Permissions',
                'label' => $p['label'],
                'value' => $writable ? 'Writable' : 'Not writable',
                'pass'  => $writable,
                'note'  => 'Must be writable by the web server',
            ];
        }

        // .env file
        $envExists = file_exists(base_path('.env'));
        $checks[]  = [
            'group' => 'File Permissions',
            'label' => '.env file',
            'value' => $envExists ? 'Exists' : 'Missing',
            'pass'  => $envExists,
            'note'  => 'Application configuration file',
        ];

        // ── Environment Config ───────────────────────────────────────────────
        $appKey = config('app.key');
        $checks[] = [
            'group' => 'Configuration',
            'label' => 'APP_KEY',
            'value' => $appKey ? 'Set (' . substr($appKey, 0, 12) . '…)' : 'Not set',
            'pass'  => !empty($appKey),
            'note'  => 'Required for encryption — run: php artisan key:generate',
        ];

        $appEnv = config('app.env', 'unknown');
        $checks[] = [
            'group' => 'Configuration',
            'label' => 'APP_ENV',
            'value' => $appEnv,
            'pass'  => true,
            'note'  => 'production recommended for live server',
            'warn'  => $appEnv !== 'production',
        ];

        $appDebug = config('app.debug');
        $checks[] = [
            'group' => 'Configuration',
            'label' => 'APP_DEBUG',
            'value' => $appDebug ? 'true' : 'false',
            'pass'  => !$appDebug,
            'note'  => 'Must be false in production',
            'warn'  => (bool) $appDebug,
        ];

        // ── Group results ────────────────────────────────────────────────────
        $grouped = [];
        foreach ($checks as $check) {
            $grouped[$check['group']][] = $check;
        }

        $totalPass = count(array_filter($checks, fn($c) => $c['pass'] && empty($c['warn'])));
        $totalFail = count(array_filter($checks, fn($c) => !$c['pass'] && empty($c['warn'])));
        $totalWarn = count(array_filter($checks, fn($c) => (!$c['pass'] || !empty($c['warn'])) && ($c['pass'] || !empty($c['warn']))));

        return view('admin.requirements', compact('grouped', 'totalPass', 'totalFail', 'totalWarn'));
    }

    private function parseBytes(string $val): int
    {
        $val  = trim($val);
        $last = strtolower($val[strlen($val) - 1] ?? '');
        $num  = (int) $val;
        return match ($last) {
            'g' => $num * 1024 * 1024 * 1024,
            'm' => $num * 1024 * 1024,
            'k' => $num * 1024,
            default => $num,
        };
    }
}
