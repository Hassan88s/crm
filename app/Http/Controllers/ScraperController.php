<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Speaker;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\Request;
use Symfony\Component\DomCrawler\Crawler;

class ScraperController extends Controller
{
    private array $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:123.0) Gecko/20100101 Firefox/123.0',
    ];

    public function index()
    {
        $events = Event::orderBy('name')->get();
        $env    = (new \App\Http\Controllers\SettingsController)->readEnvValues(['OPENAI_API_KEY']);
        $hasOpenAIKey = !empty($env['OPENAI_API_KEY']);
        return view('admin.scraper.index', compact('events', 'hasOpenAIKey'));
    }

    public function scrape(Request $request)
    {
        $data = $request->validate([
            'url'      => 'required|url',
            'mode'     => 'required|in:auto,speakers,team,agenda',
            'event_id' => 'nullable|exists:events,id',
        ]);

        try {
            $html = $this->fetchPage($data['url']);
        } catch (\Exception $e) {
            return back()->withErrors(['url' => 'Could not fetch page: ' . $e->getMessage()])->withInput();
        }

        $crawler = new Crawler($html, $data['url']);
        $people  = [];

        $mode = $data['mode'];

        // Auto-detect mode from URL/content hints
        if ($mode === 'auto') {
            $url_lower = strtolower($data['url']);
            if (str_contains($url_lower, 'speaker') || str_contains($url_lower, 'agenda')) {
                $mode = 'speakers';
            } elseif (str_contains($url_lower, 'team') || str_contains($url_lower, 'about') || str_contains($url_lower, 'people')) {
                $mode = 'team';
            } else {
                $mode = 'speakers';
            }
        }

        if ($mode === 'speakers' || $mode === 'agenda') {
            $people = $this->extractSpeakers($crawler, $data['url']);
        } elseif ($mode === 'team') {
            $people = $this->extractTeam($crawler, $data['url']);
        }

        // Deduplicate by name
        $seen   = [];
        $unique = [];
        foreach ($people as $p) {
            $key = strtolower(trim($p['first_name'] . ' ' . $p['last_name']));
            if ($key !== ' ' && !isset($seen[$key])) {
                $seen[$key] = true;
                $unique[]   = $p;
            }
        }

        // Mark which emails already exist in DB
        $existingEmails = Speaker::pluck('email')->map(fn($e) => strtolower($e))->flip()->toArray();
        foreach ($unique as &$p) {
            $p['exists'] = !empty($p['email']) && isset($existingEmails[strtolower($p['email'])]);
        }

        return view('admin.scraper.results', [
            'people'   => $unique,
            'url'      => $data['url'],
            'mode'     => $mode,
            'event_id' => $data['event_id'] ?? null,
            'events'   => Event::orderBy('name')->get(),
        ]);
    }

    public function discover(Request $request)
    {
        $data = $request->validate([
            'keywords' => 'required|string|max:300',
        ]);

        $env = (new \App\Http\Controllers\SettingsController)->readEnvValues(['OPENAI_API_KEY']);
        $apiKey = $env['OPENAI_API_KEY'] ?? '';

        if (!$apiKey) {
            return response()->json(['error' => 'OpenAI API key not configured. Go to Settings → AI to add it.'], 422);
        }

        $keywords = trim($data['keywords']);

        $prompt = <<<PROMPT
You are a research assistant helping find fintech and banking conference/summit websites that have public speaker listing pages.

The user is looking for events related to: "{$keywords}"

Return a JSON array of up to 12 relevant conference/event websites. Each object must have:
- "name": Full event name
- "url": Direct URL to the speakers or agenda page (not the homepage)
- "description": One short sentence about the event (type, location/region, audience)
- "type": One of "Speakers Page", "Agenda Page", "Team Page"

Rules:
- Only include real, well-known fintech/banking/digital finance events
- Prefer events with public speaker listings accessible without login
- Focus on events relevant to: {$keywords}
- Include international events (Europe, US, Asia-Pacific, Middle East)
- The URL must be the speakers/agenda subpage, not the homepage

Return ONLY valid JSON array, no markdown, no explanation.
PROMPT;

        try {
            // Use PHP curl directly — Guzzle stream handler has SSL issues on Windows with api.openai.com
            $payload = json_encode([
                'model'       => 'gpt-4o-mini',
                'messages'    => [['role' => 'user', 'content' => $prompt]],
                'temperature' => 0.3,
                'max_tokens'  => 1500,
            ]);

            $ch = curl_init('https://api.openai.com/v1/chat/completions');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT        => 60,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $apiKey,
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($payload),
                    'User-Agent: PulseCore/1.0',
                ],
            ]);

            $rawResponse = curl_exec($ch);
            $httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError   = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                return response()->json(['error' => 'Connection error: ' . $curlError], 422);
            }

            $result = json_decode($rawResponse, true);

            if ($httpCode !== 200) {
                $msg = $result['error']['message'] ?? "HTTP {$httpCode}: {$rawResponse}";
                return response()->json(['error' => 'OpenAI error: ' . $msg], 422);
            }

            $content = $result['choices'][0]['message']['content'] ?? '';

            // Strip markdown code fences if present
            $content = preg_replace('/^```(?:json)?\s*/m', '', $content);
            $content = preg_replace('/\s*```$/m', '', $content);

            $sites = json_decode(trim($content), true);
            if (!is_array($sites)) {
                return response()->json(['error' => 'AI returned unexpected format. Try again.'], 422);
            }

            return response()->json(['sites' => $sites]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error: ' . $e->getMessage()], 422);
        }
    }

    public function import(Request $request)
    {
        $data = $request->validate([
            'people'        => 'required|string',
            'event_id'      => 'nullable|exists:events,id',
            'selected'      => 'nullable|array',
        ]);

        $people   = json_decode($data['people'], true) ?? [];
        $selected = $data['selected'] ?? [];
        $eventId  = $data['event_id'] ?: null;

        $imported = 0;
        $skipped  = 0;

        foreach ($people as $i => $person) {
            if (!in_array((string)$i, $selected)) continue;

            $email = trim($person['email'] ?? '');

            // Skip if email exists
            if ($email && Speaker::where('email', $email)->exists()) {
                $skipped++;
                continue;
            }

            // Skip if no name at all
            $first = trim($person['first_name'] ?? '');
            $last  = trim($person['last_name'] ?? '');
            if (!$first && !$last) {
                $skipped++;
                continue;
            }

            Speaker::create([
                'first_name' => $first,
                'last_name'  => $last,
                'title'      => trim($person['title'] ?? ''),
                'company'    => trim($person['company'] ?? ''),
                'email'      => $email ?: null,
                'seniority'  => trim($person['seniority'] ?? ''),
                'country'    => trim($person['country'] ?? ''),
                'event_id'   => $eventId,
            ]);

            $imported++;
        }

        return redirect()->route('admin.speakers.index')
            ->with('success', "{$imported} speaker(s) imported from scrape. {$skipped} skipped.");
    }

    // ── Fetcher ───────────────────────────────────────────────────────────────

    private function fetchPage(string $url): string
    {
        $client = new Client([
            RequestOptions::TIMEOUT         => 20,
            RequestOptions::CONNECT_TIMEOUT => 10,
            RequestOptions::VERIFY          => false,
            RequestOptions::ALLOW_REDIRECTS => ['max' => 5],
            RequestOptions::HEADERS         => [
                'User-Agent' => $this->userAgents[array_rand($this->userAgents)],
                'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
            ],
        ]);

        $response = $client->get($url);
        return (string) $response->getBody();
    }

    // ── Speaker extraction (conference/agenda pages) ──────────────────────────

    private function extractSpeakers(Crawler $crawler, string $baseUrl): array
    {
        $people = [];

        // Strategy 0: Embedded JSON (Next.js __NEXT_DATA__, Nuxt, window state, ld+json)
        $jsonPeople = $this->extractFromEmbeddedJson($crawler);
        if (count($jsonPeople) >= 3) return $jsonPeople;
        $people = array_merge($people, $jsonPeople);

        // Strategy 1: Common speaker card CSS patterns
        $speakerSelectors = [
            '.speaker', '.speakers .speaker', '.speaker-card', '.speaker-item',
            '[class*="speaker"]', '.agenda-speaker', '.keynote-speaker',
            '.presenter', '.panelist', '[class*="presenter"]',
            '.person', '.profile-card', '[class*="profile"]',
            'article.team', '.team-member', '[class*="team-member"]',
        ];

        foreach ($speakerSelectors as $sel) {
            try {
                $nodes = $crawler->filter($sel);
                if ($nodes->count() > 0) {
                    $nodes->each(function (Crawler $node) use (&$people, $baseUrl) {
                        $p = $this->parsePersonNode($node, $baseUrl);
                        if ($p) $people[] = $p;
                    });
                    if (count($people) > 2) break;
                }
            } catch (\Exception $e) {}
        }

        // Strategy 2: Schema.org Person markup
        if (count($people) < 3) {
            try {
                $crawler->filter('[itemtype*="Person"], [itemtype*="schema.org/Person"]')
                    ->each(function (Crawler $node) use (&$people) {
                        $p = $this->parseSchemaOrg($node);
                        if ($p) $people[] = $p;
                    });
            } catch (\Exception $e) {}
        }

        // Strategy 3: Generic heuristic — find name+title pairs in page
        if (count($people) < 3) {
            $people = array_merge($people, $this->heuristicExtract($crawler));
        }

        return $people;
    }

    // ── Embedded JSON extraction (SPAs: Next.js, Nuxt, window state) ──────────

    private function extractFromEmbeddedJson(Crawler $crawler): array
    {
        $people = [];

        // ── Next.js: <script id="__NEXT_DATA__" type="application/json"> ──
        try {
            $nextDataNode = $crawler->filter('script#__NEXT_DATA__');
            if ($nextDataNode->count()) {
                $json = $nextDataNode->text('', false);
                $nextData = json_decode($json, true);
                if ($nextData) {
                    // Look for GraphQL speaker endpoint in pageProps
                    $gqlResult = $this->tryNextJsGraphQL($nextData);
                    if (count($gqlResult) >= 3) return $gqlResult;
                    $people = array_merge($people, $gqlResult);

                    // Also walk the JSON tree for embedded person objects
                    $walked = [];
                    $this->walkJsonForPeople($nextData, $walked, 0);
                    $people = array_merge($people, $walked);
                    if (count($people) >= 3) return $people;
                }
            }
        } catch (\Exception $e) {}

        // Collect all <script> tag contents
        $scripts = [];
        try {
            $crawler->filter('script')->each(function (Crawler $node) use (&$scripts) {
                $id   = $node->attr('id') ?? '';
                $type = $node->attr('type') ?? '';
                if ($id === '__NEXT_DATA__') return; // already handled
                $text = $node->text('', false);
                if ($text) $scripts[] = ['type' => $type, 'text' => $text];
            });
        } catch (\Exception $e) {}

        foreach ($scripts as $script) {
            // JSON-LD structured data
            if (stripos($script['type'], 'application/ld+json') !== false) {
                $people = array_merge($people, $this->parseJsonLd($script['text']));
                if (count($people) >= 3) return $people;
                continue;
            }

            $text = $script['text'];

            // Nuxt / Vue: window.__NUXT__ = {...}
            if (preg_match('/__NUXT__\s*=\s*(\{.+\})\s*;?\s*$/s', $text, $m)) {
                $people = array_merge($people, $this->searchJsonForPeople($m[1]));
                if (count($people) >= 3) return $people;
            }

            // Generic window.* assignments with speaker/people data
            if (preg_match('/window\.\w*(?:speakers?|people|delegates?|presenters?)\w*\s*=\s*(\[.+?\])\s*;/si', $text, $m)) {
                $people = array_merge($people, $this->searchJsonForPeople($m[1]));
                if (count($people) >= 3) return $people;
            }

            // Look for large JSON arrays that contain firstName/lastName or name+title
            if (preg_match_all('/\{[^{}]*"(?:firstName|first_name|givenName)"[^{}]*\}/s', $text, $matches)) {
                foreach ($matches[0] as $chunk) {
                    $p = $this->parseJsonPerson(json_decode($chunk, true) ?? []);
                    if ($p) $people[] = $p;
                }
                if (count($people) >= 3) return $people;
            }
        }

        return $people;
    }

    private function tryNextJsGraphQL(array $nextData): array
    {
        // Look for GraphQL endpoint + speaker query config in pageProps
        $props = $nextData['props']['pageProps'] ?? [];

        // Search recursively for a graphql endpoint pointing to speakers
        $gqlEndpoint = $this->findInArray($props, ['graphqlUrl', 'graphQLUrl', 'graphql_url', 'speakersUrl', 'apiUrl']);
        $queryName   = $this->findInArray($props, ['queryName', 'query_name', 'speakersQuery']);

        if (!$gqlEndpoint || !str_contains($gqlEndpoint, 'graphql')) return [];

        // Determine the query to use
        if (!$queryName) $queryName = 'allSpeakersWithSearch';

        // Common GraphQL speaker query shapes to try
        $queries = [
            // Money20/20 style
            ['query' => "query { $queryName { speakers { firstName lastName jobTitle company bio photo { url } } } }"],
            ['query' => "query { $queryName { nodes { firstName lastName jobTitle company } } }"],
            ['query' => "query { $queryName { items { name title organization } } }"],
            ['query' => "query { allSpeakers { firstName lastName jobTitle company } }"],
            ['query' => "query { speakers { firstName lastName jobTitle company } }"],
        ];

        foreach ($queries as $body) {
            try {
                $client = new \GuzzleHttp\Client([
                    \GuzzleHttp\RequestOptions::TIMEOUT => 15,
                    \GuzzleHttp\RequestOptions::VERIFY  => false,
                    \GuzzleHttp\RequestOptions::HEADERS => [
                        'Content-Type' => 'application/json',
                        'Accept'       => 'application/json',
                        'User-Agent'   => $this->userAgents[array_rand($this->userAgents)],
                        'Referer'      => 'https://europe.money2020.com/',
                        'Origin'       => 'https://europe.money2020.com',
                    ],
                ]);
                $response = $client->post($gqlEndpoint, [\GuzzleHttp\RequestOptions::JSON => $body]);
                $result   = json_decode((string)$response->getBody(), true);
                if (!$result || isset($result['errors'])) continue;

                $data    = $result['data'] ?? [];
                $people  = [];
                $this->walkJsonForPeople($data, $people, 0);
                if (count($people) >= 3) return $people;
            } catch (\Exception $e) {
                continue;
            }
        }

        return [];
    }

    private function findInArray(array $data, array $keys, int $depth = 0): string
    {
        if ($depth > 8) return '';
        foreach ($keys as $key) {
            if (isset($data[$key]) && is_string($data[$key])) return $data[$key];
        }
        // Also scan all string values for graphql URLs
        foreach ($data as $value) {
            if (is_string($value) && preg_match('|https?://[^\s"\']+/graphql|i', $value)) {
                return $value;
            }
            if (is_array($value)) {
                $found = $this->findInArray($value, $keys, $depth + 1);
                if ($found) return $found;
            }
        }
        return '';
    }

    private function parseJsonLd(string $json): array
    {
        $people = [];
        try {
            $data = json_decode($json, true);
            if (!$data) return [];

            // Could be array or object
            $items = isset($data[0]) ? $data : [$data];
            foreach ($items as $item) {
                if (!is_array($item)) continue;
                $type = $item['@type'] ?? '';
                if (in_array($type, ['Person', 'SpeakerRole'])) {
                    $p = $this->parseJsonPerson($item);
                    if ($p) $people[] = $p;
                }
                // Event with performers/speakers
                foreach (['performer', 'speaker', 'organizer', 'attendee'] as $key) {
                    if (isset($item[$key])) {
                        $entries = isset($item[$key][0]) ? $item[$key] : [$item[$key]];
                        foreach ($entries as $entry) {
                            if (is_array($entry)) {
                                $p = $this->parseJsonPerson($entry);
                                if ($p) $people[] = $p;
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {}
        return $people;
    }

    private function searchJsonForPeople(string $json): array
    {
        $people = [];
        try {
            $data = json_decode($json, true);
            if (!$data) return [];
            $this->walkJsonForPeople($data, $people, 0);
        } catch (\Exception $e) {}
        return $people;
    }

    private function walkJsonForPeople(mixed $node, array &$people, int $depth): void
    {
        if ($depth > 12 || !is_array($node)) return;

        // Check if this node looks like a person
        $p = $this->parseJsonPerson($node);
        if ($p) { $people[] = $p; return; }

        foreach ($node as $value) {
            if (is_array($value)) {
                $this->walkJsonForPeople($value, $people, $depth + 1);
            }
        }
    }

    private function parseJsonPerson(array $data): ?array
    {
        if (empty($data)) return null;

        // Try to find a name
        $name = '';
        $first = '';
        $last  = '';

        // Common name field variations
        foreach (['name', 'fullName', 'full_name', 'displayName', 'display_name'] as $k) {
            if (!empty($data[$k]) && is_string($data[$k])) { $name = trim($data[$k]); break; }
        }
        foreach (['firstName', 'first_name', 'givenName', 'given_name', 'forename'] as $k) {
            if (!empty($data[$k]) && is_string($data[$k])) { $first = trim($data[$k]); break; }
        }
        foreach (['lastName', 'last_name', 'familyName', 'family_name', 'surname'] as $k) {
            if (!empty($data[$k]) && is_string($data[$k])) { $last = trim($data[$k]); break; }
        }

        if (!$name && $first) $name = trim("$first $last");
        if (!$name) return null;
        if (strlen($name) < 3 || strlen($name) > 80) return null;

        // Must look like a real name (at least one capital letter, no HTML)
        if (strip_tags($name) !== $name) return null;
        if (!preg_match('/[A-Z]/', $name)) return null;

        // Filter out social media / UI component / non-human names
        $nameLower = strtolower($name);
        $socialBlacklist = ['facebook', 'twitter', 'linkedin', 'instagram', 'youtube', 'wechat',
                            'tiktok', 'pinterest', 'snapchat', 'whatsapp', 'telegram', 'discord',
                            'circle', 'icon', 'button', 'logo', 'link', 'arrow', 'menu', 'home',
                            'search', 'close', 'open', 'next', 'prev', 'back', 'forward'];
        foreach ($socialBlacklist as $word) {
            if (str_contains($nameLower, $word)) return null;
        }

        // Single-word "names" are only valid if we also have a title or company
        if (!str_contains($name, ' ') && empty($data['jobTitle'] ?? '') && empty($data['title'] ?? '')
            && empty($data['role'] ?? '') && empty($data['company'] ?? '') && empty($data['organization'] ?? '')) {
            return null;
        }

        // Must look like a person name: starts with uppercase, letters only (with hyphens/apostrophes allowed)
        if (!preg_match('/^[A-Z][a-zA-ZÀ-ÿ\'\-]+(?: [A-Za-zÀ-ÿ\'\-\.]+)+$/', $name)) return null;

        if (!$first) [$first, $last] = $this->splitName($name);

        // Title / job
        $title = '';
        foreach (['jobTitle', 'job_title', 'title', 'role', 'position', 'headline'] as $k) {
            if (!empty($data[$k]) && is_string($data[$k])) { $title = trim($data[$k]); break; }
        }

        // Company
        $company = '';
        foreach (['company', 'organization', 'organisation', 'employer', 'companyName', 'company_name'] as $k) {
            if (!empty($data[$k]) && is_string($data[$k])) { $company = trim($data[$k]); break; }
        }
        // worksFor can be object or string
        if (!$company && isset($data['worksFor'])) {
            $wf = $data['worksFor'];
            $company = is_array($wf) ? ($wf['name'] ?? '') : (is_string($wf) ? $wf : '');
        }

        // Email
        $email = '';
        foreach (['email', 'emailAddress', 'email_address'] as $k) {
            if (!empty($data[$k]) && is_string($data[$k]) && str_contains($data[$k], '@')) {
                $email = trim($data[$k]); break;
            }
        }

        // Photo
        $photo = '';
        foreach (['image', 'photo', 'avatar', 'photoUrl', 'photo_url', 'imageUrl', 'image_url', 'profileImage'] as $k) {
            if (!empty($data[$k])) {
                $photo = is_array($data[$k]) ? ($data[$k]['url'] ?? '') : (string)$data[$k];
                if ($photo && !str_starts_with($photo, 'data:')) break;
                $photo = '';
            }
        }

        return [
            'first_name' => $first,
            'last_name'  => $last,
            'title'      => $this->cleanText($title, 80),
            'company'    => $this->cleanText($company, 100),
            'email'      => $email,
            'seniority'  => $this->guessSeniority($title),
            'country'    => '',
            'photo_url'  => $photo,
        ];
    }

    private function extractTeam(Crawler $crawler, string $baseUrl): array
    {
        $people = [];

        // Strategy 0: Embedded JSON
        $jsonPeople = $this->extractFromEmbeddedJson($crawler);
        if (count($jsonPeople) >= 3) return $jsonPeople;
        $people = array_merge($people, $jsonPeople);

        $teamSelectors = [
            '.team-member', '.team__member', '[class*="team-member"]',
            '.staff-member', '.employee', '.people-item',
            '.bio-card', '.bio', '[class*="bio"]',
            '.person', '.member', '[class*="member"]',
        ];

        foreach ($teamSelectors as $sel) {
            try {
                $nodes = $crawler->filter($sel);
                if ($nodes->count() > 0) {
                    $nodes->each(function (Crawler $node) use (&$people, $baseUrl) {
                        $p = $this->parsePersonNode($node, $baseUrl);
                        if ($p) $people[] = $p;
                    });
                    if (count($people) > 2) break;
                }
            } catch (\Exception $e) {}
        }

        if (count($people) < 3) {
            $people = array_merge($people, $this->heuristicExtract($crawler));
        }

        return $people;
    }

    // ── Node parsers ──────────────────────────────────────────────────────────

    private function parsePersonNode(Crawler $node, string $baseUrl): ?array
    {
        $name    = $this->extractText($node, ['h1','h2','h3','h4','h5','.name','[class*="name"]','.title','strong']);
        $title   = $this->extractText($node, ['.role','[class*="role"]','.position','[class*="position"]','.job','[class*="job"]','.subtitle','p']);
        $company = $this->extractText($node, ['.company','[class*="company"]','.organization','[class*="org"]','.employer']);
        $email   = $this->extractEmail($node->html());
        $photo   = $this->extractImage($node, $baseUrl);

        if (!$name || strlen($name) < 3 || strlen($name) > 60) return null;

        // Skip if looks like a UI label not a name
        if (preg_match('/^(read more|learn more|view|click|download|register|submit|sign|log|get|contact|home|about|news|event|agenda)$/i', trim($name))) {
            return null;
        }

        [$first, $last] = $this->splitName($name);

        // Avoid using the name as title too
        if (strtolower(trim($title)) === strtolower(trim($name))) $title = '';

        return [
            'first_name' => $first,
            'last_name'  => $last,
            'title'      => $this->cleanText($title, 80),
            'company'    => $this->cleanText($company, 100),
            'email'      => $email,
            'seniority'  => $this->guessSeniority($title),
            'country'    => '',
            'photo_url'  => $photo,
        ];
    }

    private function parseSchemaOrg(Crawler $node): ?array
    {
        $name    = $this->extractText($node, ['[itemprop="name"]']) ?: '';
        $title   = $this->extractText($node, ['[itemprop="jobTitle"]']) ?: '';
        $company = $this->extractText($node, ['[itemprop="worksFor"]','[itemprop="memberOf"]']) ?: '';
        $email   = '';
        try { $email = $node->filter('[itemprop="email"]')->attr('content') ?? ''; } catch (\Exception $e) {}

        if (!$name) return null;
        [$first, $last] = $this->splitName($name);

        return [
            'first_name' => $first,
            'last_name'  => $last,
            'title'      => $this->cleanText($title, 80),
            'company'    => $this->cleanText($company, 100),
            'email'      => $email,
            'seniority'  => $this->guessSeniority($title),
            'country'    => '',
            'photo_url'  => '',
        ];
    }

    private function heuristicExtract(Crawler $crawler): array
    {
        $people = [];

        // Look for elements that contain a proper name followed by a title/role
        $namePattern = '/^[A-Z][a-záéíóúàèìòùäëïöü\-]+(?:\s+(?:van|de|del|von|der|la|le|al|el|bin|binti))?(?:\s+[A-Z][a-záéíóúàèìòùäëïöü\-]+){1,3}$/u';

        $seniorityWords = ['CEO','CTO','CFO','COO','CMO','CPO','CRO','CISO','VP','SVP','EVP',
                           'Director','Head','Manager','President','Founder','Partner',
                           'Principal','Lead','Chief','Officer'];

        try {
            $crawler->filter('h3,h4')->each(function (Crawler $h) use (&$people, $namePattern, $crawler, $seniorityWords) {
                $text = trim($h->text());
                if (!preg_match($namePattern, $text)) return;

                // Look at the next sibling or parent's next text
                $title   = '';
                $company = '';

                // Try next sibling p/span/div
                try {
                    $next = $h->nextAll()->filter('p,span,div,.role,.title,.position')->first();
                    if ($next->count()) $title = trim($next->text());
                } catch (\Exception $e) {}

                // Try parent container
                try {
                    $parent = $h->ancestors()->filter('[class]')->first();
                    if ($parent->count()) {
                        $ptexts = [];
                        $parent->filter('p,span')->each(function (Crawler $el) use (&$ptexts, $text) {
                            $t = trim($el->text());
                            if ($t && $t !== $text && strlen($t) < 120) $ptexts[] = $t;
                        });
                        if (!$title && !empty($ptexts)) $title = $ptexts[0] ?? '';
                        if (count($ptexts) > 1) $company = $ptexts[1] ?? '';
                    }
                } catch (\Exception $e) {}

                // Only include if we got at least a title or the name has seniority keywords
                $hasSeniority = false;
                foreach ($seniorityWords as $w) {
                    if (stripos($title . ' ' . $company, $w) !== false) { $hasSeniority = true; break; }
                }

                [$first, $last] = $this->splitName($text);
                if (!$last && !$hasSeniority) return; // Skip single-word "names"

                $people[] = [
                    'first_name' => $first,
                    'last_name'  => $last,
                    'title'      => $this->cleanText($title, 80),
                    'company'    => $this->cleanText($company, 100),
                    'email'      => $this->extractEmail($title . ' ' . $company),
                    'seniority'  => $this->guessSeniority($title),
                    'country'    => '',
                    'photo_url'  => '',
                ];
            });
        } catch (\Exception $e) {}

        return $people;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function extractText(Crawler $node, array $selectors): string
    {
        foreach ($selectors as $sel) {
            try {
                $found = $node->filter($sel)->first();
                if ($found->count()) {
                    $text = trim($found->text());
                    if ($text) return $text;
                }
            } catch (\Exception $e) {}
        }
        return '';
    }

    private function extractEmail(string $html): string
    {
        if (preg_match('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', strip_tags($html), $m)) {
            return $m[0];
        }
        return '';
    }

    private function extractImage(Crawler $node, string $baseUrl): string
    {
        try {
            $img = $node->filter('img')->first();
            if ($img->count()) {
                $src = $img->attr('data-src') ?: $img->attr('src') ?: '';
                if ($src && !str_starts_with($src, 'data:')) {
                    return $src;
                }
            }
        } catch (\Exception $e) {}
        return '';
    }

    private function splitName(string $name): array
    {
        $name  = trim(preg_replace('/\s+/', ' ', $name));
        $parts = explode(' ', $name);
        if (count($parts) === 1) return [$parts[0], ''];
        $first = array_shift($parts);
        return [$first, implode(' ', $parts)];
    }

    private function cleanText(string $text, int $max): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));
        return mb_substr($text, 0, $max);
    }

    private function guessSeniority(string $title): string
    {
        $t = strtolower($title);
        if (preg_match('/\b(ceo|cto|cfo|coo|cmo|cpo|cro|ciso|chief\s+\w+\s+officer)\b/', $t)) return 'C-Suite';
        if (preg_match('/\bvp\b|vice\s+president/', $t)) return 'VP';
        if (preg_match('/\bdirector\b/', $t)) return 'Director';
        if (preg_match('/\bhead\s+of\b/', $t)) return 'Head';
        if (preg_match('/\bmanager\b/', $t)) return 'Manager';
        if (preg_match('/\bfounder\b|\bco-founder\b/', $t)) return 'Founder';
        if (preg_match('/\bpartner\b/', $t)) return 'Partner';
        if (preg_match('/\bpresident\b/', $t)) return 'President';
        return '';
    }
}
