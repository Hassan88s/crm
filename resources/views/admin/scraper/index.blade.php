@extends('layouts.admin-sidebar')
@section('title', 'Web Scraper')
@section('page-title', 'Web Scraper')

@section('extra-styles')
<style>
    .scraper-grid { display:grid; grid-template-columns:1fr 320px; gap:1.25rem; align-items:start; }

    .form-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; }
    .card-head { padding:1rem 1.25rem; border-bottom:1px solid #f1f5f9; }
    .card-head h3 { font-size:0.95rem; font-weight:700; color:#0f172a; margin-bottom:2px; }
    .card-head p { font-size:0.78rem; color:#94a3b8; }
    .card-body { padding:1.25rem; }

    .form-group { display:flex; flex-direction:column; gap:0.4rem; margin-bottom:1rem; }
    .form-label { font-size:0.85rem; font-weight:600; color:#374151; }
    .form-hint { font-size:0.74rem; color:#94a3b8; }
    .form-input, .form-select {
        width:100%; padding:0.65rem 0.9rem;
        border:1.5px solid #d1d5db; border-radius:8px;
        font-size:0.875rem; outline:none; color:#0f172a;
        font-family:inherit; background:#fff;
        transition:border-color 140ms, box-shadow 140ms;
    }
    .form-input:focus, .form-select:focus { border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,0.1); }
    .error-msg { font-size:0.78rem; color:#dc2626; }

    /* Tabs */
    .tab-bar { display:flex; gap:4px; margin-bottom:1.25rem; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:4px; }
    .tab-btn { flex:1; padding:0.55rem 1rem; border-radius:7px; font-size:0.84rem; font-weight:600; cursor:pointer; border:none; background:transparent; color:#64748b; transition:all 140ms; }
    .tab-btn.active { background:#fff; color:#2563eb; box-shadow:0 1px 3px rgba(0,0,0,0.08); }
    .tab-pane { display:none; }
    .tab-pane.active { display:block; }

    /* Mode cards */
    .mode-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
    .mode-card {
        border:2px solid #e2e8f0; border-radius:10px; padding:0.75rem 1rem;
        cursor:pointer; transition:all 140ms; display:flex; align-items:flex-start; gap:8px;
    }
    .mode-card:hover { border-color:#93c5fd; background:#f8faff; }
    .mode-card.selected { border-color:#2563eb; background:#eff6ff; }
    .mode-card input[type=radio] { margin-top:2px; accent-color:#2563eb; flex-shrink:0; }
    .mode-card-label { font-size:0.82rem; font-weight:600; color:#374151; }
    .mode-card-sub { font-size:0.72rem; color:#94a3b8; margin-top:1px; }

    /* Target sites */
    .target-chip {
        display:inline-flex; align-items:center; gap:5px;
        background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px;
        padding:4px 9px; font-size:0.75rem; color:#374151; cursor:pointer;
        transition:all 120ms; margin:2px; text-decoration:none;
    }
    .target-chip:hover { background:#eff6ff; border-color:#93c5fd; color:#1d4ed8; }
    .target-chip svg { width:12px; height:12px; }

    /* Alert */
    .alert-error { background:#fef2f2; border:1px solid #fecaca; color:#dc2626; border-radius:8px; padding:0.75rem 1rem; font-size:0.875rem; margin-bottom:1rem; display:flex; align-items:flex-start; gap:8px; }

    /* Tips */
    .tip-box { background:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:1rem; font-size:0.78rem; color:#78350f; line-height:1.6; }
    .tip-box strong { color:#92400e; display:block; margin-bottom:4px; }

    /* Discover */
    .discover-result {
        border:1px solid #e2e8f0; border-radius:10px; padding:1rem;
        margin-bottom:0.75rem; display:flex; align-items:flex-start;
        gap:0.75rem; transition:border-color 140ms;
    }
    .discover-result:hover { border-color:#93c5fd; background:#f8faff; }
    .discover-result-icon { width:36px; height:36px; border-radius:8px; background:#eff6ff; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .discover-result-name { font-size:0.88rem; font-weight:700; color:#0f172a; }
    .discover-result-desc { font-size:0.76rem; color:#64748b; margin-top:2px; }
    .discover-result-url { font-size:0.73rem; color:#94a3b8; margin-top:3px; word-break:break-all; }
    .discover-badge { display:inline-block; padding:1px 7px; border-radius:20px; font-size:0.68rem; font-weight:600; background:#eff6ff; color:#2563eb; margin-bottom:4px; }
    .discover-scrape-btn {
        margin-top:6px; display:inline-flex; align-items:center; gap:5px;
        padding:5px 12px; border-radius:6px; font-size:0.75rem; font-weight:600;
        background:#2563eb; color:#fff; border:none; cursor:pointer; text-decoration:none;
        transition:background 140ms;
    }
    .discover-scrape-btn:hover { background:#1d4ed8; }
    .spinner { display:inline-block; width:16px; height:16px; border:2px solid #93c5fd; border-top-color:#2563eb; border-radius:50%; animation:spin 0.7s linear infinite; vertical-align:middle; margin-right:6px; }
    @keyframes spin { to { transform:rotate(360deg); } }
    .discover-error { background:#fef2f2; border:1px solid #fecaca; color:#dc2626; border-radius:8px; padding:0.75rem 1rem; font-size:0.85rem; }
    .no-key-notice { background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:0.85rem 1rem; font-size:0.83rem; color:#92400e; display:flex; align-items:center; gap:8px; }
</style>
@endsection

@section('content')

<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem;">
    <div>
        <h1 style="font-size:1.3rem; font-weight:700; color:#0f172a;">Web Scraper</h1>
        <p style="color:#64748b; font-size:0.875rem; margin-top:3px;">Extract speaker profiles from fintech, banking & digital event websites.</p>
    </div>
</div>

@if($errors->any())
    <div class="alert-error">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:1px;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        <div>{{ $errors->first() }}</div>
    </div>
@endif

{{-- Tab Bar --}}
<div class="tab-bar">
    <button class="tab-btn active" onclick="switchTab('scrape', this)">
        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle;margin-right:5px;"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        Scrape a URL
    </button>
    <button class="tab-btn" onclick="switchTab('discover', this)">
        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle;margin-right:5px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
        Discover Events with AI
    </button>
</div>

{{-- ── TAB: Scrape a URL ── --}}
<div id="tab-scrape" class="tab-pane active">
<div class="scraper-grid">

    <div>
        <div class="form-card">
            <div class="card-head">
                <h3>Scrape a Website</h3>
                <p>Paste the URL of a speakers page, team page, or agenda and we'll extract names and titles.</p>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.scraper.scrape') }}" method="POST" id="scrape-form">
                    @csrf

                    <div class="form-group">
                        <label class="form-label">Target URL <span style="color:#ef4444;">*</span></label>
                        <span class="form-hint">Paste the full URL of the speakers/team/agenda page</span>
                        <input type="url" name="url" id="url-input"
                               class="form-input {{ $errors->has('url') ? 'is-error' : '' }}"
                               value="{{ old('url') }}"
                               placeholder="https://fintechconnect.com/speakers">
                        @error('url') <span class="error-msg">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Page Type</label>
                        <span class="form-hint">Tell the scraper what kind of page this is</span>
                        <div class="mode-grid" style="margin-top:6px;">
                            @foreach([
                                ['auto',     'Auto Detect',    'Let scraper figure it out'],
                                ['speakers', 'Speakers Page',  'Conference/event speaker listings'],
                                ['team',     'Team / About',   'Company team or people page'],
                                ['agenda',   'Agenda / Schedule', 'Event agenda with speaker names'],
                            ] as [$val, $label, $sub])
                            <label class="mode-card {{ old('mode','auto') === $val ? 'selected' : '' }}" onclick="selectMode(this)">
                                <input type="radio" name="mode" value="{{ $val }}" {{ old('mode','auto') === $val ? 'checked' : '' }}>
                                <div>
                                    <div class="mode-card-label">{{ $label }}</div>
                                    <div class="mode-card-sub">{{ $sub }}</div>
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    @if($events->isNotEmpty())
                    <div class="form-group">
                        <label class="form-label">Assign to Event <span style="color:#94a3b8; font-weight:400;">(optional)</span></label>
                        <select name="event_id" class="form-select">
                            <option value="">— No event —</option>
                            @foreach($events as $event)
                                <option value="{{ $event->id }}" {{ old('event_id') == $event->id ? 'selected' : '' }}>
                                    {{ $event->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div style="padding-top:1rem; border-top:1px solid #f1f5f9; margin-top:0.5rem;">
                        <button type="submit" class="btn" id="scrape-btn">
                            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <span id="btn-text">Scrape Page</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div>
        <div class="form-card" style="margin-bottom:1rem;">
            <div class="card-head">
                <h3>Quick Targets</h3>
                <p>Popular fintech & banking speaker sources</p>
            </div>
            <div class="card-body">
                @foreach([
                    ['https://www.fintechconnect.com/speakers', 'FinTech Connect Speakers'],
                    ['https://www.moneylive.co.uk/speakers/', 'MoneyLive Speakers'],
                    ['https://www.sibos.com/programme/speakers', 'Sibos Speakers'],
                    ['https://www.finovate.com/finovatefallspeakers/', 'Finovate Fall Speakers'],
                    ['https://www.bankingtech.com/events/', 'Banking Tech Events'],
                    ['https://www.lendit.com/usa/speakers/', 'LendIt USA Speakers'],
                    ['https://events.finextra.com/speakers', 'Finextra Speakers'],
                    ['https://www.digitalfinanceforum.com/speakers', 'Digital Finance Forum'],
                ] as [$url, $label])
                <a href="#" class="target-chip" onclick="setUrl('{{ $url }}'); return false;">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    {{ $label }}
                </a>
                @endforeach
            </div>
        </div>

        <div class="tip-box">
            <strong>💡 Tips for best results</strong>
            <ul style="padding-left:1rem; margin:0;">
                <li>Use the direct <strong>speakers page</strong> URL, not the homepage</li>
                <li>Conference sites like Finovate, Sibos, MoneyLive work well</li>
                <li>Company "team" pages usually yield full bios with titles</li>
                <li>Some sites block scrapers — try the agenda page as fallback</li>
                <li>Results are a <strong>preview</strong> — you choose which to import</li>
            </ul>
        </div>
    </div>

</div>
</div>

{{-- ── TAB: Discover Events with AI ── --}}
<div id="tab-discover" class="tab-pane">
<div class="scraper-grid">

    <div>
        <div class="form-card">
            <div class="card-head">
                <h3>Discover Events with AI</h3>
                <p>Describe the type of events you're looking for and AI will suggest relevant conference websites to scrape.</p>
            </div>
            <div class="card-body">
                @if(!$hasOpenAIKey)
                <div class="no-key-notice" style="margin-bottom:1rem;">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <div>OpenAI API key not set. <a href="{{ route('admin.settings') }}#ai" style="color:#92400e; font-weight:600;">Go to Settings → AI</a> to add your key.</div>
                </div>
                @endif

                <div class="form-group">
                    <label class="form-label">Keywords / Topic</label>
                    <span class="form-hint">Describe the events or topics you want to find speakers for</span>
                    <input type="text" id="discover-input" class="form-input"
                           placeholder="e.g. banking summit Europe 2025, fintech payments conference, digital banking Asia"
                           {{ !$hasOpenAIKey ? 'disabled' : '' }}>
                </div>

                <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:1rem;">
                    @foreach(['Fintech Europe', 'Banking Summit 2025', 'Digital Payments', 'Crypto & Blockchain', 'Open Banking', 'Lending & Credit', 'Insurtech', 'RegTech'] as $kw)
                    <button onclick="setKeyword('{{ $kw }}')" class="target-chip" style="cursor:pointer; border:none;" {{ !$hasOpenAIKey ? 'disabled' : '' }}>{{ $kw }}</button>
                    @endforeach
                </div>

                <button onclick="discoverEvents()" class="btn" id="discover-btn" {{ !$hasOpenAIKey ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : '' }}>
                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    <span id="discover-btn-text">Find Events</span>
                </button>

                <div id="discover-loading" style="display:none; margin-top:1rem; color:#64748b; font-size:0.85rem;">
                    <span class="spinner"></span> AI is searching for events…
                </div>
                <div id="discover-error" class="discover-error" style="display:none; margin-top:1rem;"></div>
            </div>
        </div>

        {{-- Results --}}
        <div id="discover-results" style="margin-top:1.25rem; display:none;">
            <div style="font-size:0.85rem; font-weight:600; color:#374151; margin-bottom:0.75rem;">
                <span id="discover-count"></span> events found — click "Scrape Speakers" to extract contacts
            </div>
            <div id="discover-list"></div>
        </div>
    </div>

    <div>
        <div class="tip-box" style="margin-bottom:1rem;">
            <strong>🤖 How AI Discovery works</strong>
            <ul style="padding-left:1rem; margin:0;">
                <li>Enter keywords describing events you want (e.g. "fintech Europe 2025")</li>
                <li>AI suggests up to 12 relevant conference websites</li>
                <li>Click "Scrape Speakers" on any result to extract contacts</li>
                <li>Import discovered speakers directly into your CRM</li>
            </ul>
        </div>
        <div class="tip-box">
            <strong>💡 Good keyword examples</strong>
            <ul style="padding-left:1rem; margin:0;">
                <li>"banking summit Europe 2025"</li>
                <li>"fintech payments Asia conference"</li>
                <li>"digital banking Middle East"</li>
                <li>"crypto blockchain conference US"</li>
                <li>"insurtech regtech summit"</li>
            </ul>
        </div>
    </div>

</div>
</div>

<script>
function switchTab(name, btn) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
}
function selectMode(label) {
    document.querySelectorAll('.mode-card').forEach(c => c.classList.remove('selected'));
    label.classList.add('selected');
    label.querySelector('input[type=radio]').checked = true;
}
function setUrl(url) {
    document.getElementById('url-input').value = url;
    document.getElementById('url-input').focus();
}
function setKeyword(kw) {
    document.getElementById('discover-input').value = kw;
    document.getElementById('discover-input').focus();
}
document.getElementById('scrape-form').addEventListener('submit', function() {
    const btn = document.getElementById('scrape-btn');
    document.getElementById('btn-text').textContent = 'Scraping…';
    btn.disabled = true;
    btn.style.opacity = '0.7';
});

async function discoverEvents() {
    const kw  = document.getElementById('discover-input').value.trim();
    if (!kw) { document.getElementById('discover-input').focus(); return; }

    const btn     = document.getElementById('discover-btn');
    const loading = document.getElementById('discover-loading');
    const errBox  = document.getElementById('discover-error');
    const results = document.getElementById('discover-results');

    btn.disabled = true;
    document.getElementById('discover-btn-text').textContent = 'Searching…';
    loading.style.display = 'block';
    errBox.style.display  = 'none';
    results.style.display = 'none';

    try {
        const resp = await fetch('{{ route("admin.scraper.discover") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ keywords: kw }),
        });

        const data = await resp.json();

        if (!resp.ok || data.error) {
            errBox.textContent  = data.error || 'An error occurred.';
            errBox.style.display = 'block';
        } else {
            renderDiscoverResults(data.sites);
        }
    } catch(e) {
        errBox.textContent  = 'Network error: ' + e.message;
        errBox.style.display = 'block';
    } finally {
        btn.disabled = false;
        document.getElementById('discover-btn-text').textContent = 'Find Events';
        loading.style.display = 'none';
    }
}

function renderDiscoverResults(sites) {
    const list    = document.getElementById('discover-list');
    const results = document.getElementById('discover-results');
    const count   = document.getElementById('discover-count');

    count.textContent = sites.length;
    list.innerHTML = '';

    const colors = ['#eff6ff','#f0fdf4','#fdf4ff','#fff7ed','#f0f9ff','#fef2f2'];
    const icons  = ['#2563eb','#16a34a','#9333ea','#ea580c','#0284c7','#dc2626'];

    sites.forEach((site, i) => {
        const c = colors[i % colors.length];
        const ic = icons[i % icons.length];
        const abbr = (site.name || 'E').charAt(0).toUpperCase();

        const el = document.createElement('div');
        el.className = 'discover-result';
        el.innerHTML = `
            <div class="discover-result-icon" style="background:${c}; color:${ic}; font-weight:700; font-size:0.95rem;">
                ${abbr}
            </div>
            <div style="flex:1; min-width:0;">
                <div class="discover-badge">${escHtml(site.type || 'Speakers Page')}</div>
                <div class="discover-result-name">${escHtml(site.name || '')}</div>
                <div class="discover-result-desc">${escHtml(site.description || '')}</div>
                <div class="discover-result-url">${escHtml(site.url || '')}</div>
                <button class="discover-scrape-btn" onclick="scrapeDiscoveredSite('${escAttr(site.url)}')">
                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    Scrape Speakers
                </button>
            </div>
        `;
        list.appendChild(el);
    });

    results.style.display = 'block';
}

function scrapeDiscoveredSite(url) {
    // Switch to scrape tab and pre-fill URL then submit
    switchTab('scrape', document.querySelector('.tab-btn'));
    document.getElementById('url-input').value = url;
    document.getElementById('scrape-form').submit();
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function escAttr(s) {
    return String(s).replace(/'/g, "\\'");
}
</script>
@endsection
