<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\SpeakerController as WebSpeakerController;
use App\Models\Speaker;
use Illuminate\Http\Request;

class SpeakerController extends ApiController
{
    public function index(Request $request)
    {
        $q = Speaker::query()->with('event:id,name');

        if ($ev = $request->query('event_id'))   $q->where('event_id', $ev);
        if ($country = $request->query('country')) $q->where('country', $country);
        if ($sen = $request->query('seniority')) $q->where('seniority', $sen);
        if ($search = $request->query('search')) {
            $q->where(function ($w) use ($search) {
                $w->where('first_name', 'like', "%$search%")
                  ->orWhere('last_name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
                  ->orWhere('company', 'like', "%$search%");
            });
        }

        $q->orderBy($request->query('sort', 'last_name'), $request->query('dir', 'asc'));
        return $this->paginate($q, $request);
    }

    public function show(Speaker $speaker)
    {
        $speaker->load('event');
        return $this->ok($speaker);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name'   => 'required|string|max:100',
            'last_name'    => 'required|string|max:100',
            'title'        => 'nullable|string|max:255',
            'company'      => 'nullable|string|max:255',
            'email'        => 'nullable|email|max:255',
            'linkedin_url' => 'nullable|url|max:500',
            'seniority'    => 'nullable|string|max:100',
            'country'      => 'nullable|string|max:100',
            'event_id'     => 'nullable|exists:events,id',
        ]);
        return $this->ok(Speaker::create($data), 201);
    }

    public function update(Request $request, Speaker $speaker)
    {
        $data = $request->validate([
            'first_name'   => 'sometimes|string|max:100',
            'last_name'    => 'sometimes|string|max:100',
            'title'        => 'nullable|string|max:255',
            'company'      => 'nullable|string|max:255',
            'email'        => 'nullable|email|max:255',
            'linkedin_url' => 'nullable|url|max:500',
            'seniority'    => 'nullable|string|max:100',
            'country'      => 'nullable|string|max:100',
            'event_id'     => 'nullable|exists:events,id',
        ]);
        $speaker->update($data);
        return $this->ok($speaker);
    }

    public function destroy(Speaker $speaker)
    {
        $speaker->delete();
        return $this->ok(['deleted' => true]);
    }

    /** Proxy to the web verify action (uses OpenAI). */
    public function verify(Request $request, Speaker $speaker, WebSpeakerController $ctrl)
    {
        return $ctrl->verifyProfile($request, $speaker);
    }

    /** Proxy to the web find-LinkedIn action. */
    public function findLinkedIn(Speaker $speaker, WebSpeakerController $ctrl)
    {
        return $ctrl->findLinkedIn($speaker);
    }
}
