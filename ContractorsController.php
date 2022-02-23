<?php

namespace App\Http\Controllers;

use App\Contractors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use DB;

class ContractorsController extends Controller
{
    public function index()
    {
        $contractors = Contractors::where('user_id', Auth::user()->id)->paginate(10);
        return view('contractors.list', [
            'contractors' => $contractors,
        ]);
    }

    public function show(int $id)
    {
        $contractors = Contractors::where('id', $id)->first();

        if (isset($contractors)) {
            return view('contractors.show', [
                'contractors' => $contractors,
            ]);
        } else {
            return view('contractors.show', [
                'contractors' => $contractors,
            ]);
        }
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name'         => 'required|min:3',
            'email'        => 'required|unique:users,email',
            'phone'        => 'required|numeric|max:999999999',
            'street'       => 'required|alpha',
            'numberstreet' => 'required|numeric',
            'city'         => 'required|alpha',
            'postcode'     => 'required',
            'nip'          => 'required|numeric|max:9999999999',
            'bankaccount'  => 'required|numeric',

        ]);

        $contractors                = new Contractors;
        $contractors->user_id       = Auth::user()->id;
        $contractors->company       = Input::get('name');
        $contractors->email         = Input::get('email');
        $contractors->phone         = Input::get('phone');
        $contractors->street        = Input::get('street');
        $contractors->street_number = Input::get('numberstreet');
        $contractors->city          = Input::get('city');
        $contractors->code          = Input::get('postcode');
        $contractors->nip           = Input::get('nip');
        $contractors->bank_account  = Input::get('bankaccount');
        $contractors->save();
        return redirect()->route('contractors.index');
    }

    public function create()
    {
        return view('contractors.add');
    }

    public function destroy(int $id)
    {
            $contractors = Contractors::where('id', $id)
            ->where('user_id', Auth::user()->id)
            ->first();
        if (isset($contractors)) {
            $contractors->delete();
        }

        return redirect()->route('contractors.index');
    }

    public function update(Request $request, int $id)
    {
        $name         = Input::get('name');
        $email        = Input::get('email');
        $phone        = Input::get('phone');
        $street       = Input::get('street');
        $numberstreet = Input::get('numberstreet');
        $city         = Input::get('city');
        $postcode     = Input::get('postcode');
        $nip          = Input::get('nip');
        $bankaccount  = Input::get('bankaccount');

        $this->validate($request, [
            'name'         => 'required|min:3',
            'email'        => 'required|unique:users,email',
            'phone'        => 'required|numeric|max:999999999',
            'street'       => 'required|alpha',
            'numberstreet' => 'required|numeric',
            'city'         => 'required|alpha',
            'postcode'     => 'required',
            'nip'          => 'required|numeric',
            'bankaccount'  => 'required|numeric',
        ]);

        Contractors::where('id', $id)->update([
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
        return redirect()->route('contractors.show', ['id' => $id]);
    }

    public function search()
    {

        $company = Input::get('company');
        $data    = Contractors::query();

        if (isset($company) && strlen($company)) {
            $data->where('company', '=', $company);
        }

        $result = $data
            ->where('user_id', Auth::user()->id)
            ->paginate(10);

        return view('contractors.list', [
            'contractors' => $result,
        ]);
    }
}
