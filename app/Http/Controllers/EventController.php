<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::latest()->get();
        return view('admin.events.index', compact('events'));
    }

    public function create()
    {
        return view('admin.events.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'location'    => 'required|string|max:255',
            'date'        => 'required|date',
            'end_date'    => 'nullable|date|after_or_equal:date',
            'time'        => 'required',
            'description' => 'nullable|string',
            'status'      => 'required|in:draft,planning,confirmed',
            'image'       => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('events', 'public');
        }

        Event::create($data);

        return redirect()->route('admin.events.index')
            ->with('success', 'Event created successfully.');
    }

    public function edit(Event $event)
    {
        return view('admin.events.edit', compact('event'));
    }

    public function update(Request $request, Event $event)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'location'    => 'required|string|max:255',
            'date'        => 'required|date',
            'end_date'    => 'nullable|date|after_or_equal:date',
            'time'        => 'required',
            'description' => 'nullable|string',
            'status'      => 'required|in:draft,planning,confirmed',
            'image'       => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            // Delete old image
            if ($event->image) {
                Storage::disk('public')->delete($event->image);
            }
            $data['image'] = $request->file('image')->store('events', 'public');
        } else {
            unset($data['image']);
        }

        $event->update($data);

        return redirect()->route('admin.events.index')
            ->with('success', 'Event updated successfully.');
    }

    public function destroy(Event $event)
    {
        if ($event->image) {
            Storage::disk('public')->delete($event->image);
        }
        $event->delete();

        return redirect()->route('admin.events.index')
            ->with('success', 'Event deleted successfully.');
    }
}
