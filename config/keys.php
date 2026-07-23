<?php
return [
    'url_api' => env('URL_API'),
    // FIX (2026-07-23) : les vues Blade concatènent directement
    // config('keys.url_img') avec un chemin d'image SANS slash de tête
    // (ex: 'uploads/digipay/xxx.jpeg', voir OutboundController/User côté
    // backend). Si URL_IMG dans .env n'a pas de "/" final (cas de la
    // production Railway : "https://backend-...railway.app" sans slash), la
    // concaténation produit une URL invalide ("...railway.appuploads/...")
    // et l'image échoue silencieusement à charger — symptôme observé :
    // "Pièces justificatives" vide côté admin alors que le mobile (dont le
    // environment.apiUrl a bien un slash final) affiche les mêmes images
    // sans problème. rtrim + un slash unique garantit un format correct quel
    // que soit ce qui est renseigné dans URL_IMG (avec ou sans slash final),
    // pour les 8 vues Blade qui utilisent cette clé.
    'url_img' => rtrim(env('URL_IMG', ''), '/') . '/'
];
