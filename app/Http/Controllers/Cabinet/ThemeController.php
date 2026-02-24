<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ThemeController extends Controller
{
    public function switch(Request $request)
    {
        $theme = $request->input('theme', 'light');
        
        if (!in_array($theme, ['light', 'dark'])) {
            $theme = 'light';
        }
        
        if (Auth::check()) {
            $user = Auth::user();
            $user->theme = $theme;
            $user->save();
        }
        
        session(['user_theme' => $theme]);
        
        if ($request->ajax()) {
            return response()->json(['success' => true, 'theme' => $theme]);
        }
        
        return back();
    }
}