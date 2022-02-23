<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;

class AboutmeController extends Controller
{
    public function index()
    {
        return view('aboutme', [
            'data' => Auth::user(),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $email        = Input::get('email');
        $name         = Input::get('company');
        $city         = Input::get('city');
        $street       = Input::get('street');
        $numberstreet = Input::get('numberstreet');
        $postcode     = Input::get('postcode');
        $phone        = Input::get('phone');
        $nip          = Input::get('nip');
        $bankaccount  = Input::get('bankaccount');

        $this->validate($request, [
            'company'      => 'required|min:3',
            'email'        => 'required',
            'phone'        => 'required|numeric|max:999999999',
            'street'       => 'required|alpha',
            'numberstreet' => 'required|numeric',
            'city'         => 'required|alpha',
            'postcode'     => 'required',
            'nip'          => 'required|numeric|max:9999999999',
            'bankaccount'  => 'required|numeric',
        ]);

        User::where('id', $id)->update([
            'company'       => $name,
            'email'         => $email,
            'phone'         => $phone,
            'street'        => $street,
            'street_number' => $numberstreet,
            'city'          => $city,
            'code'          => $postcode,
            'nip'           => $nip,
            'bank_account'  => $bankaccount,
        ]);

        return redirect()->route('aboutme.index');
    }
}
