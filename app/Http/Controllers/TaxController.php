<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;

class TaxController extends Controller
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
        $menu = 'Tax';
        $client = new Client();
        $taxes = $client->get(config('keys.url_api') . 'taxes?_includes=zone&per_page=2000', [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $taxes = json_decode($taxes->getBody()->getContents(), true)['data'];
        return view('taxes.index', compact('token', 'role', 'user', 'menu', 'taxes'));
    }

    public function create(Request $request)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'Tax';
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
            $tax = '';
        } catch (\Exception $e) {
            // dump($e->getMessage());
            // return redirect()->route('login');
        }
        if ($request->getMethod() === 'POST') {
            try {
                $code = $request->get('txtCode');
                $libelle = $request->get('txtLibelle');
                $type_taxe = $request->get('txtType');
                $valeur = $request->get('txtValeur');
                $assiette = $request->get('txtAssiette');
                $zoneID = $request->get('txtZone');
                $statut = $request->get('txtStatut');

                $tx = [
                    'code' => $code,
                    'libelle' => $libelle,
                    'type' => $type_taxe,
                    'valeur' => $valeur,
                    'assiette' => $type_taxe === 'fixe' ? null : $assiette,
                    'zone_id' => $zoneID !== '' ? $zoneID : null,
                    'statut' => $statut === '1'
                ];
                $res = $client->post(config('keys.url_api') . 'taxes', [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ],
                    'json' => $tx
                ]);
                $res = json_decode($res->getBody()->getContents(), true);
                return redirect()->route('tax_list')->with('success', 'La taxe a été ajoutée avec succès.');
            } catch (\Exception $e) {
                // dump($e->getMessage());
                \Session::flash('error', 'Erreur lors de l\'enregistrement, bien vouloir reéssayer.');
            }
        }
        return view('taxes.add', compact('user', 'menu', 'role', 'tax', 'type', 'zones'));
    }

    public function edit(Request $request, $id)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'Tax';
        $client = new Client();
        $tax = $client->get(config('keys.url_api') . 'taxes/' . $id . '?_includes=zone', [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $tax = json_decode($tax->getBody()->getContents(), true);
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
            if ($zn !== null && (!isset($tax['zone']['id']) || $zn['id'] !== $tax['zone']['id'])) {
                array_push($mesZones, $zn);
            }
        }
        $zones = $mesZones;
        $type = 'edit';
        if ($request->getMethod() === 'POST') {
            try {
                $code = $request->get('txtCode');
                $libelle = $request->get('txtLibelle');
                $type_taxe = $request->get('txtType');
                $valeur = $request->get('txtValeur');
                $assiette = $request->get('txtAssiette');
                $zoneID = $request->get('txtZone');
                $statut = $request->get('txtStatut');

                $tax['code'] = $code;
                $tax['libelle'] = $libelle;
                $tax['type'] = $type_taxe;
                $tax['valeur'] = $valeur;
                $tax['assiette'] = $type_taxe === 'fixe' ? null : $assiette;
                $tax['zone_id'] = $zoneID !== '' ? $zoneID : null;
                $tax['statut'] = $statut === '1';
                $res = $client->put(config('keys.url_api') . 'taxes/' . $id, [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ],
                    'json' => $tax
                ]);
                $res = json_decode($res->getBody()->getContents(), true);
                return redirect()->route('tax_list')->with('success', 'La taxe a été modifiée avec succès.');
            } catch (\Exception $e) {
                // dump($e->getMessage());
                \Session::flash('error', 'Erreur lors de la modification, bien vouloir reéssayer.');
            }
        }
        return view('taxes.edit', compact('user', 'menu', 'role', 'tax', 'type', 'zones'));
    }

    public function show(Request $request, $id)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'Tax';
        $client = new Client();
        try {
            $tax = $client->get(config('keys.url_api') . 'taxes/' . $id . '?_includes=zone', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $tax = json_decode($tax->getBody()->getContents(), true);
        } catch (\Exception $e) {
            return redirect()->route('tax_list')->with('error', 'Taxe introuvable ou erreur lors du chargement.');
        }
        return view('taxes.show', compact('token', 'role', 'user', 'menu', 'tax'));
    }

    public function delete(Request $request)
    {
        if ($request->getMethod() === 'POST') {
            try {
                $id = $request->get('id_delete');
                $token = $request->session()->get('token');
                $client = new Client();
                $tax = $client->delete(config('keys.url_api') . 'taxes/' . $id, [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ]
                ]);
                $tax = json_decode($tax->getBody()->getContents(), true);
                return redirect()->route('tax_list')->with('success', 'La taxe a été supprimée avec succès.');
            } catch (\Exception $e) {
                // dump($e->getMessage());
                return redirect()->route('tax_list')->with('error', 'Erreur lors de la suppression, bien vouloir reéssayer..');
            }
        }
    }
}
