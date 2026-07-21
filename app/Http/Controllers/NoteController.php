<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;

class NoteController extends Controller
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
        $menu = 'Note';
        $client = new Client();
        $mesNotes = $client->get(config('keys.url_api') . 'notes?_includes=user,verifiable&per_page=3000', [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $mesNotes = json_decode($mesNotes->getBody()->getContents(), true)['data'];
        // dump($mesNotes);
        // dump('Mon User', $user);
        return view('notes.index', compact('token', 'role', 'user', 'menu', 'mesNotes'));
    }


    public function create(Request $request)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $agent = $request->session()->get('agent');
        $agent = null;
        // $agent = ($agent['agent'] !== null) ? $agent['agent'] : $agent;
        // dump($agent);
        $menu = 'Note';
        $type = 'add';
        try {
            $client = new Client();
            $host = config('keys.url_api');
            $path = ($role === 'agent' || $role === 'cashier') ? 'transactions?agent_id=' . $agent['id'] . '&per_page=300&_sortDir=desc' : 'transactions?per_page=300&_sortDir=desc';
            $host = $host . $path;
            $transactions = $client->get($host, [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $transactions = json_decode($transactions->getBody()->getContents(), true)['data'];
            // dump($transactions);
            $notes = $client->get(config('keys.url_api') . 'notes?_includes=user,verifiable', [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $notes = json_decode($notes->getBody()->getContents(), true)['data'];
            $note = count($notes) > 0 ? $notes[0] : '';
            // dump($notes, $note);
        } catch (\Exception $e) {
            $erreurM = $this->extractErrorMessage($e, $e->getMessage());
            \Log::error('[NoteController] ' . $erreurM);
            //    return redirect()->route('login');
        }
        if ($request->getMethod() === 'POST') {
            try {
                $transactionTxt = $request->get('textTransaction');
                $noteTxt = $request->get('textNote');
                $see = false;
                if (!$see) {
                    $crty = [
                        'detail' => $noteTxt,
                        'verifiable_id' => $transactionTxt,
                        'verifiable_type' => 'App\Transaction',
                        'status' => 'done',
                        'user_id' => $user['id']
                    ];
                    // dump('Objet a ajouter : ', $crty);
                    $res = $client->post(config('keys.url_api') . 'notes', [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ],
                        'json' => $crty
                    ]);
                    $res = json_decode($res->getBody()->getContents(), true);
                    return redirect()->route('note_list')->with('success', 'La note a été ajoutée avec succès.');
                    // dump($res);
                }
            } catch (\Exception $e) {
                // dump($e->getMessage());
                \Session::flash('error', 'Erreur lors de l\'enregistrement, bien vouloir reéssayer.');
            }
        }
        return view('notes.add', compact('user', 'menu', 'role', 'note', 'transactions', 'type'));
    }


    public function edit(Request $request, $id)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $agent = $request->session()->get('agent');
        $agent = null;
        // $agent = ($agent['agent'] !== null) ? $agent['agent'] : $agent;
        $menu = 'Note';
        $type = 'edit';
        $client = new Client();
        $host = config('keys.url_api');
        $path = ($role === 'agent' || $role === 'cashier') ? 'transactions?agent_id=' . $agent['id'] . '&per_page=300&_sortDir=desc' : 'transactions?per_page=300&_sortDir=desc';
        $host = $host . $path;
        $transactions = $client->get($host, [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $transactions = json_decode($transactions->getBody()->getContents(), true)['data'];
        $note = $client->get(config('keys.url_api') . 'notes/' . $id, [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $note = json_decode($note->getBody()->getContents(), true);
        $transactionNote = $client->get(config('keys.url_api') . 'transactions/' . $note['verifiable_id'], [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $transactionNote = json_decode($transactionNote->getBody()->getContents(), true);
        // dump($transactionNote);
        if ($request->getMethod() === 'POST') {
            try {

                $transactionTxt = $request->get('textTransaction');
                $noteTxt = $request->get('textNote');

                $note['detail'] = $noteTxt;
                $note['verifiable_id'] = $transactionTxt;

                // dump($note);
                $res = $client->put(config('keys.url_api') . 'notes/' . $id, [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ],
                    'json' => $note
                ]);
                $res = json_decode($res->getBody()->getContents(), true);
                return redirect()->route('note_list')->with('success', 'La note a été modifiée avec succès.');
            } catch (\Exception $e) {
                // dump($e->getMessage());
                \Session::flash('error', 'Erreur lors de la modification, bien vouloir reéssayer.');
            }
        }
        return view('notes.edit', compact('user', 'menu', 'role', 'note', 'transactions', 'type', 'transactionNote'));
    }

    public function show(Request $request, $id)
    {
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'Note';
        $client = new Client();
        $note = $client->get(config('keys.url_api') . 'notes/' . $id, [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $note = json_decode($note->getBody()->getContents(), true);
        // dump($note);
        return view('notes.show', compact('token', 'role', 'user', 'menu', 'note'));
    }

    public function delete(Request $request)
    {
        if ($request->getMethod() === 'POST') {
            try {
                $id = $request->get('id_delete');
                // dump($id);
                $token = $request->session()->get('token');
                $client = new Client();
                $note = $client->delete(config('keys.url_api') . 'notes/' . $id, [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ]
                ]);
                $note = json_decode($note->getBody()->getContents(), true);
                // dump($note);
                return redirect()->route('note_list')->with('success', 'La note a été supprimée avec succès.');
            } catch (\Exception $e) {
                // dump($e->getMessage());
                return redirect()->route('note_list')->with('error', 'Erreur lors de la suppression, bien vouloir reéssayer..');
            }
        }
    }
}
