<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Speaker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SpeakerController extends Controller
{
    public function index(Request $request)
    {
        $events = Event::orderBy('name')->get();
        $eventId = $request->get('event_id');

        $query = Speaker::with('event')->latest();

        if ($eventId) {
            $query->where('event_id', $eventId);
        }

        $speakers = $query->paginate(10)->withQueryString();

        return view('admin.speakers.index', compact('speakers', 'events', 'eventId'));
    }

    public function create()
    {
        $events = Event::orderBy('name')->get();
        return view('admin.speakers.create', compact('events'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'title'      => 'nullable|string|max:150',
            'company'    => 'nullable|string|max:150',
            'email'      => 'required|email|unique:speakers,email',
            'seniority'  => 'nullable|string|max:100',
            'country'    => 'nullable|string|max:100',
            'photo'      => 'nullable|image|max:2048',
            'event_id'   => 'nullable|exists:events,id',
        ]);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('speakers', 'public');
        }

        Speaker::create($data);

        return redirect()->route('admin.speakers.index')
            ->with('success', 'Speaker added successfully.');
    }

    public function edit(Speaker $speaker)
    {
        $events = Event::orderBy('name')->get();
        return view('admin.speakers.edit', compact('speaker', 'events'));
    }

    public function update(Request $request, Speaker $speaker)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'title'      => 'nullable|string|max:150',
            'company'    => 'nullable|string|max:150',
            'email'      => 'required|email|unique:speakers,email,' . $speaker->id,
            'seniority'  => 'nullable|string|max:100',
            'country'    => 'nullable|string|max:100',
            'photo'      => 'nullable|image|max:2048',
            'event_id'   => 'nullable|exists:events,id',
        ]);

        if ($request->hasFile('photo')) {
            if ($speaker->photo) {
                Storage::disk('public')->delete($speaker->photo);
            }
            $data['photo'] = $request->file('photo')->store('speakers', 'public');
        } else {
            unset($data['photo']);
        }

        // Allow clearing the event
        $data['event_id'] = $request->input('event_id') ?: null;

        $speaker->update($data);

        return redirect()->route('admin.speakers.index')
            ->with('success', 'Speaker updated successfully.');
    }

    public function destroy(Speaker $speaker)
    {
        if ($speaker->photo) {
            Storage::disk('public')->delete($speaker->photo);
        }
        $speaker->delete();

        return redirect()->route('admin.speakers.index')
            ->with('success', 'Speaker deleted successfully.');
    }

    public function destroyAll()
    {
        // Delete photos first
        Speaker::whereNotNull('photo')->each(function ($s) {
            Storage::disk('public')->delete($s->photo);
        });

        // Use delete() instead of truncate() to respect FK constraints
        Speaker::query()->delete();

        return redirect()->route('admin.speakers.index')
            ->with('success', 'All speakers deleted successfully.');
    }

    public function importForm()
    {
        $events = Event::orderBy('name')->get();
        return view('admin.speakers.import', compact('events'));
    }

    // ── AI Verify Profile ──────────────────────────────────────────────────

    public function verifyProfile(Speaker $speaker)
    {
        $env    = (new SettingsController)->readEnvValues(['OPENAI_API_KEY']);
        $apiKey = $env['OPENAI_API_KEY'] ?? env('OPENAI_API_KEY', '');

        if (!$apiKey) {
            return response()->json(['error' => 'OpenAI API key not configured. Go to Settings → AI.'], 422);
        }

        $profile = "Name: {$speaker->full_name}\n"
                 . "Title: " . ($speaker->title ?: 'Unknown') . "\n"
                 . "Company: " . ($speaker->company ?: 'Unknown') . "\n"
                 . "Seniority: " . ($speaker->seniority ?: 'Unknown') . "\n"
                 . "Country: " . ($speaker->country ?: 'Unknown') . "\n"
                 . "Email: {$speaker->email}\n";

        $prompt = "You are a professional researcher verifying speaker profiles for a CRM.\n\n"
                . "CURRENT PROFILE:\n{$profile}\n"
                . "TASK: Using your training knowledge, research this person deeply.\n"
                . "- Find their CURRENT job title, company, seniority level, and country.\n"
                . "- Check LinkedIn-style information, conference bios, company websites, news articles.\n"
                . "- If they've changed companies or roles, provide the UPDATED info.\n"
                . "- If something seems correct, keep it the same.\n"
                . "- If you cannot determine a field with confidence, return the original value.\n"
                . "- For seniority, use levels like: C suite, VP, Director, Head, Manager, Senior, Lead, etc.\n\n"
                . "Return ONLY valid JSON in this exact format (no markdown, no explanation):\n"
                . "{\n"
                . "  \"title\": \"current job title\",\n"
                . "  \"company\": \"current company\",\n"
                . "  \"seniority\": \"current seniority level\",\n"
                . "  \"country\": \"current country\",\n"
                . "  \"changes\": [\"list of what changed and why\"],\n"
                . "  \"confidence\": \"high/medium/low\",\n"
                . "  \"summary\": \"one line summary of findings\"\n"
                . "}";

        $payload = json_encode([
            'model'       => 'gpt-4o-mini',
            'messages'    => [
                ['role' => 'system', 'content' => 'You are a professional profile researcher. Return only valid JSON.'],
                ['role' => 'user',   'content' => $prompt],
            ],
            'temperature' => 0.2,
            'max_tokens'  => 400,
        ]);

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
        ]);
        $response = curl_exec($ch);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if (!$response) {
            return response()->json(['error' => 'AI request failed: ' . $curlErr], 500);
        }

        $json    = json_decode($response, true);
        $content = $json['choices'][0]['message']['content'] ?? '';
        $content = trim(preg_replace('/```json?|```/', '', $content));
        $result  = json_decode($content, true);

        if (!is_array($result) || !isset($result['title'])) {
            return response()->json(['error' => 'AI returned invalid response. Try again.'], 500);
        }

        // Compare and update only changed fields
        $updated = [];
        $fields  = ['title', 'company', 'seniority', 'country'];
        foreach ($fields as $field) {
            $newVal = trim($result[$field] ?? '');
            $oldVal = trim($speaker->$field ?? '');
            if ($newVal && strtolower($newVal) !== strtolower($oldVal) && strtolower($newVal) !== 'unknown') {
                $updated[$field] = ['old' => $oldVal ?: '(empty)', 'new' => $newVal];
                $speaker->$field = $newVal;
            }
        }

        if (!empty($updated)) {
            $speaker->save();
        }

        return response()->json([
            'ok'         => true,
            'updated'    => $updated,
            'changes'    => $result['changes'] ?? [],
            'confidence' => $result['confidence'] ?? 'unknown',
            'summary'    => $result['summary'] ?? 'Verification complete.',
            'speaker'    => [
                'title'     => $speaker->title,
                'company'   => $speaker->company,
                'seniority' => $speaker->seniority,
                'country'   => $speaker->country,
            ],
        ]);
    }

    public function importCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
            'event_id' => 'nullable|exists:events,id',
        ]);

        $eventId = $request->input('event_id') ?: null;
        $file    = $request->file('csv_file');
        $handle  = fopen($file->getRealPath(), 'r');

        // Read header row — strip UTF-8 BOM from first cell if present
        $header = fgetcsv($handle);
        $header = array_map(fn($h) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', $h))), $header);

        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($header)) {
                $skipped++;
                continue;
            }

            $data = array_combine($header, $row);

            $mapped = [
                'first_name' => trim($data['first name']  ?? $data['first_name']  ?? ''),
                'last_name'  => trim($data['last name']   ?? $data['last_name']   ?? ''),
                'title'      => trim($data['title']       ?? ''),
                'company'    => trim($data['company']     ?? ''),
                'email'      => trim($data['email']       ?? ''),
                'seniority'  => trim($data['seniority']   ?? ''),
                'country'    => trim($data['country']     ?? ''),
                'event_id'   => $eventId,
            ];

            if (empty($mapped['first_name']) || empty($mapped['last_name']) || empty($mapped['email'])) {
                $skipped++;
                continue;
            }

            if (Speaker::where('email', $mapped['email'])->exists()) {
                $skipped++;
                $errors[] = "Skipped duplicate email: {$mapped['email']}";
                continue;
            }

            Speaker::create($mapped);
            $imported++;
        }

        fclose($handle);

        $message = "{$imported} speaker(s) imported successfully.";
        if ($skipped) {
            $message .= " {$skipped} row(s) skipped.";
        }

        return redirect()->route('admin.speakers.index')->with('success', $message);
    }
}
