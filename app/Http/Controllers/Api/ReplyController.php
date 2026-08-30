<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ReplyController as WebReplyController;
use App\Models\EmailReply;
use Illuminate\Http\Request;

class ReplyController extends ApiController
{
    public function index(Request $request)
    {
        $q = EmailReply::query()->with('speaker:id,first_name,last_name,email');
        if ($cat = $request->query('category'))   $q->where('category', $cat);
        if ($sp  = $request->query('speaker_id')) $q->where('speaker_id', $sp);
        if ($search = $request->query('search')) {
            $q->where(function ($w) use ($search) {
                $w->where('from_email', 'like', "%$search%")
                  ->orWhere('subject',    'like', "%$search%")
                  ->orWhere('body_plain', 'like', "%$search%");
            });
        }
        $q->orderByDesc('received_at');
        return $this->paginate($q, $request);
    }

    public function show(EmailReply $reply)
    {
        $reply->load('speaker');
        return $this->ok($reply);
    }

    public function changeCategory(Request $request, EmailReply $reply, WebReplyController $ctrl)
    {
        $request->validate([
            'category' => 'required|in:Interested,Not Interested,Info Request,Out of Office,Spam,Negative,No Reply,Bounced,Confirmed,Manual Review',
        ]);
        return $ctrl->changeCategory($request, $reply);
    }

    public function reclassify(EmailReply $reply, WebReplyController $ctrl)
    {
        return $ctrl->reclassify($reply);
    }

    public function sendReply(Request $request, EmailReply $reply, WebReplyController $ctrl)
    {
        return $ctrl->sendReply($request, $reply);
    }

    public function fetch(WebReplyController $ctrl)
    {
        return $ctrl->fetch();
    }

    public function destroy(EmailReply $reply)
    {
        $reply->delete();
        return $this->ok(['deleted' => true]);
    }
}
