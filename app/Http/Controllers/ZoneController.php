<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;

class ZoneController extends Controller
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
        $menu = 'Zone';
        $client = new Client();
        $zones = $client->get(config('keys.url_api') . 'zones?per_page=3000', [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $zones = json_decode($zones->getBody()->getContents(), true)['data'];
        // dump($zones);
        return view('zones.index', compact('token', 'role', 'user', 'menu', 'zones'));
    }

    public function create(Request $request)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'Zone';
        $type = 'add';
        $client = new Client();
        try {
            $zones = $client->get(config('keys.url_api') . 'zones', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $zones = json_decode($zones->getBody()->getContents(), true)['data'];
            $zone = '';
        } catch (\Exception $e) {
            // dump($e->getMessage());
            // return redirect()->route('login');
        }
        if ($request->getMethod() === 'POST') {
            try {
                $name = $request->get('txtNomZone');
                $description = $request->get('txtDescriptionZone');
                $limitAmountPerDay = $request->get('txtLimitPerDay');
                $see = false;
                foreach ($zones as $t) {
                    if ($t['name'] . '' === $name . '') {
                        \Session::flash('error', 'Cette zone existe dejà');
                        $see = true;
                    }
                }
                if (!$see) {
                    $zon = [
                        'name' => $name,
                        'description' => $description,
                        'limit_transac_day' => $limitAmountPerDay,
                        'status' => true
                    ];
                    // dump($zon);
                    $res = $client->post(config('keys.url_api') . 'zones', [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ],
                        'json' => $zon
                    ]);
                    $res = json_decode($res->getBody()->getContents(), true);
                    return redirect()->route('zone_list')->with('success', 'La zone a été ajoutée avec succès.');
                    // dump($res);
                }
            } catch (\Exception $e) {
                // dump($e->getMessage());
                \Session::flash('error', 'Erreur lors de l\'enregistrement, bien vouloir reéssayer.');
            }
        }
        return view('zones.add', compact('user', 'menu', 'role', 'zone', 'type'));
    }

    public function edit(Request $request, $id)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'Zone';
        $client = new Client();
        $zone = $client->get(config('keys.url_api') . 'zones/' . $id, [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $zone = json_decode($zone->getBody()->getContents(), true);
        $type = 'edit';
        if ($request->getMethod() === 'POST') {
            try {
                $name = $request->get('txtNomZone');
                $description = $request->get('txtDescriptionZone');
                $limitAmountPerDay = $request->get('txtLimitPerDay');
                $mazone['description'] = $description;
                $mazone['limit_transac_day'] = $limitAmountPerDay;
                // dump($zone);
                $res = $client->put(config('keys.url_api') . 'zones/' . $id, [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ],
                    'json' => $mazone
                ]);
                $res = json_decode($res->getBody()->getContents(), true);
                return redirect()->route('zone_list')->with('success', 'La zone a été modifiée avec succès.');
            } catch (\Exception $e) {
                // dump($e->getMessage());
                \Session::flash('error', 'Erreur lors de la modification, bien vouloir reéssayer.');
            }
        }
        return view('zones.edit', compact('user', 'menu', 'role', 'zone', 'type'));
    }

    public function show(Request $request, $id)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'Zone';
        $client = new Client();
        $zone = $client->get(config('keys.url_api') . 'zones/' . $id, [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $zone = json_decode($zone->getBody()->getContents(), true);
//        dump($zone);
        return view('zones.show', compact('token', 'role', 'user', 'menu', 'zone'));
    }

    public function delete(Request $request)
    {
        if ($request->getMethod() === 'POST') {
            try {
                $id = $request->get('id_delete');
                // dump($id);
                $token = $request->session()->get('token');
                $client = new Client();
                $countries = $client->get(config('keys.url_api') . 'countries?zone_id=' . $id . '&per_page=1', [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ]
                ]);
                $countries = json_decode($countries->getBody()->getContents(), true)['data'];
                if (count($countries) === 0) {
                    $zone = $client->delete(config('keys.url_api') . 'zones/' . $id, [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ]
                    ]);
                    $zone = json_decode($zone->getBody()->getContents(), true);
                    // dump($zone);
                    return redirect()->route('zone_list')->with('success', 'La zone a été supprimée avec succès.');
                } else {
                    return redirect()->route('zone_list')->with('error', 'Cette zone est utilisé par un pays.');
                }
            } catch (\Exception $e) {
                // dump($e->getMessage());
                return redirect()->route('zone_list')->with('error', 'Erreur lors de la suppression, bien vouloir reéssayer..');
            }
        }
    }
}
