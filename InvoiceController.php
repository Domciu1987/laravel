<?php

namespace App\Http\Controllers;

use App\Contractors;
use App\Invoice;
use App\Products;
use App\User;
use DateTime;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use PDF;
use Session;
use sumValues;

class InvoiceController extends Controller
{
    /**
     * Show all invoice
     * @return type
     */

    public function index()
    {
        $invoice = DB::table('invoice')
            ->leftJoin('contractors', 'invoice.contractor_id', '=', 'contractors.id')
            ->select('invoice.*', 'contractors.company')
            ->where('invoice.user_id', Auth::user()->id)
            ->paginate(10);

        return view('invoice.list', [
            'invoice' => $invoice]);
    }
    /**
     * Add new invoice
     *
     * @return type
     */
    public function add()
    {
        if (Auth::user()->company) {
            $invoice     = Invoice::where('user_id', '=', auth()->user()->id)->orderBy('invoice_nr', 'DESC')->first();
            $contractors = Contractors::where('user_id', Auth::user()->id)->get();

            if ($invoice && date('Y') == $invoice->year) {
                $numberinvoice = $invoice->invoice_nr + 1;
            } else {
                $numberinvoice = 1;
            }

            $date       = date('Y-m-d');
            $futuredate = date("Y-m-d", strtotime("$date +7 day"));

            return view('invoice.add', [
                'contractors'   => $contractors,
                'numberinvoice' => $numberinvoice,
                'date'          => $date,
                'futuredate'    => $futuredate]);
        } else {
            return redirect()->route('aboutme.index')->withErrors(['field_name' => ['Przed dodaniem faktury uzupełnij swój profil i dodaj pierwszego Kontrahenta.']]);
        }
    }

