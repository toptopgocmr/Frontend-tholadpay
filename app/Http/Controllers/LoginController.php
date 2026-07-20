<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\MessageBag;

class LoginController extends Controller
{
    function login(Request $request)
    {
        Log::info('Call login page by user');
        if ($this->isAuth($request)) {
            if ($request->getMethod() === 'POST') {
                $email = $request->get('_email');
                $password = $request->get('_password');
                $data = [
                    'email' => $email,
                    'password' => $password
                ];
                $errors = new MessageBag();

                try {
                    $client = new Client();
                    $response = $client->post(config('keys.url_api') . 'auth/login', [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json'
                        ],
                        'json' => $data
                    ]);
                    $response = json_decode($response->getBody()->getContents(), true);
                    if (isset($response['data']) && !$response['errors']) {
                        if ($response['data']['user']['is_active']) {
                            Log::info('Login successful by user: ' . $email);
                            $url_img = getenv('APP_URL');
                            $response['data']['user']['url_img'] = $url_img;
                            $response['data']['user']['settings'] = $url_img;
                            $request->session()->put('user', $response['data']['user']);
                            $request->session()->put('token', $response['data']['token']);
                            $request->session()->put('role', $response['data']['userRole'][0]);
                            // Horodatage de connexion, utilise par la topbar pour afficher
                            // "connecté depuis" (date + heure) a cote du profil.
                            $request->session()->put('login_time', now());
                            return redirect()->route('admin');
                        } else {
                            $errors->add('error', 'Vous avec ete bloque par l\'administrateur, veuillez le contacter');
                            return view('auth.security.login', compact('errors'));
                        }
                    } else {
                        $errors->add('error', 'Email ou mot de passe incorrect.');
                        return view('auth.security.login', compact('errors'));
                    }
                } catch (ConnectException $exception) {
                    // Le serveur backend (URL_API) ne repond pas du tout : DNS/port ferme/timeout.
                    Log::error('Login backend unreachable (' . config('keys.url_api') . ') : ' . $exception->getMessage());
                    $errors->add('error', 'Le serveur d\'authentification est injoignable. Verifiez que l\'API backend est demarree et accessible a : ' . config('keys.url_api'));
                    return view('auth.security.login', compact('errors'));
                } catch (ClientException $exception) {
                    // 4xx : l'API a repondu mais refuse (401/422 = mauvais identifiants).
                    $status = $exception->getResponse() ? $exception->getResponse()->getStatusCode() : 0;
                    Log::warning('Login failed for ' . $email . ' (HTTP ' . $status . ')');
                    $errors->add('error', 'Email ou mot de passe incorrect.');
                    return view('auth.security.login', compact('errors'));
                } catch (ServerException $exception) {
                    // 5xx : l'API est joignable mais plante cote serveur.
                    Log::error('Login backend error (HTTP 5xx) : ' . $exception->getMessage());
                    $errors->add('error', 'Le serveur d\'authentification a rencontre une erreur interne. Veuillez reessayer dans un instant.');
                    return view('auth.security.login', compact('errors'));
                } catch (RequestException $exception) {
                    // Autre erreur de transport.
                    Log::error('Login request exception : ' . $exception->getMessage());
                    $errors->add('error', 'Erreur de communication avec le serveur d\'authentification.');
                    return view('auth.security.login', compact('errors'));
                } catch (\Exception $exception) {
                    // Filet de securite : on log tout pour pouvoir diagnostiquer.
                    Log::error('Login unexpected exception : ' . $exception->getMessage());
                    $errors->add('error', 'Erreur systeme, veuillez reessayer plus tard.');
                    return view('auth.security.login', compact('errors'));
                }
            }
            return view('auth.security.login');
        } else {
            return redirect()->route('admin');
        }
    }

    public function forgotPassword(Request $request)
    {
        if ($request->getMethod() === 'POST') {
            $email = $request->get('_email');
            $data = [
                'email' => $email
            ];
            $errors = new MessageBag();
            try {
                $client = new Client();
                $response = $client->post(config('keys.url_api') . 'auth/fortgot_password', [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json'
                    ],
                    'json' => $data
                ]);
                $response = json_decode($response->getBody()->getContents(), true);
                \Session::flash('success', 'Vous allez recevoir un mail de confirmation.');
                return view('auth.security.login');
            } catch (\Exception $exception) {
                $errors->add('error', 'Une exception au niveau serveur.');
                return view('auth.security.forgotpassword', compact('errors'));
            }
        }
        return view('auth.security.forgotpassword');
    }

    public function creerNewPassword(Request $request, $email, $token)
    {
        $myData = [
            'email' => trim(strtolower($email))
        ];
        $client = new Client();
        $user = $client->get(config('keys.url_api') . 'auth/get_user_by_email', [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'json' => $myData
        ]);
        $user = json_decode($user->getBody()->getContents(), true)['data'];

        if ($request->getMethod() === 'POST') {
            $errors = new MessageBag();
            $passe = $request->get('_password');
            $passeConf = $request->get('_passwordConf');
            if ($passe !== $passeConf) {
                $errors->add('error', 'Les mots de passe ne sont pas identiques.');
                return view('auth.security.newpassword', compact('email', 'token', 'user', 'errors'));
            } else {
                try {
                    $us = [
                        'password' => $passeConf,
                        'email' => $user['email']
                    ];
                    $res = $client->post(config('keys.url_api') . 'auth/updatepassword', [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json'
                        ],
                        'json' => $us
                    ]);
                    $res = json_decode($res->getBody()->getContents(), true);
                    \Session::flash('success', 'Veuillez-vous connecter avec votre nouveau mot de passe.');
                    return redirect()->route('login');
                } catch (\Exception $exception) {
                    $errors->add('error', 'Une exception est survenue, veuillez contacter l\'administrateur.');
                    return view('auth.security.newpassword', compact('email', 'token', 'user', 'errors'));
                }
            }
        }
        return view('auth.security.newpassword', compact('email', 'token', 'user'));
    }


    public function logout(Request $request)
    {
        $userSess = $request->session()->get('user');
        if (is_array($userSess) && isset($userSess['email'])) {
            Log::info('Logout successful by user: ' . $userSess['email']);
        }
        $request->session()->remove('user');
        $request->session()->remove('token');
        $request->session()->remove('role');
        $request->session()->remove('agent');
        return redirect()->route('login');
    }

    /**
     * Verifie via l'API si la session courante est valide.
     * Retourne true quand l'utilisateur n'est PAS authentifie (= il faut afficher /login).
     */
    public function isAuth($request)
    {
        if ($request->session()->exists('token')) {
            try {
                $token = $request->session()->get('token');
                $client = new Client();
                $response = $client->get(config('keys.url_api') . 'users/me', [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                    ]
                ]);
                $response = json_decode($response->getBody()->getContents(), true);
                if (isset($response['id'])) {
                    return false;
                }
                return true;
            } catch (\Exception $exception) {
                return true;
            }
        } else {
            return true;
        }
    }
}
