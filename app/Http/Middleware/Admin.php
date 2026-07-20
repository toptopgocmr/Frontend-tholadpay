<?php

namespace App\Http\Middleware;

use Closure;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class Admin
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
            if ($request->session()->get('role') === 'administrator') {
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
                        return true;
                    }
                    $request->session()->remove('user');
                    $request->session()->remove('token');
                    $request->session()->remove('role');
                    return false;
                } catch (\Exception $exception) {
//                    dump($exception->getMessage());
                    $request->session()->remove('user');
                    $request->session()->remove('token');
                    $request->session()->remove('role');
                    return false;
//                    if ($exception->getCode() === 401) {
//                    }
                }
            } else { return false; }
        } else { return false; }
    }
}
