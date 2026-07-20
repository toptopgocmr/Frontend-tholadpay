<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;

class InitiateController extends Controller
{

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @return mixed
     */    

    public function create(Request $request)
    {
        $email_exist = '';
        $phone_exist = '';
        $token = $request->session()->get('token');
        $role = $request->session()->get('role');
        $user = $request->session()->get('user');
        $menu = 'Initiate';
        $type = 'add';
        $user_exist = '';
        $client = new Client();
        $userEdit = (isset($userEdit) && $userEdit !== null) ? $userEdit : null;
        $countries = $client->get(config('keys.url_api') . 'countries', [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        $countries = json_decode($countries->getBody()->getContents(), true)['data'];
        // dump($countries);
        // dump($role);

        $typeEnvoi = '';
        $email = '';
        $phone = '';
        $generatedPasse = '';
        if ($request->getMethod() === 'POST') {
            // $see = false;
            $typeEnvoi = $request->get('textType');
            $email = $request->get('textEmail');
            $phone = $request->get('textPhone');
            $codePhone = $request->get('textCodePhone');
            $password = $request->get('textPassword');
            $emailDB = $request->get('textEmailDB');
            $phoneDB = $request->get('textPhoneDB');
            $userID = $request->get('textIdUser');
            $textTypeEnvoiAfter = $request->get('textTypeEnvoiAfter');
            // dump('TYPE : ', $type);
            // if (!$see) {
            $us = [];
            if(empty($password) && $userEdit === null){
                if ($typeEnvoi === 'email') {
                    $us = [
                        'email' => $email
                    ];
                    // dump('Email : ', $us);
                    $res = $client->get(config('keys.url_api') . 'auth/get_user_by_email', [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json'
                        ],
                        'json' => $us
                    ]);
                    // $res = json_decode($res->getBody()->getContents(), true)['data'];
                } else {
                    $us = [
                        'phone_number' => $codePhone.$phone
                    ];
                    // dump('Phone : ', $us);
                    $res = $client->post(config('keys.url_api') . 'auth/get_user_by_phone', [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json'
                        ],
                        'json' => $us
                    ]);
                }
                $res = json_decode($res->getBody()->getContents(), true)['data'];
                // dump('USER DB : ', $res);
                if($res !== null){
                    $usr = $client->get(config('keys.url_api') . 'users/'.$res['id'].'?_includes=agent.agent,user_roles.role', [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ]
                    ]);
                    $usr = json_decode($usr->getBody()->getContents(), true);
                    // dump($usr);
                    // dump($usr['user_roles'][0]['role']['name']);
                    if($usr !== null  && $role !== 'administrator' && $usr['user_roles'][0]['role']['name'] === 'administrator'){
                        $userEdit = null; 
                        return redirect()->route('initiate_add')->with('error', 'Vous etes pas autorisé a modifier ce compte !');
                    } else {
                        $userEdit = $usr;              
                    
                        // $generatedPasse = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
                        // dump($generatedPasse);
                        // dump($userEdit);
                        // $taille = 4;
                        // $mdp = '';
                        // $chaine = "0123456789";
                        // $long = strlen($chaine);
                        // srand((double)microtime()*1000000);
                        // for ($i=0; $i < $taille; $i++)$mdp=$mdp.substr($chaine, rand(0,$long-1),1);
                        $generatedPasse = $this->getGeneratingCode(4);
                    }
                } else {
                    $userEdit = null; 
                    return redirect()->route('initiate_add')->with('error', 'Ce compte est trouvable ! Réessyez');
                }
            } else if(!empty($password)){
                // dump($password, $emailDB);
                // $userEdit = null; 
                $us = [
                    'is_active' => 0,
                    'admin_id' => $user['id'],
                    'status' => 'Suspended'
                ];
                $res = $client->put(config('keys.url_api') . 'users/' . $userID, [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ],
                    'json' => $us
                ]);
                
                $obj = [
                    'password' => $password,
                    'email' => strtolower(trim($emailDB))
                ];
                $result = $client->post(config('keys.url_api') . 'auth/updatepassword', [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json'
                    ],
                    'json' => $obj
                ]);
                $result = json_decode($result->getBody()->getContents(), true)['data']['user'];                
                // dump($result);
                // envoi du SMS
                $txtFrom = "TholadPay";
                $txtMessage = "Suite à votre demande de réinitialisation du mot de passe, votre nouveau mot de passe est : ".$password;
                // dump($txtTo);
                $data_json = [
                    "from" =>  $txtFrom,
                    "to" =>  $phoneDB,
                    "text" =>  $txtMessage
                ];

                $smsSend = $client->post(config('keys.url_api') . 'auth/send_sms_to_phone', [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json'
                    ],
                    'json' => $data_json
                ]);
                $smsSend = json_decode($smsSend->getBody()->getContents(), true);
                // dump('SMS SEND : ', $smsSend['data']['response'], $smsSend['errors']);
                
                return redirect()->route('initiate_add')->with('success', 'Le mot de passe a été modifié et le compte est suspendu.');
            } else {
                return redirect()->route('initiate_add')->with('error', 'Veuillez saisir le mot de passe temporaire.');
            }
                        // return redirect()->route('initiate_add')->with('success', 'L\'utilisateur a été ajouté avec succès.');
                    // }
                // }
            /*try {

            } catch (\Exception $e) {
                
                dump($e->getMessage());
                \Session::flash('error', 'Erreur lors de la modification, bien vouloir reéssayer.');
                // return view('users.add', compact('email_exist', 'phone_exist', 'user', 'menu', 'towns', 'roles', 'role'))
                //    ->with('error', 'Erreur lors de l\'enregistrement, bien vouloir reéssayer.');
            }*/
        }
        return view('initiates.add', compact('userEdit', 'type', 'phone', 'email', 'typeEnvoi', 'generatedPasse', 'user', 'menu', 'role', 'countries', 'user_exist'));
    }

    public function getGeneratingCode($taille)
    {
        $mdp = '';
        $chaine = "abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ@$%#^&*()_=+-{[}]?\/><.:;";
        $cars = "1234567890";
        $long = strlen($cars);
        srand((double)microtime()*1000000);
        for ($i=0; $i < $taille; $i++)$mdp=$mdp.substr($cars, rand(0,$long-1),1);

        return $mdp;
    }
}
