<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @return mixed
     * @throws
     */
    public function index(Request $request)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $agent = $request->session()->get('agent');
        $menu = 'Transaction';
        // Résoudre l'agent : si l'agent a lui-même un agent parent, utiliser le parent
        if ($agent !== null && isset($agent['agent']) && $agent['agent'] !== null) {
            $agent = $agent['agent'];
        }
        $transactions = [];
        $date = new \DateTime();
        $day = new \DateTime();
        $date_start = new \DateTime();
        $client = new Client();
        try {
            $d = new \DateTime();
            // $d = $d->format('Y-m-d');
            $d = @gmdate('Y-m-d');
            $host = config('keys.url_api');
            if ($role === 'administrator') {
                $path = 'transactions?_includes=notes,sender,sender.user,agent,user,user.agent,outbound.bank,outbound.mobile&per_page=3000&created_at=' . $d . '&_sortDir=desc';
            } else if ($role === 'csa' || $role === 'finance_manager' || $role === 'technical_support') {
                $path = 'transactions?_includes=notes,sender,sender.user,agent,user,user.agent,outbound.bank,outbound.mobile&per_page=3000&created_at=' . $d . '&_sortDir=desc';
            } else {
                $path = 'transactions?agent_id=' . $agent['id'] . '&_includes=notes,sender,sender.user,agent,user,user.agent,outbound.bank,outbound.mobile&per_page=3000&created_at=' . $d . '&_sortDir=desc';
            }

            // $path = 'transactions?_includes=notes,sender,sender.user,agent,user,user.agent,outbound.bank,outbound.mobile&per_page=3000&created_at='.$d.'&_sortDir=desc';
            $host = $host . $path;
            $transactions = $client->get($host, [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $transactions = json_decode($transactions->getBody()->getContents(), true)['data'];
            $trans = [];
            $tr['valid'] = null;
            $tr['isnote'] = '0';
            foreach ($transactions as $tr) {
                if (count($tr['notes']) > 0)
                    $tr['isnote'] = '1';
                if ($tr['valid_id'] !== null) {
                    $usr = $client->get(config('keys.url_api') . 'users/' . $tr['valid_id'], [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ]
                    ]);
                    $usr = json_decode($usr->getBody()->getContents(), true);
                    $tr['valid'] = $usr;
                }
                array_push($trans, $tr);
            }
            $transactions = $trans;
            // dump($transactions);

            $trans = [];
            foreach ($transactions as $tr) { // on check le status des transaction envoyer a terrapay
                $tr['valid'] = null;
                $tr['isnote'] = '0';
                if (count($tr['notes']) > 0)
                    $tr['isnote'] = '1';

                // @unlink($tr['notes']);
                if ($tr['transaction_status'] === 'approuved' && $tr['reference'] !== '' && $tr['date_init'] === $d && $tr['etat_transac'] !== 'success' && $tr['etat_transac'] !== 'failed') {
                    // dump($tr);
                    $tar = [
                        'referenceID' => $tr['reference'],
                        'client_id' => $tr['corridor_id'],
                        // FIX (2026-07-06) : indique au backend quel service Peex interroger
                        // (Remittance/clients vs Disbursement) — voir OutboundController::
                        // check_transaction_status(). Sans ça, les virements bancaires
                        // n'étaient jamais trouvés (toujours interrogés via Disbursement).
                        'type' => (isset($tr['outbound']['bank']) && $tr['outbound']['bank'] !== null) ? 'bank' : 'mobile',
                    ];
                    $res = $client->post(config('keys.url_api') . 'check_transaction_status', [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ],
                        'json' => $tar
                    ]);
                    $res = json_decode($res->getBody()->getContents(), true);
                    // dump($res, $tr);
                    // NETTOYAGE TERRAPAY -> PEEX (2026-07-04) : ce bloc supposait un format
                    // {status:200|400, message, transaction:{transaction_reference,
                    // transaction_status, updated_at}, transactionStatus} — hérité de
                    // l'ancienne API TerraPay. OutboundController::check_transaction_status()
                    // relaie en réalité soit :
                    //   - une ERREUR de notre backend/Peex : {status:<code HTTP>, message}
                    //     (ex: 404 "Transactions not found... (3 days)") ;
                    //   - un TABLEAU de transactions Peex (doc:
                    //     https://peex-api-docs.peexit.com/disbursement/all-request) :
                    //     [{id, track_id, amount, status, currency, identifier_by, type, ...}],
                    //     avec status parmi new|pending|paid|failed|canceled|rejected.
                    // Avec l'ancien test `$res['status'] === 200`, cette clé n'était JAMAIS
                    // 200 dans aucun des deux cas -> la branche succès n'était jamais
                    // atteinte, et `=== 400` non plus (nos erreurs renvoient de vrais codes
                    // HTTP 401/404/500) -> les transactions n'étaient donc jamais mises à
                    // jour automatiquement, quel que soit leur statut réel chez Peex.
                    $isBackendError = isset($res['status']) && isset($res['message']);
                    $peexTx = (!$isBackendError && is_array($res) && isset($res[0])) ? $res[0] : null;
                    $peexStatus = $peexTx['status'] ?? null;

                    if ($peexTx !== null && $peexStatus === 'paid') {
                        $maTrans = [
                            'referenceID' => $peexTx['track_id'] ?? $tr['reference'],
                            'payer' => 1,
                            'etat_transac' => 'success',
                            'date_complete' => @date('Y-m-d'),
                            'observations' => 'Transaction payée avec succès (Peex, statut "paid").'
                        ];
                        // dump($maTrans);
                        $trans = $client->put(config('keys.url_api') . 'transactions/' . $tr['id'], [
                            'verify' => false,
                            'headers' => [
                                'Content-Type' => 'application/json',
                                'Authorization' => 'Bearer ' . $token
                            ],
                            'json' => $maTrans
                        ]);
                        $trans = json_decode($trans->getBody()->getContents(), true);
                        if ($maTrans['payer'] === 1) {
                            $phone_sender =  $tr['user']['phone_number'];
                            $txtFrom = "TholadPay";
                            $successSMS = [
                                "from" =>  $txtFrom,
                                "to" =>  $phone_sender,
                                "text" =>  "Cher(e) client(e), votre transaction TholadPay N° " . $tr['ranking'] . " vers " . $tr['receiving_country'] . " a été effectuée avec succès. TholadPay vous remercie."
                            ];
                            $resSend = $client->post(config('keys.url_api') . 'auth/send_sms_to_phone', [
                                'verify' => false,
                                'headers' => [
                                    'Content-Type' => 'application/json'
                                ],
                                'json' => $successSMS
                            ]);
                            $resSend = json_decode($resSend->getBody()->getContents(), true);
                        }
                    } else if ($peexTx !== null && in_array($peexStatus, ['failed', 'rejected', 'canceled'])) {
                        // Transaction définitivement en échec côté Peex.
                        if ($tr['etat_transac'] !== 'failed') {
                            $manage = 'Transaction Peex : statut "' . $peexStatus . '".';
                            $maTrans = [
                                'etat_transac' => 'failed',
                                'date_complete' => @date('Y-m-d'),
                                'observations' => $manage
                            ];
                            // dump($maTrans);
                            $trans = $client->put(config('keys.url_api') . 'transactions/' . $tr['id'], [
                                'verify' => false,
                                'headers' => [
                                    'Content-Type' => 'application/json',
                                    'Authorization' => 'Bearer ' . $token
                                ],
                                'json' => $maTrans
                            ]);
                            $trans = json_decode($trans->getBody()->getContents(), true);
                            if ($maTrans['etat_transac'] === 'failed') {
                                $myAgent = $client->get(config('keys.url_api') . 'agents/' . $tr['agent']['id'], [
                                    'verify' => false,
                                    'headers' => [
                                        'Content-Type' => 'application/json',
                                        'Authorization' => 'Bearer ' . $token
                                    ]
                                ]);
                                $myAgent = json_decode($myAgent->getBody()->getContents(), true);

                                $maAgent = [
                                    'solde' => (floatval($myAgent['solde']) + (floatval($tr['amount']) + floatval($tr['fees'])))
                                ];
                                // dump($maAgent, $myAgent, 'MONTANT ' . $tr['amount'],  'FRAIS ' . $tr['fees']);
                                $agen = $client->put(config('keys.url_api') . 'agents/' . $myAgent['id'], [
                                    'verify' => false,
                                    'headers' => [
                                        'Content-Type' => 'application/json',
                                        'Authorization' => 'Bearer ' . $token
                                    ],
                                    'json' => $maAgent
                                ]);
                                $agen = json_decode($agen->getBody()->getContents(), true);
                                // dump('Agent modifier ', $agen);
                                $phone_sender =  $tr['user']['phone_number'];
                                $txtFrom = "THOLADPAY";
                                $rejectedSMS = [
                                    "from" =>  $txtFrom,
                                    "to" =>  $phone_sender,
                                    "text" =>  "Cher(e) client(e), votre transaction TholadPay N° " . $tr['ranking'] . " vers " . $tr['receiving_country'] . " a été rejetée. Prière de vous rapprocher de TholadPay pour plus de détails."
                                ];
                                $resSend = $client->post(config('keys.url_api') . 'auth/send_sms_to_phone', [
                                    'verify' => false,
                                    'headers' => [
                                        'Content-Type' => 'application/json'
                                    ],
                                    'json' => $rejectedSMS
                                ]);
                                $resSend = json_decode($resSend->getBody()->getContents(), true);
                            }
                        }
                    }
                    // else {
                    //     \Session::flash('error', 'Vérifier votre connexion internet !');
                    // }
                }
            }
            // $transactions = $trans;
            // dump($transactions);

        } catch (ClientException $e) {
            $status = $e->getResponse() ? $e->getResponse()->getStatusCode() : 0;
            if ($status === 401) {
                // Token JWT expiré — forcer la reconnexion
                return redirect()->route('logout')->with('error', 'Votre session a expiré. Veuillez vous reconnecter.');
            }
            Log::warning('[TransactionController@index] HTTP ' . $status . ' : ' . ($e->getResponse() ? (string)$e->getResponse()->getBody() : ''));
        } catch (\Exception $e) {
            Log::error('[TransactionController@index] Exception : ' . $e->getMessage());
        }
        return view('transactions.index', compact('token', 'role', 'user', 'menu', 'transactions', 'date_start', 'date', 'day'));
    }



    public function create(Request $request)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'Transaction';
        $type = 'add';
        $transaction = '';
        $typeTransactions = [
            ['label' => 'Bancaire', 'value' => 'Bank'],
            ['label' => 'Mobile', 'value' => 'Mobile']
        ];
        // dump($typeTransactions);

        $client = new Client();
        $transactions = $client->get(config('keys.url_api') . 'transactions?_includes=sender,sender.user,notes,user,user.agent,outbound.bank,outbound.mobile', [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $transactions = json_decode($transactions->getBody()->getContents(), true)['data'];
        // dump($transactions);
        $countries = $client->get(config('keys.url_api') . 'countries', [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $countries = json_decode($countries->getBody()->getContents(), true)['data'];
        $users = $client->get(config('keys.url_api') . 'users?_includes=agent,addresses,user_roles.role', [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $users = json_decode($users->getBody()->getContents(), true)['data'];
        $customers = [];
        foreach ($users as $u) {
            $role = $u['user_roles'];
            if (isset($role[0]['role_id']) && $role[0]['role_id'] === 4) {
                array_push($customers, $u);
            }
        }
        $users = $customers;
        $allTransactions = $client->get(config('keys.url_api') . 'transactions', [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $allTransactions = json_decode($allTransactions->getBody()->getContents(), true)['data'];
        $transaction = count($transactions) > 0 ? $transactions[0] : '';

        $nbreCar = 3;
        $ranking = "KL-" . @sprintf("%0" . $nbreCar . "d", (count($allTransactions) + 1));

        if ($request->getMethod() === 'POST') {
            try {
                $user_id = $request->get('userId');
                $cniNumero = $request->get('numI');
                $dateExpiration = $request->get('dateExp');
                $typeEnvoi = $request->get('typeEnvoi');

                $see = false;
                if (!$see) {
                    $sender = [
                        'user_id' => @trim($user_id),
                        'sex' => 'M',
                        'date_exp_id' => $dateExpiration,
                        'type_id' => 'CNI',
                        'email' => '',
                        'country' => 'Congo',
                        'cni_number' => $cniNumero,
                        'valid' => true
                    ];
                    $res = $client->post(config('keys.url_api') . 'senders', [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ],
                        'json' => $sender
                    ]);
                    $res = json_decode($res->getBody()->getContents(), true);
                    $tabCountry = @explode("|", $request->get('country'));
                    $currency = $tabCountry[1];

                    $infoTRansac = [
                        'recipient_last_name' => $request->get('prenomB'),
                        'recipient_first_name' => $request->get('nomB'),
                        'receiving_country' => $tabCountry[2], // $currency,
                        'recipient_phone' => $request->get('phoneB'),
                        'transaction_reference' => $request->get('origin'),
                        'from_currency' => $currency,
                        'transaction_status' => 'waiting',
                        'aml_cft' => 0,
                        'to_currency' => $currency,
                        'transaction_reason' => $request->get('reason'),
                        'amount' => $request->get('amount'),
                        'sender_id' => $res['id'],
                        'ranking' => $ranking,
                        'user_id' => $user['id']
                    ];
                    $resQuest = $client->post(config('keys.url_api') . 'transactions', [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ],
                        'json' => $infoTRansac
                    ]);
                    $resQuest = json_decode($resQuest->getBody()->getContents(), true);
                    return redirect()->route('transaction_list')->with('success', 'La transaction a été ajoutée avec succès.');

                    $outbound = [
                        'remitance_purpose' => $request->get('reason'),
                        'description' => '',
                        'country' => $tabCountry[2],
                        'transaction_id' => $resQuest['id']
                    ];
                    $resOutbound = $client->post(config('keys.url_api') . 'outbounds', [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ],
                        'json' => $outbound
                    ]);
                    $resOutbound = json_decode($resOutbound->getBody()->getContents(), true);

                    if ($typeEnvoi === 'Bank') {
                        $bank = [
                            'bank_account_no' => $request->get('numTrs'),
                            'short_code' => ' ',
                            'organisation' => ' ',
                            'outbound_id' => $resOutbound['id']
                        ];
                        $resBank = $client->post(config('keys.url_api') . 'mobiles', [
                            'verify' => false,
                            'headers' => [
                                'Content-Type' => 'application/json',
                                'Authorization' => 'Bearer ' . $token
                            ],
                            'json' => $bank
                        ]);
                        $resBank = json_decode($resBank->getBody()->getContents(), true);
                    } else if ($typeEnvoi === 'Mobile') {
                        $mobile = [
                            'mobile_phone_credit' => $request->get('numTrs'),
                            'mobile_phone_debit' => ' ',
                            'outbound_id' => $resOutbound['id']
                        ];
                        $resMobile = $client->post(config('keys.url_api') . 'mobiles', [
                            'verify' => false,
                            'headers' => [
                                'Content-Type' => 'application/json',
                                'Authorization' => 'Bearer ' . $token
                            ],
                            'json' => $mobile
                        ]);
                        $resMobile = json_decode($resMobile->getBody()->getContents(), true);
                    }
                }
            } catch (\Exception $e) {
                \Session::flash('error', 'Erreur lors de l\'enregistrement, bien vouloir reéssayer.');
            }
        }

        return view('transactions.add', compact('user', 'menu', 'role', 'users', 'typeTransactions', 'countries', 'transaction', 'type'));
    }

   public function search(Request $request)
{
    $sd = $request->get('search_start');
    $d = $request->get('search_trs');
    $token = $request->session()->get('token');
    $role = $request->session()->get('role');
    $user = $request->session()->get('user');
    $agent = $request->session()->get('agent');

    // ✅ FIX 1 : ne pas écraser $agent avec null
    // Extraire correctement l'agent depuis la session
    if ($agent !== null && isset($agent['agent'])) {
        $agent = $agent['agent'];
    }

    $menu = 'Transaction';
    $day = new \DateTime();
    $transactions = [];

    // FIX (2026-07-04) : les deux bornes étaient tronquées à minuit (Y-m-d, sans
    // heure). Le backend compare ça à `created_at` (timestamp complet) via
    // created_at-bt (between) — la borne de FIN valait donc implicitement
    // "$dt 00:00:00", ce qui excluait TOUTES les transactions du jour de fin
    // sélectionné (créées après minuit). Résultat : rechercher "aujourd'hui à
    // aujourd'hui" ne renvoyait que les transactions d'avant minuit, donc
    // rien du jour même — d'où l'impression de résultats manquants/décalés
    // d'un jour. On étend maintenant la borne de fin jusqu'à 23:59:59.
    $de = (new \DateTime($sd))->format('Y-m-d') . ' 00:00:00';
    $date_start = new \DateTime($sd);
    $dt = (new \DateTime($d))->format('Y-m-d') . ' 23:59:59';
    $date = new \DateTime($d);

    if ($request->getMethod() === 'POST') {
        try {
            $client = new Client();
            $host = config('keys.url_api');

            // Les bornes contiennent maintenant un espace et des ":" (heure) —
            // encodage requis pour ne pas produire une URL invalide.
            $deEnc = rawurlencode($de);
            $dtEnc = rawurlencode($dt);

            if ($role === 'administrator') {
                $path = 'transactions?_includes=notes,sender,sender.user,agent,user,user.agent,outbound.bank,outbound.mobile&per_page=3000&created_at-bt=' . $deEnc . ',' . $dtEnc . '&_sortDir=desc';
            } else if ($role === 'csa' || $role === 'finance_manager' || $role === 'technical_support') {
                $path = 'transactions?_includes=notes,sender,sender.user,agent,user,user.agent,outbound.bank,outbound.mobile&per_page=3000&etat_transac=success&created_at-bt=' . $deEnc . ',' . $dtEnc . '&_sortDir=desc';
            } else {
                // ✅ FIX 1 (suite) : $agent n'est plus null ici
                $agentId = ($agent !== null && isset($agent['id'])) ? $agent['id'] : 0;
                $path = 'transactions?agent_id=' . $agentId . '&_includes=notes,sender,sender.user,agent,user,user.agent,outbound.bank,outbound.mobile&per_page=3000&created_at-bt=' . $deEnc . ',' . $dtEnc . '&_sortDir=desc';
            }

            $response = $client->get($host . $path, [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);

            // ✅ FIX 2 : vérifier que ['data'] existe avant d'y accéder
            $decoded = json_decode($response->getBody()->getContents(), true);
            $rawTransactions = isset($decoded['data']) ? $decoded['data'] : [];

            // ✅ FIX 3 : plus d'initialisation de $tr avant le foreach
            $trans = [];
            foreach ($rawTransactions as $tr) {
                $tr['isnote'] = (isset($tr['notes']) && count($tr['notes']) > 0) ? '1' : '0';
                $tr['valid'] = null;

                if (isset($tr['valid_id']) && $tr['valid_id'] !== null) {
                    $usr = $client->get(config('keys.url_api') . 'users/' . $tr['valid_id'], [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ]
                    ]);
                    $tr['valid'] = json_decode($usr->getBody()->getContents(), true);
                }
                array_push($trans, $tr);
            }
            $transactions = $trans;

        } catch (\Exception $e) {
            // ✅ Log propre au lieu de dump en production
            \Log::error('TransactionController@search : ' . $e->getMessage());
        }
    }
    return view('transactions.index', compact('token', 'role', 'user', 'menu', 'transactions', 'date', 'date_start', 'day'));
}
    public function show(Request $request, $id)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $agent = $request->session()->get('agent');
        $menu = 'Transaction';
        $transaction = null;
        $date = new \DateTime();
        $day = new \DateTime();
        try {
            $client = new Client();
            $transaction = $client->get(config('keys.url_api') . 'transactions/' . $id . '?_includes=sender,agent,sender.user,user,user.agent,outbound.bank,outbound.mobile', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $transaction = json_decode($transaction->getBody()->getContents(), true);
            // dd($transaction);
            // dump($transaction);
            // dump((string)$transaction['outbound']['bank']['bank_account_no']);
            // BUG corrigé : cette vérification comparait l'ID de l'utilisateur admin/agent
            // connecté à l'ID du CLIENT (transaction['user'], l'émetteur/bénéficiaire du
            // transfert) — ces deux ID ne coïncident quasiment jamais, donc TOUT rôle
            // différent de 'administrator' se faisait déconnecter en cliquant sur "i".
            // On reprend désormais exactement le même périmètre d'accès que index() :
            // les rôles à accès large (administrator/csa/finance_manager/technical_support)
            // voient tout, les autres (agent/cashier) doivent être rattachés au même
            // agent que celui propriétaire de la transaction (transaction.agent_id).
            $fullAccessRoles = ['administrator', 'csa', 'finance_manager', 'technical_support'];
            if (!in_array($role, $fullAccessRoles)) {
                $agentScope = $agent;
                if ($agentScope !== null && isset($agentScope['agent']) && $agentScope['agent'] !== null) {
                    $agentScope = $agentScope['agent'];
                }
                $agentId = $agentScope['id'] ?? null;
                if ($agentId === null || (string) ($transaction['agent_id'] ?? '') !== (string) $agentId) {
                    return redirect()->route('logout')->with('error', 'Vous n\'avez pas accès à cette page.');
                }
            }
        } catch (\Exception $e) {
            // dump($e->getMessage());
        }
        return view('transactions.show', compact('token', 'role', 'user', 'menu', 'transaction', 'date', 'day'));
    }

    /**
     * Reçu imprimable d'une transaction (pending/success uniquement — voir garde
     * ci-dessous). Vue autonome (pas de layout admin) avec impression automatique,
     * ouverte dans un nouvel onglet depuis le bouton "Imprimer le reçu" de
     * transactions.show. Même périmètre d'accès que show() ci-dessus.
     */
    public function receipt(Request $request, $id)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $agent = $request->session()->get('agent');
        $transaction = null;
        try {
            $client = new Client();
            $response = $client->get(config('keys.url_api') . 'transactions/' . $id . '?_includes=sender,agent,sender.user,user,user.agent,outbound.bank,outbound.mobile', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $transaction = json_decode($response->getBody()->getContents(), true);

            $fullAccessRoles = ['administrator', 'csa', 'finance_manager', 'technical_support'];
            if (!in_array($role, $fullAccessRoles)) {
                $agentScope = $agent;
                if ($agentScope !== null && isset($agentScope['agent']) && $agentScope['agent'] !== null) {
                    $agentScope = $agentScope['agent'];
                }
                $agentId = $agentScope['id'] ?? null;
                if ($agentId === null || (string) ($transaction['agent_id'] ?? '') !== (string) $agentId) {
                    return redirect()->route('logout')->with('error', 'Vous n\'avez pas accès à cette page.');
                }
            }
        } catch (\Exception $e) {
            return redirect()->route('transaction_list')->with('error', 'Transaction introuvable.');
        }

        // Le reçu est disponible pour : en attente (acknowledged), réussie (success),
        // rejetée/échouée (failed) ou annulée (cancelled) — cf. demande utilisateur
        // du 2026-07-22 d'avoir un reçu justificatif même pour les transactions
        // rejetées par Peex (statut "REJECTED" côté API -> etat_transac = failed).
        // Seul un état "New" (jamais soumise) reste sans reçu, faute d'info exploitable.
        $etat = $transaction['etat_transac'] ?? null;
        if (!in_array($etat, ['acknowledged', 'success', 'failed', 'cancelled'])) {
            return redirect()->route('transaction_show', $id)->with('error', 'Le reçu n\'est pas disponible pour cette transaction.');
        }

        return view('transactions.receipt', compact('transaction', 'etat'));
    }

    public function update(Request $request, $id)
    {
        $day = new \DateTime();
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $agent = $request->session()->get('agent');
        $type = 'valid';
        $menu = 'Transaction';
        $client_id = 0;
        $clientName = '';
        $currency = 'Congolese franc';
        try {
            $client = new Client();
            $transaction = $client->get(config('keys.url_api') . 'transactions/' . $id . '?_includes=sender,agent,sender.user,user,user.addresses,user.addresses.town,outbound.bank,outbound.mobile&order=desc', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $transaction = json_decode($transaction->getBody()->getContents(), true);
            // dump($transaction);
            if ($transaction['transaction_status'] === 'waiting') {
                $montanTrans = $transaction['amount'] + $transaction['fees'];
                if ($transaction['sender']['status'] !== 'approuved') {
                    return redirect()->route('customer_list')->with('error', 'Veuillez approuver le customer : ' . $transaction['user']['full_name']);
                } else if ($transaction['receiving_country_code'] === null) {
                    return redirect()->route('transaction_list')->with('error', 'Erreur lors de la validation! code pays introuvable.');
                } else if ($montanTrans > $transaction['agent']['solde']) {
                    return redirect()->route('transaction_list')->with('error', 'Erreur : Le montant de la transaction est supérieur au solde disponible !');
                } else {
                    $codeP = $transaction['receiving_country_code'];
                    $currency = $transaction['to_currency'];
                    $partner = $client->get(config('keys.url_api') . 'get_partner?country_code=' . $codeP, [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ]
                    ]);
                    $partner = json_decode($partner->getBody()->getContents(), true);
                    // dump('transaction : ', $transaction, $day->format('Y-m-d'));
                    // dump('Partner : ',$partner, $partner['client']);
                    // dump($day->format('Y-m-d'), ' ', date_create($transaction['created_at'])->format('Y-m-d'));
                    $client_id = isset($partner['client']) ? $partner['client']['id'] : 0;
                    $clientName = isset($partner['client'])  ? $partner['client']['name'] : '';
                    // dump('client_id : '. $client_id);
                    if ($client_id == 0) {
                        return redirect()->route('transaction_list')->with('error', 'Corridor non disponible.');
                    }
                    if ($day->format('Y-m-d') !== date_create($transaction['created_at'])->format('Y-m-d')) {
                        return redirect()->route('transaction_list')->with('error', 'La date de validation est dépassée.');
                    }
                    if ($role !== 'administrator' && ($role === 'cashier' && $transaction['user']['id'] !== $user['id']) && ($role === 'agent' && $transaction['agent']['agent']['id'] !== $agent['id'])) {
                        return redirect()->route('logout')->with('error', 'Vous n\'avez pas accès à cette page.');
                    }
                    $sender = $client->get(config('keys.url_api') . 'senders/' . $transaction['sender']['id'], [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ]
                    ]);
                    $sender = json_decode($sender->getBody()->getContents(), true);
                    if ($request->getMethod() === 'POST') {
                        try {
                            // dump($transaction);
                            $p = null;
                            if ($transaction['outbound']['bank'] !== null) {
                                $infoTrans = [
                                    'receiver_full_name' => trim($request->get('nomB') . ' ' . $request->get('prenomB')),
                                    'bankname' => trim($transaction['outbound']['bank']['organisation']),
                                    'bankaccountno' => trim($transaction['outbound']['bank']['bank_account_no']),
                                    'shortcode' => trim($transaction['outbound']['bank']['short_code']),
                                    'receiving_country' => $transaction['receiving_country_code'],
                                    'client_id' => (isset($partner)) ? $partner['client']['id'] : 0
                                ];
                                $p = $client->post(config('keys.url_api') . 'check_bank_account_status', [
                                    'verify' => false,
                                    'headers' => [
                                        'Content-Type' => 'application/json',
                                        'Authorization' => 'Bearer ' . $token
                                    ],
                                    'json' => $infoTrans
                                ]);
                                $p = json_decode($p->getBody()->getContents(), true);
                            } else if ($transaction['outbound']['mobile'] !== null) {

                                $infoTrans = [
                                    'receiver_full_name' => trim($request->get('nomB') . ' ' . $request->get('prenomB')),
                                    'receiver_phone' => trim($request->get('phoneB')),
                                    // FIX : ce champ manquait ici (présent seulement dans la branche bancaire
                                    // juste au-dessus). Sans lui, check_account_status côté backend renvoyait
                                    // systématiquement 422 "receiving_country is required", affiché en admin
                                    // comme "Validation refusée par le serveur (HTTP 422)".
                                    'receiving_country' => $transaction['receiving_country_code'],
                                    'client_id' => (isset($partner)) ? $partner['client']['id'] : 0
                                ];
                                $p = $client->post(config('keys.url_api') . 'check_account_status', [
                                    'verify' => false,
                                    'headers' => [
                                        'Content-Type' => 'application/json',
                                        'Authorization' => 'Bearer ' . $token
                                    ],
                                    'json' => $infoTrans
                                ]);
                                $p = json_decode($p->getBody()->getContents(), true);
                            }
                            // dump($infoTrans);
                            // FIX (2026-07-07) : `$p['status'] === 200` teste uniquement le succès HTTP
                            // de l'appel backend, pas la validité RÉELLE du compte. Pour le mobile money,
                            // check_account_status() renvoie désormais toujours 'status'=>200 en cas de
                            // réponse HTTP OK — MAIS Peex peut répondre "valide" au niveau HTTP tout en
                            // indiquant business-side que le wallet est invalide (ex: {"valid":false,
                            // "message":"Error verifying wallet"}, observé en sandbox pour un numéro
                            // pourtant documenté comme numéro de test valide). Sans ce contrôle
                            // supplémentaire, la validation passait quand même, la transaction avancait
                            // jusqu'à l'envoi réel, puis échouait (rejetée) chez Peex au moment du
                            // disbursement — un échec qui aurait dû être détecté ICI, bien plus tôt,
                            // avec un message clair pour l'agent. `valid` est absent pour la branche
                            // bancaire (check_bank_account_status ne renvoie pas ce champ) : `?? null`
                            // garantit qu'on ne bloque jamais cette branche par erreur.
                            $accountInvalid = array_key_exists('valid', $p ?? []) && $p['valid'] === false;
                            if (isset($p) && $p['status'] === 200 && !$accountInvalid) {
                                return redirect()->route('transaction_quote', $transaction['id'])->with('success', 'Vérification du compte avec succes !');
                            } else {
                                // FIX : $p['message'] est déjà le texte brut (Peex ne renvoie pas de JSON
                                // imbriqué dans "message"), l'ancien explode() renvoyait toujours vide.
                                $manage = $p['message'] ?? ($accountInvalid
                                    ? 'Le compte du bénéficiaire n\'a pas pu être vérifié par Peex (numéro ou wallet invalide).'
                                    : 'Erreur inconnue lors de la vérification du compte.');
                                return redirect()->route('transaction_valid', $transaction['id'])->with('error', $manage);
                            }
                        } catch (ConnectException $e) {
                            Log::error('[update/check_account] backend injoignable : ' . $e->getMessage());
                            return redirect()->route('transaction_valid', $transaction['id'])
                                ->with('error', 'Le serveur de validation est injoignable. Veuillez réessayer.');
                        } catch (ClientException $e) {
                            $status = $e->getResponse() ? $e->getResponse()->getStatusCode() : 0;
                            $body = $e->getResponse() ? (string) $e->getResponse()->getBody() : '';
                            Log::warning('[update/check_account] HTTP ' . $status . ' : ' . $body);
                            // FIX : on affichait un message générique sans jamais regarder le corps de la
                            // réponse — impossible de distinguer "numéro de bénéficiaire introuvable chez
                            // Peex" (cas normal en sandbox avec un numéro de test) d'un vrai problème de
                            // configuration. On extrait maintenant le message réel renvoyé par le backend.
                            $decoded = json_decode($body, true);
                            $innerMsg = is_array($decoded) ? ($decoded['message'] ?? ($decoded['error']['message'] ?? null)) : null;
                            $userMsg = $innerMsg
                                ? $innerMsg
                                : 'Validation refusée par le serveur (HTTP ' . $status . '). Vérifiez les coordonnées du bénéficiaire.';
                            return redirect()->route('transaction_valid', $transaction['id'])->with('error', $userMsg);
                        } catch (ServerException $e) {
                            $status = $e->getResponse() ? $e->getResponse()->getStatusCode() : 0;
                            $body   = $e->getResponse() ? (string) $e->getResponse()->getBody() : '';
                            $bodyShort = mb_substr($body, 0, 800);
                            Log::error('[update/check_account] erreur serveur HTTP ' . $status . ' — body : ' . $bodyShort);
                            return redirect()->route('transaction_valid', $transaction['id'])
                                ->with('error', 'Le serveur de validation a renvoyé HTTP ' . $status . '. Détail : ' . $bodyShort);
                        } catch (\Exception $e) {
                            Log::error('[update/check_account] exception inattendue : ' . $e->getMessage());
                            return redirect()->route('transaction_valid', $transaction['id'])
                                ->with('error', 'Erreur inattendue lors de la vérification du compte : ' . $e->getMessage());
                        }
                    }
                }
            } else {
                return redirect()->route('transaction_list')->with('error', 'Cette transaction n\'est plus éditable.');
            }
        } catch (ConnectException $e) {
            Log::error('[update] backend injoignable : ' . $e->getMessage());
            return redirect()->route('transaction_list')
                ->with('error', 'Serveur de transactions injoignable. Vérifiez que l\'API backend est démarrée.');
        } catch (ClientException $e) {
            $status = $e->getResponse() ? $e->getResponse()->getStatusCode() : 0;
            $body = $e->getResponse() ? (string) $e->getResponse()->getBody() : '';
            Log::warning('[update] HTTP ' . $status . ' sur récupération transaction/partner/sender : ' . $body);
            return redirect()->route('transaction_list')
                ->with('error', 'Erreur HTTP ' . $status . ' lors du chargement de la transaction.');
        } catch (ServerException $e) {
            $status = $e->getResponse() ? $e->getResponse()->getStatusCode() : 0;
            $body   = $e->getResponse() ? (string) $e->getResponse()->getBody() : '';
            Log::error('[update] erreur serveur HTTP ' . $status . ' — body : ' . mb_substr($body, 0, 1500));

            // Tente d'extraire un message lisible depuis la réponse JSON du backend
            $userMsg = 'Le serveur a renvoyé une erreur interne (HTTP ' . $status . ').';
            $decoded = json_decode($body, true);
            if (is_array($decoded)) {
                // Cas observé : { "error": { "message": "...", "code": 502 } }
                $innerMsg  = $decoded['error']['message']  ?? ($decoded['message'] ?? null);
                $innerCode = $decoded['error']['code']     ?? null;
                if ($innerCode == 502 || ($innerMsg && stripos($innerMsg, 'Bad Gateway') !== false)) {
                    // Essaie de retrouver le country_code mentionné dans le message
                    $country = '';
                    if (preg_match('/country_code=([A-Z]{2,3})/i', $innerMsg, $m)) {
                        $country = strtoupper($m[1]);
                    }
                    $userMsg = 'Le partenaire externe de paiement' . ($country ? ' pour le pays ' . $country : '')
                        . ' est temporairement indisponible (502 Bad Gateway). Veuillez réessayer dans quelques minutes.';
                } elseif ($innerMsg) {
                    $userMsg = 'Erreur backend : ' . mb_substr($innerMsg, 0, 400);
                }
            }
            return redirect()->route('transaction_list')->with('error', $userMsg);
        } catch (\Exception $e) {
            // Inclut les TypeError / Undefined index lors de l'accès à $transaction[...]
            Log::error('[update] exception inattendue (cause réelle) : ' . $e->getMessage()
                . ' à ' . $e->getFile() . ':' . $e->getLine());
            return redirect()->route('transaction_list')
                ->with('error', 'Erreur lors de la validation : ' . $e->getMessage());
        }
        return view('transactions.update', compact('currency', 'client_id', 'type', 'token', 'role', 'user', 'menu', 'transaction', 'clientName', 'partner'));
    }


    public function getquotation(Request $request, $id)
    {
        $day = new \DateTime();
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $agent = $request->session()->get('agent');
        $type = 'quote';
        $menu = 'Transaction';
        $currency = 'Congolese franc';
        $quote = null;
        $client = new Client();
        try {
            $transaction = $client->get(config('keys.url_api') . 'transactions/' . $id . '?_includes=sender,agent,sender.user,user,user.addresses,user.addresses.town,user.agent,user.agent.agent,outbound.bank,outbound.mobile&order=desc', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $transaction = json_decode($transaction->getBody()->getContents(), true);
            // dump($transaction);
            $codeP = $transaction['receiving_country_code'];
            $partner = $client->get(config('keys.url_api') . 'get_partner?country_code=' . $codeP, [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $partner = json_decode($partner->getBody()->getContents(), true);
            // FIX (2026-07-04, voir §4.31 du rapport) : ce bloc récupérait le taux
            // EUR (`currencies?code=EUR`) et divisait le montant saisi par ce taux
            // (`$amount / $currentRate`, ex : 4500 / 710) AVANT de l'envoyer à
            // get_quotation/get_bank_quotation avec `sendingCurrency` codée en dur
            // sur 'EUR'. Reliquat de l'ancienne intégration TerraPay (expéditeurs
            // toujours en EUR) : nos expéditeurs sont en XAF, comme le confirme le
            // mobile (transaction.page.ts, getPeexQuotation() envoie déjà
            // `sendingCurrency: 'XAF'` et le montant BRUT, sans division). Cette
            // double conversion (division par 710 puis reconversion EUR->XAF côté
            // backend) écrasait le montant réel (4500 XAF) en une valeur quasi
            // nulle, affichée comme "Montant à Percevoir : 0,01 XAF" alors que le
            // mobile affichait correctement "4 500,00 XAF" pour la même
            // transaction. Supprimé : on envoie désormais le montant saisi tel
            // quel, avec `sendingCurrency`/`requestCurrency` = 'XAF', exactement
            // comme le mobile.
            if ($transaction['transaction_status'] === 'waiting') {
                if ($request->getMethod() === 'POST') {
                    {

                        try {

                            $montant = (int) preg_replace('/[^0-9]/', '', $request->get('amount'));
                            $p = null;
                            if ($transaction['outbound']['bank'] !== null) {
                                $infoTrans = [
                                    'sender_phone' => $transaction['user']['phone_number'],
                                    'amount' => $montant,
                                    'receivingCurrency' => $transaction['to_currency'],
                                    'requestCurrency' => 'XAF',
                                    'sendingCurrency' => 'XAF',
                                    'user_id' => $transaction['user']['id'],
                                    'bankaccountno' => trim($transaction['outbound']['bank']['bank_account_no']),
                                    'receiving_country' => $transaction['receiving_country_code'],
                                    'client_id' => (isset($partner)) ? $partner['client']['id'] : 0
                                ];
                                $p = $client->post(config('keys.url_api') . 'get_bank_quotation', [
                                    'verify' => false,
                                    'headers' => [
                                        'Content-Type' => 'application/json',
                                        'Authorization' => 'Bearer ' . $token
                                    ],
                                    'json' => $infoTrans
                                ]);
                                $p = json_decode($p->getBody()->getContents(), true);
                            } else if ($transaction['outbound']['mobile'] !== null) {
                                $infoTrans = [
                                    'receiver_full_name' => trim($transaction['recipient_first_name'] . ' ' . $transaction['recipient_last_name']),
                                    'receiver_phone' => trim($request->get('numTrs')),
                                    'sender_phone' => $transaction['user']['phone_number'],
                                    'amount' => $montant,
                                    'receivingCurrency' => $transaction['to_currency'],
                                    'requestCurrency' => 'XAF',
                                    'sendingCurrency' => 'XAF',
                                    'user_id' => $transaction['user']['id'],
                                    'client_id' => (isset($partner)) ? $partner['client']['id'] : 0
                                ];
                                $p = $client->post(config('keys.url_api') . 'get_quotation', [
                                    'verify' => false,
                                    'headers' => [
                                        'Content-Type' => 'application/json',
                                        'Authorization' => 'Bearer ' . $token
                                    ],
                                    'json' => $infoTrans
                                ]);
                                $p = json_decode($p->getBody()->getContents(), true);
                            }
                            // FIX (2026-07-04) : OutboundController::computeLocalQuotation()
                            // renvoie un objet PLAT ({quoteId, fxrate, convertedAmount, fees,
                            // ...}) SANS wrapper {status:200, quotes:[...]} — c'est l'ancien
                            // format TerraPay que ce code attendait encore, jamais mis à jour
                            // (contrairement au mobile, cf. validatetransaction.page.ts
                            // validerquota(), déjà corrigé pour lire directement result.fxrate/
                            // result.convertedAmount). `$p['status']` n'existe donc jamais en
                            // succès -> "Undefined array key 'status'" observé en admin.
                            // On détecte le succès via la présence de 'convertedAmount' (absent
                            // uniquement sur les réponses d'erreur, qui ont 'status'+'message').
                            if (isset($p) && isset($p['convertedAmount'])) {
                                // Reconstruit les noms de clés attendus par transaction.blade.php
                                // (fxRate, receivingAmount, quoteId).
                                $quote = [
                                    'quoteId' => $p['quoteId'] ?? null,
                                    'fxRate' => $p['fxrate'] ?? null,
                                    'receivingAmount' => $p['convertedAmount'],
                                ];
                                // FIX (2026-07-04) : ->with() ne flashe la donnée que pour LA
                                // requête suivante — insuffisant, la page sendtransaction() peut
                                // être rechargée/re-soumise plusieurs fois avant confirmation (voir
                                // commentaire détaillé dans sendtransaction()). session()->put()
                                // avec une clé propre à cette transaction la rend disponible tant
                                // qu'elle n'est pas explicitement écrasée par une nouvelle cotation.
                                $request->session()->put('quote_' . $transaction['id'], $quote);
                                return redirect()->route('transaction_transac', $transaction['id'])->with('success', 'Quotation réussie !');
                            } else {
                                // FIX : idem — $p['message'] est déjà du texte brut, plus de JSON imbriqué à extraire.
                                $erreurM = $p['message'] ?? 'Erreur inconnue lors de la cotation.';
                                return redirect()->route('transaction_quote', $transaction['id'])->with('error', 'Erreur : ' . $erreurM);
                            }
                        } catch (\Exception $e) {
                            $erreurM = $this->extractErrorMessage($e, 'Erreur inconnue lors de la cotation.');
                            return redirect()->route('transaction_quote', $transaction['id'])->with('error', 'Erreur : ' . $erreurM);
                        }
                    }
                }
            } else {
                return redirect()->route('transaction_list')->with('error', 'Cette transaction n\'est plus éditable.');
            }
        } catch (\Exception $e) {
            // dump($e->getMessage());
            return redirect()->route('transaction_list')->with('error', 'Erreur lors de la validation du compte.');
        }
        return view('transactions.quote', compact('currency', 'type', 'token', 'role', 'user', 'menu', 'transaction', 'partner', 'quote'));
    }


    public function sendtransaction(Request $request, $id)
    {
        $day = new \DateTime();
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $agent = $request->session()->get('agent');
        $type = 'transac';
        $menu = 'Transaction';
        $currency = 'Congolese franc';
        // FIX (2026-07-04) : getquotation() flashait $quote via redirect()->with(),
        // qui n'est disponible que pour LA requête suivante (mécanisme "flash" de
        // Laravel, purgé automatiquement ensuite). Ça fonctionnait tant que le
        // navigateur servait une page HTML mise en cache après le premier
        // affichage (montant correct figé dans le cache) — mais depuis la
        // suppression du cache navigateur (voir §4.18 du rapport), chaque
        // rechargement de CETTE page (ou le simple retour sur la page après le
        // clic "Confirmer la transaction") déclenche une vraie requête fraîche,
        // pour laquelle la session flashée est déjà vide -> $quote = null ->
        // "Montant à Percevoir : 0 XAF". On lit désormais depuis une clé de
        // session PERSISTANTE et propre à cette transaction (`quote_{id}`),
        // écrite par getquotation() via session()->put() (pas ->with()).
        $quote = session()->get('quote_' . $id);
        try {
            $client = new Client();
            $transaction = $client->get(config('keys.url_api') . 'transactions/' . $id . '?_includes=sender,agent,sender.user,user,user.addresses,user.addresses.town,user.agent,user.agent.agent,outbound.bank,outbound.mobile&order=desc', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $transaction = json_decode($transaction->getBody()->getContents(), true);
            // dump($transaction);
            $codeP = $transaction['receiving_country_code'];
            $partner = $client->get(config('keys.url_api') . 'get_partner?country_code=' . $codeP, [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $partner = json_decode($partner->getBody()->getContents(), true);
            if ($request->getMethod() === 'POST') {
                $montanTrans = $transaction['amount'] + $transaction['fees'];
                if ($montanTrans > $transaction['agent']['solde']) {
                    return redirect()->route('transaction_quote', $transaction['id'])->with('error', 'Erreur : Le montant de la transaction est supérieur au solde !');
                } else {
                    if ($request->get('montant') === '') {
                        return redirect()->route('transaction_quote', $transaction['id']);
                    } else {
                        try {
                            $p = null;
                            if ($transaction['outbound']['bank'] !== null) {
                                // FIX (nettoyage TerraPay -> Peex) : ne garder que les champs
                                // réellement lus par OutboundController::send_bank_transaction()
                                // (voir sa liste $request->get(...) — user_id, sender_id,
                                // bank_iban/bankaccountno, bank_swift/sortcode,
                                // bank_address/address, track_id/reference, amount,
                                // sendingCurrency/currency, receiver_phone, receiver_first_name,
                                // receiver_last_name, receiving_country). Les anciens champs
                                // TerraPay (sending_amount, description, receiver_full_name,
                                // quoteId, reason, fees, client_id, birth_date, issuerDate,
                                // issuerCountry, city, province, postal_code, title) n'étaient
                                // lus par aucun endpoint Peex — supprimés.
                                $infoTrans = [
                                    'amount' => $request->get('montant'),
                                    'currency' => $transaction['to_currency'],
                                    'receiver_first_name' => trim($transaction['recipient_first_name']),
                                    'receiver_last_name' => trim($transaction['recipient_last_name']),
                                    'receiver_phone' => trim($transaction['recipient_phone']),
                                    'receiving_country' => $transaction['receiving_country_code'],
                                    'user_id' => $transaction['user']['id'],
                                    'sender_id' => $transaction['sender']['id'],
                                    'reference' => $transaction['ranking'], // réference venant de la base de donnees local
                                    // FIX (2026-07-04) : le backend utilise 'reference' comme track_id
                                    // Peex si aucun 'track_id' n'est fourni. Or `ranking` est généré
                                    // côté mobile via un COMPTEUR de transactions du jour qui repart de
                                    // 1 après une remise à zéro de la base de test (voir §4.20/§4.23 du
                                    // rapport) — une nouvelle transaction peut alors récupérer EXACTEMENT
                                    // le même ranking qu'une transaction déjà envoyée à Peex plus tôt, et
                                    // Peex rejette alors tout renvoi avec "Cette référence de transaction
                                    // a déjà été utilisée". `track_id` est désormais généré séparément,
                                    // garanti unique à CHAQUE tentative d'envoi (indépendant du ranking),
                                    // tandis que `reference` reste le ranking lisible pour la traçabilité.
                                    'track_id' => $transaction['ranking'] . '-' . uniqid(),
                                    'bankname' => trim(@$transaction['outbound']['bank']['organisation']),
                                    'bankaccountno' => trim(@$transaction['outbound']['bank']['bank_account_no']),
                                    'sortcode' => trim(@$transaction['outbound']['bank']['short_code']),
                                    // FIX : bank_address n'était pas envoyé ici — le backend (send_bank_transaction)
                                    // retombait alors sur l'adresse perso de l'expéditeur (mauvaise donnée, et souvent
                                    // absente), ce qui déclenchait "bank_iban, bank_swift and bank_address are
                                    // required" dès que le sender n'avait pas d'adresse enregistrée.
                                    'bank_address' => trim(@$transaction['outbound']['bank']['bank_address']),
                                ];
                                // dump($infoTrans);

                                $p = $client->post(config('keys.url_api') . 'send_bank_transaction', [
                                    'verify' => false,
                                    'headers' => [
                                        'Content-Type' => 'application/json',
                                        'Authorization' => 'Bearer ' . $token
                                    ],
                                    'json' => $infoTrans
                                ]);
                                $p = json_decode($p->getBody()->getContents(), true);
                                // dump($p);

                            } else if ($transaction['outbound']['mobile'] !== null) {
                                // FIX (nettoyage TerraPay -> Peex) : ne garder que les champs
                                // réellement lus par OutboundController::send_transaction()
                                // (user_id, sender_id, receiver_phone, receiving_country,
                                // receiver_first_name, receiver_last_name, amount,
                                // sendingCurrency/currency, track_id/reference). Les anciens
                                // champs TerraPay (sending_amount, description,
                                // receiver_full_name, quoteId, reason, fees, client_id,
                                // birth_date, issuerDate, issuerCountry, address, city,
                                // province, postal_code, title) n'étaient lus par aucun
                                // endpoint Peex — supprimés.
                                $infoTrans = [
                                    'amount' => $request->get('montant'),
                                    'currency' => $transaction['to_currency'],
                                    'receiver_first_name' => trim($transaction['recipient_first_name']),
                                    'receiver_last_name' => trim($transaction['recipient_last_name']),
                                    'receiver_phone' => $transaction['recipient_phone'],
                                    'receiving_country' => $transaction['receiving_country_code'],
                                    'user_id' => $transaction['user']['id'],
                                    'sender_id' => $transaction['sender']['id'],
                                    'reference' => $transaction['ranking'], // réference venant de la base de donnees local
                                    // FIX (2026-07-04) : voir commentaire identique dans la branche
                                    // bancaire ci-dessus — `track_id` garanti unique par tentative,
                                    // indépendant du `ranking` (vulnérable aux remises à zéro de la base).
                                    'track_id' => $transaction['ranking'] . '-' . uniqid(),
                                ];

                                $p = $client->post(config('keys.url_api') . 'send_transaction', [
                                    'verify' => false,
                                    'headers' => [
                                        'Content-Type' => 'application/json',
                                        'Authorization' => 'Bearer ' . $token
                                    ],
                                    'json' => $infoTrans
                                ]);
                                $p = json_decode($p->getBody()->getContents(), true);
                            }
                            if (isset($p) && $p['status'] === 200) {
                                // FIX (2026-07-06) : Peex renvoie l'objet sous la clé "request"
                                // (doc officielle disbursement/request-payment et bank-payment-request),
                                // SANS "created_at"/"updated_at". Le code hérité de TerraPay lisait
                                // $p['transaction']['transaction_reference']/['created_at']/['updated_at'],
                                // des clés qui n'existent pas chez Peex : 'reference' était donc
                                // enregistré à null et les dates à une chaîne vide, décorrélant
                                // silencieusement la transaction en base du vrai track_id envoyé à
                                // Peex — ce qui rendait le suivi de statut ultérieur impossible
                                // (check_transaction_status ne retrouvait plus jamais rien).
                                // OutboundController::send_transaction()/send_bank_transaction()
                                // exposent désormais un 'track_id' stable au niveau racine ; on
                                // horodate localement puisque Peex ne fournit pas ces dates.
                                $now = @date('Y-m-d H:i:s');
                                $tran2Update = [
                                    'reference' => $p['track_id'] ?? $p['reference'] ?? $transaction['ranking'],
                                    'validate' => 1,
                                    'transaction_status' => 'approuved',
                                    'etat_transac' => 'Pending',
                                    'valid_id' => $user['id'],
                                    'corridor_id' => $partner['client']['id'],
                                    'nom_api' => $partner['client']['name'],
                                    'fxrate' => $request->get('fxRate'),
                                    'validate_at' => $now,
                                    'date_init' => $now,
                                    'date_complete' => $now
                                ];
                                $res = $client->put(config('keys.url_api') . 'transactions/' . $transaction['id'], [
                                    'verify' => false,
                                    'headers' => [
                                        'Content-Type' => 'application/json',
                                        'Authorization' => 'Bearer ' . $token
                                    ],
                                    'json' => $tran2Update
                                ]);
                                $res = json_decode($res->getBody()->getContents(), true);
                                $amountAgent = floatval($transaction['agent']['solde']) - (floatval($transaction['amount']) + floatval($transaction['fees']));
                                // mettre a jour le solde de l'agent
                                $agent2Update = [
                                    'solde' => $amountAgent
                                ];
                                $resAg = $client->put(config('keys.url_api') . 'agents/' . $transaction['agent_id'], [
                                    'verify' => false,
                                    'headers' => [
                                        'Content-Type' => 'application/json',
                                        'Authorization' => 'Bearer ' . $token
                                    ],
                                    'json' => $agent2Update
                                ]);
                                $resAg = json_decode($resAg->getBody()->getContents(), true);

                                // Nettoyage : la cotation de cette transaction vient d'être utilisée
                                // avec succès, on la retire de la session (évite qu'elle traîne
                                // indéfiniment une fois la transaction validée).
                                $request->session()->forget('quote_' . $transaction['id']);

                                $phone_sender =  $transaction['user']['phone_number'];
                                $phone_receive = $transaction['recipient_phone'];
                                $txtFrom = "THOLADPAY";

                                $successSMS = [
                                    "from" =>  $txtFrom,
                                    "to" =>  $phone_sender,
                                    "text" =>  "Cher(e) client(e), votre transaction TholadPay N° " . $transaction['ranking'] . " de " . $transaction['amount'] . " FCFA vers " . $transaction['receiving_country'] . " Votre opération est en cours de traitement. TholadPay vous remercie."
                                ];
                                $resSend = $client->post(config('keys.url_api') . 'auth/send_sms_to_phone', [
                                    'verify' => false,
                                    'headers' => [
                                        'Content-Type' => 'application/json'
                                    ],
                                    'json' => $successSMS
                                ]);
                                $resSend = json_decode($resSend->getBody()->getContents(), true);

                                return redirect()->route('transaction_list')->with('success', 'Paiement effectué avec succes! Verifiez le status !');
                            } else {
                                // FIX : idem — $p['message'] est déjà du texte brut, plus de JSON imbriqué à extraire.
                                $manage = $p['message'] ?? 'Erreur inconnue lors de l\'envoi de la transaction.';
                                return redirect()->route('transaction_transac', $transaction['id'])->with('error', 'Erreur : ' . $manage);
                            }
                        } catch (\Exception $e) {
                            $manage = $this->extractErrorMessage($e, 'Erreur inconnue lors de l\'envoi de la transaction.');
                            return redirect()->route('transaction_transac', $transaction['id'])->with('error', 'Erreur : ' . $manage);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // dump($e->getMessage());
            return redirect()->route('transaction_list')->with('error', 'Erreur lors de la validation du compte.');
        }
        return view('transactions.transaction', compact('currency', 'type', 'token', 'role', 'user', 'menu', 'transaction', 'quote'));
    }

    public function checkStatusOfTransaction(Request $request, $id)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'Transaction';
        $transaction = null;
        try {
            $client = new Client();
            $transaction = $client->get(config('keys.url_api') . 'transactions/' . $id . '?_includes=sender,agent,sender.user,user,user.agent,outbound.bank,outbound.mobile', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $transaction = json_decode($transaction->getBody()->getContents(), true);

            $tar = [
                'referenceID' => $transaction['reference'],
                'client_id' => $transaction['corridor_id'],
                // FIX (2026-07-06) : voir commentaire identique dans index() — routage
                // Remittance (bancaire) vs Disbursement (mobile money) côté backend.
                'type' => (isset($transaction['outbound']['bank']) && $transaction['outbound']['bank'] !== null) ? 'bank' : 'mobile',
            ];
            $res = $client->post(config('keys.url_api') . 'check_transaction_status', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ],
                'json' => $tar
            ]);
            $res = json_decode($res->getBody()->getContents(), true);

            // FIX (nettoyage TerraPay -> Peex) : le backend renvoie soit un tableau brut
            // Peex (succès, ex: [{...,"status":"paid"}]) soit {status:<code HTTP>, message}
            // en cas d'erreur backend. L'ancien code testait $res['status'] === 200/400,
            // ce qui ne correspondait à aucune de ces deux formes réelles (dead code).
            $isBackendError = isset($res['status']) && isset($res['message']);
            $peexTx = (!$isBackendError && is_array($res) && isset($res[0])) ? $res[0] : null;
            $peexStatus = $peexTx['status'] ?? null;

            if ($peexTx !== null && $peexStatus === 'paid') {
                $maTrans = [
                    'referenceID' => $peexTx['track_id'] ?? $transaction['reference'],
                    'payer' => 1,
                    'etat_transac' => 'success',
                    'date_complete' => @date('Y-m-d'),
                    'observations' => 'Transaction payée avec succès (Peex, statut "paid").'
                ];
                $trans = $client->put(config('keys.url_api') . 'transactions/' . $id, [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ],
                    'json' => $maTrans
                ]);
                $trans = json_decode($trans->getBody()->getContents(), true);
                $phone_sender =  $transaction['user']['phone_number'];
                $txtFrom = "THOLADPAY";
                $successSMS = [
                    "from" =>  $txtFrom,
                    "to" =>  $phone_sender,
                    "text" =>  "Cher(e) client(e), votre transaction TholadPay N° " . $transaction['ranking'] . " vers " . $transaction['receiving_country'] . " a été effectuée avec succès. THOLADPay vous remercie."
                ];
                $resSend = $client->post(config('keys.url_api') . 'auth/send_sms_to_phone', [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json'
                    ],
                    'json' => $successSMS
                ]);
                $resSend = json_decode($resSend->getBody()->getContents(), true);
                return redirect()->route('transaction_list')->with('success', 'La transaction ' . $transaction['ranking'] . ' a été payé avec succès.');
            } else if ($peexTx !== null && in_array($peexStatus, ['failed', 'rejected', 'canceled'])) {
                $manage = 'Transaction Peex : statut "' . $peexStatus . '".';
                $maTrans = [
                    'etat_transac' => 'failed',
                    'date_complete' => @date('Y-m-d'),
                    'observations' => $manage
                ];
                $trans = $client->put(config('keys.url_api') . 'transactions/' . $id, [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ],
                    'json' => $maTrans
                ]);
                $trans = json_decode($trans->getBody()->getContents(), true);
                if ($maTrans['etat_transac'] === 'failed') { // si la transaction echouer, on remet le fond
                    $myAgent = $client->get(config('keys.url_api') . 'agents/' . $transaction['agent']['id'], [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ]
                    ]);
                    $myAgent = json_decode($myAgent->getBody()->getContents(), true);

                    $maAgent = [
                        'solde' => (floatval($myAgent['solde']) + (floatval($transaction['amount']) + floatval($transaction['fees'])))
                    ];
                    // dump('MONTANT Total ' .$maAgent['solde'], ' Amount ' . (floatval($transaction['amount']) + floatval($transaction['fees'])));
                    $agen = $client->put(config('keys.url_api') . 'agents/' . $myAgent['id'], [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ],
                        'json' => $maAgent
                    ]);
                    $agen = json_decode($agen->getBody()->getContents(), true);
                    // dump('Agent modifier ', $agen);
                    $phone_sender =  $transaction['user']['phone_number'];
                    $txtFrom = "THOLADPAY";
                    $rejectedSMS = [
                        "from" =>  $txtFrom,
                        "to" =>  $phone_sender,
                        "text" =>  "Cher(e) client(e), votre transaction TholadPay N° " . $transaction['ranking'] . " vers " . $transaction['receiving_country'] . " a été rejetée. Prière de vous rapprocher de TholadPay pour plus de détails."
                    ];
                    $resSend = $client->post(config('keys.url_api') . 'auth/send_sms_to_phone', [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json'
                        ],
                        'json' => $rejectedSMS
                    ]);
                    $resSend = json_decode($resSend->getBody()->getContents(), true);
                }
                return redirect()->route('transaction_list')->with('error', $manage);
            } else if ($isBackendError) {
                return redirect()->route('transaction_list')->with('error', $res['message'] ?? 'Erreur lors de la vérification du statut.');
            } else {
                return redirect()->route('transaction_list')->with('error', 'Transaction en attente de traitement chez Peex (statut : ' . ($peexStatus ?? 'inconnu') . ').');
            }
        } catch (\Exception $e) {
            // dump($e->getMessage());
            return redirect()->route('transaction_list')->with('error', 'Erreur lors de la vérification du status du paiement.');
        }
        return view('transactions.show', compact('token', 'role', 'user', 'menu', 'transaction'));
    }

    public function delete(Request $request)
    {
        if ($request->getMethod() === 'POST') {
            try {
                $id = $request->get('id_delete');
                $note = $request->get('note');
                $idUser = $request->get('idUser');
                $token = $request->session()->get('token');
                $user = $request->session()->get('user');
                $receiveCountry = $request->get('receive_country');
                $numero = $request->get('numero');
                $phone_sender = $request->get('phone_sender');
                $client = new Client();
                $us = [
                    'transaction_status' => 'cancelled',
                    'etat_transac' => 'cancelled',
                    'validate' => false,
                    'valid_id' => $idUser,
                    'validate_at' => date('Y-m-d H:i:s')
                ];

                $noteObj = [
                    'detail' => $note,
                    'verifiable_id' => $id,
                    'verifiable_type' => 'App\Transaction',
                    'status' => 'done',
                    'user_id' => $idUser
                ];
                // dd($receiveCountry, $phone_sender, $numero);
                $txtFrom = "THOLADPAY";
                $successSMS = [
                    "from" =>  $txtFrom,
                    "to" =>  $phone_sender,
                    "text" =>  "Cher(e) client(e), votre transaction TholadPay N° " . $numero . " vers " . $receiveCountry . " a été annulée pour (" . $note . "). Prière de vous rapprocher de THOLADPay pour plus de détails."
                ];
                $resSend = $client->post(config('keys.url_api') . 'auth/send_sms_to_phone', [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json'
                    ],
                    'json' => $successSMS
                ]);
                $resSend = json_decode($resSend->getBody()->getContents(), true);

                if (!empty($note)) {
                    $res = $client->put(config('keys.url_api') . 'transactions/' . $id, [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ],
                        'json' => $us
                    ]);
                    $resNote = $client->post(config('keys.url_api') . 'notes', [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ],
                        'json' => $noteObj
                    ]);
                    return redirect()->route('transaction_list')->with('success', 'L\'opération a réussie.');
                } else {
                    return redirect()->route('transaction_show')->with('error', 'Veuillez saisir la raison de l\'annulation de la transaction');
                }
            } catch (\Exception $e) {
                // dump($e->getMessage());
                return redirect()->route('transaction_list')->with('error', 'Erreur lors de l\'annulation, bien vouloir reéssayer..');
            }
        }
    }

    public function validateTransaction(Request $request)
    {
        if ($request->getMethod() === 'POST') {
            try {
                $id = $request->get('id_validate');
                $phone_sender =  $request->get('phone_sender');
                $phone_receive = $request->get('phone_receive');
                $idUser = $request->get('idUser');
                $txtFrom = "THOLADPAY";
                $token = $request->session()->get('_token');
                $client = new Client();
                $us = [
                    'transaction_status' => 'approuved',
                    'validate' => true,
                    'valid_id' => $idUser,
                    'validate_at' => date('Y-m-d H:i:s')
                ];
                $senderMy = [
                    "from" =>  $txtFrom,
                    "to" =>  $phone_sender,
                    "text" =>  "Vous avez effectué une transaction sur THOLADPAY. Votre bénéficiaire est informé par SMS"
                ];
                // $resSend = $client->post(config('keys.url_api') . 'auth/send_sms_to_phone', [
                //     'verify' => false,
                //     'headers' => [
                //         'Content-Type' => 'application/json'
                //     ],
                //     'json' => $senderMy
                // ]);
                // $resSend = json_decode($resSend->getBody()->getContents(), true);

                // $receiver = [
                //     "from" =>  $txtFrom,
                //     "to" =>  $phone_receive,
                //     "text" =>  "Vous etes bénéficiaire d'une transation sur THOLADPAY. Rendez vous dans l'agence la plus proche"
                // ];
                // $resReceived = $client->post(config('keys.url_api') . 'auth/send_sms_to_phone', [
                //     'verify' => false,
                //     'headers' => [
                //         'Content-Type' => 'application/json'
                //     ],
                //     'json' => $receiver
                // ]);
                // $resReceived = json_decode($resReceived->getBody()->getContents(), true);
                return redirect()->route('transaction_list')->with('success', 'L\'opération a réussie. Des SMS ont été envoyé respectivement á l\'emetteur et au destinataire.');
            } catch (\Exception $e) {
                // dump($e->getMessage());
                return redirect()->route('transaction_list')->with('error', 'Erreur lors de la validation, bien vouloir reéssayer..');
            }
        }
    }


    public function viewNotes(Request $request, $idTransaction)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'Transaction';
        $type = 'Note';

        $client = new Client();
        $transaction = $client->get(config('keys.url_api') . 'transactions/' . $idTransaction . '?_includes=notes', [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $transaction = json_decode($transaction->getBody()->getContents(), true);
        $notes = $transaction['notes'];

        return view('transactions.note', compact('user', 'menu', 'role', 'notes', 'transaction', 'type'));
    }


    public function showTraceFullTransaction(Request $request, $id)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'Transaction';
        $transaction = null;
        $admin = null;
        try {
            $client = new Client();
            $transaction = $client->get(config('keys.url_api') . 'transactions/' . $id . '?_includes=sender,sender.user,user,user.agent,outbound.bank,outbound.mobile', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $transaction = json_decode($transaction->getBody()->getContents(), true);
            $admin = $client->get(config('keys.url_api') . 'users/' . $transaction['valid_id'], [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $admin = json_decode($admin->getBody()->getContents(), true);
        } catch (\Exception $e) {
            // dump($e->getMessage());
        }
        return view('transactions.trace', compact('token', 'role', 'user', 'menu', 'transaction', 'admin'));
    }
}