    /**
     * Description
     *
     * @param Request $request
     *
     * @return PDF
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_nr'        => 'required',
            'year'              => 'required',
            'payed'             => 'required|numeric|min:0',
            'datefrom'          => 'required|before:dateto',
            'dateto'            => 'required',
            'invoice.*.product' => 'required|alpha',
            'invoice.*.unit'    => 'required|numeric|min:0',
            'invoice.*.qty'     => 'required|numeric|min:0',
            'company'           => 'required',
            'vat'               => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $invoiceData = $request->request->all();

        foreach ($invoiceData['invoice'] as $key => $item) {
            $invoiceData['invoice'][$key]['total'] = ($item['unit'] * $item['qty']);
            $invoiceData['total_netto']            = ($invoiceData['total_netto'] ?? 0) + $invoiceData['invoice'][$key]['total'];
            $invoiceData['total_vat']              = $invoiceData['total_netto'] * ($invoiceData['vat'] / 100);
            $invoiceData['brutto']                 = $invoiceData['total_netto'] + $invoiceData['total_vat'];
            $invoiceData['payrest']                = $invoiceData['brutto'] - $invoiceData['payed'];
        }

        if ($invoiceData['payed'] > $invoiceData['brutto']) {
            Session::flash('message', 'Kwota zapłacono nie może byc wyższa od kwoty brutto');
            return redirect()->back()->withInput();
        }

        $numberinvoice = Invoice::where('invoice_nr', '=', Input::get('invoice_nr'))
                        ->Where('year', '=', Input::get('year'))
                        ->exists();
        if ($numberinvoice === true) {
            Session::flash('message', 'Taki nr faktury już istnieje.');
            return redirect()->back()->withInput();
        }

        $invoice                = new Invoice();
        $invoice->user_id       = Auth::user()->id;
        $invoice->contractor_id = $invoiceData['company'];
        $invoice->invoice_nr    = $invoiceData['invoice_nr'];
        $invoice->year          = $invoiceData['year'];
        $invoice->kind          = $invoiceData['kind'];
        $invoice->date_from     = $invoiceData['datefrom'];
        $invoice->date_to       = $invoiceData['dateto'];
        $invoice->payed         = $invoiceData['payed'];
        $invoice->pay_rest      = $invoiceData['payrest'];
        $invoice->payment_kind  = $invoiceData['paykind'];
        $invoice->netto         = $invoiceData['total_netto'];
        $invoice->vat           = $invoiceData['vat'];
        $invoice->total_vat     = $invoiceData['total_vat'];
        $invoice->brutto        = $invoiceData['brutto'];
        $invoice->save();

        $products = []; //make empty []

        foreach ($invoiceData['invoice'] as $key => $item) {
            $inv              = new Products();
            $inv->invoice_id  = $invoice['id'];
            $inv->description = $item['product'];
            $inv->pieces      = $item['qty'];
            $inv->price       = $item['unit'];
            $inv->total       = $invoiceData['invoice'][$key]['total'];
            $inv->save();

            $products[] = $inv; //put to [] $inv
        };

        $contractor = Contractors::where('id', $invoiceData['company'])->first();
        $number     = 1; // $invoice in blade from db

        if ($invoice['kind'] == 'PL') {
            $pdf = PDF::loadView('invoice.done', [
                'products'   => $products,
                'contractor' => $contractor,
                'invoice'    => $invoice,
                'number'     => $number,
            ]);
            return $pdf->download('invoice.pdf');
        } else {
            $pdf = PDF::loadView('invoice.ue', [
                'invoice'    => $invoice,
                'contractor' => $contractor,
                'products'   => $products,
                'number'     => $number,
            ]);
            return $pdf->download('invoice.pdf');
        };
    }
    /**
     * download invoice from list of invoice
     * @param   $id
     * @return type
     */
    public function download($id)
    {
        $invoice    = Invoice::where('id', $id)->first();
        $contractor = Contractors::where('id', $invoice->contractor_id)->first();
        $products   = Products::where('invoice_id', $id)->get();
        $number     = 1;

        if ($invoice['kind'] == 'PL') {
            $pdf = PDF::loadView('invoice.done', [
                'invoice'    => $invoice,
                'contractor' => $contractor,
                'products'   => $products,
                'number'     => $number,
            ]);
            return $pdf->download('invoice.pdf');
        } else {
            $pdf = PDF::loadView('invoice.ue', [
                'invoice'    => $invoice,
                'contractor' => $contractor,
                'products'   => $products,
                'number'     => $number,
            ]);
            return $pdf->download('invoice.pdf');
        }
    }
    /**
     * Edit to update
     * @return type
     */
    public function show($id)
    {
        $contractor = DB::table('invoice')
            ->leftJoin('contractors', 'invoice.contractor_id', '=', 'contractors.id')
            ->where('invoice.id', $id)
            ->first();
        $invoice  = Invoice::where('id', $id)->first();
        $products = Products::where('invoice_id', $id)->get();

        $kind = $invoice['kind'];

        if ($kind === 'PL') {
            $kind = 'UE';
        } else {
            $kind = 'PL';
        }

        $payment = $invoice['payment_kind'];

        if ($payment === 'Gotówka') {
            $payment = 'Przelew';
        } else {
            $payment = 'Gotówka';
        }

        
        if (isset($contractor)) {
            return view('invoice.show', [
                'invoice'    => $invoice,
                'contractor' => $contractor,
                'products'   => $products,
                'kind'       => $kind,
                'payment'    => $payment,
            ]);
        } else {
            return redirect()->route('invoice.index');
        }
    }

