<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Speaker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check() && Auth::user()->email === config('admin.email')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($data)) {
            $request->session()->regenerate();

            if (Auth::user()->email !== config('admin.email')) {
                Auth::logout();
                return back()->withErrors(['email' => 'Unauthorized for admin area.']);
            }

            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors(['email' => 'Invalid email or password.'])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }

    public function dashboard()
    {
        $totalEvents     = Event::count();
        $confirmedEvents = Event::where('status', 'confirmed')->count();
        $upcomingEvents  = Event::where('date', '>=', now()->toDateString())->count();
        $recentEvents    = Event::latest()->take(5)->get();
        $totalSpeakers    = Speaker::count();
        $headSpeakers     = Speaker::whereRaw('LOWER(seniority) LIKE ?', ['%head%'])->count();
        $csuiteSpeakers   = Speaker::whereRaw('LOWER(seniority) LIKE ?', ['%c suite%'])
                                   ->orWhereRaw('LOWER(seniority) LIKE ?', ['%c-suite%'])
                                   ->orWhereRaw('LOWER(seniority) IN (?)', ['ceo,cto,coo,cmo,cfo'])->count();
        $managerSpeakers  = Speaker::whereRaw('LOWER(seniority) LIKE ?', ['%manager%'])->count();
        $countriesCount   = Speaker::whereNotNull('country')->distinct('country')->count('country');
        $recentSpeakers   = Speaker::latest()->take(4)->get();

        return view('admin.dashboard', compact(
            'totalEvents', 'confirmedEvents', 'upcomingEvents', 'recentEvents',
            'totalSpeakers', 'headSpeakers', 'csuiteSpeakers', 'managerSpeakers',
            'countriesCount', 'recentSpeakers'
        ));
    }

    public function customers()
    {
        return view('admin.customers');
    }

    public function leads()
    {
        return view('admin.leads');
    }

    public function deals()
    {
        return view('admin.deals');
    }

    public function events()
    {
        return view('admin.events');
    }

    public function speakers()
    {
        return view('admin.speakers');
    }

    public function sessions()
    {
        return view('admin.sessions');
    }

    public function reports()
    {
        return view('admin.reports');
    }

    public function settings()
    {
        return view('admin.settings');
    }
}
