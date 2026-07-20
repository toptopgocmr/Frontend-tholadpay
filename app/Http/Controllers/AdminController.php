<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;

class AdminController extends Controller
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
        $menu = 'Dashboard';
        $client = new Client();
        //  dd($token, $role, $user, $agent);
        // $agent = ($agent['agent'] !== null) ? $agent['agent'] : $agent;
        // $agent = null;
        // dump($agent, $user, $role);
        // dump($agent);
        // $request->session()->put('agent', $agent);
        $admins = 0;
        $cashiers = 0;
        $agents = 0;
        $trans = 0;
        $revenues = 0;
        $moy = 0;
        $fraisEnvoi = 0;
        $transAttente = 0;
        $transEchec = 0;
        $transCancel = 0;
        $montantTotal = 0; // egal montant envoyer plus les frais
        $prefundValid = 0;
        $prefundAnnul = 0;
        $prefundEchec = 0;
        $nbreCustomers = 0;
        $nbreFinances = 0;
        $totalSoldesDisponible = 0;
        $peexSolde = null;
        $peexActive = null;
        $finances = array();
        // if($agent !== null)
        // $agent['solde'] = number_format(floatval($agent['solde'].''), 2);
        if ($role === 'administrator' || $role === 'csa' || $role === 'finance_manager') {
            $users = $client->get(config('keys.url_api') . 'users?_includes=agent,agent.agent,user_roles.role&per_page=500', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $users = json_decode($users->getBody()->getContents(), true)['data'];
            // dump($users);
            foreach ($users as $u) {
                if (count($u['user_roles']) > 0 && $u['user_roles'][0]['role']['name'] === 'finance_manager') { // on teste si le finance manager role FM est actif et en charge
                    array_push($finances, $u);
                }
                if ((($u['agent'] === null && $u['status'] !== 'Rejected')) || ($u['agent'] !== null && $u['agent']['agent'] !== null && $u['agent']['is_partner'] === 0)) $admins += 1;
                else if ($u['agent'] !== null && $u['status'] !== 'Rejected' && $u['agent']['agent'] === null && $u['agent']['is_partner'] === 1) $agents += 1;
                else if ($u['agent'] !== null && $u['status'] !== 'Rejected' && $u['agent']['agent'] !== null && $u['agent']['is_partner'] === 1) $cashiers += 1;
            }
            // dump($finances);
            $nbreFinances = count($finances);
            $trans = $client->get(config('keys.url_api') . 'transactions?_includes=sender,sender.user,user,user.agent,outbound.bank,outbound.mobile&per_page=30000000&_sortDir=desc', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $trans = json_decode($trans->getBody()->getContents(), true)['data'];
            // dump($trans);
            // $transactions = $trans;
            $transactions = array();
            $transaFailed = array();
            $transaCancel = array();
            $transaWait = array();
            $myCustomers = array();
            foreach ($trans as $t) {
                if ($t['etat_transac'] === 'success') {
                    $revenues += $t['amount'];
                    $fraisEnvoi += $t['fees'];
                    array_push($transactions, $t);
                } else if ($t['etat_transac'] === 'failed') {
                    array_push($transaFailed, $t);
                } else if ($t['etat_transac'] === 'cancelled') {
                    array_push($transaCancel, $t);
                } else {
                    array_push($transaWait, $t);
                }
                // on ajoute expediteur
                array_push($myCustomers, $t['user']);
            }
            $montantTotal = $revenues + $fraisEnvoi;
            $trans = count($transactions);
            $moy = $trans > 0 ? number_format(floatval(($revenues / $trans) . ''), 3) : 0;
            $revenues = number_format(floatval($revenues . ''), 2);
            $fraisEnvoi = number_format(floatval($fraisEnvoi . ''), 2);
            $transAttente = count($transaWait);
            $transEchec = count($transaFailed);
            $transCancel = count($transaCancel);
            $customers = array_unique($myCustomers, SORT_REGULAR); // cette fonction permet de retire les doublons du tableau
            $nbreCustomers = count($customers);
            // dump($revenues);
            $prefunds = $client->get(config('keys.url_api') . 'prefundings?_includes=agent&per_page=30000000&_sortDir=desc', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $prefunds = json_decode($prefunds->getBody()->getContents(), true)['data'];
            // dump($prefunds);
            $prefunSucceed = array();
            $prefunCancel = array();
            $prefunWait = array();
            $prefunSucceedVal = 0;
            $prefunCancelVal = 0;
            $prefunWaitVal = 0;

            foreach ($prefunds as $pref) {
                if ($pref['status'] === 'Validated' || $pref['status'] === 'Received') {
                    // array_push($prefunSucceed, $pref);
                    $prefunSucceedVal += $pref['amount'];
                } else if ($pref['status'] === 'New') {
                    // array_push($prefunWait, $pref);
                    $prefunWaitVal += $pref['amount'];
                } else {
                    // array_push($prefunCancel, $pref);
                    $prefunCancelVal += $pref['amount'];
                }
            }
            $prefundValid = number_format(floatval($prefunSucceedVal . ''), 2);
            $prefundAnnul = number_format(floatval($prefunWaitVal . ''), 2);
            $prefundEchec = number_format(floatval($prefunCancelVal . ''), 2);

            $agts = $client->get(config('keys.url_api') . 'agents', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $agts = json_decode($agts->getBody()->getContents(), true)['data'];
            // dump($prefunds);
            $allAgents = array();
            $mySolde = 0;

            foreach ($agts as $ag) {
                $mySolde += $ag['solde'];
            }
            $totalSoldesDisponible = number_format(floatval($mySolde . ''), 2);

            // Solde réel du compte Peex (sandbox ou prod selon PEEX_URL/.env) — distinct de
            // $totalSoldesDisponible ci-dessus qui est la somme des soldes locaux des agents,
            // pas l'argent réellement disponible chez Peex. Voir OutboundController::get_peex_account
            // (GET clients/me, non protégé par jwt.auth).
            try {
                $peexAccount = $client->get(config('keys.url_api') . 'get_peex_account', [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ]
                ]);
                $peexAccount = json_decode($peexAccount->getBody()->getContents(), true);
                $peexSolde = isset($peexAccount['solde']) ? number_format(floatval($peexAccount['solde'] . ''), 2) : null;
                $peexActive = $peexAccount['is_activated'] ?? null;
            } catch (\Exception $e) {
                $peexSolde = null;
                $peexActive = null;
            }
        }
        // dd($agent);
        if ($role === 'agent' || $role === 'cashier' || $role === 'technical_support') {
            if ($role === 'agent') {
                $users = $client->get(config('keys.url_api') . 'agents?_includes=user,user.user_roles.role,agent.agent&agent_id=' . $agent['id'] . '&is_partner=1&per_page=200', [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ]
                ]);
                $users = json_decode($users->getBody()->getContents(), true)['data'];
                // dump($users);
                if ($role === 'agent') {
                    $caissiers = [];
                    foreach ($users as $u) {
                        if ($u['user'] !== null && $u['agent']['is_partner'] === 1) {
                            // dump($u);
                            array_push($caissiers, $u['user']);
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
                $cashiers = count($users);
            }
            if ($agent !== null)
                $totalSoldesDisponible = number_format(floatval($agent['solde'] . ''), 2);
            $myRequete = 'transactions?agent_id=' . $agent['id'] . '&_includes=sender,user,outbound.bank,outbound.mobile&per_page=30000000&_sortDir=desc';
            if ($role === 'cashier')
                $myRequete = 'transactions?valid_id=' . $user['id'] . '&_includes=sender,user,outbound.bank,outbound.mobile&per_page=30000000&_sortDir=desc';
            // $trans = $client->get(config('keys.url_api') . 'transactions?etat_transac=success&_includes=sender,sender.user&sender.user_id=' .$user['id']. ',user,user.agent,outbound.bank,outbound.mobile&per_page=30000000&_sortDir=desc', [
            // $trans = $client->get(config('keys.url_api') . 'transactions?etat_transac=success&_includes=sender,sender.user,user,user.agent,outbound.bank,outbound.mobile&sender.user_id=' .$user['id']. '&per_page=30000000&_sortDir=desc', [
            // $trans = $client->get(config('keys.url_api') . 'transactions?etat_transac=success&_includes=sender,sender.user,user,user.agent,outbound.bank,outbound.mobile&agent_id=' .$user['id']. '&per_page=30000000&_sortDir=desc', [
            if ($role === 'technical_support')
                $myRequete = 'transactions?_includes=sender,user,outbound.bank,outbound.mobile&per_page=30000000&_sortDir=desc';

            $trans = $client->get(config('keys.url_api') . $myRequete, [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $trans = json_decode($trans->getBody()->getContents(), true)['data'];
            // dump($trans);
            $transactions = array();
            $transaFailed = array();
            $transaCancel = array();
            $transaWait = array();
            $myCustomers = array();
            foreach ($trans as $t) {
                if ($t['etat_transac'] === 'success') {
                    $revenues += $t['amount'];
                    $fraisEnvoi += $t['fees'];
                    array_push($transactions, $t);
                } else if ($t['etat_transac'] === 'failed') {
                    array_push($transaFailed, $t);
                } else if ($t['etat_transac'] === 'cancelled') {
                    array_push($transaCancel, $t);
                } else {
                    array_push($transaWait, $t);
                }
                if ($role === 'cashier' && $t['reference'] !== '') { // si le caissier se connecté à  un PSA et qu'il a validé la transaction, on ajoute expediteur
                    array_push($myCustomers, $t['user']);
                }
            }
            $montantTotal = $revenues + $fraisEnvoi;
            $trans = count($transactions);
            $moy = $trans > 0 ? number_format(floatval(($revenues / $trans) . ''), 2) : 0;
            $revenues = number_format(floatval($revenues . ''), 2);
            $fraisEnvoi = number_format(floatval($fraisEnvoi . ''), 2);
            $transAttente = count($transaWait);
            $transEchec = count($transaFailed);
            $transCancel = count($transaCancel);
            $customers = array_unique($myCustomers, SORT_REGULAR); // cette fonction permet de retirer les doublons du tableau
            $nbreCustomers = count($customers);
        }
        // dump($user);
        return view('home', compact('token', 'role', 'user', 'menu', 'admins', 'agents', 'cashiers', 'trans', 'moy', 'revenues', 'transCancel', 'transactions', 'agent', 'fraisEnvoi', 'transAttente', 'transEchec', 'montantTotal', 'prefundValid', 'prefundAnnul', 'prefundEchec', 'nbreCustomers', 'nbreFinances', 'totalSoldesDisponible', 'peexSolde', 'peexActive'));
    }
}
