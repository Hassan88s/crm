<?php

namespace App\Http\Controllers\Api;

use App\Models\EmailLog;
use Illuminate\Http\Request;

class EmailLogController extends ApiController
{
    public function index(Request $request)
    {
        $q = EmailLog::query()->with(['speaker:id,first_name,last_name,email', 'smtpAccount:id,name']);
        if ($s = $request->query('status'))     $q->where('status', $s);
        if ($sp = $request->query('speaker_id')) $q->where('speaker_id', $sp);
        if ($smtp = $request->query('smtp_account_id')) $q->where('smtp_account_id', $smtp);
        if ($from = $request->query('from'))    $q->where('created_at', '>=', $from);
        if ($to   = $request->query('to'))      $q->where('created_at', '<=', $to);
        $q->orderByDesc('id');
        return $this->paginate($q, $request);
    }

    public function show(EmailLog $log)
    {
        $log->load(['speaker', 'smtpAccount']);
        return $this->ok($log);
    }
}
