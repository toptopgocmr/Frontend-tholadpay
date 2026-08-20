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
     * AJOUT (2026-08-08) : sélectionne, dans le tableau brut renvoyé par Peex
     * (all_requests / disbursement all_requests), l'entrée correspondant
     * réellement au track_id interrogé.
     *
     * Investigation "aucun motif détaillé" (transaction REJECTED sans détail) :
     * le code prenait jusqu'ici systématiquement $res[0] (le PREMIER élément du
     * tableau), en supposant implicitement que le paramètre ?track_id= envoyé à
     * Peex filtre déjà le résultat côté serveur. Rien dans le code ni dans les
     * commentaires existants ne prouvait cette hypothèse — si Peex ignore ce
     * paramètre (l'endpoint s'appelle "all_requests", pas "request/{track_id}")
     * et renvoie systématiquement la liste complète des dernières transactions,
     * $res[0] pouvait correspondre à une AUTRE transaction que celle affichée,
     * avec un statut/message n'ayant rien à voir. On cherche désormais l'entrée
     * dont track_id correspond exactement ; si aucune ne correspond (tableau
     * réellement déjà filtré par Peex, ou track_id absent de la réponse), on
     * retombe sur le comportement précédent ($res[0]) pour ne rien casser.
     */
    private function pickPeexEntry(array $res, $trackId)
    {
        if ($trackId) {
            foreach ($res as $entry) {
                if (is_array($entry) && isset($entry['track_id']) && (string) $entry['track_id'] === (string) $trackId) {
                    return $entry;
                }
            }
        }
        return $res[0] ?? null;
    }

    /**
     * AJOUT (2026-08-08) : construit le message de motif à partir de la réponse
     * Peex en essayant plusieurs noms de champ possibles. La doc officielle
     * (https://peex-api-docs.peexit.com/disbursement/all-request) documente
     * "message", déjà lu depuis le 2026-07-28 — mais Peex peut très bien laisser
     * ce champ vide/null pour un rejet donné (limitation réelle de leur API,
     * pas un bug de lecture) tout en renseignant un détail sous un autre nom
     * (reason/note/remarks/narration/description/comment) selon le type
     * d'erreur. On essaie ces alternatives avant de conclure à une vraie
     * absence de motif côté Peex, et on conserve la réponse brute complète
     * dans les observations (au lieu de la perdre) pour permettre un diagnostic
     * futur sans avoir à reproduire l'incident.
     */
    private function extractPeexMessage(array $peexTx): string
    {
        foreach (['message', 'reason', 'note', 'remarks', 'narration', 'description', 'comment', 'failure_reason'] as $key) {
            $val = trim((string) ($peexTx[$key] ?? ''));
            if ($val !== '') {
                return $val;
            }
        }
        return '';
    }

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
                $path = 'transactions?_includes=notes,sender,sender.user,agent,user,user.agent,outbound.bank,outbound.mobile,outbound.cash&per_page=3000&created_at=' . $d . '&_sortDir=desc';
            } else if ($role === 'csa' || $role === 'finance_manager' || $role === 'technical_support') {
                $path = 'transactions?_includes=notes,sender,sender.user,agent,user,user.agent,outbound.bank,outbound.mobile,outbound.cash&per_page=3000&created_at=' . $d . '&_sortDir=desc';
            } else {
                $path = 'transactions?agent_id=' . $agent['id'] . '&_includes=notes,sender,sender.user,agent,user,user.agent,outbound.bank,outbound.mobile,outbound.cash&per_page=3000&created_at=' . $d . '&_sortDir=desc';
            }

            // $path = 'transactions?_includes=notes,sender,sender.user,agent,user,user.agent,outbound.bank,outbound.mobile,outbound.cash&per_page=3000&created_at='.$d.'&_sortDir=desc';
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
                // AJOUT (2026-08-08) : corridor_id=3 = transfert interne (voir
                // InternalTransferController) — rien n'est "en cours" chez un partenaire
                // externe pour ce type de transaction, il n'y a donc rien à interroger ici ;
                // le statut ne change que via payout_internal_transaction (code de retrait).
                if ($tr['transaction_status'] === 'approuved' && $tr['reference'] !== '' && $tr['date_init'] === $d && $tr['etat_transac'] !== 'success' && $tr['etat_transac'] !== 'failed' && (string) ($tr['corridor_id'] ?? '') !== '3') {
                    // dump($tr);
                    $isDigitwaceTx = (string) ($tr['corridor_id'] ?? '') === '2';
                    $tar = [
                        'referenceID' => $tr['reference'],
                        'client_id' => $tr['corridor_id'],
                        // FIX (2026-07-06) : indique au backend quel service Peex interroger
                        // (Remittance/clients vs Disbursement) — voir OutboundController::
                        // check_transaction_status(). Sans ça, les virements bancaires
                        // n'étaient jamais trouvés (toujours interrogés via Disbursement).
                        'type' => (isset($tr['outbound']['bank']) && $tr['outbound']['bank'] !== null) ? 'bank' : 'mobile',
                        // Transaction envoyée via DigitWace (corridor_id=2, voir
                        // OutboundController::get_partner) -> statut interrogé via
                        // GET /transaction/status/{ref} (doc §XI) plutôt que Peex all_requests.
                        'partner' => $isDigitwaceTx ? 'digitwace' : 'peex',
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

                    // Normalisation DigitWace -> forme Peex, voir commentaire identique et
                    // plus détaillé dans checkStatusOfTransaction() ci-dessous.
                    if ($isDigitwaceTx && isset($res['transaction']) && is_array($res['transaction'])) {
                        $dwStatusRaw = strtoupper((string) ($res['transaction']['Status'] ?? ''));
                        $statusMap = ['PAID' => 'paid', 'CANCEL' => 'failed'];
                        $res = [[
                            'status' => $statusMap[$dwStatusRaw] ?? 'pending',
                            'track_id' => $tr['reference'],
                            'message' => is_string($res['messages'] ?? null) ? $res['messages'] : $dwStatusRaw,
                        ]];
                    }
                    $partnerLabel = $isDigitwaceTx ? 'DigitWace' : 'Peex';
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
                    $peexTx = (!$isBackendError && is_array($res)) ? $this->pickPeexEntry($res, $tr['reference']) : null;
                    $peexStatus = $peexTx['status'] ?? null;

                    if ($peexTx !== null && $peexStatus === 'paid') {
                        $maTrans = [
                            'referenceID' => $peexTx['track_id'] ?? $tr['reference'],
                            'payer' => 1,
                            'etat_transac' => 'success',
                            'date_complete' => @date('Y-m-d'),
                            'observations' => 'Transaction payée avec succès (' . $partnerLabel . ', statut "paid").'
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
                            $txtFrom = "Send-Paz";
                            $successSMS = [
                                "from" =>  $txtFrom,
                                "to" =>  $phone_sender,
                                "text" =>  "Cher(e) client(e), votre transaction Send-Paz N° " . $tr['ranking'] . " vers " . $tr['receiving_country'] . " a été effectuée avec succès. Send-Paz vous remercie."
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
                        // Transaction définitivement en échec côté partenaire.
                        if ($tr['etat_transac'] !== 'failed') {
                            // FIX (2026-07-28) : Peex renvoie aussi un champ "message" par transaction
                            // (doc https://peex-api-docs.peexit.com/disbursement/all-request, ex:
                            // {..., "status":"rejected", "message":"..."}), jusqu'ici jamais lu -> le
                            // motif exact d'un rejet n'était donc jamais visible en admin, seulement
                            // le mot "rejected" sans explication.
                            $peexMessage = $this->extractPeexMessage($peexTx);
                            $manage = 'Transaction ' . $partnerLabel . ' : statut "' . $peexStatus . '".' . ($peexMessage !== '' ? ' Motif : ' . $peexMessage : ' (' . $partnerLabel . ' n\'a fourni aucun motif détaillé. Réponse brute : ' . json_encode($peexTx) . ')');
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
                                $txtFrom = "Send-Paz";
                                $rejectedSMS = [
                                    "from" =>  $txtFrom,
                                    "to" =>  $phone_sender,
                                    "text" =>  "Cher(e) client(e), votre transaction Send-Paz N° " . $tr['ranking'] . " vers " . $tr['receiving_country'] . " a été rejetée. Prière de vous rapprocher de Send-Paz pour plus de détails."
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
        $transactions = $client->get(config('keys.url_api') . 'transactions?_includes=sender,sender.user,notes,user,user.agent,outbound.bank,outbound.mobile,outbound.cash', [
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
                $path = 'transactions?_includes=notes,sender,sender.user,agent,user,user.agent,outbound.bank,outbound.mobile,outbound.cash&per_page=3000&created_at-bt=' . $deEnc . ',' . $dtEnc . '&_sortDir=desc';
            } else if ($role === 'csa' || $role === 'finance_manager' || $role === 'technical_support') {
                $path = 'transactions?_includes=notes,sender,sender.user,agent,user,user.agent,outbound.bank,outbound.mobile,outbound.cash&per_page=3000&etat_transac=success&created_at-bt=' . $deEnc . ',' . $dtEnc . '&_sortDir=desc';
            } else {
                // ✅ FIX 1 (suite) : $agent n'est plus null ici
                $agentId = ($agent !== null && isset($agent['id'])) ? $agent['id'] : 0;
                $path = 'transactions?agent_id=' . $agentId . '&_includes=notes,sender,sender.user,agent,user,user.agent,outbound.bank,outbound.mobile,outbound.cash&per_page=3000&created_at-bt=' . $deEnc . ',' . $dtEnc . '&_sortDir=desc';
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
            $transaction = $client->get(config('keys.url_api') . 'transactions/' . $id . '?_includes=sender,agent,sender.user,user,user.agent,outbound.bank,outbound.mobile,outbound.cash', [
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
            // AJOUT (2026-08-08) : les transferts internes (corridor_id=3) sont
            // consultés/imprimés par l'agence PAYEUSE depuis l'écran "Retrait interne"
            // — par construction, ce n'est jamais l'agence qui a envoyé (règle
            // anti-fraude ajoutée le même jour, voir InternalTransferController::
            // isSameSendingAgency côté backend). La restriction "même agent_id que
            // l'expéditeur" ci-dessous bloquerait donc systématiquement ces rôles ;
            // on l'exempte pour ce corridor.
            $isInternalTransfer = (string) ($transaction['corridor_id'] ?? '') === '3';
            if (!in_array($role, $fullAccessRoles) && !$isInternalTransfer) {
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
            $response = $client->get(config('keys.url_api') . 'transactions/' . $id . '?_includes=sender,agent,sender.user,user,user.agent,outbound.bank,outbound.mobile,outbound.cash', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $transaction = json_decode($response->getBody()->getContents(), true);

            $fullAccessRoles = ['administrator', 'csa', 'finance_manager', 'technical_support'];
            // AJOUT (2026-08-08) : les transferts internes (corridor_id=3) sont
            // consultés/imprimés par l'agence PAYEUSE depuis l'écran "Retrait interne"
            // — par construction, ce n'est jamais l'agence qui a envoyé (règle
            // anti-fraude ajoutée le même jour, voir InternalTransferController::
            // isSameSendingAgency côté backend). La restriction "même agent_id que
            // l'expéditeur" ci-dessous bloquerait donc systématiquement ces rôles ;
            // on l'exempte pour ce corridor.
            $isInternalTransfer = (string) ($transaction['corridor_id'] ?? '') === '3';
            if (!in_array($role, $fullAccessRoles) && !$isInternalTransfer) {
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
        // AJOUT (2026-08-08) : AwaitingPickup (transfert interne validé, code généré,
        // en attente de retrait — voir InternalTransferController) doit aussi avoir un
        // reçu, notamment pour que l'expéditeur ait une preuve avec le code dessus.
        // Seul un état "New" (jamais soumise) reste sans reçu, faute d'info exploitable.
        $etat = $transaction['etat_transac'] ?? null;
        if (!in_array($etat, ['acknowledged', 'success', 'failed', 'cancelled', 'AwaitingPickup'])) {
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
        // Partenaire choisi par l'agent/admin pour acheminer ce transfert (Peex ou
        // DigitWace, voir OutboundController::get_partner) : soumis via le select
        // "partner" du formulaire étape 1 ; on retombe sur la session (déjà choisi
        // à un chargement précédent de cette même page) puis sur 'peex' par défaut
        // pour ne rien changer au comportement existant si jamais rien n'est fourni.
        $partnerChoice = $request->get('partner') ?: $request->session()->get('partner_' . $id, 'peex');
        // AJOUT (2026-08-20) : voir dw_extra_{id} pour DigitWace ci-dessous —
        // même principe pour PawaPay (opérateur + motif/origine des fonds),
        // pré-rempli dans la vue si l'agent revient sur cette étape.
        $pawapayExtra = $request->session()->get('pawapay_extra_' . $id, []);
        try {
            $client = new Client();
            $transaction = $client->get(config('keys.url_api') . 'transactions/' . $id . '?_includes=sender,agent,sender.user,user,user.addresses,user.addresses.town,outbound.bank,outbound.mobile,outbound.cash&order=desc', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $transaction = json_decode($transaction->getBody()->getContents(), true);
            // dump($transaction);
            // Cash Pickup (retrait en espèces) — 3e mode de livraison, saisi dès la
            // création mobile (voir Cash/outbound.cash). Peex ne le propose pas du
            // tout : on force donc DigitWace si jamais Peex a été choisi (ou rien
            // n'a été choisi) pour ce type de transaction.
            // FIX (2026-08-08) : ce forçage écrasait AUSSI un choix explicite
            // 'internal' — rendant impossible tout retrait interne (réseau
            // Send-Paz, sans partenaire externe) pour une transaction Cash Pickup,
            // alors que le réseau interne fonctionne quel que soit le type de
            // compte choisi à la création (voir InternalTransferController). On
            // ne force plus DigitWace que si le choix n'est pas déjà 'internal'.
            $isCashPickup = isset($transaction['outbound']['cash']) && $transaction['outbound']['cash'] !== null;
            if ($isCashPickup && $partnerChoice !== 'internal') {
                $partnerChoice = 'digitwace';
            }
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
                    $partner = $client->get(config('keys.url_api') . 'get_partner?country_code=' . $codeP . '&partner=' . $partnerChoice, [
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
                        // On mémorise le partenaire choisi (+ les champs propres à
                        // DigitWace, obligatoires côté API mais absents du modèle Peex :
                        // relation/idType/idNumber bénéficiaire, voir doc §VI/§VIII) pour
                        // les étapes 2/3 (getquotation/sendtransaction), qui rechargent
                        // la transaction sans repasser par ce formulaire.
                        $request->session()->put('partner_' . $id, $partnerChoice);
                        if ($partnerChoice === 'digitwace') {
                            $request->session()->put('dw_extra_' . $id, [
                                'receiver_id_number' => trim((string) $request->get('receiver_id_number')),
                                'receiver_id_type' => $request->get('receiver_id_type') ?: 'PP',
                                'relation' => trim((string) $request->get('relation')),
                            ]);
                        }
                        // AJOUT (2026-08-20) : mémorise le choix d'opérateur + motif/origine
                        // des fonds PawaPay pour l'étape 3 (sendtransaction), qui recharge la
                        // transaction sans repasser par ce formulaire — même principe que
                        // dw_extra_{id} ci-dessus pour DigitWace.
                        if ($partnerChoice === 'pawapay') {
                            $request->session()->put('pawapay_extra_' . $id, [
                                'pawapay_operator' => trim((string) $request->get('pawapay_operator')) ?: 'AIRTEL',
                                'pawapay_purpose_of_funds' => trim((string) $request->get('pawapay_purpose_of_funds')) ?: 'PERSONAL_TRANSFER',
                                'pawapay_source_of_funds' => trim((string) $request->get('pawapay_source_of_funds')) ?: 'SALARY',
                            ]);
                        }
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
                                    'client_id' => (isset($partner)) ? $partner['client']['id'] : 0,
                                    'partner' => $partnerChoice,
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
                                    'partner' => $partnerChoice,
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
                            } else if ($transaction['outbound']['cash'] !== null) {
                                // AJOUT (2026-08-08) : Cash Pickup — DigitWace n'expose aucune
                                // vérification préalable pour ce mode (comme Mobile/Bank DigitWace,
                                // voir OutboundController::check_account_status/check_bank_account_status,
                                // qui renvoient déjà un pass-through neutre pour ce partenaire). On
                                // passe donc directement à l'étape cotation sans appel HTTP.
                                $p = ['status' => 200, 'valid' => null];
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
        return view('transactions.update', compact('currency', 'client_id', 'type', 'token', 'role', 'user', 'menu', 'transaction', 'clientName', 'partner', 'partnerChoice', 'pawapayExtra'));
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
        // Choisi à l'étape 1 (transactions.update) et mémorisé en session — voir
        // TransactionController::update(). 'peex' par défaut si l'agent arrive
        // directement sur cette étape sans être passé par l'étape 1 (lien direct).
        $partnerChoice = $request->session()->get('partner_' . $id, 'peex');
        try {
            $transaction = $client->get(config('keys.url_api') . 'transactions/' . $id . '?_includes=sender,agent,sender.user,user,user.addresses,user.addresses.town,user.agent,user.agent.agent,outbound.bank,outbound.mobile,outbound.cash&order=desc', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $transaction = json_decode($transaction->getBody()->getContents(), true);
            // dump($transaction);
            $codeP = $transaction['receiving_country_code'];
            $partner = $client->get(config('keys.url_api') . 'get_partner?country_code=' . $codeP . '&partner=' . $partnerChoice, [
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

                            // Ce formulaire (transactions.quote) capture déjà "origin"/"reason" —
                            // champs historiques non consommés jusqu'ici (voir TerraPay). Pour
                            // DigitWace, on les réutilise comme originFund/reason (doc §XVII/
                            // §XVIII, valeurs imposées par DigitWace, voir liste dans la vue) et
                            // on les fusionne avec les infos déjà mémorisées à l'étape 1
                            // (receiver_id_number/type, relation).
                            if ($partnerChoice === 'digitwace') {
                                $dwExtra = $request->session()->get('dw_extra_' . $id, []);
                                $dwExtra['origin_fund'] = trim((string) $request->get('origin')) ?: ($dwExtra['origin_fund'] ?? '');
                                $dwExtra['reason'] = trim((string) $request->get('reason')) ?: ($dwExtra['reason'] ?? '');
                                $request->session()->put('dw_extra_' . $id, $dwExtra);
                            }

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
                                    'client_id' => (isset($partner)) ? $partner['client']['id'] : 0,
                                    'partner' => $partnerChoice,
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
                                    'client_id' => (isset($partner)) ? $partner['client']['id'] : 0,
                                    'partner' => $partnerChoice,
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
                            } else if ($transaction['outbound']['cash'] !== null) {
                                // AJOUT (2026-08-08) : Cash Pickup — get_quotation() calcule un
                                // fxrate purement local (OutboundController::computeLocalQuotation),
                                // sans dépendre du mode de livraison ; on réutilise donc le même
                                // endpoint que Mobile.
                                $infoTrans = [
                                    'receiver_full_name' => trim($transaction['recipient_first_name'] . ' ' . $transaction['recipient_last_name']),
                                    'receiver_phone' => trim($transaction['recipient_phone']),
                                    'sender_phone' => $transaction['user']['phone_number'],
                                    'amount' => $montant,
                                    'receivingCurrency' => $transaction['to_currency'],
                                    'requestCurrency' => 'XAF',
                                    'sendingCurrency' => 'XAF',
                                    'user_id' => $transaction['user']['id'],
                                    'client_id' => (isset($partner)) ? $partner['client']['id'] : 0,
                                    'partner' => $partnerChoice,
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
        return view('transactions.quote', compact('currency', 'type', 'token', 'role', 'user', 'menu', 'transaction', 'partner', 'quote', 'partnerChoice'));
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
        // Choisi à l'étape 1, voir commentaire identique dans getquotation().
        $partnerChoice = $request->session()->get('partner_' . $id, 'peex');
        $dwExtra = $request->session()->get('dw_extra_' . $id, []);
        // Voir commentaire identique dans update() — opérateur + motif/origine des
        // fonds PawaPay, mémorisés à l'étape 1.
        $pawapayExtra = $request->session()->get('pawapay_extra_' . $id, []);
        try {
            $client = new Client();
            $transaction = $client->get(config('keys.url_api') . 'transactions/' . $id . '?_includes=sender,agent,sender.user,user,user.addresses,user.addresses.town,user.agent,user.agent.agent,outbound.bank,outbound.mobile,outbound.cash&order=desc', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $transaction = json_decode($transaction->getBody()->getContents(), true);
            // dump($transaction);
            // Voir commentaire identique et détaillé dans update() — Cash Pickup
            // n'existe pas chez Peex, on force DigitWace SAUF si 'internal' a été
            // explicitement mémorisé en session à l'étape 1 (réseau interne
            // Send-Paz, valable pour tout type de compte).
            if (isset($transaction['outbound']['cash']) && $transaction['outbound']['cash'] !== null && $partnerChoice !== 'internal') {
                $partnerChoice = 'digitwace';
            }
            $codeP = $transaction['receiving_country_code'];
            $partner = $client->get(config('keys.url_api') . 'get_partner?country_code=' . $codeP . '&partner=' . $partnerChoice, [
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
                            // AJOUT (2026-08-08) : transfert interne (voir InternalTransferController) —
                            // prioritaire sur le type Bank/Mobile/Cash choisi à la création : aucun appel
                            // externe, juste un code de retrait aléatoire. S'applique quel que soit le
                            // type puisque le bénéficiaire retire de toute façon en espèces chez
                            // n'importe quel agent tholadpay du pays destinataire.
                            if ($partnerChoice === 'internal') {
                                $p = $client->post(config('keys.url_api') . 'send_internal_transaction', [
                                    'verify' => false,
                                    'headers' => [
                                        'Content-Type' => 'application/json',
                                        'Authorization' => 'Bearer ' . $token
                                    ],
                                    'json' => []
                                ]);
                                $p = json_decode($p->getBody()->getContents(), true);
                            } else if ($transaction['outbound']['bank'] !== null) {
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
                                    'partner' => $partnerChoice,
                                ];
                                if ($partnerChoice === 'digitwace') {
                                    // Champs exigés par DigitWace, sans équivalent Peex (voir doc
                                    // §VI/§X et OutboundController::sendDigitwaceBankTransaction) :
                                    // idNumber/idType bénéficiaire (mémorisés à l'étape 1), relation/
                                    // originFund/reason (étape 1 pour relation, étape 2 — champs
                                    // "origin"/"reason" déjà existants — pour les deux autres).
                                    $infoTrans['receiver_id_number'] = $dwExtra['receiver_id_number'] ?? '';
                                    $infoTrans['receiver_id_type'] = $dwExtra['receiver_id_type'] ?? 'PP';
                                    $infoTrans['relation'] = $dwExtra['relation'] ?? '';
                                    $infoTrans['origin_fund'] = $dwExtra['origin_fund'] ?? '';
                                    $infoTrans['reason'] = $dwExtra['reason'] ?? '';
                                }
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
                                    'partner' => $partnerChoice,
                                ];
                                if ($partnerChoice === 'digitwace') {
                                    // Voir commentaire identique dans la branche bancaire ci-dessus.
                                    $infoTrans['receiver_id_number'] = $dwExtra['receiver_id_number'] ?? '';
                                    $infoTrans['receiver_id_type'] = $dwExtra['receiver_id_type'] ?? 'PP';
                                    $infoTrans['relation'] = $dwExtra['relation'] ?? '';
                                    $infoTrans['origin_fund'] = $dwExtra['origin_fund'] ?? '';
                                    $infoTrans['reason'] = $dwExtra['reason'] ?? '';
                                }
                                // AJOUT (2026-08-20) : champs exigés par PawaPay (voir
                                // OutboundController::sendPawapayRemittance/requirePawapayReferenceFields),
                                // mémorisés à l'étape 1 (update()).
                                if ($partnerChoice === 'pawapay') {
                                    $infoTrans['pawapay_operator'] = $pawapayExtra['pawapay_operator'] ?? 'AIRTEL';
                                    $infoTrans['pawapay_purpose_of_funds'] = $pawapayExtra['pawapay_purpose_of_funds'] ?? 'PERSONAL_TRANSFER';
                                    $infoTrans['pawapay_source_of_funds'] = $pawapayExtra['pawapay_source_of_funds'] ?? 'SALARY';
                                }

                                $p = $client->post(config('keys.url_api') . 'send_transaction', [
                                    'verify' => false,
                                    'headers' => [
                                        'Content-Type' => 'application/json',
                                        'Authorization' => 'Bearer ' . $token
                                    ],
                                    'json' => $infoTrans
                                ]);
                                $p = json_decode($p->getBody()->getContents(), true);
                            } else if ($transaction['outbound']['cash'] !== null) {
                                // AJOUT (2026-08-08) : Cash Pickup — propre à DigitWace (voir
                                // OutboundController::send_cash_transaction, qui rejette
                                // explicitement toute autre valeur de 'partner'). Les champs
                                // receiver_city/security_question/security_answer viennent
                                // d'outbound.cash (saisis dès la création mobile, voir App\Cash) ;
                                // les champs relation/origin_fund/reason/receiver_id_number/type
                                // viennent de dw_extra comme pour Bank/Mobile ci-dessus.
                                $infoTrans = [
                                    'amount' => $request->get('montant'),
                                    // FIX : envoyés explicitement (plutôt que le seul 'currency' utilisé par
                                    // les branches Bank/Mobile ci-dessus, ambigu — voir OutboundController::
                                    // send_cash_transaction qui distingue bien sendingCurrency/receivingCurrency).
                                    'sendingCurrency' => 'XAF',
                                    'receivingCurrency' => $transaction['to_currency'],
                                    'receiver_first_name' => trim($transaction['recipient_first_name']),
                                    'receiver_last_name' => trim($transaction['recipient_last_name']),
                                    'receiver_phone' => trim($transaction['recipient_phone']),
                                    'receiving_country' => $transaction['receiving_country_code'],
                                    'receiver_city' => trim(@$transaction['outbound']['cash']['receiver_city']),
                                    'security_question' => trim(@$transaction['outbound']['cash']['security_question']),
                                    'security_answer' => trim(@$transaction['outbound']['cash']['security_answer']),
                                    'user_id' => $transaction['user']['id'],
                                    'sender_id' => $transaction['sender']['id'],
                                    'reference' => $transaction['ranking'],
                                    'track_id' => $transaction['ranking'] . '-' . uniqid(),
                                    'partner' => $partnerChoice,
                                    'receiver_id_number' => $dwExtra['receiver_id_number'] ?? '',
                                    'receiver_id_type' => $dwExtra['receiver_id_type'] ?? 'PP',
                                    'relation' => $dwExtra['relation'] ?? '',
                                    'origin_fund' => $dwExtra['origin_fund'] ?? '',
                                    'reason' => $dwExtra['reason'] ?? '',
                                ];
                                $p = $client->post(config('keys.url_api') . 'send_cash_transaction', [
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
                                $isInternalTx = $partnerChoice === 'internal';
                                $tran2Update = [
                                    'reference' => $p['track_id'] ?? $p['reference'] ?? $transaction['ranking'],
                                    'validate' => 1,
                                    'transaction_status' => 'approuved',
                                    // AJOUT (2026-08-08) : statut dédié pour les transferts internes — pas
                                    // "Pending" (qui, pour Peex/DigitWace, signifie "en cours de traitement
                                    // chez le partenaire") : ici il n'y a rien en cours chez qui que ce soit,
                                    // on attend juste que le bénéficiaire se présente avec le code. Exclu du
                                    // polling automatique de statut (voir index()) et ajouté à la liste des
                                    // statuts autorisant un reçu (voir receipt()).
                                    'etat_transac' => $isInternalTx ? 'AwaitingPickup' : 'Pending',
                                    'valid_id' => $user['id'],
                                    'corridor_id' => $partner['client']['id'],
                                    'nom_api' => $partner['client']['name'],
                                    'fxrate' => $request->get('fxRate'),
                                    'validate_at' => $now,
                                    'date_init' => $now,
                                    'date_complete' => $now,
                                ];
                                if ($isInternalTx) {
                                    $tran2Update['internal_pickup_code'] = $p['pickup_code'] ?? $p['reference'] ?? null;
                                }
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
                                // indéfiniment une fois la transaction validée). Idem pour le choix
                                // de partenaire et les champs DigitWace propres à cette transaction.
                                $request->session()->forget('quote_' . $transaction['id']);
                                $request->session()->forget('partner_' . $transaction['id']);
                                $request->session()->forget('dw_extra_' . $transaction['id']);
                                $request->session()->forget('pawapay_extra_' . $transaction['id']);

                                $phone_sender =  $transaction['user']['phone_number'];
                                $phone_receive = $transaction['recipient_phone'];
                                $txtFrom = "Send-Paz";
                                $pickupCode = $tran2Update['internal_pickup_code'] ?? null;

                                // AJOUT (2026-08-08) : pour un transfert interne, le SMS générique
                                // ("en cours de traitement") ne suffit pas — sans le code, ni
                                // l'expéditeur ni le bénéficiaire ne peuvent retirer l'argent nulle
                                // part. On envoie donc un texte dédié à l'expéditeur (avec le code, à
                                // transmettre lui-même si besoin) ET, en plus du SMS habituel, un
                                // second SMS directement au bénéficiaire ($phone_receive était calculé
                                // mais jamais utilisé jusqu'ici).
                                if ($isInternalTx && $pickupCode) {
                                    $successSMS = [
                                        "from" =>  $txtFrom,
                                        "to" =>  $phone_sender,
                                        "text" =>  "Cher(e) client(e), votre transaction Send-Paz N° " . $transaction['ranking'] . " de " . $transaction['amount'] . " FCFA vers " . $transaction['receiving_country'] . " a été validée. Code de retrait : " . $pickupCode . ". Communiquez ce code au bénéficiaire pour le retrait. Send-Paz vous remercie."
                                    ];
                                } else {
                                    $successSMS = [
                                        "from" =>  $txtFrom,
                                        "to" =>  $phone_sender,
                                        "text" =>  "Cher(e) client(e), votre transaction Send-Paz N° " . $transaction['ranking'] . " de " . $transaction['amount'] . " FCFA vers " . $transaction['receiving_country'] . " Votre opération est en cours de traitement. Send-Paz vous remercie."
                                    ];
                                }
                                $resSend = $client->post(config('keys.url_api') . 'auth/send_sms_to_phone', [
                                    'verify' => false,
                                    'headers' => [
                                        'Content-Type' => 'application/json'
                                    ],
                                    'json' => $successSMS
                                ]);
                                $resSend = json_decode($resSend->getBody()->getContents(), true);

                                if ($isInternalTx && $pickupCode && $phone_receive) {
                                    $beneficiarySMS = [
                                        "from" =>  $txtFrom,
                                        "to" =>  $phone_receive,
                                        "text" =>  "Vous avez un transfert Send-Paz de " . ($transaction['montant_beneficiaire'] ?? $transaction['amount']) . " à retirer. Code de retrait : " . $pickupCode . ". Présentez ce code et une pièce d'identité dans n'importe quelle agence Send-Paz au " . $transaction['receiving_country'] . "."
                                    ];
                                    $resSendBenef = $client->post(config('keys.url_api') . 'auth/send_sms_to_phone', [
                                        'verify' => false,
                                        'headers' => [
                                            'Content-Type' => 'application/json'
                                        ],
                                        'json' => $beneficiarySMS
                                    ]);
                                    $resSendBenef = json_decode($resSendBenef->getBody()->getContents(), true);
                                }

                                // AJOUT (2026-08-08) : DigitWace exige un appel /transaction/confirm
                                // après la création (voir OutboundController::confirmDigitwaceTransaction) —
                                // sans lui, la transaction reste bloquée en "WAITING CONFIRMATION" chez
                                // DigitWace et n'est jamais réellement payée. send_transaction/
                                // send_bank_transaction/send_cash_transaction tentent désormais cette
                                // confirmation automatiquement (avec 1 retry), mais si elle échoue quand
                                // même, on l'affiche clairement ici plutôt que de laisser croire que tout
                                // est réglé.
                                // FIX (2026-08-15) : confirmDigitwaceTransaction() peut désormais renvoyer
                                // 'uncertain' (appel /transaction/confirm réussi en HTTP mais avec un code
                                // de statut ne correspondant à aucun code de succès documenté — voir
                                // OutboundController::DIGITWACE_CONFIRM_SUCCESS_CODES), en plus de 'failed'
                                // (exception HTTP). Ces deux cas doivent bloquer le message "Paiement
                                // effectué avec succès" : seul 'confirmed' signifie que DigitWace a
                                // réellement validé la transaction.
                                if (in_array($p['confirm_status'] ?? null, ['failed', 'uncertain'], true)) {
                                    // NB : confirm_status n'existe que sur les réponses DigitWace (voir
                                    // OutboundController::confirmDigitwaceTransaction) — $partnerLabel n'est
                                    // pas défini dans cette méthode (contrairement à index()/
                                    // checkStatusOfTransaction()), mais ce cas ne peut de toute façon se
                                    // produire que pour ce partenaire.
                                    $confirmLabel = ($p['confirm_status'] ?? null) === 'uncertain' ? 'NON CONFIRMÉE (statut incertain)' : 'NON CONFIRMÉE';
                                    return redirect()->route('transaction_list')->with('error',
                                        'Transaction créée chez DigitWace mais ' . $confirmLabel . ' ('
                                        . ($p['confirm_message'] ?? 'raison inconnue')
                                        . '). Référence : ' . ($p['reference'] ?? $transaction['ranking'])
                                        . '. Vérifiez le statut réel avant de considérer la transaction comme payée, ou contactez le support DigitWace.');
                                }
                                if ($isInternalTx && $pickupCode) {
                                    return redirect()->route('transaction_list')->with('success',
                                        'Transfert interne validé ! Code de retrait : ' . $pickupCode
                                        . ' — à communiquer au bénéficiaire pour le retrait en agence.');
                                }
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
        return view('transactions.transaction', compact('currency', 'type', 'token', 'role', 'user', 'menu', 'transaction', 'quote', 'partnerChoice'));
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
            $transaction = $client->get(config('keys.url_api') . 'transactions/' . $id . '?_includes=sender,agent,sender.user,user,user.agent,outbound.bank,outbound.mobile,outbound.cash', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $transaction = json_decode($transaction->getBody()->getContents(), true);

            // corridor_id = 2 -> transaction envoyée via DigitWace (voir OutboundController::
            // get_partner) ; on précise 'partner' pour router GET /transaction/status/{ref}
            // (doc §XI) plutôt que Peex all_requests.
            $isDigitwaceTx = (string) ($transaction['corridor_id'] ?? '') === '2';
            $tar = [
                'referenceID' => $transaction['reference'],
                'client_id' => $transaction['corridor_id'],
                // FIX (2026-07-06) : voir commentaire identique dans index() — routage
                // Remittance (bancaire) vs Disbursement (mobile money) côté backend.
                'type' => (isset($transaction['outbound']['bank']) && $transaction['outbound']['bank'] !== null) ? 'bank' : 'mobile',
                'partner' => $isDigitwaceTx ? 'digitwace' : 'peex',
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

            // DigitWace renvoie un objet unique {"transaction":{...,"Status":"PAID"|"CANCEL"|
            // "PENDING"|"WAITING CONFIRMATION"|"PROCESSING"|"LOCKED"}} (doc §XI), très
            // différent du tableau Peex {[{...,"status":"paid"}]}. On le ramène ici à la même
            // forme normalisée que Peex pour réutiliser telle quelle toute la logique
            // success/failed/pending ci-dessous (SMS, remboursement agent, etc.).
            if ($isDigitwaceTx && isset($res['transaction']) && is_array($res['transaction'])) {
                $dwStatusRaw = strtoupper((string) ($res['transaction']['Status'] ?? ''));
                $statusMap = ['PAID' => 'paid', 'CANCEL' => 'failed'];
                $res = [[
                    'status' => $statusMap[$dwStatusRaw] ?? 'pending',
                    'track_id' => $transaction['reference'],
                    'message' => is_string($res['messages'] ?? null) ? $res['messages'] : $dwStatusRaw,
                ]];
            }

            // FIX (nettoyage TerraPay -> Peex) : le backend renvoie soit un tableau brut
            // Peex (succès, ex: [{...,"status":"paid"}]) soit {status:<code HTTP>, message}
            // en cas d'erreur backend. L'ancien code testait $res['status'] === 200/400,
            // ce qui ne correspondait à aucune de ces deux formes réelles (dead code).
            $isBackendError = isset($res['status']) && isset($res['message']);
            $peexTx = (!$isBackendError && is_array($res)) ? $this->pickPeexEntry($res, $transaction['reference']) : null;
            $peexStatus = $peexTx['status'] ?? null;
            $partnerLabel = $isDigitwaceTx ? 'DigitWace' : 'Peex';

            if ($peexTx !== null && $peexStatus === 'paid') {
                $maTrans = [
                    'referenceID' => $peexTx['track_id'] ?? $transaction['reference'],
                    'payer' => 1,
                    'etat_transac' => 'success',
                    'date_complete' => @date('Y-m-d'),
                    'observations' => 'Transaction payée avec succès (' . $partnerLabel . ', statut "paid").'
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
                $txtFrom = "Send-Paz";
                $successSMS = [
                    "from" =>  $txtFrom,
                    "to" =>  $phone_sender,
                    "text" =>  "Cher(e) client(e), votre transaction Send-Paz N° " . $transaction['ranking'] . " vers " . $transaction['receiving_country'] . " a été effectuée avec succès. Send-Paz vous remercie."
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
                // FIX (2026-07-28) : voir commentaire identique dans index() — on remonte
                // désormais le champ "message" de Peex pour afficher le vrai motif du rejet.
                $peexMessage = $this->extractPeexMessage($peexTx);
                $manage = 'Transaction ' . $partnerLabel . ' : statut "' . $peexStatus . '".' . ($peexMessage !== '' ? ' Motif : ' . $peexMessage : ' (' . $partnerLabel . ' n\'a fourni aucun motif détaillé. Réponse brute : ' . json_encode($peexTx) . ')');
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
                    $txtFrom = "Send-Paz";
                    $rejectedSMS = [
                        "from" =>  $txtFrom,
                        "to" =>  $phone_sender,
                        "text" =>  "Cher(e) client(e), votre transaction Send-Paz N° " . $transaction['ranking'] . " vers " . $transaction['receiving_country'] . " a été rejetée. Prière de vous rapprocher de Send-Paz pour plus de détails."
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
                return redirect()->route('transaction_list')->with('error', 'Transaction en attente de traitement chez ' . $partnerLabel . ' (statut : ' . ($peexStatus ?? 'inconnu') . ').');
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
                $txtFrom = "Send-Paz";
                $successSMS = [
                    "from" =>  $txtFrom,
                    "to" =>  $phone_sender,
                    "text" =>  "Cher(e) client(e), votre transaction Send-Paz N° " . $numero . " vers " . $receiveCountry . " a été annulée pour (" . $note . "). Prière de vous rapprocher de Send-Paz pour plus de détails."
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
                $txtFrom = "Send-Paz";
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
                    "text" =>  "Vous avez effectué une transaction sur Send-Paz. Votre bénéficiaire est informé par SMS"
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
                //     "text" =>  "Vous etes bénéficiaire d'une transation sur Send-Paz. Rendez vous dans l'agence la plus proche"
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
            $transaction = $client->get(config('keys.url_api') . 'transactions/' . $id . '?_includes=sender,sender.user,user,user.agent,outbound.bank,outbound.mobile,outbound.cash', [
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

    /**
     * AJOUT (2026-08-08) : écran "payer un retrait interne" (voir
     * InternalTransferController côté backend / Task #21). Formulaire en 2
     * étapes géré par un seul champ caché 'step', même principe que le
     * wizard de validation existant (update -> getquotation -> sendtransaction) :
     *   1. 'lookup'  : l'agent saisit le code de retrait -> lookup_internal_transaction
     *                  affiche montant/nom du bénéficiaire pour vérification.
     *   2. 'confirm' : l'agent saisit la pièce d'identité présentée ->
     *                  payout_internal_transaction enregistre le paiement et
     *                  crédite le solde de l'agent payeur (décision utilisateur
     *                  du 2026-08-08 : l'agent avance l'argent de sa caisse, le
     *                  règlement entre agences se fait hors application).
     * N'importe quel agent connecté peut payer n'importe quel code (décision
     * utilisateur : pas d'agence fixe à l'envoi) — countryMismatchWarning côté
     * backend affiche juste un avertissement non-bloquant si le pays de
     * l'agent diffère du pays destinataire.
     */
    /**
     * Construit la requête de filtrage des transferts internes (corridor_id=3)
     * partagée entre payoutInternal() (liste paginée) et payoutInternalExport()
     * (export CSV complet, mêmes filtres, pas de pagination) — voir demande
     * utilisateur du 2026-08-08 ("mettre la date de à pour la recherche sur
     * une période, faire l'extraction de la liste en Excel").
     */
    private function internalTransferListQuery(Request $request): array
    {
        $filters = [
            'code' => trim((string) $request->get('f_code')),
            'date_from' => trim((string) $request->get('f_date_from')),
            'date_to' => trim((string) $request->get('f_date_to')),
            'agency' => trim((string) $request->get('f_agency')),
            'status' => trim((string) $request->get('f_status')),
        ];

        $query = [
            'corridor_id' => 3,
            '_includes' => 'user,agent,agent.country',
            '_sort' => 'created_at',
            '_sortDir' => 'desc',
        ];
        if ($filters['code'] !== '') {
            $query['internal_pickup_code-lk'] = $filters['code'];
        }
        if ($filters['date_from'] !== '' && $filters['date_to'] !== '') {
            // whereBetween sur un timestamp : on inclut toute la journée de fin,
            // sinon "23:59:59" implicite à "00:00:00" exclurait les transactions
            // du dernier jour.
            $query['created_at-bt'] = $filters['date_from'] . ',' . $filters['date_to'] . ' 23:59:59';
        } elseif ($filters['date_from'] !== '') {
            $query['created_at-get'] = $filters['date_from'] . ' 00:00:00';
        } elseif ($filters['date_to'] !== '') {
            $query['created_at-let'] = $filters['date_to'] . ' 23:59:59';
        }
        if ($filters['agency'] !== '') {
            $query['agent-fk'] = 'nom_commercial-lk=' . $filters['agency'];
        }
        if ($filters['status'] !== '') {
            $query['etat_transac'] = $filters['status'];
        }

        return [$filters, $query];
    }

    /**
     * AJOUT (2026-08-08) : export CSV (ouvrable dans Excel — aucune librairie
     * Excel n'est installée dans cette app, voir composer.json) de la liste des
     * transferts internes, avec les mêmes filtres que l'écran.
     */
    public function payoutInternalExport(Request $request)
    {
        $token = $request->session()->get('token');
        [, $listQuery] = $this->internalTransferListQuery($request);
        $listQuery['should_paginate'] = 'false';
        $listQuery['per_page'] = 10000;

        $client = new Client();
        try {
            $res = $client->get(config('keys.url_api') . 'transactions', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ],
                'query' => $listQuery
            ]);
            $rows = json_decode($res->getBody()->getContents(), true) ?: [];
        } catch (\Exception $e) {
            $rows = [];
        }

        $filename = 'transferts-internes-' . date('Y-m-d-His') . '.csv';
        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            // BOM UTF-8 : Excel (Windows) affiche sinon les accents mal encodés.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Statut', 'Référence', 'Code de retrait', 'Bénéficiaire', 'Téléphone bénéficiaire',
                'Montant', 'Devise', 'Pays', 'Agence expéditrice', 'Pays expéditeur', 'Expéditeur',
                'Envoyé le', 'Consulté le'], ';');
            foreach ($rows as $tx) {
                $statutLabels = ['AwaitingPickup' => 'En attente', 'success' => 'Payé', 'Rejected' => 'Rejeté'];
                fputcsv($out, [
                    $statutLabels[$tx['etat_transac'] ?? ''] ?? ($tx['etat_transac'] ?? ''),
                    $tx['ranking'] ?? '',
                    $tx['internal_pickup_code'] ?? '',
                    trim((($tx['recipient_first_name'] ?? '') . ' ' . ($tx['recipient_last_name'] ?? ''))),
                    $tx['recipient_phone'] ?? '',
                    $tx['montant_beneficiaire'] ?? '',
                    $tx['to_currency'] ?? '',
                    $tx['receiving_country'] ?? '',
                    $tx['agent']['nom_commercial'] ?? '',
                    $tx['agent']['country']['name'] ?? '',
                    trim((($tx['user']['first_name'] ?? '') . ' ' . ($tx['user']['last_name'] ?? ''))),
                    $tx['created_at'] ?? '',
                    $tx['beneficiary_checked_in_at'] ?? '',
                ], ';');
            }
            fclose($out);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function payoutInternal(Request $request)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $agent = $request->session()->get('agent');
        $menu = 'InternalPayout';
        // Résoudre l'agent (même logique que index()) : si l'agent a lui-même
        // un agent parent, c'est ce dernier qui encaisse/décaisse réellement.
        if ($agent !== null && isset($agent['agent']) && $agent['agent'] !== null) {
            $agent = $agent['agent'];
        }

        // FIX (2026-08-08) : un administrateur/finance_manager/technical_support
        // n'a AUCUN compte agent en session ($agent === null ici) — jusqu'ici le
        // formulaire envoyait quand même agent_id=null à l'API, qui refusait avec
        // "pickup_code, agent_id et payout_id_number sont requis" (payout_internal_
        // transaction exige les 3). On laisse donc ces rôles CHOISIR quelle agence
        // encaisse, via un sélecteur alimenté par la liste des agences (is_partner=1).
        // Pour un agent/cashier déjà lié à une agence, ce sélecteur ne s'affiche pas :
        // $payerAgentId reste simplement $agent['id'], comportement inchangé.
        $needsAgentPicker = $agent === null;
        $agentsList = [];
        $client = new Client();
        if ($needsAgentPicker) {
            try {
                $res = $client->get(config('keys.url_api') . 'agents?_includes=user&is_partner=1&per_page=500', [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ]
                ]);
                $agentsList = json_decode($res->getBody()->getContents(), true)['data'] ?? [];
            } catch (\Exception $e) {
                $agentsList = [];
            }
        }
        $payerAgentId = $agent['id'] ?? $request->get('payer_agent_id');

        // AJOUT (2026-08-08) : liste de TOUS les transferts internes (tous statuts),
        // avec filtres — demande utilisateur : "l'admin doit voir toutes les
        // transactions en liste, et mettre des filtres pour les rechercher, code,
        // date, nom de l'agence etc." Remplace l'ancienne liste limitée aux seuls
        // retraits déjà "consultés" par leur bénéficiaire (celle-ci reste visible :
        // colonne "Consulté le", triable/filtrable comme le reste).
        $listPage = max(1, (int) ($request->get('list_page') ?: 1));
        [$filters, $listQuery] = $this->internalTransferListQuery($request);
        $filterCode = $filters['code'];
        $filterDateFrom = $filters['date_from'];
        $filterDateTo = $filters['date_to'];
        $filterAgency = $filters['agency'];
        $filterStatus = $filters['status'];
        $listQuery['per_page'] = 30;
        $listQuery['page'] = $listPage;

        $pendingList = [];
        $listMeta = ['total' => 0, 'current_page' => 1, 'last_page' => 1];
        try {
            $res = $client->get(config('keys.url_api') . 'transactions', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ],
                'query' => $listQuery
            ]);
            $body = json_decode($res->getBody()->getContents(), true);
            $pendingList = $body['data'] ?? [];
            $listMeta = [
                'total' => $body['total'] ?? count($pendingList),
                'current_page' => $body['current_page'] ?? 1,
                'last_page' => $body['last_page'] ?? 1,
            ];
        } catch (\Exception $e) {
            $pendingList = [];
        }

        $step = 'lookup';
        $lookup = null;
        $pickupCode = null;

        if ($request->getMethod() === 'POST') {
            $pickupCode = strtoupper(trim((string) $request->get('pickup_code')));

            if ($needsAgentPicker && !$payerAgentId) {
                return redirect()->route('internal_payout')->with('error', 'Veuillez sélectionner l\'agence qui encaisse.');
            }

            if ($request->get('reject') === '1') {
                // AJOUT (2026-08-08) : rejet du retrait (bénéficiaire non conforme,
                // pièce d'identité invalide, ou autre motif) — voir
                // InternalTransferController::reject_internal_transaction.
                $reason = $request->get('rejection_reason');
                if (!$reason) {
                    return redirect()->route('internal_payout')->with('error', 'Veuillez sélectionner un motif de rejet.');
                }
                try {
                    $res = $client->post(config('keys.url_api') . 'reject_internal_transaction', [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ],
                        'json' => [
                            'pickup_code' => $pickupCode,
                            'agent_id' => $payerAgentId,
                            'rejection_reason' => $reason,
                            'rejection_note' => $request->get('rejection_note'),
                        ]
                    ]);
                    $body = json_decode($res->getBody()->getContents(), true);
                    return redirect()->route('internal_payout')->with('warning',
                        'Transaction ' . ($body['ranking'] ?? '') . ' rejetée.');
                } catch (ClientException $e) {
                    $body = json_decode($e->getResponse()->getBody()->getContents(), true);
                    return redirect()->route('internal_payout')->with('error', $body['message'] ?? 'Erreur lors du rejet.');
                } catch (\Exception $e) {
                    return redirect()->route('internal_payout')->with('error', 'Erreur inattendue lors du rejet.');
                }
            }

            if ($request->get('confirm') === '1') {
                // Étape 2 : confirmation du paiement.
                try {
                    $res = $client->post(config('keys.url_api') . 'payout_internal_transaction', [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ],
                        'json' => [
                            'pickup_code' => $pickupCode,
                            'agent_id' => $payerAgentId,
                            'payout_id_number' => $request->get('payout_id_number'),
                            'payout_id_type' => $request->get('payout_id_type') ?: 'CNI',
                        ]
                    ]);
                    $body = json_decode($res->getBody()->getContents(), true);
                    return redirect()->route('internal_payout')->with('success',
                        'Paiement confirmé ! Transaction ' . ($body['ranking'] ?? '') . ' — '
                        . number_format($body['amount_paid'] ?? 0, 0, ',', ' ') . ' versés au bénéficiaire.');
                } catch (ClientException $e) {
                    $body = json_decode($e->getResponse()->getBody()->getContents(), true);
                    return redirect()->route('internal_payout')->with('error', $body['message'] ?? 'Erreur lors de la confirmation du paiement.');
                } catch (\Exception $e) {
                    return redirect()->route('internal_payout')->with('error', 'Erreur inattendue lors de la confirmation du paiement.');
                }
            }

            // Étape 1 : recherche par code.
            if ($pickupCode === '') {
                return redirect()->route('internal_payout')->with('error', 'Veuillez saisir un code de retrait.');
            }
            try {
                $res = $client->post(config('keys.url_api') . 'lookup_internal_transaction', [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ],
                    'json' => [
                        'pickup_code' => $pickupCode,
                        'agent_id' => $payerAgentId,
                    ]
                ]);
                $body = json_decode($res->getBody()->getContents(), true);
                $lookup = $body['transaction'] ?? null;
                $step = 'confirm';
                if (!empty($body['warning'])) {
                    session()->flash('warning', $body['warning']);
                }
            } catch (ClientException $e) {
                $body = json_decode($e->getResponse()->getBody()->getContents(), true);
                return redirect()->route('internal_payout')->with('error', $body['message'] ?? 'Code de retrait introuvable.');
            } catch (\Exception $e) {
                return redirect()->route('internal_payout')->with('error', 'Erreur inattendue lors de la recherche du code.');
            }
        }

        return view('transactions.internal_payout', compact('token', 'role', 'user', 'menu', 'step', 'lookup', 'pickupCode', 'needsAgentPicker', 'agentsList', 'payerAgentId', 'pendingList', 'listMeta', 'filterCode', 'filterDateFrom', 'filterDateTo', 'filterAgency', 'filterStatus'));
    }
}
