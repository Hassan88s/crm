<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\InboxController as WebInboxController;
use Illuminate\Http\Request;

/**
 * Thin JSON wrappers around the web InboxController.
 * Callers that need Blade-free responses go through here.
 */
class InboxController extends ApiController
{
    public function index(Request $request, WebInboxController $ctrl)
    {
        return $ctrl->index($request);
    }

    public function show(Request $request, string $uid, WebInboxController $ctrl)
    {
        return $ctrl->show($request, $uid);
    }

    public function markRead(Request $request, string $uid, WebInboxController $ctrl)
    {
        return $ctrl->markRead($request, $uid);
    }

    public function move(Request $request, string $uid, WebInboxController $ctrl)
    {
        return $ctrl->move($request, $uid);
    }

    public function destroy(Request $request, string $uid, WebInboxController $ctrl)
    {
        return $ctrl->destroy($request, $uid);
    }
}
