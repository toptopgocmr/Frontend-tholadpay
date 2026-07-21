<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;

class UserController extends Controller
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
        $agent = $request->session()->get('agent');
        $menu = 'User';
        // $agent = null;
        // dump($agent);
        // $agent = ($agent['agent'] !== null) ? $agent['agent'] : $agent;
        // dump($agent);
        // dump($token);
        $client = new Client();
        $host = config('keys.url_api');
        // $path = $role === 'administrator' ? 'role_users?_includes=user,user.user_roles.role,user.transactions,user.addresses&role_id4&_sortDir=desc' :
        $path = ($role === 'administrator' || $role === 'finance_manager') ? 'users?per_page=2000&_includes=agent,addresses,user_roles.role&_sortDir=desc' :
            'agents?_includes=user,user.user_roles.role,agent.agent,user.addresses&agent_id=' . $agent['id'] . '&per_page=200';
        $host = $host . $path;
        // dump($host);
        $users = $client->get($host, [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $users = json_decode($users->getBody()->getContents(), true)['data'];
        // dump($users);
        // $caissiers = [];
        if ($role === 'agent') {
            $caissiers = [];
            foreach ($users as $u) {
                if ($u['user'] !== null && $u['agent']['is_partner'] === 1) {
                    // dump($u);
                    array_push($caissiers, $u['user']);
                }
            }
            $users = $caissiers;
        } else if ($role === 'finance_manager') {
            $caissiers = [];
            foreach ($users as $u) {
                // dump($u);
                if ($u !== null && count($u['user_roles']) > 0 && $u['user_roles'][0]['role']['name'] !== 'administrator' && $u['user_roles'][0]['role']['name'] !== 'technical_support' && $u['user_roles'][0]['role']['name'] !== 'customer') {
                    // dump($u);
                    array_push($caissiers, $u);
                }
            }
            $users = $caissiers;
        } else {
            $caissiers = [];
            foreach ($users as $u) {
                if ($u !== null && count($u['user_roles']) > 0 && $u['user_roles'][0]['role']['name'] !== 'customer') {
                    // dump($u);
                    array_push($caissiers, $u);
                }
            }
            $users = $caissiers;
            // dump('Admin ',$users);
        }
        // dump('Final usrs ',$users);
        return view('users.index', compact('token', 'role', 'user', 'menu', 'users'));
    }

    public function create(Request $request)
    {
        $email_exist = '';
        $phone_exist = '';
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $agent = $request->session()->get('agent');
        $menu = 'User';
        $monAdresse = '';
        // dump($role);
        // dump($agent);
        try {
            $client = new Client();
            $countries = $client->get(config('keys.url_api') . 'countries?calling_code=242&limit=1', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $countries = json_decode($countries->getBody()->getContents(), true)['data'];
            $country_id = $countries[0]['id'];
            if ($role !== 'agent') {
                $towns = $client->get(config('keys.url_api') . 'towns?country_id=' . $country_id . '&per_page=1000', [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ]
                ]);
                $towns = json_decode($towns->getBody()->getContents(), true)['data'];
            } else {
                $towns = [];
                $adrs = $client->get(config('keys.url_api') . 'addresses?_includes=town&user_id=' . $user['id'], [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ]
                ]);
                $adrs = json_decode($adrs->getBody()->getContents(), true)['data'];
                foreach ($adrs as $ad) {
                    $monAdresse = $ad['name'];
                    array_push($towns, $ad['town']);
                }
                $towns = array_unique($towns, SORT_REGULAR); // retire les doublons du tableau
            }
            $roles = $client->get(config('keys.url_api') . 'roles', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $roles = json_decode($roles->getBody()->getContents(), true)['data'];
            $myRoles = [];
            foreach ($roles as $r) {
                if ($role === 'administrator') {
                    if ($r['name'] !== 'customer') {
                        array_push($myRoles, $r);
                    }
                } else {
                    if ($r['name'] === 'agent' || $r['name'] === 'cashier' || $r['name'] === 'csa') {
                        array_push($myRoles, $r);
                    }
                }
            }
            $roles = $myRoles;
            // dump($roles);
            $users = $client->get(config('keys.url_api') . 'users?per_page=3000', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $users = json_decode($users->getBody()->getContents(), true)['data'];
            $agts = $client->get(config('keys.url_api') . 'agents?per_page=3000&_includes=user,agent&is_partner=1', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $agts = json_decode($agts->getBody()->getContents(), true)['data'];
            $agents = [];
            foreach ($agts as $ag) {
                if ($ag['agent'] === null) {
                    array_push($agents, $ag);
                }
            }
            $financesM = [];
            if ($role !== 'finance_manager') {
                $finances = $client->get(config('keys.url_api') . 'agents?per_page=3000&_includes=user,agent&is_partner=0', [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ]
                ]);
                $finances = json_decode($finances->getBody()->getContents(), true)['data'];
                foreach ($finances as $fi) {
                    if ($fi['agent'] === null) {
                        array_push($financesM, $fi);
                    }
                }
            } else {
                $finances = $client->get(config('keys.url_api') . 'agents/' . $agent['id'] . '?_includes=user,agent', [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ]
                ]);
                $finances = json_decode($finances->getBody()->getContents(), true);
                if ($finances !== null)
                    $financesM = array($finances);
            }
            // dump($financesM);
            // dump($agents);
        } catch (\Exception $e) {
            // dump($e->getMessage());
            // return redirect()->route('login');
        }
        if ($request->getMethod() === 'POST') {
            try {
                $see = false;
                foreach ($users as $u) {
                    if ($u['phone_number'] === '242' . $request->get('txtPhone')) {
                        \Session::flash('error', 'Ce telephone existe dejà');
                        $see = true;
                    }
                    if ($u['email'] === $request->get('txtEmail')) {
                        \Session::flash('error', 'Cet email existe dejà');
                        $see = true;
                    }
                }
                if (!$see) {
                    if ($role === 'agent') { // si le user connecter est PMA
                        if (empty($request->get('textRole'))) {
                            $request->merge(['textRole' => '9|cashier']);
                        }
                        $request->merge(['txtNomC' => $agent['nom_commercial']]);
                        $request->merge(['idAgent' => $agent['id']]);
                    }
                    if ($role ==='finance_manager' && empty($request->get('textRole'))) { // si le user connecter est FM
                        $request->merge(['textRole' => '5|csa']);
                        $request->merge(['idFinance' => $agent['id']]);
                    }
                    $us = [
                        'first_name' => $request->get('textNom'),
                        'last_name' => $request->get('textPrenom'),
                        'email' => $request->get('txtEmail'),
                        'phone_number' => $request->get('txtPhone'),
                        'role_id' => explode("|", $request->get('textRole'))[0],
                        'password' => $request->get('textPassword'),
                        'country' => !empty($request->get('country')) ? $request->get('country') : 'CG',
                    ];
                    // dd($request);
                    $res = $client->post(config('keys.url_api') . 'auth/signup', [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ],
                        'json' => $us
                    ]);
                    $res = json_decode($res->getBody()->getContents(), true);
                    // dump($res);
                    $address = [
                        'name' => $request->get('txtAdresse'),
                        'is_primary' => true,
                        'description' => ' ',
                        'town_id' => $request->get('txtTowns'),
                        'user_id' => $res['data']['user']['id'],
                    ];
                    // dump($address);
                    $res1 = $client->post(config('keys.url_api') . 'addresses', [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ],
                        'json' => $address
                    ]);
                    // dump($res1);
                    $res1 = json_decode($res1->getBody()->getContents(), true);
                    $img = [
                        'avatar_picture' => $request->get('imgUser'),
                        'logo_picture' => $request->get('imgLogos'),
                        'user_id' => $res['data']['user']['id'],
                        'agent_id' => '',
                    ];
                    //                    dump($img);
                    if (explode("|", $request->get('textRole'))[1] === 'administrator' || explode("|", $request->get('textRole'))[1] === 'customer' || explode("|", $request->get('textRole'))[1] === 'technical_support') {
                        $this->addImg($img);
                        return redirect()->route('user_list')->with('success', 'L\'utilisateur a été ajouté avec succès.');
                    } else {
                        $agentAg = [
                            'nom_commercial' => $request->get('txtNomC'),
                            'logo' => ' ',
                            'solde' => 0,
                            'solde_utilisable' => 0,
                            'agent_id' => 0,
                            'user_id' => $res['data']['user']['id'],
                            'is_partner' => true,
                            // 'created_at' => date('Y-m-d H:i:s'),
                            // 'updated_at' => date('Y-m-d H:i:s'),
                        ];
                        $roleName = @explode("|", $request->get('textRole'))[1];
                        if ($roleName === 'cashier') {
                            $agentAg['agent_id'] = $request->get('idAgent');
                            $agentAg['is_partner'] = true;
                        }
                        if ($roleName === 'csa') {
                            $agentAg['agent_id'] = $request->get('idFinance');
                            $agentAg['is_partner'] = false;
                        }
                        if ($roleName === 'finance_manager') {
                            $agentAg['is_partner'] = false;
                        }
                        // if ($role === 'finance_manager' || $role === 'agent') {
                        //     $agentAg['nom_commercial'] = $agent['nom_commercial'];
                        // }
                        // dump('Agent a inserer : ', $agentAg, $roleName);
                        // if($agent['agent_id'] === null){
                        // if($roleName === 'Agent'){
                        // unset($agent['agent_id']);
                        $res2 = $client->post(config('keys.url_api') . 'agents', [
                            'verify' => false,
                            'headers' => [
                                'Content-Type' => 'application/json',
                                'Authorization' => 'Bearer ' . $token
                            ],
                            'json' => $agentAg
                        ]);
                        $res2 = json_decode($res2->getBody()->getContents(), true);
                        // dump('Agent creer ', $res2);
                        if ($res2 !== null) {
                            $img['agent_id'] = $res2['id'];
                        }
                        // }
                        $this->addImg($img);
                        return redirect()->route('user_list')->with('success', 'L\'utilisateur a été ajouté avec succès.');
                    }
                }
            } catch (\Exception $e) {
                $erreurM = $this->extractErrorMessage($e, 'Une erreur est survenue lors de l\'enregistrement. Veuillez réessayer.');
                \Session::flash('error', 'Erreur lors de l\'enregistrement : ' . $erreurM);
            }
        }
        $type = 'add';
        $userEdit = '';
        return view('users.add', compact('userEdit', 'type', 'email_exist', 'phone_exist', 'user', 'menu', 'towns', 'roles', 'role', 'agents', 'financesM', 'monAdresse', 'agent'));
    }

    public function edit(Request $request, $id)
    {
        $email_exist = '';
        $phone_exist = '';
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'User';
        // dump($role);
        // dump($id);
        try {
            $client = new Client();
            $userEdit = $client->get(config('keys.url_api') . 'users/' . $id . '?users?per_page=2000&_includes=agent,addresses,user_roles.role,addresses.town', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $userEdit = json_decode($userEdit->getBody()->getContents(), true);
            $ph = explode('242', $userEdit['phone_number']);
            $userEdit['phone_number'] = count($ph) === 1 ? $ph[0] : $ph[1];
            // dump($userEdit);
            $countries = $client->get(config('keys.url_api') . 'countries?calling_code=242&limit=1', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $countries = json_decode($countries->getBody()->getContents(), true)['data'];
            // dump($countries);
            $country_id = $countries[0]['id'];
            if ($role !== 'agent') {
                $towns = $client->get(config('keys.url_api') . 'towns?country_id=' . $country_id . '&per_page=1000', [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ]
                ]);
                $towns = json_decode($towns->getBody()->getContents(), true)['data'];
            } else {
                $towns = [];
                $adrs = $client->get(config('keys.url_api') . 'addresses?_includes=town&user_id=' . $user['id'], [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ]
                ]);
                $adrs = json_decode($adrs->getBody()->getContents(), true)['data'];
                foreach ($adrs as $ad) {
                    // $monAdresse = $ad['name'];
                    array_push($towns, $ad['town']);
                }
                $towns = array_unique($towns, SORT_REGULAR); // retire les doublons du tableau
            }
            $roles = $client->get(config('keys.url_api') . 'roles', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $roles = json_decode($roles->getBody()->getContents(), true)['data'];
            // dump($roles);
            $users = $client->get(config('keys.url_api') . 'users?&per_page=3000', [ // users diff de celui ou on est
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $users = json_decode($users->getBody()->getContents(), true)['data'];
            // dump($users);
            $agts = $client->get(config('keys.url_api') . 'agents?per_page=3000&_includes=user,agent', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $agts = json_decode($agts->getBody()->getContents(), true)['data'];
            // dump($agts);
            $agents = [];
            foreach ($agts as $ag) {
                if ($ag['agent'] === null) {
                    array_push($agents, $ag);
                }
            }
            // dump($agents);
        } catch (\Exception $e) {
            //            dump($e->getMessage());
            //            return redirect()->route('login');
        }
        if ($request->getMethod() === 'POST') {
            try {
                $see = false;
                $countPhn = 0;
                $countEml = 0;
                $email = strtolower(trim($request->get('txtEmail')));
                foreach ($users as $u) {
                    // dump($u['phone_number']);
                    if ($u['id'] . '' != '' . $id && $u['phone_number'] . '' == '242' . $request->get('txtPhone')) {
                        $countPhn += 1;
                        if ($countPhn > 0) {
                            \Session::flash('error', 'Ce telephone existe dejà');
                            $see = true;
                        }
                    }
                    if (($u['id'] . '' != '' . $id) && ($u['email'] == $request->get('txtEmail'))) {
                        $countEml += 1;
                        if ($countEml > 0) {
                            \Session::flash('error', 'Cet email existe dejà');
                            $see = true;
                        }
                    }
                }
                if (!$see && $countPhn <= 0 && $countEml <= 0) {
                    $p = explode('242', $request->get('txtPhone'));
                    $phoneUsr = count($p) === 1 ? $p[0] : $p[1];
                    $us = [
                        'first_name' => $request->get('textNom'),
                        'last_name' => $request->get('textPrenom')
                        // 'phone_number' => count($p) === 1 ? '242'. $p[0] : '242'. $p[1]
                    ];
                    if ($role === 'administrator' || $role === 'agent') {
                        $us['is_active'] = $request->get('textStatus') === '1' ? 1 : 0;
                        $us['status'] = $request->get('textStatus') === '1' ? 1 : 0;
                    }
                    if ($userEdit['email'] !== $email)
                        $us['email'] = $email;

                    if ($userEdit['phone_number'] !== $phoneUsr)
                        $us['phone_number'] = '242' . $phoneUsr;

                    if ($countPhn === 1) unset($us['phone_number']);
                    // dump($userEdit);
                    // dump($us);
                    $res = $client->put(config('keys.url_api') . 'users/' . $id, [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ],
                        'json' => $us
                    ]);
                    $res = json_decode($res->getBody()->getContents(), true);
                    $address = [
                        'name' => $request->get('txtAdresse'),
                        'is_primary' => true,
                        'description' => ' ',
                        'town_id' => $request->get('txtTowns'),
                        'user_id' => $res['id'],
                    ];
                    // dump($address);
                    // if ($userEdit['addresses'][0]['name'] !== $request->get('txtAdresse')) {
                    $res1 = $client->post(config('keys.url_api') . 'addresses', [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ],
                        'json' => $address
                    ]);
                    $res1 = json_decode($res1->getBody()->getContents(), true);
                    // }
                    $img = [
                        'avatar_picture' => $request->get('imgUser'),
                        'logo_picture' => $request->get('imgLogos'),
                        'user_id' => $id,
                        'agent_id' => '',
                    ];
                    // dump($img);
                    $tabImg = explode(';base64,', $img['avatar_picture']);
                    // dump(count($tabImg));
                    if (count($tabImg) > 1) // si il a charger une image, on insere
                        $this->addImg($img);
                    if ($userEdit['user_roles'][0]['role']['name'] === 'administrator') {
                        // $this->addImg($img);
                        return redirect()->route('user_list')->with('success', 'L\'administreur a été modifié avec succès.');
                    } else {
                        /*$agent = [
                            'nom_commercial' => $request->get('txtNomC'),
                            'user_id' => $res['id']
                        ];
                        if($userEdit['agent'] !== null){
                            // dump($userEdit, $agent);
                            $res2 = $client->put(config('keys.url_api') . 'agents/' . $userEdit['agent']['id'], [
                                'verify' => false,
                                'headers' => [
                                    'Content-Type' => 'application/json',
                                    'Authorization' => 'Bearer ' . $token
                                ],
                                'json' => $agent
                            ]);;
                            $res2 = json_decode($res2->getBody()->getContents(), true);
                        } */
                        return redirect()->route('user_list')->with('success', 'L\'utilisateur a été modifié avec succès.');
                    }
                }
            } catch (\Exception $e) {
                $erreurM = $this->extractErrorMessage($e, 'Une erreur est survenue lors de la modification. Veuillez réessayer.');
                \Session::flash('error', 'Erreur lors de la modification : ' . $erreurM);
            }
        }
        $type = 'edit';
        return view('users.edit', compact('userEdit', 'type', 'email_exist', 'phone_exist', 'user', 'menu', 'towns', 'roles', 'role', 'agents'));
    }

    public
    function createCashier(Request $request)
    {
        $email_exist = '';
        $phone_exist = '';
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $agent = $request->session()->get('agent');
        $agent = null;
        // $agent = ($agent['agent'] !== null) ? $agent['agent'] : $agent;
        $menu = 'User';
        // dump($agent);
        try {
            $client = new Client();
            $userV = $client->get(config('keys.url_api') . 'users/' . $user['id'] . '?_includes=agent,agent.agent,addresses.town,user_roles.role', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $userV = json_decode($userV->getBody()->getContents(), true);
            // dump($userV);
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
            $roles = $client->get(config('keys.url_api') . 'roles', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $roles = json_decode($roles->getBody()->getContents(), true)['data'];
            $users = $client->get(config('keys.url_api') . 'users?per_page=3000', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $users = json_decode($users->getBody()->getContents(), true)['data'];
            $agts = $client->get(config('keys.url_api') . 'agents?per_page=3000&_includes=user,agent', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $agts = json_decode($agts->getBody()->getContents(), true)['data'];
            $agents = [];
            foreach ($agts as $ag) {
                if ($ag['agent'] === null) {
                    array_push($agents, $ag);
                }
            }
            // dump($agents);
        } catch (\Exception $e) {
            // dump($e->getMessage());
            // return redirect()->route('login');
        }
        if ($request->getMethod() === 'POST') {
            try {
                $see = false;
                foreach ($users as $u) {
                    if ($u['phone_number'] === '242' . $request->get('txtPhone')) {
                        \Session::flash('error', 'Ce telephone existe dejà');
                        $see = true;
                    }
                    if ($u['email'] === $request->get('txtEmail')) {
                        \Session::flash('error', 'Cet email existe dejà');
                        $see = true;
                    }
                }
                if (!$see) {
                    $roles = $client->get(config('keys.url_api') . 'roles', [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ]
                    ]);
                    // $role_id = 0;
                    $roles = json_decode($roles->getBody()->getContents(), true)['data'];
                    foreach ($roles as $r) {
                        if ($r['name'] === 'cashier') {
                            $role_id = $r['id'];
                        }
                    }
                    $us = [
                        'first_name' => $request->get('textNom'),
                        'last_name' => $request->get('textPrenom'),
                        'email' => $request->get('txtEmail'),
                        'phone_number' => $request->get('txtPhone'),
                        'role_id' => $role_id,
                        'password' => $request->get('textPassword')
                    ];
                    $res = $client->post(config('keys.url_api') . 'auth/signup', [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ],
                        'json' => $us
                    ]);
                    // dump($res);
                    $res = json_decode($res->getBody()->getContents(), true);
                    $address = [
                        'name' => $request->get('txtAdresse'),
                        'is_primary' => true,
                        'description' => ' ',
                        'town_id' => $request->get('txtTowns'),
                        'user_id' => $res['data']['user']['id'],
                    ];
                    // dump($address);
                    $res1 = $client->post(config('keys.url_api') . 'addresses', [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ],
                        'json' => $address
                    ]);
                    $res1 = json_decode($res1->getBody()->getContents(), true);
                    $img = [
                        'avatar_picture' => $request->get('imgUser'),
                        'logo_picture' => $request->get('imgLogos'),
                        'user_id' => $res['data']['user']['id'],
                        'agent_id' => '0',
                    ];
                    //                    dump($img);
                    $userV = $client->get(config('keys.url_api') . 'users/' . $user['id'] . '?_includes=agent,agent.agent,addresses.town,user_roles.role', [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ]
                    ]);
                    $userV = json_decode($userV->getBody()->getContents(), true);
                    // dump($userV);
                    $agentAg = [
                        'nom_commercial' => $userV['agent']['nom_commercial'],
                        'logo' => ' ',
                        'solde' => 0,
                        'solde_utilisable' => 0,
                        'user_id' => $res['data']['user']['id'],
                        'agent_id' => $userV['agent']['id']
                    ];
                    if ($role === 'agent') {
                        $agentAg['is_partner'] = true;
                    }
                    if ($role === 'finance_manager') {
                        $agentAg['is_partner'] = false;
                    }
                    $agentAg['nom_commercial'] = ($agentAg['nom_commercial'] !== null) ? $agentAg['nom_commercial'] : ' ';
                    // dump($agentAg);
                    $res2 = $client->post(config('keys.url_api') . 'agents', [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ],
                        'json' => $agentAg
                    ]);;
                    $res2 = json_decode($res2->getBody()->getContents(), true);
                    $img['agent_id'] = $res2['id'];
                    $this->addImg($img);
                    return redirect()->route('user_list')->with('success', 'L\'utilisateur a été ajouté avec succès.');
                }
            } catch (\Exception $e) {
                // dump($e->getMessage());
                \Session::flash('error', 'Erreur lors de l\'enregistrement, bien vouloir reéssayer.');
                // return view('users.add', compact('email_exist', 'phone_exist', 'user', 'menu', 'towns', 'roles', 'role'))
                //    ->with('error', 'Erreur lors de l\'enregistrement, bien vouloir reéssayer.');
            }
        }
        return view('users.add', compact('email_exist', 'phone_exist', 'user', 'menu', 'towns', 'roles', 'role', 'agents'));
    }

    public
    function show(Request $request, $id)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $agent = $request->session()->get('agent');
        // $agent = null;
        $menu = 'User';
        $roleUser = null;
        $rle = '';
        $montantTransReussies = 0;
        $nbreTransReussies = 0;
        $nbreParPage = 6;
        $transactions = [];
        // dump($role);
        try {
            $client = new Client();
            $userV = $client->get(config('keys.url_api') . 'users/' . $id . '?_includes=agent,agent.agent,addresses.town,user_roles.role', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $userV = json_decode($userV->getBody()->getContents(), true);
            // dump($userV);
            // dump($userV['agent']['agent']);
            if ($userV !== null) {
                $agent = ($userV['agent']['agent'] !== null) ? $userV['agent']['agent'] : $userV['agent'];
                // dump($agent);
                $roleUser = count($userV['user_roles']) > 0 ? $userV['user_roles'][0]['role'] : null;
                $rle = count($userV['user_roles']) > 0 ? ucwords($userV['user_roles'][0]['role']['name']) : ''; // role du user
            }
            // dump($userV);
            // dump($roleUser, $rle);
            if ($role === 'administrator' || $role === 'finance_manager' || ($role === 'agent' && $userV['agent']['agent_id'] === $agt['id'])) {
                $link = 'transactions?_includes=sender,sender.user,user,user.agent,outbound.bank,outbound.mobile&per_page=10';
                if ($agent !== null) { // si agent est null, on charge plus de transaction
                    $link = 'transactions?_includes=sender,sender.user,user,user.agent,outbound.bank,outbound.mobile&agent_id=' . $agent['id'] . '&per_page=3000';
                    $nbreParPage = 10;
                }
                $transactions = $client->get(config('keys.url_api') . $link . '&_sortDir=desc', [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ]
                ]);
                $transactions = json_decode($transactions->getBody()->getContents(), true)['data'];
                $int = 0;
                foreach ($transactions as $t) {
                    if ($t['etat_transac'] === 'success') {
                        $montantTransReussies += $t['amount'] + $t['fees'];
                        $int = $int + 1;
                        // dump($int , $montantTransReussies);
                    }
                }
                $montantTransReussies = number_format(floatval($montantTransReussies . ''), 2);
                $agent['solde'] = number_format(floatval($agent['solde'] . ''), 2);
                $agent['solde_utilisable'] = number_format(floatval($agent['solde_utilisable'] . ''), 2);
                $nbreTransReussies = $int;
            } else {
                return redirect()->route('logout');
            }
            // dump($transactions);
        } catch (\Exception $e) {
            // dump($e->getMessage());
            // return redirect()->route('login');
        }
        return view('users.show', compact('user', 'menu', 'userV', 'transactions', 'role', 'agent', 'rle', 'roleUser', 'montantTransReussies', 'nbreTransReussies', 'rle', 'roleUser', 'nbreParPage'));
    }

    public
    function addImg($img)
    {
        try {
            //            dump($img);
            $client = new Client();
            $images = $client->post(config('keys.url_api') . 'images', [
                'verify' => false,
                'headers' => [
                    'content-type' => 'application/x-www-form-urlencoded'
                ],
                'form_params' => $img
            ]);
            $images = json_decode($images->getBody()->getContents(), true);
            //            dump($images);
        } catch (\Exception $exception) {
            //            dump($exception->getMessage());
        }
    }

    public function delete(Request $request)
    {
        $token = $request->session()->get('token');
        $user = $request->session()->get('user');
        if ($request->getMethod() === 'POST') {
            try {
                $id = $request->get('id_delete');
                $role = $request->get('id_role');
                // dump($id);
                // dump($role);
                $client = new Client();
                $requeteAdd = 'valid_id=' . $id;
                $agentsDB = array();
                $imagesDB = array();
                $usersDB = array();
                $mesAdressses = [];
                $mesImages = [];
                array_push($usersDB, ['user_id' => $id]);
                if ($role === 'agent' || $role === 'csa' || $role === 'cashier' || $role === 'finance_manager') {
                    $agentsDB = $client->get(config('keys.url_api') . 'agents?user_id=' . $id, [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ]
                    ]);
                    $agentsDB = json_decode($agentsDB->getBody()->getContents(), true)['data'];

                    if ($role === 'agent' || $role === 'finance_manager') {
                        $requeteAdd = 'agent_id=' . $agentsDB[0]['id'];
                        $agents = $client->get(config('keys.url_api') . 'agents?agent_id=' . $agentsDB[0]['id'] . '&per_page=100', [
                            'verify' => false,
                            'headers' => [
                                'Content-Type' => 'application/json',
                                'Authorization' => 'Bearer ' . $token
                            ]
                        ]);
                        $agents = json_decode($agents->getBody()->getContents(), true)['data'];
                        foreach ($agents as $ag) {
                            array_push($agentsDB, $ag);
                            array_push($usersDB, ['user_id' => $ag['user_id']]);
                        }
                    }
                    // dump($agentsDB, $usersDB);
                }
                $transactions = $client->get(config('keys.url_api') . 'transactions?' . $requeteAdd . '&per_page=1', [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ]
                ]);
                $transactions = json_decode($transactions->getBody()->getContents(), true)['data'];
                // dump($transactions);
                foreach ($usersDB as $usr) {
                    $adresses = $client->get(config('keys.url_api') . 'addresses?user_id=' . $usr['user_id'], [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ]
                    ]);
                    $adresses = json_decode($adresses->getBody()->getContents(), true)['data'];
                    $mesAdressses = @array_merge($mesAdressses, $adresses);
                }
                // dump($mesAdressses);

                if (count($transactions) === 0) {
                    foreach ($mesAdressses as $adr) {
                        $res = $client->delete(config('keys.url_api') . 'addresses/' . $adr['id'], [
                            'verify' => false,
                            'headers' => [
                                'Content-Type' => 'application/json',
                                'Authorization' => 'Bearer ' . $token
                            ]
                        ]);
                        $res = json_decode($res->getBody()->getContents(), true);
                    }

                    foreach ($agentsDB as $agt) {
                        $resAg = $client->delete(config('keys.url_api') . 'agents/' . $agt['id'], [
                            'verify' => false,
                            'headers' => [
                                'Content-Type' => 'application/json',
                                'Authorization' => 'Bearer ' . $token
                            ]
                        ]);
                        $resAg = json_decode($resAg->getBody()->getContents(), true);
                    }

                    foreach ($usersDB as $us) {
                        $user = $client->delete(config('keys.url_api') . 'users/' . $us['user_id'], [
                            'verify' => false,
                            'headers' => [
                                'Content-Type' => 'application/json',
                                'Authorization' => 'Bearer ' . $token
                            ]
                        ]);
                        $user = json_decode($user->getBody()->getContents(), true);
                    }
                    return redirect()->route('user_list')->with('success', 'L\'Utilisateur a été supprimé avec succès.');
                } else {
                    return redirect()->route('user_list')->with('error', 'Cet utilisateur a déjà validation une transaction, ne peut être supprimé.');
                }
            } catch (\Exception $e) {
                // dump($e->getMessage());
                return redirect()->route('user_list')->with('error', 'Erreur lors de la suppression, bien vouloir reéssayer..');
            }
        }
    }
}
