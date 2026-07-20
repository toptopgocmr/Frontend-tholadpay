<?php

namespace App\Http\Controllers;

use Cassandra\Date;
use GuzzleHttp\Client;
use Illuminate\Http\File;
use Illuminate\Http\Request;

class PrefundController extends Controller
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
        // dump($user, $agent, $role);
        $menu = 'Prefund';
        $client = new Client();
        $host = config('keys.url_api');
        $path = ($role === 'administrator' || $role === 'finance_manager') ? 'prefundings?per_page=3000&_includes=agent&_sortDir=desc' :
            'prefundings?agent_id=' . $agent['id'] . '&per_page=2000';
        $host = $host . $path;
        $prefunds = $client->get($host, [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $prefunds = json_decode($prefunds->getBody()->getContents(), true)['data'];
//        dump($prefunds);
        return view('prefunds.index', compact('token', 'role', 'user', 'menu', 'prefunds'));
    }

    public function create(Request $request)
    {
        $email_exist = '';
        $phone_exist = '';
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $agent = $request->session()->get('agent');
        $menu = 'Prefund';
        $type = 'add';
        try {
            $client = new Client();
            $prefunds = $client->get(config('keys.url_api') . 'prefundings?per_page=300', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $prefunds = json_decode($prefunds->getBody()->getContents(), true)['data'];
            $prefund = count($prefunds) > 0 ? $prefunds[0] : '';
        } catch (\Exception $e) {
//            dump($e->getMessage());
//            return redirect()->route('login');
        }
        if ($request->getMethod() === 'POST') {
            try {
                $typeP = $request->get('typeP');
                $amount = $request->get('amount');
                $description = $request->get('description');
                $dt = new \DateTime();
                $pref = [
                    'paiement_type' => $typeP,
                    'amount' => $amount,
                    'description' => $description,
                    'date_paiement' => $dt->format('Y-m-d H:i:s'),
                    'status' => 'New',
                    'valid' => false,
                    'agent_id' => $agent['id']
                ];
//                dump($pref);
                $res = $client->post(config('keys.url_api') . 'prefundings', [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ],
                    'json' => $pref
                ]);
                $res = json_decode($res->getBody()->getContents(), true);
//                dump($res);
                $img = [
                    'prove_picture' => $request->get('imgPr'),
                    'prefunding_id' => $res['id']
                ];
//                dump($img);
                $this->addImg($img);
                return redirect()->route('prefund_list')->with('success', 'Le préfinancement a été ajouté avec succès.');
//                    dump($res);
            } catch (\Exception $e) {
//                dump($e->getMessage());
                \Session::flash('error', 'Erreur lors de l\'enregistrement, bien vouloir reéssayer.');
//                return view('users.add', compact('email_exist', 'phone_exist', 'user', 'menu', 'towns', 'roles', 'role'))
//                    ->with('error', 'Erreur lors de l\'enregistrement, bien vouloir reéssayer.');
            }
        }
        return view('prefunds.add', compact('user', 'menu', 'role', 'prefund', 'type'));
    }

    public function edit(Request $request, $id)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'Prefund';
        $client = new Client();
        $prefund = $client->get(config('keys.url_api') . 'prefundings/' . $id . '?_includes=agent', [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $prefund = json_decode($prefund->getBody()->getContents(), true);
        $type = 'edit';
        if ($request->getMethod() === 'POST') {
            try {
                if (!$prefund['valid']) {
                    $typeP = $request->get('typeP');
                    $status = $request->get('status');
                    $dt = new \DateTime();
                    $prefund['status'] = $status;
                    $prefund['description'] = $request->get('description');;
                    $prefund['paiement_type'] = $typeP;
                    // $prefund['paiement_type'] = $typeP;
                    $prefund['updated_at'] = $dt->format('Y-m-d H:i:s');
                    // $prefund['valid'] = $status === 'Validated' ? true : false;
                    // dump($prefund);paiement_type
                    $res = $client->put(config('keys.url_api') . 'prefundings/' . $id, [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ],
                        'json' => $prefund
                    ]);
                    $res = json_decode($res->getBody()->getContents(), true);
                    // Utiliser $res['valid'] (données fraîches après PUT) et non $prefund['valid']
                    // (données avant mise à jour) pour éviter la double validation
                    if ($res['status'] === 'Validated') {
                        if (!$res['valid']) {
                            $agent = $client->get(config('keys.url_api') . 'agents/' . $prefund['agent']['id'], [
                                'verify' => false,
                                'headers' => [
                                    'Content-Type' => 'application/json',
                                    'Authorization' => 'Bearer ' . $token
                                ]
                            ]);
                            $agent = json_decode($agent->getBody()->getContents(), true);
                            $agent['solde'] += $prefund['amount'];
                            $agent['updated_at'] = date('Y-m-d H:i:s');
                            unset($agent['agent_id']);
                            $rs = $client->put(config('keys.url_api') . 'agents/' . $prefund['agent']['id'], [
                                'verify' => false,
                                'headers' => [
                                    'Content-Type' => 'application/json',
                                    'Authorization' => 'Bearer ' . $token
                                ],
                                'json' => $agent
                            ]);
                            $rs = json_decode($rs->getBody()->getContents(), true);
                            // Marquer le préfinancement comme validé (valid = true)
                            $r = $client->put(config('keys.url_api') . 'prefundings/' . $id, [
                                'verify' => false,
                                'headers' => [
                                    'Content-Type' => 'application/json',
                                    'Authorization' => 'Bearer ' . $token
                                ],
                                'json' => ['valid' => true]
                            ]);
                            $r = json_decode($r->getBody()->getContents(), true);
                        } else {
                            return redirect()->route('prefund_list')->with('success', 'Le préfinancement a déjà été validé.');
                        }
                    }
                    return redirect()->route('prefund_list')->with('success', 'Le préfinancement a été modifié avec succès.');
                } else {
                    return redirect()->route('prefund_list')->with('success', 'Le préfinancement a déjà été validé.');
                }
            } catch (\Exception $e) {
                // dump($e->getMessage());
                \Session::flash('error', 'Erreur lors de la modification, bien vouloir reéssayer.');
            }
        }
        return view('prefunds.edit', compact('user', 'menu', 'role', 'prefund', 'type'));
    }

    public function show(Request $request, $id)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $agent = $request->session()->get('agent');
        $menu = 'Prefund';
        $client = new Client();
        $prefund = $client->get(config('keys.url_api') . 'prefundings/' . $id . '?_includes=agent,agent.agent', [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $prefund = json_decode($prefund->getBody()->getContents(), true);
        /*dump($prefund);
        dd($agent);
        if ($prefund['agent']['id'] === $agent['id'] || $role === 'administrator' || $prefund['agent']['agent']['id'] === $agent['id']) {
        } else {
            return redirect()->route('admin');
        }*/
        $isOwner = isset($prefund['agent']['id']) && $prefund['agent']['id'] === $agent['id'];
        $isSubAgent = isset($prefund['agent']['agent']['id']) && $prefund['agent']['agent']['id'] === $agent['id'];
        $isAdmin11 = $role === 'administrator';
        $isFinanceManager = $role === 'finance_manager';
        if ($isOwner || $isAdmin11 || $isSubAgent || $isFinanceManager) {
            // autorisé
            
        } else {
            return redirect()->route('admin');
        }
        // dump($prefund);
        return view('prefunds.show', compact('token', 'role', 'user', 'menu', 'prefund'));
    }

    public function prefund(Request $request, $id)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $agt = $request->session()->get('agent');
        // dump($agt);
        $menu = 'Prefund';
        $client = new Client();
        if ($role === 'agent' || $role === 'administrator') {
            $agent = $client->get(config('keys.url_api') . 'agents/' . $id . '?_includes=user,user.user_roles.role', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $agent = json_decode($agent->getBody()->getContents(), true);
//            dump($agent);
            if (($agent['user']['user_roles'][0]['role']['name'] === 'agent' && $role === 'administrator') ||
                ($agent['user']['user_roles'][0]['role']['name'] === 'cashier' && ($role === 'agent' && $agt['id'] === $agent['agent_id']))) {
                if ($request->getMethod() === 'POST') {
                    try {
                        $amount = $request->get('amount');
                        if (intval($amount) > 0 && intval($amount) < ($role === 'administrator' ? intval($agent['solde']) : intval($agt['solde_utilisable']))) {
                            if ($role === 'administrator') {
                                $agent['solde'] = intval($agent['solde']) - intval($amount);
                                $agent['solde_utilisable'] = intval($agent['solde_utilisable']) + intval($amount);
                                unset($agent['agent_id']);
                                $res = $client->put(config('keys.url_api') . 'agents/' . $id, [
                                    'verify' => false,
                                    'headers' => [
                                        'Content-Type' => 'application/json',
                                        'Authorization' => 'Bearer ' . $token
                                    ],
                                    'json' => $agent
                                ]);
                                $res = json_decode($res->getBody()->getContents(), true);
                            } else {
                                $agent['solde'] = intval($agent['solde']) + intval($amount);
                                $agent['solde_utilisable'] = intval($agent['solde_utilisable']) + intval($amount);
//                                unset($agent['agent_id']);
                                $res = $client->put(config('keys.url_api') . 'agents/' . $id, [
                                    'verify' => false,
                                    'headers' => [
                                        'Content-Type' => 'application/json',
                                        'Authorization' => 'Bearer ' . $token
                                    ],
                                    'json' => $agent
                                ]);
                                $res = json_decode($res->getBody()->getContents(), true);
                                $agt['solde_utilisable'] = intval($agt['solde_utilisable']) - intval($amount);
                                unset($agt['agent_id']);
                                $rs = $client->put(config('keys.url_api') . 'agents/' . $agt['id'], [
                                    'verify' => false,
                                    'headers' => [
                                        'Content-Type' => 'application/json',
                                        'Authorization' => 'Bearer ' . $token
                                    ],
                                    'json' => $agt
                                ]);
                                $rs = json_decode($rs->getBody()->getContents(), true);
                            }
                            \Session::flash('succes', 'Préfinancement effectué avec succès.');
                            return redirect()->route('user_show', $agent['user']['id']);
                        } else {
                            \Session::flash('error', 'Erreur lors de l\'enregistrement, bien vouloir reéssayer.');
                        }
                    } catch (\Exception $exception) {
//                        dump($exception->getMessage());
                        \Session::flash('error', 'Erreur lors de l\'enregistrement, bien vouloir reéssayer.');
                    }
                }
                return view('prefunds.prefund', compact('token', 'role', 'user', 'menu', 'agent', 'agt'));
            } else {
                return redirect()->route('logout');
            }
        } else {
            return redirect()->route('logout');
        }
    }

    public function delete(Request $request)
    {
        if ($request->getMethod() === 'POST') {
            try {
                $id = $request->get('id_delete');
//                dump($id);
                $token = $request->session()->get('token');
                $client = new Client();
                $prefund = $client->delete(config('keys.url_api') . 'prefundings/' . $id, [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ]
                ]);
                $prefund = json_decode($prefund->getBody()->getContents(), true);
//                dump($prefund);
                return redirect()->route('prefund_list')->with('success', 'Le préfinancement a été supprimé avec succès.');
            } catch (\Exception $e) {
//                dump($e->getM essage());
                return redirect()->route('prefund_list')->with('error', 'Erreur lors de la suppression, bien vouloir reéssayer..');
            }
        }
    }

    public function addImg($img)
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
}
