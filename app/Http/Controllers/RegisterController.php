<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\MessageBag;

class RegisterController extends Controller
{
    //
        function register(Request $request){
            $errors = new MessageBag();
            // dd('This is some useful information.');
            if ($request->getMethod() === 'POST') {
                // dd('This is some useful information. dsdsdsdsd ', $request);
                $token = $request->get('_token');
                $firstName = $request->get('_first_name');
                $lastName = $request->get('_last_name');
                $phone = $request->get('_phone_number');
                $email = $request->get('_email');
                $password = $request->get('_password');
                $confpassword = $request->get('_conf_password');
                $data = [
                    'email' => $email,
                    'phone_number' => $phone,
                    'last_name' => $lastName,
                    'first_name' => $firstName,
                    'role_id' => 4,
                    'is_active' => 0,
                    'password' => $password
                ];
                
                if($password !== $confpassword){
                    $errors->add('error', 'Les mots de passe ne sont pas identiques.');
                    return view('auth.security.register', compact('errors'));
                } else {
                    try {
                        $client = new Client();
                        $response = $client->post(config('keys.url_api') . 'auth/signup', [
                            'verify' => false,
                            'headers' => [
                                'Content-Type' => 'application/json'
                            ],
                            'json' => $data
                        ]);
                        // dump($response);
                        $response = json_decode($response->getBody()->getContents(), true);
                        // dump($response['data']);
                        // dd('Tout est OK : ', $response);
                        // return view('auth.security.login');
                        $errors->add('success', 'Veuillez-vous connecter !');
                        return view('auth.security.login', compact('errors'));
                    } catch (\Exception $exception) {                    
                        // dd('erreor .', $exception);
                        $errors->add('error', 'Une exception au niveau serveur.');
                        return view('auth.security.register', compact('errors'));
                    }
                }
            } 
            else {
                return view('auth.security.register');
            }           
        }
    }