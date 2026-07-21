<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;

class CustomerController extends Controller
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
        // $agent = null;
        // $agent = ($agent['agent'] !== null) ? $agent['agent'] : $agent;
        $menu = 'Customer';
        // dump($agent);
        $client = new Client();
        $host = config('keys.url_api');
        // $path = 'role_users?per_page=2000&_includes=user,role,user.transactions,user.addresses&role_id=4&_sortDir=desc';
        $path = ($role === 'agent' || $role === 'cashier') ? 'transactions?_includes=user,sender,user.addresses&agent_id=' . $agent['id'] . '&per_page=30000&_sortDir=desc' :
            'senders?_includes=user,user.addresses&per_page=30000&_sortDir=desc';
        $host = $host . $path;
        // dump($host);
        $customers = $client->get($host, [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $customers = json_decode($customers->getBody()->getContents(), true)['data'];
        $custs = [];
        // dump($customers);
        foreach ($customers as $cu) {
            if ($cu['user']['status'] !== 'Rejected') {
                if (!isset($cu['sender'])) { // si il ne trouve pas les senders
                    $value = [
                        'user' => $cu['user'],
                        'sender' => $cu
                    ];
                    // @unlink($value['sender']['user']); // supprimer user de sender
                } else { // si trouve les transac
                    $value = [
                        'user' => $cu['user'],
                        'sender' => $cu['sender']
                    ];
                }
                // $obj_merged = (object) array_merge((array) $value['user'], (array) $value['sender']);
                array_push($custs, (array)$value);
            }
        }
        // $custs = (array) json_decode($custs, true);
        $customers = array_unique($custs, SORT_REGULAR); // retire les doublons du tableau
        // dump($customers);
        return view('customers.index', compact('token', 'role', 'user', 'menu', 'customers'));
    }

    public function create(Request $request)
    {
        $email_exist = '';
        $phone_exist = '';
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'Customer';
        $sexes = array(
            array('value' => 'M', 'name' => 'Masculin'),
            array('value' => 'F', 'name' => 'Feminin')
        );
        $typeCartes = array(
            array('value' => 'CNI', 'name' => 'Carte nationnal d\'identité'),
            array('value' => 'Passport', 'name' => 'Passport')
        );
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
            // dump($roles);
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
                    $us = [
                        'first_name' => $request->get('textNom'),
                        'last_name' => $request->get('textPrenom'),
                        'email' => $request->get('txtEmail'),
                        'phone_number' => $request->get('txtPhone'),
                        'role_id' => explode("|", $request->get('textRole'))[0],
                        'password' => $request->get('textPassword'),
                        'admin_id' => $user['id']
                    ];
                    // dump($us);
                    $res = $client->post(config('keys.url_api') . 'auth/signup', [
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
                    $valid = false;
                    $statut = 'waiting';
                    if (!empty($request->get('imgRectos')) && !empty($request->get('imgVersos'))) {
                        $valid = false;
                        $statut = 'Approuved';
                    }
                    $myDate = $request->get('textDateExp');
                    if (!empty($myDate)) {
                        $tab = explode('/', $myDate);
                        $myDate = $tab[2] . '-' . $tab[0] . '-' . $tab[1];
                    }
                    $sender = [
                        'cni_number' => $request->get('textNumero'),
                        'country' => 'Congo',
                        'sex' => $request->get('textSexe'),
                        'type_id' => $request->get('textCarte'),
                        'date_exp_id' => $myDate,
                        'valid' => $valid,
                        'user_id' => $res['data']['user']['id'],
                        'status' => $statut
                    ];
                    // dump($sender);
                    $send = $client->post(config('keys.url_api') . 'senders', [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json'
                        ],
                        'json' => $sender
                    ]);
                    $send = json_decode($send->getBody()->getContents(), true);
                    // dump($send);
                    if ($send !== null) {
                        $image = [
                            'cni_picture' => $request->get('imgRectos'),
                            'justif_picture' => $request->get('imgVersos'),
                            'sender_id' => $send['id'] // sender ID
                        ];
                        // dump($image);
                        $this->addImg($image);
                    }

                    /*$img = [
                        'avatar_picture' => $request->get('imgUser'),
                        'logo_picture' => $request->get('imgLogos'),
                        'user_id' => $res['data']['user']['id'],
                        'agent_id' => '',
                    ];*/
                    // dump($img);
                    // if (explode("|", $request->get('textRole'))[1] === 'Administrateur') {
                    // $this->addImg($img);
                    return redirect()->route('customer_list')->with('success', 'Le customer a été ajouté avec succès.');
                }
            } catch (\Exception $e) {

                // dump($e->getMessage());
                \Session::flash('error', 'Erreur lors de l\'enregistrement, bien vouloir reéssayer.');
                //                return view('users.add', compact('email_exist', 'phone_exist', 'user', 'menu', 'towns', 'roles', 'role'))
                //                    ->with('error', 'Erreur lors de l\'enregistrement, bien vouloir reéssayer.');
            }
        }
        $type = 'add';
        $userEdit = '';
        return view('customers.add', compact('userEdit', 'type', 'email_exist', 'phone_exist', 'user', 'menu', 'towns', 'roles', 'role', 'agents', 'sexes', 'typeCartes'));
    }

    public function addImg($img)
    {
        try {
            // dump($img);
            $client = new Client();
            $images = $client->post(config('keys.url_api') . 'images', [
                'verify' => false,
                'headers' => [
                    'content-type' => 'application/x-www-form-urlencoded'
                ],
                'form_params' => $img
            ]);
            $images = json_decode($images->getBody()->getContents(), true);
            // dump($images);
        } catch (\Exception $exception) {
            // dump($exception->getMessage());
        }
    }

    public function edit(Request $request, $id)
    {
        $email_exist = '';
        $phone_exist = '';
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'Customer';
        $sexes = array(
            array('value' => 'M', 'name' => 'Masculin'),
            array('value' => 'F', 'name' => 'Feminin')
        );
        $typeCartes = array(
            array('value' => 'CNI', 'name' => 'Carte nationnal d\'identité'),
            array('value' => 'Passport', 'name' => 'Passport')
        );
        try {
            $client = new Client();
            $sender = $client->get(config('keys.url_api') . 'senders?user_id=' . $id, [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $sender = json_decode($sender->getBody()->getContents(), true)['data'];
            $userEdit = $client->get(config('keys.url_api') . 'users/' . $id . '?&_includes=agent,addresses,user_roles.role,addresses.town', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $userEdit = json_decode($userEdit->getBody()->getContents(), true);
            $ph = explode('242', $userEdit['phone_number']);
            $userEdit['phone_number'] = count($ph) === 1 ? $ph[0] : $ph[1];
            $userEdit['sender'] = (count($sender) > 0) ? $sender[0] : null;
            // dump($userEdit);
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
            // dump($roles);
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
                $countPhn = 0;
                $countEml = 0;
                // dd($request);
                // foreach ($users as $u) {
                //     if ($u['phone_number'].'' === '242' . $request->get('txtPhone')) {
                //         $countPhn += 1;
                //         if ($countPhn > 1) {
                //             \Session::flash('error', 'Ce telephone existe dejà');
                //             $see = true;
                //         }
                //     }
                //     if ($u['email'] === $request->get('txtEmail')) {
                //         $countEml += 1;
                //         if ($countEml > 1) {
                //             \Session::flash('error', 'Cet email existe dejà');
                //             $see = true;
                //         }
                //     }
                // }
                // if (!$see && $countPhn <= 1 && $countEml <= 1) {

                // $p = explode('242', $request->get('txtPhone'));
                $myTap = $request->get('valueOfTab');
                if ($myTap === 'info') {
                    $us = [
                        'first_name' => $request->get('textNom'),
                        'last_name' => $request->get('textPrenom'),
                        'admin_id' => $user['id'],
                        // 'is_active' => $request->get('textStatus') === '1' ? 1 : 0,
                        // 'status' => $request->get('textStatus') === '1' ? 1 : 0,
                        // 'phone_number' => count($p) === 1 ? '242'. $p[0] : '242'. $p[1]
                    ];
                    // if ($countPhn === 1) unset($us['phone_number']);
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

                    if ($userEdit['sender'] !== null) {
                        $dateDeliv = new \DateTime($request->get('textDateDeliv'));
                        $dateDeliv = $dateDeliv->format('Y-m-d');

                        $dateExpir = new \DateTime($request->get('textDateExp'));
                        $dateExpir = $dateExpir->format('Y-m-d');

                        $usSend = [
                            'sex' => $request->get('textSexe'),
                            'type_id' => $request->get('textCarte'),
                            'date_exp_id' => $dateExpir,
                            'issuer_date' => $dateDeliv,
                            'issuer_country' => $request->get('lieuDeDeliv'),
                            'postal_code' => $request->get('textCodePos'),
                            'cni_number' => $request->get('textNumero'),
                            'title' => ($request->get('textSexe') === 'F') ? 'Mme.' : 'Mr.',
                        ];
                        $resSender = $client->put(config('keys.url_api') . 'senders/' . $userEdit['sender']['id'], [
                            'verify' => false,
                            'headers' => [
                                'Content-Type' => 'application/json',
                                'Authorization' => 'Bearer ' . $token
                            ],
                            'json' => $usSend
                        ]);
                        $resSender = json_decode($resSender->getBody()->getContents(), true);
                    }
                }
                // dump($userEdit);
                if ($myTap === 'adresse' && count($userEdit['addresses']) > 0) {
                    $address = [
                        'name' => $request->get('txtAdresse'),
                        'town_id' => $request->get('txtTowns'),
                        'province' => $request->get('txtProvince')
                    ];
                    $resAdr = $client->put(config('keys.url_api') . 'addresses/' . $userEdit['addresses'][0]['id'], [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ],
                        'json' => $address
                    ]);
                    $resAdr = json_decode($resAdr->getBody()->getContents(), true);
                }
                if ($myTap === 'pieces' && $userEdit['sender'] !== null) {
                    $image = [
                        'cni_picture' => $request->get('imgRectos'),
                        'justif_picture' => $request->get('imgVersos'),
                        'sender_id' => $userEdit['sender']['id'] // sender ID
                    ];
                    // dump($image);
                    $this->addImg($image);
                }

                //                    dump($address);
                // if ((count($userEdit['addresses']) > 0 && $userEdit['addresses'][0]['name'] !== $request->get('txtAdresse')) || count($userEdit['addresses']) === 0) {
                //     $res1 = $client->post(config('keys.url_api') . 'addresses', [
                //         'verify' => false,
                //         'headers' => [
                //             'Content-Type' => 'application/json',
                //             'Authorization' => 'Bearer ' . $token
                //         ],
                //         'json' => $address
                //     ]);
                //     $res1 = json_decode($res1->getBody()->getContents(), true);
                // }

                return redirect()->route('customer_list')->with('success', 'Le customer a été modifié avec succès.');

                // }
            } catch (\Exception $e) {
                $erreurM = $this->extractErrorMessage($e, 'Une erreur est survenue lors de l\'enregistrement. Veuillez réessayer.');
                \Session::flash('error', 'Erreur lors de l\'enregistrement : ' . $erreurM);
            }
        }
        $type = 'edit';
        return view('customers.edit', compact('userEdit', 'type', 'email_exist', 'phone_exist', 'user', 'menu', 'towns', 'roles', 'role', 'agents', 'typeCartes', 'sexes'));
    }

    public function show(Request $request, $id)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $agent = $request->session()->get('agent');
        $menu = 'Customer';
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
            // if ($role === 'administrator' || $role === 'finance_manager' || $role === 'csa' || ($role === 'agent' && $userV['agent']['agent_id'] === $agent['id'])) {

            $transactions = $client->get(config('keys.url_api') . 'transactions?_includes=sender,sender.user,user,user.agent,outbound.bank,outbound.mobile&user_id=' . $userV['id'] . '&limit=5&_sortDir=desc', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $transactions = json_decode($transactions->getBody()->getContents(), true)['data'];
            // dump($transactions);

            $sender = $client->get(config('keys.url_api') . 'senders?user_id=' . $id, [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $sender = json_decode($sender->getBody()->getContents(), true);
            $sender = ($sender['total'] > 0) ? $sender['data'][0] : null;
            // dump($sender);
            // } else {
            //     return redirect()->route('home');
            // }
            // dump($transactions);
        } catch (\Exception $e) {
            //            dump($e->getMessage());
            //            return redirect()->route('login');
        }
        return view('customers.show', compact('user', 'menu', 'userV', 'transactions', 'role', 'sender'));
    }


    /*public function delete(Request $request)
    {
        if ($request->getMethod() === 'POST') {
            try {
                $id = $request->get('id_delete');
//                dump($id);
                $token = $request->session()->get('token');
                $client = new Client();
                $transactions = $client->get(config('keys.url_api') . 'transactions?user_id=' . $id . '&per_page=1', [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ]
                ]);
                $transactions = json_decode($transactions->getBody()->getContents(), true)['data'];
                // dump($transactions);
                if (count($transactions) === 0) {
                    $user = $client->delete(config('keys.url_api') . 'users/' . $id, [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ]
                    ]);
                    $user = json_decode($user->getBody()->getContents(), true);
                // dump($town);
                    return redirect()->route('customer_list')->with('success', 'L\'Utilisateur a été supprimé avec succès.');
                } else {
                    return redirect()->route('customer_list')->with('error', 'Cet utilisateur a déjà validation une transaction, bien vouloir le desactiver.');
                }
            } catch (\Exception $e) {

                dump($e->getMessage());
                // return redirect()->route('customer_list')->with('error', 'Erreur lors de la suppression, bien vouloir reéssayer..');
            }
        }
    }*/

    public function delete(Request $request)
    {
        if ($request->getMethod() === 'POST') {
            try {
                $id = $request->get('id_delete');
                $idSender = $request->get('id_deleteS');
                // dump($id);
                $token = $request->session()->get('token');
                $user = $request->session()->get('user');
                // dump($token);
                $client = new Client();
                $us = [
                    'is_active' => 0,
                    'admin_id' => $user['id'],
                    'status' => 'Rejected'
                ];
                // dump($us);
                $res = $client->put(config('keys.url_api') . 'users/' . $id, [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ],
                    'json' => $us
                ]);
                $sus = ['status' => 'Rejected'];
                // dump($sus, $idSender, $id);
                $rens = $client->put(config('keys.url_api') . 'senders/' . $idSender, [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ],
                    'json' => $us
                ]);
                // dump($res);
                return redirect()->route('customer_list')->with('success', 'L\'opération a réussie.');
            } catch (\Exception $e) {
                // dump($e->getMessage());
                return redirect()->route('customer_list')->with('error', 'Erreur lors de la suppression, bien vouloir reéssayer..');
            }
        }
    }

    public function validateCustomer(Request $request)
    {
        if ($request->getMethod() === 'POST') {
            try {
                $id = $request->get('id_validate');
                $idSender = $request->get('id_validateS');
                // dump($id);
                $token = $request->session()->get('token');
                $user = $request->session()->get('user');
                $client = new Client();
                $us = [
                    'is_active' => 1,
                    'admin_id' => $user['id'],
                    'status' => 'approuved'
                ];
                // dump($us);
                $res = $client->put(config('keys.url_api') . 'users/' . $id, [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ],
                    'json' => $us
                ]);
                $sus = ['status' => 'approuved'];
                // dump($sus, $idSender, $id);
                $rens = $client->put(config('keys.url_api') . 'senders/' . $idSender, [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ],
                    'json' => $us
                ]);

                // La validation d'un customer doit aussi lui attribuer le rôle
                // "customer" (table role_user) : le mobile vérifie ce rôle avant
                // d'autoriser un envoi, et sans cette attribution un client
                // marqué "approuved" ici n'est jamais reconnu côté mobile.
                $this->ensureCustomerRole($client, $token, $id);

                // dump($res);
                return redirect()->route('customer_list')->with('success', 'L\'opération a réussie.');
            } catch (\Exception $e) {

                // dump($e->getMessage());
                return redirect()->route('customer_list')->with('error', 'Erreur lors de la validation du customer, bien vouloir reéssayer..');
            }
        }
    }

    /**
     * S'assure que l'utilisateur possède bien le rôle "customer" (role_user).
     * Recherche dynamiquement l'id du rôle "customer" via l'API, avec repli
     * sur l'id 4 (convention déjà utilisée ailleurs dans cette codebase, voir
     * TransactionController::index -> role_id === 4).
     * N'interrompt jamais le flux d'approbation si l'attribution échoue (le
     * rôle peut déjà exister, l'API renvoie alors une erreur qu'on ignore).
     */
    private function ensureCustomerRole(Client $client, $token, $userId)
    {
        try {
            $roleId = 4;
            $rolesRes = $client->get(config('keys.url_api') . 'roles?name=customer', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $roles = json_decode($rolesRes->getBody()->getContents(), true);
            if (!empty($roles['data'][0]['id'])) {
                $roleId = $roles['data'][0]['id'];
            }

            $client->post(config('keys.url_api') . 'role_users', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ],
                'json' => [
                    'user_id' => $userId,
                    'role_id' => $roleId,
                    'user_type' => 'App\User'
                ]
            ]);
        } catch (\Exception $e) {
            // Le rôle existe déjà pour cet utilisateur (l'API renvoie une
            // erreur "Object already exists" dans ce cas) ou une erreur
            // réseau ponctuelle : on ignore silencieusement, l'important est
            // de ne jamais bloquer l'approbation du customer pour ça.
        }
    }

    public function showTransactiomUser(Request $request, $id)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $agt = $request->session()->get('agent');
        $menu = 'Customer';
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
            // if ($role === 'administrator' || ($role === 'agent' && $userV['agent']['agent_id'] === $agt['id'])) { $userV['id']
            // dump($userV);
            // $transactions = $client->get(config('keys.url_api') . 'transactions?_includes=sender,sender.user,user,user.agent,outbound.bank,outbound.mobile', [
            $transactions = $client->get(config('keys.url_api') . 'transactions?_includes=sender,agent,sender.user,user,outbound.bank,outbound.mobile&user_id=' . $id . '&per_page=30000&_sortDir=desc', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $transactions = json_decode($transactions->getBody()->getContents(), true)['data'];
            // dump($transactions);
            // } else {
            //     return redirect()->route('logout');
            // }
            // dump($transactions, 'Customer : ', $id);
        } catch (\Exception $e) {
            //            dump($e->getMessage());
            //            return redirect()->route('login');
        }
        return view('customers.transaction', compact('user', 'menu', 'userV', 'transactions', 'role'));
    }

    public function cancelTransaction(Request $request)
    {
        if ($request->getMethod() === 'POST') {
            try {
                $id = $request->get('id_delete');
                // dump($id);
                $token = $request->session()->get('token');
                $client = new Client();
                $us = [
                    'transaction_status' => 'cancelled'
                ];
                // dump($us);
                $res = $client->put(config('keys.url_api') . 'transactions/' . $id, [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ],
                    'json' => $us
                ]);
                // dump('Modification OK : ', $res);
                return redirect()->route('customer_list')->with('success', 'L\'opération a réussie.');
            } catch (\Exception $e) {

                // dump($e->getMessage());
                return redirect()->route('customer_list')->with('error', 'Erreur lors de l\'annulation de la transaction, bien vouloir reéssayer..');
            }
        }
    }
}
