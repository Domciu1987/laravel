<?php

namespace App\Http\Controllers\Auth;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Hash;
use Illuminate\Support\Facades\Input;
use Mail;

class RegisterController extends Controller
{
    /**
     * Index
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('register');
    }

    /**
     * Store
     *
     * @param  Request $request
     *
     * @return \Illuminate\View\View
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'confirmpassword' => 'required|min:3',
            'password'        => 'required|min:3',
            'email'           => 'required|unique:users,email',
        ]);

        if ($validator->fails()) {
            return redirect()->route('register.index')
                ->withErrors($validator)
                ->withInput();
        } else {
            $user           = new User;
            $user->email    = Input::get('email');
            $user->password = Hash::make(Input::get('password'));
            $user->ip       = $request->getClientIp();
            $user->web      = $request->header('User-Agent');
            $user->save();

            return view('login')->withErrors(
                [
                    'field_name' => [
                        'Twoje konto zostało zarejestrowane. Możesz się zalogować'
                    ]
                ]
            );
        }
    }
}