    public function destroy(int $id)
    {
        if (isset($invoice)) {
            $invoice = Invoice::where('id', $id)
            ->where('user_id', Auth::user()->id)
            ->first();
            $invoice->delete();
        }
        return redirect()->route('invoice.index');
    }
    /**
     * update
     * @return type
     */
    public function update(Request $request, $id)
    {
        $invoicenr   = Input::get('invoice_nr');
        $datefrom    = Input::get('datefrom');
        $dateto      = Input::get('dateto');
        $kind        = Input::get('kind');
        $paymentkind = Input::get('payment_kind');
        $payed       = Input::get('payed');
        $product     = Input::get('invoice[*][product]');
        $unit        = Input::get('invoice[*][price]');
        $qty         = Input::get('invoice[*][pieces]');

        $this->validate($request, [
            'payed'             => 'required|numeric|min:0',
            'datefrom'          => 'required|before:dateto',
            'dateto'            => 'required',
            'invoice.*.product' => 'required|alpha',
            'invoice.*.price'   => 'required|numeric',
            'invoice.*.pieces'  => 'required|numeric',
        ]);

        $invoiceData = $request->request->all();

        foreach ($invoiceData['invoice'] as $key => $item) {
            $invoiceData['invoice'][$key]['total'] = ($item['price'] * $item['pieces']);
            $invoiceData['total_netto']            = ($invoiceData['total_netto'] ?? 0) + $invoiceData['invoice'][$key]['total'];
            $invoiceData['total_vat']              = $invoiceData['total_netto'] * ($invoiceData['vat'] / 100);
            $invoiceData['brutto']                 = $invoiceData['total_netto'] + $invoiceData['total_vat'];
            $invoiceData['payrest']                = $invoiceData['brutto'] - $invoiceData['payed'];
        }

        $list = Products::where('invoice_id', $id)->get();
        foreach ($list as $key => $item) {
            if (isset($invoiceData['invoice'][$item->id])) {
                $item->description = $invoiceData['invoice'][$item->id]['product'];
                $item->pieces      = $invoiceData['invoice'][$item->id]['pieces'];
                $item->price       = $invoiceData['invoice'][$item->id]['price'];
                $item->total       = $invoiceData['invoice'][$item->id]['total'];
                $item->save();
                unset($invoiceData['invoice'][$item->id]);
            } else {
                $item->delete();
            }
        }

        if (!empty($invoiceData['invoice'])) {
            foreach ($invoiceData['invoice'] as $key => $item) {
                $invoice              = new Products();
                $invoice->invoice_id  = $id;
                $invoice->description = $item['product'];
                $invoice->pieces      = $item['pieces'];
                $invoice->price       = $item['price'];
                $invoice->total       = $item['total'];
                $invoice->save();
            }
        }

            Invoice::where('id', $id)->update([
                'invoice_nr'   => $invoicenr,
                'date_from'    => $datefrom,
                'date_to'      => $dateto,
                'kind'         => $kind,
                'payment_kind' => $paymentkind,
                'payed'        => $payed,
                'pay_rest'     => $invoiceData['payrest'],
                'netto'        => $invoiceData['total_netto'],
                'vat'          => $invoiceData['vat'],
                'total_vat'    => $invoiceData['total_vat'],
                'brutto'       => $invoiceData['brutto'],
            ]);

        return redirect()->route('invoice.index');
    }

    public function search()
    {
        $company = Input::get('company');
        $data    = Invoice::query()
                    ->select('invoice.*', 'contractors.company')
                    ->where('invoice.user_id', Auth::user()->id)
                    ->leftJoin('contractors', 'contractors.id', '=', 'invoice.contractor_id');


        if (isset($company) && strlen($company)) {
            $data->where('company', '=', $company);
        }

        return view('invoice.list', [
            'invoice' => $data->paginate(10)
        ]);
    }

    public function date()
    {
        $datefrom   = Input::get('date');
        $date       = DateTime::createFromFormat('m-Y', $datefrom);
        $dateformat = $date->format('m');
        $data       = Invoice::query();
        $data->whereMonth('date_from', $dateformat)
            ->leftJoin('contractors', 'invoice.contractor_id', '=', 'contractors.id')
            ->select('invoice.*', 'contractors.company')
            ->where('invoice.user_id', Auth::user()->id);
        $result = $data->paginate(10);

        return view('invoice.list', [
            'invoice' => $result]);
    }
}
