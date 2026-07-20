<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;

class TarificationController extends Controller
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
        $menu = 'Tarif';
        $client = new Client();
        $tarifications = $client->get(config('keys.url_api') . 'tarifications?_includes=zone&per_page=2000', [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $tarifications = json_decode($tarifications->getBody()->getContents(), true)['data'];
        return view('tarifications.index', compact('token', 'role', 'user', 'menu', 'tarifications'));
    }

    public function create(Request $request)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'Tarif';
        $type = 'add';
        $client = new Client();
        try {
            $tarifications = $client->get(config('keys.url_api') . 'tarifications', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $tarifications = json_decode($tarifications->getBody()->getContents(), true)['data'];
            $zones = $client->get(config('keys.url_api') . 'zones', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $zones = json_decode($zones->getBody()->getContents(), true)['data'];
            $tarification = '';
        } catch (\Exception $e) {
            // dump($e->getMessage());
            // return redirect()->route('login');
        }
        if ($request->getMethod() === 'POST') {
            try {
                $tarif1 = $request->get('txtTarif');
                $tarif2 = $request->get('txtTarif2');
                $frais = $request->get('txtFrais');
                $zoneID = $request->get('txtZone');
                $see = false;
                $compared = false;
                if($tarif1 === $tarif2){
                    \Session::flash('error', 'Les prix A et B, ne doivent pas etre identiques !');
                    $compared = true;
                } else if($tarif1 > $tarif2){
                    \Session::flash('error', 'Le prix A, ne peut etre supérieur au prix B !');
                    $compared = true;
                }
                foreach ($tarifications as $t) {
                    if ($t['tarif_1'] . '' === $tarif1 . '' && $t['tarif_2'] . '' === $tarif2 . '' && $t['zone_id'] . '' === $zoneID . '') {
                        \Session::flash('error', 'Cette grille tarifaire existe dejà');
                        $see = true;
                    }
                }
                if (!$see && !$compared) {
                    $tar = [
                        'tarif_1' => $tarif1,
                        'tarif_2' => $tarif2,
                        'frais' => $frais,
                        'zone_id' => $zoneID,
                        'status' => true
                    ];
                    $res = $client->post(config('keys.url_api') . 'tarifications', [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ],
                        'json' => $tar
                    ]);
                    $res = json_decode($res->getBody()->getContents(), true);
                    return redirect()->route('tarif_list')->with('success', 'La grille tarifaire a été ajoutée avec succès.');
                }
            } catch (\Exception $e) {
                // dump($e->getMessage());
                \Session::flash('error', 'Erreur lors de l\'enregistrement, bien vouloir reéssayer.');
            }
        }
        return view('tarifications.add', compact('user', 'menu', 'role', 'tarification', 'type', 'zones'));
    }

    public function edit(Request $request, $id)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'Tarif';
        $client = new Client();
        $tarification = $client->get(config('keys.url_api') . 'tarifications/' . $id . '?_includes=zone', [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $tarification = json_decode($tarification->getBody()->getContents(), true);
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
            if($zn !== null && $zn['id'] !== $tarification['zone']['id']){
                array_push($mesZones, $zn); 
            }           
        }
        $zones = $mesZones;
        $type = 'edit';
        if ($request->getMethod() === 'POST') {
            try {
                $tarif1 = $request->get('txtTarif');
                $tarif2 = $request->get('txtTarif2');
                $frais = $request->get('txtFrais');
                $zoneID = $request->get('txtZone');

                $tarification['tarif_1'] = $tarif1;
                $tarification['tarif_2'] = $tarif2;
                $tarification['frais'] = $frais;
                $tarification['zone_id'] = $zoneID;
                $res = $client->put(config('keys.url_api') . 'tarifications/' . $id, [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ],
                    'json' => $tarification
                ]);
                $res = json_decode($res->getBody()->getContents(), true);
                return redirect()->route('tarif_list')->with('success', 'La grille tarifaire a été modifiée avec succès.');
            } catch (\Exception $e) {
                // dump($e->getMessage());
                \Session::flash('error', 'Erreur lors de la modification, bien vouloir reéssayer.');
            }
        }
        return view('tarifications.edit', compact('user', 'menu', 'role', 'tarification', 'type', 'zones'));
    }

    public function show(Request $request, $id)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'Tarif';
        $client = new Client();
        $tarification = $client->get(config('keys.url_api') . 'tarifications/' . $id, [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $tarification = json_decode($tarification->getBody()->getContents(), true);
        return view('transactions.show', compact('token', 'role', 'user', 'menu', 'tarification'));
    }

    public function delete(Request $request)
    {
        if ($request->getMethod() === 'POST') {
            try {
                $id = $request->get('id_delete');
                $token = $request->session()->get('token');
                $client = new Client();
                $transactions = $client->get(config('keys.url_api') . 'transactions?tarif_id=' . $id . '&per_page=1', [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ]
                ]);
                $transactions = json_decode($transactions->getBody()->getContents(), true)['data'];
                if (count($transactions) === 0) {
                    $tarification = $client->delete(config('keys.url_api') . 'tarifications/' . $id, [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ]
                    ]);
                    $tarification = json_decode($tarification->getBody()->getContents(), true);
                    return redirect()->route('tarif_list')->with('success', 'La grille tarifaire a été supprimée avec succès.');
                } else {
                    return redirect()->route('tarif_list')->with('error', 'Cette grille tarifaire est déja utilisé.');
                }
            } catch (\Exception $e) {
                // dump($e->getMessage());
                return redirect()->route('tarif_list')->with('error', 'Erreur lors de la suppression, bien vouloir reéssayer..');
            }
        }
    }
}
