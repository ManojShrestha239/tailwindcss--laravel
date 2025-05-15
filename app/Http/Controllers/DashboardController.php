<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function dashboard()
    {
        return view('dashboard.main');
    }

    public function calendar()
    {
        return view('dashboard.components.calendar');
    }
    public function profile()
    {
        return view('dashboard.components.profile');
    }
    public function baseTable()
    {
        return view('dashboard.components.base-table');
    }

    public function notfound()
    {
        return view('dashboard.pages.404');
    }

    public function maintenance()
    {
        return view('dashboard.pages.maintenance');
    }
}
