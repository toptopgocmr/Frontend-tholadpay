<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\File;
use Illuminate\Http\Request;

class CountryController extends Controller
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
        $menu = 'Country';
        $client = new Client();
        $countries = $client->get(config('keys.url_api') . 'countries?_includes=zone&per_page=3000&_sortDir=desc', [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $countries = json_decode($countries->getBody()->getContents(), true)['data'];
//        dump($countries);
        return view('countries.index', compact('token', 'role', 'user', 'menu', 'countries'));
    }

    public function create(Request $request)
    {
        $email_exist = '';
        $phone_exist = '';
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'Country';
        $type = 'add';
        try {
            $client = new Client();
            $zones = $client->get(config('keys.url_api') . 'zones', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $zones = json_decode($zones->getBody()->getContents(), true)['data'];
            $countries = $client->get(config('keys.url_api') . 'countries?_includes=zone&per_page=300', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $countries = json_decode($countries->getBody()->getContents(), true)['data'];
            $country = count($countries) > 0 ? $countries[0] : '';
            // dump($country);
        } catch (\Exception $e) {
//            dump($e->getMessage());
//            return redirect()->route('login');
        }
        if ($request->getMethod() === 'POST') {
            try {
                $name = $request->get('textNom');
                $code = $request->get('textCodeP');
                $abbr2 = $request->get('txtNomP2');
                $abbr3 = $request->get('txtNomP3');
                $curr = $request->get('txtCurrency');
                $sym = $request->get('txtCurrSym');
                $zoneID = $request->get('txtZone');
                $codeCurrency = $request->get('txtCurrCode');
                $isTauxExchange = $request->get('txtIsExchange');
                $see = false;
                foreach ($countries as $c) {
                    // dump($c['calling_code']);
                    // dump($code);
                    if ($c['full_name'] . '' === $name . '') {
                        \Session::flash('error', 'Ce pays existe dejà');
                        $see = true;
                    }
                    if ($c['calling_code'] . '' === $code . '') {
                        \Session::flash('error', 'Ce code de pays existe dejà');
                        $see = true;
                    }
                    if ($c['iso_3166_2'] . '' === $abbr2 . '') {
                        \Session::flash('error', 'Cette abbréviation(2) existe dejà');
                        $see = true;
                    }
                    if ($c['iso_3166_3'] . '' === $abbr3 . '') {
                        \Session::flash('error', 'Cette abbréviation(3) existe dejà');
                        $see = true;
                    }
                }
                if (!$see) {
                    $crty = [
                        'full_name' => $name,
                        'citizenship' => $name,
                        'capital' => $name,
                        'name' => $name,
                        'country_code' => trim($code),
                        'calling_code' => trim($code),
                        'currency' => $curr,
                        'currency_code' => $codeCurrency,
                        'currency_symbol' => $sym,
                        'currency_sub_unit' => 'ras',
                        'region_code' => 'ras',
                        'sub_region_code' => 'ras',
                        'eea' => 0,
                        'iso_3166_2' => trim($abbr2),
                        'iso_3166_3' => trim($abbr3),
                        'zone_id' => $zoneID,
                        'is_covered' => $isTauxExchange
                    ];
                    // dump($crty);
                    $res = $client->post(config('keys.url_api') . 'countries', [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ],
                        'json' => $crty
                    ]);
                    $res = json_decode($res->getBody()->getContents(), true);
                    return redirect()->route('country_list')->with('success', 'Le pays a été ajouté avec succès.');
//                    dump($res);
                }
            } catch (\Exception $e) {
                // dump($e->getMessage());
                \Session::flash('error', 'Erreur lors de l\'enregistrement, bien vouloir reéssayer.');
//                return view('users.add', compact('email_exist', 'phone_exist', 'user', 'menu', 'towns', 'roles', 'role'))
//                    ->with('error', 'Erreur lors de l\'enregistrement, bien vouloir reéssayer.');
            }
        }
        return view('countries.add', compact('user', 'menu', 'role', 'country', 'type', 'zones'));
    }

    public function edit(Request $request, $id)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'Country';
        $type = 'edit';
        $client = new Client();
        $country = $client->get(config('keys.url_api') . 'countries/' . $id . '?_includes=zone', [
        // $country = $client->get(config('keys.url_api') . 'countries?id=' . $id, [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $country = json_decode($country->getBody()->getContents(), true);
        $zones = $client->get(config('keys.url_api') . 'zones', [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $zones = json_decode($zones->getBody()->getContents(), true)['data'];        
        $mesZones = [];
        foreach ($zones as $zn) {
            if($zn !== null && $zn['id'] !== $country['zone']['id']){
                array_push($mesZones, $zn); 
            }           
        }
        $zones = $mesZones;
        $countries = $client->get(config('keys.url_api') . 'countries?id<>'.$id.'&per_page=300', [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $countries = json_decode($countries->getBody()->getContents(), true)['data'];
        // dump('Pays autres : ', $countries);
        // dump($zones);
        // if(count($country) > 0)
        //     $country = $country[0];
        // dump($country);
        if ($request->getMethod() === 'POST') {
            try {
                $name = $request->get('textNom');
                $code = $request->get('textCodeP');
                $abbr2 = $request->get('txtNomP2');
                $abbr3 = $request->get('txtNomP3');
                $curr = $request->get('txtCurrency');
                $sym = $request->get('txtCurrSym');
                $codeCurr = $request->get('txtCurrCode');
                $zoneID = $request->get('txtZone');
                $country['full_name'] = $name;
                $country['calling_code'] = $code;
                $country['currency'] = $curr;
                $country['iso_3166_2'] = $abbr2;
                $country['iso_3166_3'] = $abbr3;
                $country['currency_code'] = $codeCurr;
                $country['currency_symbol'] = $sym;
                $country['country_code'] = $code;
                $country['zone_id'] = $zoneID;

                                   
                //    $crty = [
                //        'full_name' => $name,
                //        'citizenship' => $name,
                //        'capital' => $name,
                //        'name' => $name,
                //        'country_code' => $code,
                //        'calling_code' => $code,
                //        'currency' => $curr,
                //        'currency_code' => $sym,
                //        'currency_symbol' => $sym,
                //        'currency_sub_unit' => 'ras',
                //        'region_code' => 'ras',
                //        'sub_region_code' => 'ras',
                //        'eea' => 0,
                //        'iso_3166_2' => $abbr2,
                //        'iso_3166_3' => $abbr3
                //    ];
                //    $country->unset('flag');
                unset($country['flag']);
                // dump($country);
                $res = $client->put(config('keys.url_api') . 'countries/' . $id, [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ],
                    'json' => $country
                ]);
                $res = json_decode($res->getBody()->getContents(), true);
                return redirect()->route('country_list')->with('success', 'Le pays a été modifié avec succès.');                

            } catch (\Exception $e) {
                // dump($e->getMessage());
                \Session::flash('error', 'Erreur lors de la modification, bien vouloir reéssayer.');
            }
        }
        return view('countries.edit', compact('user', 'menu', 'role', 'country', 'type', 'zones'));
    }

    public function show(Request $request, $id)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'Country';
        $client = new Client();
        $country = $client->get(config('keys.url_api') . 'countries/' . $id, [
        // $country = $client->get(config('keys.url_api') . 'countries?id=' . $id, [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $country = json_decode($country->getBody()->getContents(), true);
        // if(count($country) > 0)
        //     $country = $country[0];
        // dump($country);
        return view('countries.show', compact('token', 'role', 'user', 'menu', 'country'));
    }

    public function delete(Request $request)
    {
        if ($request->getMethod() === 'POST') {
            try {
                $id = $request->get('id_delete');
//                dump($id);
                $token = $request->session()->get('token');
                $client = new Client();
                $country = $client->delete(config('keys.url_api') . 'countries/' . $id, [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ]
                ]);
                $country = json_decode($country->getBody()->getContents(), true);
//                dump($country);
                return redirect()->route('country_list')->with('success', 'Le pays a été supprimé avec succès.');
            } catch (\Exception $e) {
                // dump($e->getMessage());
                return redirect()->route('country_list')->with('error', 'Erreur lors de la suppression, bien vouloir reéssayer..');
            }
        }
    }
}
