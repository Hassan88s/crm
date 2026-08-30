<?php

namespace App\Http\Controllers\Api;

use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends ApiController
{
    public function index(Request $request)
    {
        $q = Event::query()->withCount('speakers');

        if ($s = $request->query('status')) $q->where('status', $s);
        if ($search = $request->query('search')) {
            $q->where(function ($w) use ($search) {
                $w->where('name', 'like', "%$search%")
                  ->orWhere('location', 'like', "%$search%");
            });
        }
        $q->orderBy($request->query('sort', 'date'), $request->query('dir', 'desc'));

        return $this->paginate($q, $request);
    }

    public function show(Event $event)
    {
        $event->loadCount('speakers');
        return $this->ok($event);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'location'    => 'nullable|string|max:255',
            'date'        => 'nullable|date',
            'end_date'    => 'nullable|date',
            'time'        => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'status'      => 'nullable|in:draft,planning,confirmed',
        ]);
        return $this->ok(Event::create($data), 201);
    }

    public function update(Request $request, Event $event)
    {
        $data = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'location'    => 'nullable|string|max:255',
            'date'        => 'nullable|date',
            'end_date'    => 'nullable|date',
            'time'        => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'status'      => 'nullable|in:draft,planning,confirmed',
        ]);
        $event->update($data);
        return $this->ok($event);
    }

    public function destroy(Event $event)
    {
        $event->delete();
        return $this->ok(['deleted' => true]);
    }

    public function speakers(Request $request, Event $event)
    {
        return $this->paginate($event->speakers()->getQuery()->orderBy('last_name'), $request);
    }
}
