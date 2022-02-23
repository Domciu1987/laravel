<?php

namespace App\Http\Controllers;

use App\Contractors;
use App\Invoice;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index()
    {
    
            $contractors = Contractors::where('user_id', Auth::user()->id)
                ->count();

            $invoice = Invoice::where('user_id', Auth::user()->id)
                ->count();

            $total = Invoice::where('user_id', Auth::user()->id)
                ->sum('brutto');

            return view('home', [
                'contractors' => $contractors,
                'invoice'     => $invoice,
                'total'       => $total,
            ]);
    }
}
