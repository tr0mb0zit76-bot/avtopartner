<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        return view('cabinet.reports.index');
    }
    
    public function salary()
    {
        return view('cabinet.reports.salary');
    }
    
    public function kpi()
    {
        return view('cabinet.reports.kpi');
    }
}
