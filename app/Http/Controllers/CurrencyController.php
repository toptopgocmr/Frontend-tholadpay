<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'Currency';
        $client = new Client();
        $currencies = $client->get(config('keys.url_api') . 'currencies?&per_page=30000', [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $currencies = json_decode($currencies->getBody()->getContents(), true)['data'];
        // dump($currencies);
        return view('currencies.index', compact('token', 'role', 'user', 'menu', 'currencies'));
    }

    public function create(Request $request)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'Currency';
        $type = 'add';
        $client = new Client();
        try {
            $currencies = $client->get(config('keys.url_api') . 'currencies', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $currencies = json_decode($currencies->getBody()->getContents(), true)['data'];            
            $currency = '';
        } catch (\Exception $e) {
        //    dump($e->getMessage());
        //    return redirect()->route('login');
        }
        if ($request->getMethod() === 'POST') {
            try {
                $code = $request->get('textCode');
                $symbol = $request->get('textSymbol');
                $rate = $request->get('textRate');
                $fees = $request->get('textFees');
                $see = false;
                foreach ($currencies as $t) {
                    // dump($c['calling_code']);
                    // dump($code);
                    if ($t['code'] . '' === $code . '') {
                        \Session::flash('error', 'Ce taux de change existe dejà');
                        $see = true;
                    }
                }
                if (!$see) {
                    $twn = [
                        'code' => $code,
                        'symbol' => $symbol,
                        'fees' => $fees,
                        'rate' => $rate
                    ];
                    // dump($crty);
                    $res = $client->post(config('keys.url_api') . 'currencies', [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ],
                        'json' => $twn
                    ]);
                    $res = json_decode($res->getBody()->getContents(), true);
                    return redirect()->route('currency_list')->with('success', 'Le taux de change a été ajouté avec succès.');
                    // dump($res);
                }
            } catch (\Exception $e) {
                // dump($e->getMessage());
                \Session::flash('error', 'Erreur lors de l\'enregistrement, bien vouloir reéssayer.');
            }
        }
        return view('currencies.add', compact('user', 'menu', 'role', 'currency', 'type'));
    }

    public function edit(Request $request, $id)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'Currency';
        $client = new Client();
        $currency = $client->get(config('keys.url_api') . 'currencies/' . $id, [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $currency = json_decode($currency->getBody()->getContents(), true);
        
        $type = 'edit';
        if ($request->getMethod() === 'POST') {
            try {
                $code = $request->get('textCode');
                $symbol = $request->get('textSymbol');
                $rate = $request->get('textRate');
                $fees = $request->get('textFees');
                $currency['code'] = $code;
                $currency['symbol'] = $symbol;
                $currency['rate'] = $rate;
                $currency['fees'] = $fees;
                // dump($currency);
                $res = $client->put(config('keys.url_api') . 'currencies/' . $id, [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ],
                    'json' => $currency
                ]);
                $res = json_decode($res->getBody()->getContents(), true);
                return redirect()->route('currency_list')->with('success', 'Le taux de change a été modifié avec succès.');
            } catch (\Exception $e) {
                // FIX (2026-07-04, audit code) : dump() brut remplacé par l'extraction
                // du vrai message d'erreur (même correctif que TransactionController,
                // voir rapport_integration_peex.md §4.10).
                $erreurM = $e->getMessage();
                if ($e instanceof RequestException && $e->hasResponse()) {
                    $body = json_decode($e->getResponse()->getBody()->getContents(), true);
                    $erreurM = $body['message'] ?? $erreurM;
                }
                \Session::flash('error', 'Erreur lors de la modification : ' . $erreurM);
            }
        }
        return view('currencies.edit', compact('user', 'menu', 'role', 'currency', 'type'));
    }

    public function show(Request $request, $id)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'Currency';
        $client = new Client();
        $currency = $client->get(config('keys.url_api') . 'currencies/' . $id, [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $currency = json_decode($currency->getBody()->getContents(), true);
        // dump($town);
        return view('currencies.show', compact('token', 'role', 'user', 'menu', 'currency'));
    }

    public function delete(Request $request)
    {
        if ($request->getMethod() === 'POST') {
            try {
                $id = $request->get('id_delete');
                // dump($id);
                $token = $request->session()->get('token');
                $client = new Client();
                $currency = $client->delete(config('keys.url_api') . 'currencies/' . $id, [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ]
                ]);
                $currency = json_decode($currency->getBody()->getContents(), true);
                // dump($town);
                return redirect()->route('currency_list')->with('success', 'Le taux de change a été supprimé avec succès.');
                
            } catch (\Exception $e) {
                // FIX (2026-07-04, audit code) : dump() brut remplacé par l'extraction
                // du vrai message d'erreur.
                $erreurM = $e->getMessage();
                if ($e instanceof RequestException && $e->hasResponse()) {
                    $body = json_decode($e->getResponse()->getBody()->getContents(), true);
                    $erreurM = $body['message'] ?? $erreurM;
                }
                return redirect()->route('currency_list')->with('error', 'Erreur lors de la suppression : ' . $erreurM);
            }
        }
    }
}
