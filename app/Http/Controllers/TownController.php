<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;

class TownController extends Controller
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
        $menu = 'Town';
        $client = new Client();
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
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $towns = json_decode($towns->getBody()->getContents(), true)['data'];
//        dump($towns);
        return view('towns.index', compact('token', 'role', 'user', 'menu', 'towns'));
    }

    public function create(Request $request)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'Town';
        $type = 'add';
        $client = new Client();
        try {
            $countries = $client->get(config('keys.url_api') . 'countries?calling_code=242&limit=1', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $countries = json_decode($countries->getBody()->getContents(), true)['data'];
            $country_id = $countries[0]['id'];
            $towns = $client->get(config('keys.url_api') . 'towns?country_id=' . $country_id . '&per_page=1000', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $towns = json_decode($towns->getBody()->getContents(), true)['data'];
            $town = '';
        } catch (\Exception $e) {
//            dump($e->getMessage());
//            return redirect()->route('login');
        }
        if ($request->getMethod() === 'POST') {
            try {
                $name = $request->get('textNom');
                $see = false;
                foreach ($towns as $t) {
//                    dump($c['calling_code']);
//                    dump($code);
                    if ($t['name'] . '' === $name . '') {
                        \Session::flash('error', 'Cette ville existe dejà');
                        $see = true;
                    }
                }
                if (!$see) {
                    $twn = [
                        'name' => $name,
                        'district' => $name,
                        'country_id' => $country_id
                    ];
//                    dump($crty);
                    $res = $client->post(config('keys.url_api') . 'towns', [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ],
                        'json' => $twn
                    ]);
                    $res = json_decode($res->getBody()->getContents(), true);
                    return redirect()->route('town_list')->with('success', 'La ville a été ajoutée avec succès.');
//                    dump($res);
                }
            } catch (\Exception $e) {
                // dump($e->getMessage());
                \Session::flash('error', 'Erreur lors de l\'enregistrement, bien vouloir reéssayer.');
            }
        }
        return view('towns.add', compact('user', 'menu', 'role', 'town', 'type'));
    }

    public function edit(Request $request, $id)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'Town';
        $client = new Client();
        $town = $client->get(config('keys.url_api') . 'towns/' . $id, [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $town = json_decode($town->getBody()->getContents(), true);
        $type = 'edit';
        if ($request->getMethod() === 'POST') {
            try {
                $name = $request->get('textNom');
                $town['name'] = $name;
                $town['district'] = $name;
//                dump($town);
                $res = $client->put(config('keys.url_api') . 'towns/' . $id, [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ],
                    'json' => $town
                ]);
                $res = json_decode($res->getBody()->getContents(), true);
                return redirect()->route('town_list')->with('success', 'La ville a été modifiée avec succès.');
            } catch (\Exception $e) {
                $erreurM = $this->extractErrorMessage($e, 'Une erreur est survenue lors de la modification. Veuillez réessayer.');
                \Session::flash('error', 'Erreur lors de la modification : ' . $erreurM);
            }
        }
        return view('towns.edit', compact('user', 'menu', 'role', 'town', 'type'));
    }

    public function show(Request $request, $id)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'Town';
        $client = new Client();
        $town = $client->get(config('keys.url_api') . 'towns/' . $id, [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $town = json_decode($town->getBody()->getContents(), true);
//        dump($town);
        return view('towns.show', compact('token', 'role', 'user', 'menu', 'town'));
    }

    public function delete(Request $request)
    {
        if ($request->getMethod() === 'POST') {
            try {
                $id = $request->get('id_delete');
//                dump($id);
                $token = $request->session()->get('token');
                $client = new Client();
                $addresses = $client->get(config('keys.url_api') . 'addresses?town_id=' . $id . '&per_page=1', [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ]
                ]);
                $addresses = json_decode($addresses->getBody()->getContents(), true)['data'];
                if (count($addresses) === 0) {
                    $town = $client->delete(config('keys.url_api') . 'towns/' . $id, [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ]
                    ]);
                    $town = json_decode($town->getBody()->getContents(), true);
//                dump($town);
                    return redirect()->route('town_list')->with('success', 'La ville a été supprimée avec succès.');
                } else {
                    return redirect()->route('town_list')->with('error', 'Cette ville est utilisé par un utilisateur.');
                }
            } catch (\Exception $e) {
                // dump($e->getMessage());
                return redirect()->route('town_list')->with('error', 'Erreur lors de la suppression, bien vouloir reéssayer..');
            }
        }
    }
}
