<?php

namespace App\Http\Controllers;

use GuzzleHttp\Exception\RequestException;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Construit un message d'erreur clair et compréhensible à partir d'une
     * exception levée lors d'un appel à l'API backend, au lieu d'exposer
     * le texte brut de Guzzle (méthode HTTP, URL, code de statut, JSON).
     *
     * Gère les deux formats de réponse d'erreur renvoyés par l'API :
     *  - {"message": "...", "errors": {"champ": ["message", ...]}}  (format Laravel standard)
     *  - {"champ": ["message", ...]}                                 (erreurs de validation à plat,
     *                                                                  ex : réponse de auth/signup)
     *
     * @param \Throwable $e
     * @param string $default Message affiché si aucun message exploitable n'a été trouvé.
     * @return string
     */
    protected function extractErrorMessage(\Throwable $e, string $default = 'Une erreur est survenue. Veuillez réessayer.'): string
    {
        if ($e instanceof RequestException && $e->hasResponse()) {
            $body = json_decode($e->getResponse()->getBody()->getContents(), true);

            if (is_array($body)) {
                if (!empty($body['errors']) && is_array($body['errors'])) {
                    $messages = [];
                    foreach ($body['errors'] as $fieldMessages) {
                        foreach ((array) $fieldMessages as $msg) {
                            if (is_string($msg)) {
                                $messages[] = $msg;
                            }
                        }
                    }
                    if (!empty($messages)) {
                        return implode(' ', $messages);
                    }
                }

                if (!empty($body['message']) && is_string($body['message'])) {
                    return $body['message'];
                }

                // Format à plat : {"champ": ["message", ...], ...}
                $messages = [];
                foreach ($body as $value) {
                    if (is_array($value)) {
                        foreach ($value as $msg) {
                            if (is_string($msg)) {
                                $messages[] = $msg;
                            }
                        }
                    } elseif (is_string($value)) {
                        $messages[] = $value;
                    }
                }
                if (!empty($messages)) {
                    return implode(' ', $messages);
                }
            }
        }

        return $default;
    }
}
