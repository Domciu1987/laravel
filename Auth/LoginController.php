<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Validator;
use Session;
use Mail;
use App\User;

class LoginController extends Controller
{
    /**
     * Display view to the login user.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('login');
    }

    /**
     * Login to user account.
     *
     * @param  Request $request
     *
     * @return \Illuminate\View\View
     */
    public function store(Request $request)
    {
        $email    = Input::get('email');
        $password = Input::get('password');
        
        $validator = Validator::make(
            $request->all(),
            [
                'email'    => 'required|max:30|email',
                'password' => 'required'
            ]
        );

        if (Auth::attempt(['email' => $email, 'password' => $password])) {
            return redirect()->route('home.index');
        } else {
            return redirect()
            ->back()
            ->withErrors($validator)
            ->withErrors(['Pole login/hasło jest niepoprawne lub konto jest niezarejestrowane!'])
            ->withInput();
        }
    }

    /**
     * Delete user.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy()
    {
        Auth::logout();

        return redirect()->route('home.index');
    }
}
