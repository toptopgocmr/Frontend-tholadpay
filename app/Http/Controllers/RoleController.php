<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;

class RoleController extends Controller
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
        $menu = 'Role';
        $client = new Client();
        $roles = $client->get(config('keys.url_api') . 'roles?per_page=3000', [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $roles = json_decode($roles->getBody()->getContents(), true)['data'];
        // dump($roles);
        return view('roles.index', compact('token', 'role', 'user', 'menu', 'roles'));
    }

    public function create(Request $request)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'Role';
        $type = 'add';
        $client = new Client();
        try {
            $roles = $client->get(config('keys.url_api') . 'roles', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $roles = json_decode($roles->getBody()->getContents(), true)['data'];
            // $retailOutlet = '';
        } catch (\Exception $e) {
//            dump($e->getMessage());
//            return redirect()->route('login');
        }
        if ($request->getMethod() === 'POST') {
            // dd($roles);
            try {
                $name = @str_replace(" ","_", $request->get('textName'));
                $displayName = $request->get('textDisplayName');
                $description = $request->get('description');
                $see = false;
                foreach ($roles as $t) {
//                    dump($c['calling_code']);
//                    dump($code);
                    if ($t['name'] . '' === $name . '') {
                        \Session::flash('error', 'Ce role existe dejà');
                        $see = true;
                    }
                }
                if (!$see) {
                    $twn = [
                        'name' => $name,
                        'display_name' => $displayName,
                        'description' => $description
                    ];
                    // dd($twn);
                    $res = $client->post(config('keys.url_api') . 'roles', [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ],
                        'json' => $twn
                    ]);
                    $res = json_decode($res->getBody()->getContents(), true);
                    return redirect()->route('role_list')->with('success', 'Le role a été ajouté avec succès.');
//                    dump($res);
                }
            } catch (\Exception $e) {
                // dump($e->getMessage());
                \Session::flash('error', 'Erreur lors de l\'enregistrement, bien vouloir reéssayer.');
            }
        }
        return view('roles.add', compact('user', 'menu', 'role', 'roles', 'type'));
    }

    public function edit(Request $request, $id)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'Role';
        $client = new Client();
        $mRole = $client->get(config('keys.url_api') . 'roles/' . $id, [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $mRole = json_decode($mRole->getBody()->getContents(), true);
        $type = 'edit';
        if ($request->getMethod() === 'POST') {
            try {
                // dd($request);
                // $name = @str_replace(" ","_", $request->get('textName')); $request->get('textName');
                $displayName = $request->get('textDisplayName');
                $description = $request->get('description');

                $role = [
                    // 'name' => $name,
                    'display_name' => $displayName,
                    'description' => $description
                ];
                // dd($role);
                $res = $client->put(config('keys.url_api') . 'roles/' . $id, [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ],
                    'json' => $role
                ]);
                $res = json_decode($res->getBody()->getContents(), true);
                return redirect()->route('role_list')->with('success', 'Le role a été modifié avec succès.');
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
        return view('roles.edit', compact('user', 'menu', 'role', 'mRole', 'type'));
    }

    public function show(Request $request, $id)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'Role';
        $client = new Client();
        $role = $client->get(config('keys.url_api') . 'roles/' . $id, [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $role = json_decode($role->getBody()->getContents(), true);
        // dump($town);
        return view('roles.show', compact('token', 'role', 'user', 'menu', 'role'));
    }

    public function delete(Request $request)
    {
        if ($request->getMethod() === 'POST') {
            try {
                $id = $request->get('id_delete');
                // dump($id);
                $token = $request->session()->get('token');
                $client = new Client();
                $roles = $client->delete(config('keys.url_api') . 'roles/' . $id, [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ]
                ]);
                $roles = json_decode($roles->getBody()->getContents(), true);
                // dump($town);
                return redirect()->route('role_list')->with('success', 'Le role a été supprimé avec succès.');
                
            } catch (\Exception $e) {
                // dump($e->getMessage());
                return redirect()->route('role_list')->with('error', 'Erreur lors de la suppression, bien vouloir reéssayer..');
            }
        }
    }
}
