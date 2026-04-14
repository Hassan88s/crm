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
        $events  = Event::orderBy('name')->get();
        $eventId = $request->get('event_id');
        $search  = $request->get('search');

        $query = Speaker::with('event')->latest();

        if ($eventId) {
            $query->where('event_id', $eventId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%")
                  ->orWhere('country', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%");
            });
        }

        $speakers = $query->paginate(50)->withQueryString();

        return view('admin.speakers.index', compact('speakers', 'events', 'eventId', 'search'));
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
            'linkedin_url' => 'nullable|url|max:255',
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
            'linkedin_url' => 'nullable|url|max:255',
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

    public function verifyProfile(Request $request, Speaker $speaker)
    {
        $env    = (new SettingsController)->readEnvValues(['OPENAI_API_KEY']);
        $apiKey = $env['OPENAI_API_KEY'] ?? env('OPENAI_API_KEY', '');

        if (!$apiKey) {
            return response()->json(['error' => 'OpenAI API key not configured. Go to Settings → AI.'], 422);
        }

        $emailDomain = strtolower(trim(substr(strrchr($speaker->email, "@"), 1)));

        // Use LinkedIn URL from request (LinkedIn verify button) or from speaker record
        $requestBody = json_decode($request->getContent(), true);
        $linkedinUrl = $request->input('linkedin_url')
            ?? ($requestBody['linkedin_url'] ?? null)
            ?? ($speaker->linkedin_url ?? '');

        // If a new LinkedIn URL was provided via request, save it to DB immediately
        if ($linkedinUrl && stripos($linkedinUrl, 'linkedin.com') !== false) {
            $speaker->linkedin_url = trim($linkedinUrl);
            $speaker->save();
        }

        $profile = "Name: {$speaker->full_name}\n"
                 . "Title: " . ($speaker->title ?: 'Unknown') . "\n"
                 . "Company: " . ($speaker->company ?: 'Unknown') . "\n"
                 . "Seniority: " . ($speaker->seniority ?: 'Unknown') . "\n"
                 . "Country: " . ($speaker->country ?: 'Unknown') . "\n"
                 . "Email: {$speaker->email}\n"
                 . "Email Domain: {$emailDomain}\n"
                 . "LinkedIn: " . ($linkedinUrl ?: 'Not provided') . "\n";

        $prompt = <<<PROMPT
You are an elite OSINT investigator specializing in professional identity verification.

CURRENT PROFILE:
{$profile}

MISSION:
Verify and update this person's CURRENT professional identity. You MUST conduct an exhaustive multi-strategy web search.

═══ PHASE 1: LINKEDIN (HIGHEST PRIORITY) ═══
If a LinkedIn URL is provided:
  → Visit it directly and extract ALL current info (title, company, location, headline)
If NO LinkedIn URL is provided:
  → Run MULTIPLE search queries to find their LinkedIn:
    • "{$speaker->full_name}" site:linkedin.com/in
    • "{$speaker->first_name} {$speaker->last_name}" "{$emailDomain}" linkedin
    • "{$speaker->full_name}" "{$speaker->company}" linkedin
  → Try name variations: with/without middle name, common abbreviations
  → If company is known, search: site:linkedin.com "{$speaker->company}" "{$speaker->last_name}"

═══ PHASE 2: COMPANY RESEARCH ═══
Derive ALL company name variants from email domain "{$emailDomain}":
  • Strip TLD (.com, .co.uk, .be, .nl, .no, etc.)
  • Split hyphens: "first-abu-dhabi" → "First Abu Dhabi"
  • Check parent/subsidiary: "bankfab.com" → "First Abu Dhabi Bank (FAB)"
  • Check brand aliases: "dnb.no" → "DNB Bank ASA" or "DNB"
  • Check if domain is a group/holding: add "Group", "Bank", "Holdings" variants
Search for:
  • site:{$emailDomain} "{$speaker->last_name}" team OR about OR leadership OR speakers
  • "{$speaker->full_name}" "{$emailDomain}"
  • "{$speaker->full_name}" + each company variant

═══ PHASE 3: BROADER WEB SEARCH ═══
Run these additional searches:
  • "{$speaker->full_name}" + title keywords from current profile
  • "{$speaker->full_name}" speaker OR panelist OR conference OR summit
  • "{$speaker->full_name}" + country if known
  • "{$speaker->email}" (exact email search)
  • Check: conference speaker pages, fintech/banking event agendas, press releases
  • Check: RocketReach, ZoomInfo, Crunchbase, Bloomberg profiles

═══ PHASE 4: CROSS-REFERENCE & DECIDE ═══
Priority of sources (highest to lowest):
  1. LinkedIn profile (most authoritative for current role)
  2. Official company website (team/about/leadership page)
  3. Recent conference bios (within last 12 months)
  4. Professional directories (RocketReach, ZoomInfo)
  5. News articles, press releases
  6. Other public mentions

Rules:
  • Country = current professional location, NOT nationality
  • Normalize seniority to: C suite, VP, Director, Head, Manager, Lead, Senior, Principal, Specialist, Unknown
  • If person clearly exists at the company but you can't confirm exact title, use the best available title
  • If multiple results for same name, match using company + email domain + country
  • Do NOT return "Unknown" if you found ANY evidence — use best available data
  • ONLY keep original value if you found ZERO evidence for that field

OUTPUT — Return ONLY valid JSON, no markdown, no explanation:
{
  "title": "current job title or original if truly no evidence found",
  "company": "current company or original if truly no evidence found",
  "seniority": "normalized seniority level",
  "country": "current professional country",
  "linkedin_url": "full LinkedIn URL or empty string",
  "changes": ["field: old → new | source: where you found this"],
  "confidence": "high|medium|low",
  "summary": "one-line summary with the strongest source cited"
}
PROMPT;

        // Use Responses API with web search for live data
        $payload = json_encode([
            'model'                 => 'gpt-5.4',
            'input'                 => $prompt,
            'instructions'          => 'You are an elite OSINT investigator. You MUST run multiple web searches using different query strategies before concluding. Do not give up after one search. Try at least 3-5 different search queries with name variations, company variants, and email domain. Return only valid JSON.',
            'tools'                 => [
                ['type' => 'web_search_preview', 'search_context_size' => 'high'],
            ],
            'temperature'           => 0.2,
            'max_output_tokens'     => 1000,
        ]);

        $ch = curl_init('https://api.openai.com/v1/responses');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 90,
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

        $json = json_decode($response, true);

        // Check for API errors
        if (isset($json['error'])) {
            return response()->json(['error' => 'OpenAI error: ' . ($json['error']['message'] ?? 'Unknown error')], 500);
        }

        // Extract text content from Responses API output
        $content = '';
        if (isset($json['output']) && is_array($json['output'])) {
            foreach ($json['output'] as $item) {
                if (($item['type'] ?? '') === 'message' && isset($item['content'])) {
                    foreach ($item['content'] as $block) {
                        if (($block['type'] ?? '') === 'output_text') {
                            $content = $block['text'] ?? '';
                            break 2;
                        }
                    }
                }
            }
        }

        $content = trim(preg_replace('/```json?|```/', '', $content));
        $result  = json_decode($content, true);

        if (!is_array($result) || !isset($result['title'])) {
            \Log::error('AI verify profile invalid response', ['raw' => $response, 'content' => $content]);
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

        // Handle LinkedIn URL — save if found/updated
        $newLinkedin = trim($result['linkedin_url'] ?? '');
        $oldLinkedin = trim($speaker->linkedin_url ?? '');
        if ($newLinkedin && stripos($newLinkedin, 'linkedin.com') !== false
            && strtolower($newLinkedin) !== strtolower($oldLinkedin)) {
            $updated['linkedin_url'] = ['old' => $oldLinkedin ?: '(empty)', 'new' => $newLinkedin];
            $speaker->linkedin_url = $newLinkedin;
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
                'title'        => $speaker->title,
                'company'      => $speaker->company,
                'seniority'    => $speaker->seniority,
                'country'      => $speaker->country,
                'linkedin_url' => $speaker->linkedin_url,
            ],
        ]);
    }

    // ── Find LinkedIn URL (Apollo first, then AI fallback) ──────────────

    public function findLinkedIn(Speaker $speaker)
    {
        // If already has LinkedIn URL, return it
        if ($speaker->linkedin_url && stripos($speaker->linkedin_url, 'linkedin.com') !== false) {
            return response()->json(['ok' => true, 'linkedin_url' => $speaker->linkedin_url, 'already_exists' => true]);
        }

        $env = (new SettingsController)->readEnvValues(['OPENAI_API_KEY', 'APOLLO_API_KEY']);

        // ── STEP 1: Try Apollo API first (fast & accurate) ──────────────
        $apolloKey = $env['APOLLO_API_KEY'] ?? env('APOLLO_API_KEY', '');
        if ($apolloKey) {
            $apolloResult = $this->searchApollo($speaker, $apolloKey);
            if ($apolloResult) {
                $speaker->linkedin_url = $apolloResult['linkedin_url'];

                // Also update other fields if Apollo has better data
                if (!empty($apolloResult['title']) && strtolower($apolloResult['title']) !== 'unknown') {
                    $speaker->title = $apolloResult['title'];
                }
                if (!empty($apolloResult['seniority']) && strtolower($apolloResult['seniority']) !== 'unknown') {
                    $speaker->seniority = $apolloResult['seniority'];
                }
                if (!empty($apolloResult['country']) && strtolower($apolloResult['country']) !== 'unknown') {
                    $speaker->country = $apolloResult['country'];
                }

                $speaker->save();

                return response()->json([
                    'ok'           => true,
                    'linkedin_url' => $apolloResult['linkedin_url'],
                    'source'       => 'apollo',
                    'confidence'   => 'high',
                    'summary'      => 'Found via Apollo (' . ($apolloResult['source'] ?? 'search') . '): ' . ($apolloResult['title'] ?? '') . ' at ' . ($apolloResult['company'] ?? $speaker->company),
                ]);
            }
        }

        // ── STEP 2: Fall back to AI web search ──────────────────────────
        $apiKey = $env['OPENAI_API_KEY'] ?? env('OPENAI_API_KEY', '');
        if (!$apiKey) {
            return response()->json(['error' => 'No API keys configured (Apollo or OpenAI).'], 422);
        }

        return $this->findLinkedInViaAI($speaker, $apiKey);
    }

    // ── Apollo API: Enrichment + People Search ─────────────────────────

    private function searchApollo(Speaker $speaker, string $apolloKey): ?array
    {
        $emailDomain = strtolower(trim(substr(strrchr($speaker->email, "@"), 1)));

        // Strategy 1: People Enrichment by email (most accurate, direct match)
        $result = $this->apolloEnrich($apolloKey, $speaker);
        if ($result) return $result;

        // Strategy 2: People Search by email
        $result = $this->apolloSearch($apolloKey, $speaker, [
            'q_keywords' => $speaker->email,
        ]);
        if ($result) return $result;

        // Strategy 3: Search by name + company domain
        $result = $this->apolloSearch($apolloKey, $speaker, [
            'person_titles'         => [$speaker->title ?: ''],
            'q_organization_domains' => $emailDomain,
            'person_names'          => [$speaker->full_name],
        ]);
        if ($result) return $result;

        // Strategy 4: Search by name + company name
        if ($speaker->company) {
            $result = $this->apolloSearch($apolloKey, $speaker, [
                'person_names'           => [$speaker->full_name],
                'q_organization_name'    => $speaker->company,
            ]);
            if ($result) return $result;
        }

        // Strategy 5: Search by name only (broadest)
        $result = $this->apolloSearch($apolloKey, $speaker, [
            'person_names' => [$speaker->full_name],
        ]);

        return $result;
    }

    // ── Apollo People Enrichment (by email — most accurate) ─────────────

    private function apolloEnrich(string $apiKey, Speaker $speaker): ?array
    {
        $payload = json_encode([
            'email'      => $speaker->email,
            'first_name' => $speaker->first_name,
            'last_name'  => $speaker->last_name,
        ]);

        $ch = curl_init('https://api.apollo.io/api/v1/people/match');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'x-api-key: ' . $apiKey,
                'Content-Type: application/json',
                'Cache-Control: no-cache',
            ],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) return null;

        $json   = json_decode($response, true);
        $person = $json['person'] ?? null;

        if (!$person) return null;

        $linkedinUrl = $person['linkedin_url'] ?? '';
        if (!$linkedinUrl || stripos($linkedinUrl, 'linkedin.com') === false) {
            return null;
        }

        return [
            'linkedin_url' => $linkedinUrl,
            'title'        => $person['title'] ?? null,
            'company'      => $person['organization']['name'] ?? null,
            'seniority'    => $this->mapApolloSeniority($person['seniority'] ?? ''),
            'country'      => $person['country'] ?? null,
            'city'         => $person['city'] ?? null,
            'source'       => 'apollo_enrich',
        ];
    }

    // ── Apollo People Search (by filters) ──────────────────────────────

    private function apolloSearch(string $apiKey, Speaker $speaker, array $params): ?array
    {
        $payload = json_encode(array_merge($params, [
            'page'     => 1,
            'per_page' => 5,
        ]));

        $ch = curl_init('https://api.apollo.io/api/v1/mixed_people/api_search');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'x-api-key: ' . $apiKey,
                'Content-Type: application/json',
                'Cache-Control: no-cache',
            ],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) return null;

        $json = json_decode($response, true);
        $people = $json['people'] ?? [];

        if (empty($people)) return null;

        // Find best match
        foreach ($people as $person) {
            $linkedinUrl = $person['linkedin_url'] ?? '';
            if (!$linkedinUrl || stripos($linkedinUrl, 'linkedin.com') === false) {
                continue;
            }

            // Verify name match
            $firstMatch = strtolower(trim($person['first_name'] ?? '')) === strtolower(trim($speaker->first_name));
            $lastMatch  = strtolower(trim($person['last_name'] ?? '')) === strtolower(trim($speaker->last_name));
            if (!$firstMatch && !$lastMatch) {
                continue;
            }

            return [
                'linkedin_url' => $linkedinUrl,
                'title'        => $person['title'] ?? null,
                'company'      => $person['organization']['name'] ?? null,
                'seniority'    => $this->mapApolloSeniority($person['seniority'] ?? ''),
                'country'      => $person['country'] ?? null,
                'city'         => $person['city'] ?? null,
                'source'       => 'apollo_search',
            ];
        }

        return null;
    }

    private function mapApolloSeniority(string $seniority): string
    {
        return match(strtolower($seniority)) {
            'c_suite', 'owner', 'founder' => 'C suite',
            'vp'                          => 'VP',
            'director'                    => 'Director',
            'manager'                     => 'Manager',
            'senior'                      => 'Senior',
            'entry'                       => 'Specialist',
            default                       => ucfirst($seniority) ?: 'Unknown',
        };
    }

    // ── AI-powered LinkedIn search (fallback) ────────────────────────────

    private function findLinkedInViaAI(Speaker $speaker, string $apiKey)
    {
        $emailDomain = strtolower(trim(substr(strrchr($speaker->email, "@"), 1)));
        $company   = $speaker->company ?: 'Unknown';
        $title     = $speaker->title ?: 'Unknown';
        $country   = $speaker->country ?: 'Unknown';
        $firstName = $speaker->first_name;
        $lastName  = $speaker->last_name;
        $fullName  = $speaker->full_name;

        $prompt = <<<LIPROMPT
You are an expert LinkedIn profile finder. Your ONLY job is to find this person's LinkedIn profile URL.

TARGET PERSON:
  Name: {$fullName}
  Title: {$title}
  Company: {$company}
  Email: {$speaker->email}
  Email Domain: {$emailDomain}
  Country: {$country}

YOU MUST RUN ALL OF THESE SEARCHES (do not skip any):

Search Round 1 — Direct LinkedIn searches:
  • "{$fullName}" site:linkedin.com/in
  • "{$firstName} {$lastName}" site:linkedin.com/in "{$company}"
  • "{$firstName} {$lastName}" site:linkedin.com/in "{$emailDomain}"

Search Round 2 — Name + Company variations:
  • "{$lastName}" site:linkedin.com/in "{$company}"
  • "{$fullName}" linkedin "{$emailDomain}"
  • "{$firstName} {$lastName}" linkedin profile {$country}

Search Round 3 — Broader matching:
  • "{$fullName}" "{$company}" profile
  • "{$fullName}" {$title} linkedin
  • "{$speaker->email}" linkedin

Search Round 4 — Directory/aggregator sites:
  • "{$fullName}" "{$company}" site:rocketreach.co OR site:zoominfo.com OR site:theorg.com
  • These sites often show LinkedIn URLs in their results

MATCHING RULES:
  • The person MUST match on at least 2 of: name, company/domain, country, title
  • LinkedIn slug often follows patterns: firstname-lastname, firstnamelastname, firstname-lastname-123abc
  • If multiple profiles match the name, use company/email domain/country to pick the right one
  • Return the full URL: https://www.linkedin.com/in/slug-here
  • If you find a match on a directory site that links to LinkedIn, return the LinkedIn URL

Return ONLY valid JSON, no markdown:
{"linkedin_url": "https://www.linkedin.com/in/... or empty string if truly not found after all searches", "confidence": "high|medium|low", "summary": "which search found it, or what you tried"}
LIPROMPT;

        $payload = json_encode([
            'model'              => 'gpt-5.4',
            'input'              => $prompt,
            'instructions'       => 'You are a LinkedIn profile finder. Run EVERY search query listed. Do NOT stop after the first search. Try ALL 4 rounds of searches before concluding. Return only valid JSON.',
            'tools'              => [['type' => 'web_search_preview', 'search_context_size' => 'high']],
            'temperature'        => 0.2,
            'max_output_tokens'  => 400,
        ]);

        $ch = curl_init('https://api.openai.com/v1/responses');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 90,
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

        $json = json_decode($response, true);

        if (isset($json['error'])) {
            return response()->json(['error' => 'OpenAI error: ' . ($json['error']['message'] ?? 'Unknown')], 500);
        }

        // Extract text from Responses API
        $content = '';
        if (isset($json['output']) && is_array($json['output'])) {
            foreach ($json['output'] as $item) {
                if (($item['type'] ?? '') === 'message' && isset($item['content'])) {
                    foreach ($item['content'] as $block) {
                        if (($block['type'] ?? '') === 'output_text') {
                            $content = $block['text'] ?? '';
                            break 2;
                        }
                    }
                }
            }
        }

        $content = trim(preg_replace('/```json?|```/', '', $content));
        $result  = json_decode($content, true);

        $foundUrl = trim($result['linkedin_url'] ?? '');

        if ($foundUrl && stripos($foundUrl, 'linkedin.com/in/') !== false) {
            $speaker->linkedin_url = $foundUrl;
            $speaker->save();

            return response()->json([
                'ok'           => true,
                'linkedin_url' => $foundUrl,
                'source'       => 'ai_search',
                'confidence'   => $result['confidence'] ?? 'unknown',
                'summary'      => $result['summary'] ?? 'LinkedIn profile found via AI search.',
            ]);
        }

        return response()->json([
            'ok'           => true,
            'linkedin_url' => null,
            'source'       => 'ai_search',
            'confidence'   => $result['confidence'] ?? 'low',
            'summary'      => $result['summary'] ?? 'LinkedIn profile not found.',
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

        // Read file and convert encoding to UTF-8 if needed
        $rawContent = file_get_contents($file->getRealPath());
        $encoding   = mb_detect_encoding($rawContent, ['UTF-8', 'Windows-1252', 'ISO-8859-1', 'ASCII'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $rawContent = mb_convert_encoding($rawContent, 'UTF-8', $encoding);
        }
        // Also handle any remaining non-UTF8 bytes
        $rawContent = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $rawContent);

        // Write cleaned content to temp file for fgetcsv
        $tmpPath = tempnam(sys_get_temp_dir(), 'csv_');
        file_put_contents($tmpPath, $rawContent);
        $handle = fopen($tmpPath, 'r');

        // Read header row — strip UTF-8 BOM from first cell if present
        $header = fgetcsv($handle);
        $header = array_map(fn($h) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', $h))), $header);

        $imported = 0;
        $skipped  = 0;
        $errors   = [];
        $rowNum   = 1; // header is row 1

        // Log headers for debugging
        \Log::info('CSV Import headers', ['headers' => $header, 'count' => count($header)]);

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            // Skip completely empty rows
            if (!array_filter($row)) {
                continue;
            }

            // Handle column count mismatch — pad or trim row to match header
            $headerCount = count($header);
            $rowCount    = count($row);
            if ($rowCount < $headerCount) {
                $row = array_pad($row, $headerCount, '');
            } elseif ($rowCount > $headerCount) {
                $row = array_slice($row, 0, $headerCount);
            }

            $data = array_combine($header, $row);

            // Flexible header mapping — try multiple common variations
            $mapped = [
                'first_name'   => trim($data['first name'] ?? $data['first_name'] ?? $data['firstname'] ?? $data['first'] ?? ''),
                'last_name'    => trim($data['last name']  ?? $data['last_name']  ?? $data['lastname']  ?? $data['last']  ?? ''),
                'title'        => trim($data['title']      ?? $data['job title']  ?? $data['job_title'] ?? $data['jobtitle'] ?? $data['position'] ?? ''),
                'company'      => trim($data['company']    ?? $data['company name'] ?? $data['company_name'] ?? $data['organization'] ?? ''),
                'email'        => trim($data['email']      ?? $data['email address'] ?? $data['email_address'] ?? ''),
                'linkedin_url' => trim($data['profileurl'] ?? $data['linkedin_url'] ?? $data['linkedin'] ?? $data['profile_url'] ?? $data['linkedin url'] ?? $data['linkedinurl'] ?? ''),
                'seniority'    => trim($data['seniority']  ?? $data['level'] ?? ''),
                'country'      => trim($data['country']    ?? $data['location'] ?? ''),
                'event_id'     => $eventId,
            ];

            // Try to split full name if first/last are empty but "name" or "full name" exists
            if (empty($mapped['first_name']) && empty($mapped['last_name'])) {
                $fullName = trim($data['name'] ?? $data['full name'] ?? $data['full_name'] ?? $data['fullname'] ?? $data['speaker'] ?? $data['speaker name'] ?? '');
                if ($fullName) {
                    $parts = explode(' ', $fullName, 2);
                    $mapped['first_name'] = trim($parts[0] ?? '');
                    $mapped['last_name']  = trim($parts[1] ?? '');
                }
            }

            // Skip only if no name at all
            if (empty($mapped['first_name']) && empty($mapped['last_name'])) {
                $skipped++;
                $errors[] = "Row {$rowNum}: Missing name";
                continue;
            }

            // Set empty email to null
            if (empty($mapped['email'])) {
                $mapped['email'] = null;
            }

            // Skip duplicate emails (only if email is provided)
            if ($mapped['email'] && Speaker::where('email', $mapped['email'])->exists()) {
                $skipped++;
                $errors[] = "Row {$rowNum}: Duplicate email {$mapped['email']}";
                continue;
            }

            Speaker::create($mapped);
            $imported++;
        }

        fclose($handle);
        @unlink($tmpPath);

        $message = "{$imported} speaker(s) imported successfully.";
        if ($skipped) {
            $message .= " {$skipped} row(s) skipped.";
        }
        if (!empty($errors)) {
            $message .= " Details: " . implode('; ', array_slice($errors, 0, 10));
            if (count($errors) > 10) $message .= '… and ' . (count($errors) - 10) . ' more.';
        }

        return redirect()->route('admin.speakers.index')->with('success', $message);
    }
}
