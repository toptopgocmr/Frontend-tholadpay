<?php

namespace App\Http\Middleware;

use Closure;
use GuzzleHttp\Client;

class Cashier
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($this->isAuth($request)) {
            return $next($request);
        }
        return redirect('login')->with('error', 'Permission Denied!!! You do not have administrative access.');
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @return mixed
     */
    public function isAuth($request)
    {
        if ($request->session()->exists('token')) {
            $role = $request->session()->get('role');
            if ($role === 'administrator' || $role === 'agent' || $role === 'cashier') {
                try {
                    $token = $request->session()->get('token');
                    $client = new Client();
                    $response = $client->get(config('keys.url_api') . 'users/me?_includes=agent.agent', [
                        'verify' => false,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ]
                    ]);
                    $response = json_decode($response->getBody()->getContents(), true);
                    //                    dump($response);
                    if (isset($response['id'])) {
                        if ($role === 'agent' || $role === 'cashier') {
                            $resp = $client->get(config('keys.url_api') . 'users/' . $response['id'] . '?_includes=agent.agent', [
                                'verify' => false,
                                'headers' => [
                                    'Content-Type' => 'application/json',
                                    'Authorization' => 'Bearer ' . $token
                                ]
                            ]);
                            $resp = json_decode($resp->getBody()->getContents(), true);
                            //                            dump($resp);
                            $request->session()->put('agent', $resp['agent']);
                        }
                        return true;
                    }
                    $request->session()->remove('user');
                    $request->session()->remove('token');
                    $request->session()->remove('role');
                    return false;
                } catch (\Exception $exception) {
                    $request->session()->remove('user');
                    $request->session()->remove('token');
                    $request->session()->remove('role');
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
