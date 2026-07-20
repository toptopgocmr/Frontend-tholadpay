<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;

class RetailOutletController extends Controller
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
        $menu = 'Retailoutlet';
        $client = new Client();
        $retailOutlets = $client->get(config('keys.url_api') . 'retail_outlets?_includes=town&per_page=3000', [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $retailOutlets = json_decode($retailOutlets->getBody()->getContents(), true)['data'];
        // dump($retailOutlets);
        return view('retailoutlets.index', compact('token', 'role', 'user', 'menu', 'retailOutlets'));
    }

    public function create(Request $request)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'Retailoutlet';
        $type = 'add';
        $client = new Client();
        try {
            $retailOutlets = $client->get(config('keys.url_api') . 'retail_outlets', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $retailOutlets = json_decode($retailOutlets->getBody()->getContents(), true)['data'];
            $countries = $client->get(config('keys.url_api') . 'countries?calling_code=242&limit=1', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $countries = json_decode($countries->getBody()->getContents(), true)['data'];
    //        dump($countries);
            $country_id = $countries[0]['id'];
            $towns = $client->get(config('keys.url_api') . 'towns?country_id=' . $country_id . '&per_page=300000', [
            // $towns = $client->get(config('keys.url_api') . 'towns?per_page=300000', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $towns = json_decode($towns->getBody()->getContents(), true)['data'];
            $retailOutlet = '';
        } catch (\Exception $e) {
//            dump($e->getMessage());
//            return redirect()->route('login');
        }
        if ($request->getMethod() === 'POST') {
            try {
                $name = $request->get('textNom');
                $description = $request->get('textDescription');
                $townID = $request->get('textTown');
                $rue = $request->get('textRue');
                $see = false;
                foreach ($retailOutlets as $t) {
//                    dump($c['calling_code']);
//                    dump($code);
                    if ($t['name'] . '' === $name . '') {
                        \Session::flash('error', 'Ce point de vente existe dejà');
                        $see = true;
                    }
                }
                if (!$see) {
                    $twn = [
                        'name' => $name,
                        'description' => $description,
                        'town_id' => $townID,
                        'rue' => $rue,
                        'status' => 'done'
                        // 'country_id' => $country_id
                    ];
//                    dump($crty);
                    $res = $client->post(config('keys.url_api') . 'retail_outlets', [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ],
                        'json' => $twn
                    ]);
                    $res = json_decode($res->getBody()->getContents(), true);
                    return redirect()->route('retailoutlet_list')->with('success', 'Le point de vente a été ajouté avec succès.');
//                    dump($res);
                }
            } catch (\Exception $e) {
                // dump($e->getMessage());
                \Session::flash('error', 'Erreur lors de l\'enregistrement, bien vouloir reéssayer.');
            }
        }
        return view('retailoutlets.add', compact('user', 'menu', 'role', 'towns', 'retailOutlet', 'type'));
    }

    public function edit(Request $request, $id)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'Retailoutlet';
        $client = new Client();
        $retailOutlets = $client->get(config('keys.url_api') . 'retail_outlets/' . $id.'?_includes=town', [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $retailOutlets = json_decode($retailOutlets->getBody()->getContents(), true);
        $towns = $client->get(config('keys.url_api') . 'towns', [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $towns = json_decode($towns->getBody()->getContents(), true)['data'];
        $type = 'edit';
        if ($request->getMethod() === 'POST') {
            try {
                $name = $request->get('textNom');
                $description = $request->get('textDescription');
                $townID = $request->get('textTown');
                $rue = $request->get('textRue');
                $retailOutlets['name'] = $name;
                $retailOutlets['description'] = $description;
                $retailOutlets['town_id'] = $townID;
                $retailOutlets['rue'] = $rue;
                // $retailOutlets['district'] = $name;
//                dump($town);
                $res = $client->put(config('keys.url_api') . 'retail_outlets/' . $id, [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ],
                    'json' => $retailOutlets
                ]);
                $res = json_decode($res->getBody()->getContents(), true);
                return redirect()->route('retailoutlet_list')->with('success', 'Le point de vente a été modifié avec succès.');
            } catch (\Exception $e) {
                // FIX (2026-07-04, audit code) : dump() brut remplacé par l'extraction du vrai message.
                $erreurM = $e->getMessage();
                if ($e instanceof RequestException && $e->hasResponse()) {
                    $body = json_decode($e->getResponse()->getBody()->getContents(), true);
                    $erreurM = $body['message'] ?? $erreurM;
                }
                \Session::flash('error', 'Erreur lors de la modification : ' . $erreurM);
            }
        }
        return view('retailoutlets.edit', compact('user', 'menu', 'role', 'towns', 'retailOutlets', 'type'));
    }

    public function show(Request $request, $id)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'Retailoutlet';
        $client = new Client();
        $retailOutlets = $client->get(config('keys.url_api') . 'retail_outlets/' . $id, [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $retailOutlets = json_decode($retailOutlets->getBody()->getContents(), true);
        // dump($town);
        return view('retailoutlets.show', compact('token', 'role', 'user', 'menu', 'retailOutlets'));
    }

    public function delete(Request $request)
    {
        if ($request->getMethod() === 'POST') {
            try {
                $id = $request->get('id_delete');
                // dump($id);
                $token = $request->session()->get('token');
                $client = new Client();
                $retailOutlets = $client->delete(config('keys.url_api') . 'retail_outlets/' . $id, [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ]
                ]);
                $retailOutlets = json_decode($retailOutlets->getBody()->getContents(), true);
                // dump($town);
                return redirect()->route('retailoutlet_list')->with('success', 'Le poit de vente a été supprimé avec succès.');
                
            } catch (\Exception $e) {
                // dump($e->getMessage());
                return redirect()->route('retailoutlet_list')->with('error', 'Erreur lors de la suppression, bien vouloir reéssayer..');
            }
        }
    }
}
