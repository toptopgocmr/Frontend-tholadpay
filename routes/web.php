<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/

Route::get('/', function () { return redirect()->route('admin'); });
Route::get('admin', [ 'middleware' => ['all'], 'uses' => 'AdminController@index', 'as' => 'admin' ]);
Route::get('admin/utilisateurs', [ 'middleware' => ['all'], 'uses' => 'UserController@index', 'as' => 'user_list' ]);
Route::match(array('GET','POST'), 'admin/utilisateurs/ajouter', [ 'middleware' => ['agent'], 'uses' => 'UserController@create', 'as' => 'user_add' ]);
Route::match(array('GET','POST'), 'admin/utilisateurs/{id}/modifier', [ 'middleware' => ['agent'], 'uses' => 'UserController@edit', 'as' => 'user_edit' ]);
Route::post('admin/utilisateurs/supprimer', [ 'middleware' => ['all'], 'uses' => 'UserController@delete', 'as' => 'user_delete' ]);
Route::match(array('GET','POST'), 'admin/utilisateurs/ajouter/caissier', [ 'middleware' => ['agent'], 'uses' => 'UserController@createCashier', 'as' => 'user_add_agent' ]);
Route::match(array('GET','POST'), 'admin/utilisateurs/{id}/detail', [ 'middleware' => ['all'], 'uses' => 'UserController@show', 'as' => 'user_show' ]);
// FIX (2026-07-04) : ajout du middleware 'cors' (qui pose aussi les en-têtes
// "cache-control: no-cache, no-store", voir app/Http/Middleware/Cors.php) sur
// toutes les pages de détail/validation de transaction. Sans en-tête explicite,
// le navigateur pouvait servir une page HTML mise en cache (retour arrière,
// onglet resté ouvert, etc.) montrant des données obsolètes pour une
// transaction pourtant à jour en base — cause du numéro bénéficiaire erroné
// observé par le client alors que la base était correcte (voir §4.17 du rapport).
Route::get('admin/transactions', [ 'middleware' => ['all', 'cors'], 'uses' => 'TransactionController@index', 'as' => 'transaction_list' ]);
Route::match(array('GET','POST'), 'admin/transactions/ajouter', [ 'middleware' => ['all', 'cors'], 'uses' => 'TransactionController@create', 'as' => 'transaction_add' ]);
Route::match(array('GET','POST'), 'admin/transactions/search', [ 'middleware' => ['all', 'cors'], 'uses' => 'TransactionController@search', 'as' => 'transaction_search' ]);
Route::get('admin/transactions/{id}', [ 'middleware' => ['all', 'cors'], 'uses' => 'TransactionController@show', 'as' => 'transaction_show' ]);
Route::get('admin/transactions/{id}/trace', [ 'middleware' => ['all', 'cors'], 'uses' => 'TransactionController@showTraceFullTransaction', 'as' => 'transaction_trace' ]);
Route::match(array('GET','POST'), 'admin/transactions/{id}/validation', [ 'middleware' => ['all', 'cors'], 'uses' => 'TransactionController@update', 'as' => 'transaction_valid' ]);
Route::match(array('GET','POST'), 'admin/transactions/{id}/quotation', [ 'middleware' => ['all', 'cors'], 'uses' => 'TransactionController@getquotation', 'as' => 'transaction_quote' ]);
Route::match(array('GET','POST'), 'admin/transactions/{id}/sendtransaction', [ 'middleware' => ['all', 'cors'], 'uses' => 'TransactionController@sendtransaction', 'as' => 'transaction_transac' ]);
Route::match(array('GET','POST'), 'admin/transactions/{id}/checkstatus', [ 'middleware' => ['all', 'cors'], 'uses' => 'TransactionController@checkStatusOfTransaction', 'as' => 'transaction_check' ]);
Route::match(array('GET','POST'), 'admin/transactions/{id}/notes', [ 'middleware' => ['all', 'cors'], 'uses' => 'TransactionController@viewNotes', 'as' => 'transaction_notes' ]);

Route::match(array('GET','POST'), 'admin/transactions/validate', [ 'middleware' => ['all'], 'uses' => 'TransactionController@validateTransaction', 'as' => 'transaction_validate' ]);
Route::match(array('GET','POST'), 'admin/transactions/cancel', [ 'middleware' => ['all'], 'uses' => 'TransactionController@delete', 'as' => 'transaction_delete' ]);

Route::get('admin/villes', [ 'middleware' => ['agent'], 'uses' => 'TownController@index', 'as' => 'town_list' ]);
Route::match(array('GET','POST'), 'admin/villes/ajouter', [ 'middleware' => ['agent'], 'uses' => 'TownController@create', 'as' => 'town_add' ]);
Route::get('admin/villes/{id}/detail', [ 'middleware' => ['admin'], 'uses' => 'TownController@show', 'as' => 'town_show' ]);
Route::match(array('GET','POST'), 'admin/villes/{id}/modifier', [ 'middleware' => ['admin'], 'uses' => 'TownController@edit', 'as' => 'town_edit' ]);
Route::post('admin/villes/supprimer', [ 'middleware' => ['admin'], 'uses' => 'TownController@delete', 'as' => 'town_delete' ]);
Route::get('admin/pays', [ 'middleware' => ['admin'], 'uses' => 'CountryController@index', 'as' => 'country_list' ]);
Route::get('admin/pays/{id}/detail', [ 'middleware' => ['admin'], 'uses' => 'CountryController@show', 'as' => 'country_show' ]);
Route::match(array('GET','POST'), 'admin/pays/{id}/modifier', [ 'middleware' => ['admin'], 'uses' => 'CountryController@edit', 'as' => 'country_edit' ]);
Route::post('admin/pays/supprimer', [ 'middleware' => ['admin'], 'uses' => 'CountryController@delete', 'as' => 'country_delete' ]);
Route::match(array('GET','POST'), 'admin/pays/ajouter', [ 'middleware' => ['admin'], 'uses' => 'CountryController@create', 'as' => 'country_add' ]);
Route::get('admin/prefinancement', [ 'middleware' => ['all'], 'uses' => 'PrefundController@index', 'as' => 'prefund_list' ]);
Route::get('admin/prefinancement/{id}/detail', [ 'middleware' => ['all'], 'uses' => 'PrefundController@show', 'as' => 'prefund_show' ]);
Route::match(array('GET','POST'), 'admin/prefinancement/{id}/modifier', [ 'middleware' => ['all'], 'uses' => 'PrefundController@edit', 'as' => 'prefund_edit' ]);
Route::match(array('GET','POST'), 'admin/agent/{id}/prefinancement', [ 'middleware' => ['agent'], 'uses' => 'PrefundController@prefund', 'as' => 'prefund_account' ]);
Route::post('admin/prefinancement/supprimer', [ 'middleware' => ['agent'], 'uses' => 'PrefundController@delete', 'as' => 'prefund_delete' ]);
Route::match(array('GET','POST'), 'admin/prefinancement/ajouter', [ 'middleware' => ['agent'], 'uses' => 'PrefundController@create', 'as' => 'prefund_add' ]);

// Le contrôleur applicatif (App\Http\Controllers\LoginController) gère le login via l'API
// (Guzzle + session). Auth\LoginController est seulement le squelette Laravel par défaut
// (trait AuthenticatesUsers) qui attend les champs email/password et un guard local — ce
// qui provoquait la boucle de redirection ERR_TOO_MANY_REDIRECTS.
Route::match(array('GET','POST'), '/login', [ 'uses' => 'LoginController@login', 'as' => 'login' ]);
Route::get('/logout', [ 'uses' => 'LoginController@logout', 'as' => 'logout' ]);

Route::match(array('GET','POST'), '/register', [ 'uses' => 'RegisterController@register']);
Route::get('admin/points', [ 'middleware' => ['retail_agent'], 'uses' => 'RetailOutletController@index', 'as' => 'retailoutlet_list' ]);
Route::match(array('GET','POST'), 'admin/points/ajouter', [ 'middleware' => ['retail_agent'], 'uses' => 'RetailOutletController@create', 'as' => 'retailoutlet_add' ]);
Route::match(array('GET','POST'), 'admin/points/{id}/modifier', [ 'middleware' => ['retail_agent'], 'uses' => 'RetailOutletController@edit', 'as' => 'retailoutlet_edit' ]);
Route::post('admin/points/supprimer', [ 'middleware' => ['retail_agent'], 'uses' => 'RetailOutletController@delete', 'as' => 'retailoutlet_delete' ]);
// Route::get('admin/points/{id}/detail', [ 'middleware' => ['admin'], 'uses' => 'RetailOutletController@show', 'as' => 'retailoutlet_show' ]);
Route::get('admin/notes', [ 'middleware' => ['all'], 'uses' => 'NoteController@index', 'as' => 'note_list' ]);
Route::match(array('GET','POST'), 'admin/notes/ajouter', [ 'middleware' => ['all'], 'uses' => 'NoteController@create', 'as' => 'note_add' ]);
Route::match(array('GET','POST'), 'admin/notes/{id}/modifier', [ 'middleware' => ['all'], 'uses' => 'NoteController@edit', 'as' => 'note_edit' ]);
Route::post('admin/notes/supprimer', [ 'middleware' => ['all'], 'uses' => 'NoteController@delete', 'as' => 'note_delete' ]);
Route::get('admin/notes/{id}/detail', [ 'middleware' => ['all'], 'uses' => 'NoteController@show', 'as' => 'note_show' ]);

// Mêmes raisons que pour /login : les méthodes forgotPassword() et creerNewPassword()
// n'existent que dans App\Http\Controllers\LoginController, pas dans Auth\LoginController.
Route::match(array('GET','POST'), '/passForgot', [ 'uses' => 'LoginController@forgotPassword']);
Route::match(array('GET','POST'), '/newpassword/{email}/{token}/', [ 'uses' => 'LoginController@creerNewPassword']);

Route::get('admin/customers', [ 'middleware' => ['all'], 'uses' => 'CustomerController@index', 'as' => 'customer_list' ]);
Route::match(array('GET','POST'), 'admin/customers/ajouter', [ 'middleware' => ['all'], 'uses' => 'CustomerController@create', 'as' => 'customer_add' ]);
Route::match(array('GET','POST'), 'admin/customers/{id}/modifier', [ 'middleware' => ['all'], 'uses' => 'CustomerController@edit', 'as' => 'customer_edit' ]);
Route::post('admin/customers/supprimer', [ 'middleware' => ['all'], 'uses' => 'CustomerController@delete', 'as' => 'customer_delete' ]);
Route::get('admin/customers/{id}/detail', [ 'middleware' => ['all'], 'uses' => 'CustomerController@show', 'as' => 'customer_show' ]);
Route::match(array('GET','POST'), 'admin/customers/{id}/transactions', [ 'middleware' => ['all'], 'uses' => 'CustomerController@showTransactiomUser', 'as' => 'customer_transac' ]);
Route::match(array('GET','POST'), 'admin/customers/validate', [ 'middleware' => ['all'], 'uses' => 'CustomerController@validateCustomer', 'as' => 'customer_validate' ]);
Route::match(array('GET','POST'), 'admin/customers/cancel', [ 'middleware' => ['all'], 'uses' => 'CustomerController@cancelTransaction', 'as' => 'customer_transac_cancel' ]);

Route::get('admin/roles', [ 'middleware' => ['admin'], 'uses' => 'RoleController@index', 'as' => 'role_list' ]);
Route::match(array('GET','POST'), 'admin/roles/ajouter', [ 'middleware' => ['admin'], 'uses' => 'RoleController@create', 'as' => 'role_add' ]);
Route::match(array('GET','POST'), 'admin/roles/{id}/modifier', [ 'middleware' => ['admin'], 'uses' => 'RoleController@edit', 'as' => 'role_edit' ]);
Route::post('admin/roles/supprimer', [ 'middleware' => ['admin'], 'uses' => 'RoleController@delete', 'as' => 'role_delete' ]);
Route::match(array('GET','POST'), 'admin/roles/{id}/detail', [ 'middleware' => ['admin'], 'uses' => 'RoleController@show', 'as' => 'role_show' ]);

Route::get('admin/currencies', [ 'middleware' => ['all'], 'uses' => 'CurrencyController@index', 'as' => 'currency_list' ]);
Route::match(array('GET','POST'), 'admin/currencies/ajouter', [ 'middleware' => ['all'], 'uses' => 'CurrencyController@create', 'as' => 'currency_add' ]);
Route::match(array('GET','POST'), 'admin/currencies/{id}/modifier', [ 'middleware' => ['all'], 'uses' => 'CurrencyController@edit', 'as' => 'currency_edit' ]);
Route::post('admin/currencies/supprimer', [ 'middleware' => ['all'], 'uses' => 'CurrencyController@delete', 'as' => 'currency_delete' ]);
Route::match(array('GET','POST'), 'admin/currencies/{id}/detail', [ 'middleware' => ['admin'], 'uses' => 'CurrencyController@show', 'as' => 'currency_show' ]);
Route::match(array('GET','POST'), 'admin/initiates', [ 'middleware' => ['all'], 'uses' => 'InitiateController@create', 'as' => 'initiate_add' ]);

Route::get('admin/zones', [ 'middleware' => ['admin'], 'uses' => 'ZoneController@index', 'as' => 'zone_list' ]);
Route::match(array('GET','POST'), 'admin/zones/ajouter', [ 'middleware' => ['admin'], 'uses' => 'ZoneController@create', 'as' => 'zone_add' ]);
Route::match(array('GET','POST'), 'admin/zones/{id}/modifier', [ 'middleware' => ['admin'], 'uses' => 'ZoneController@edit', 'as' => 'zone_edit' ]);
Route::post('admin/zones/supprimer', [ 'middleware' => ['admin'], 'uses' => 'ZoneController@delete', 'as' => 'zone_delete' ]);
Route::match(array('GET','POST'), 'admin/zones/{id}/detail', [ 'middleware' => ['admin'], 'uses' => 'ZoneController@show', 'as' => 'zone_show' ]);

Route::get('admin/tarifications', [ 'middleware' => ['all'], 'uses' => 'TarificationController@index', 'as' => 'tarif_list' ]);
Route::match(array('GET','POST'), 'admin/tarifications/ajouter', [ 'middleware' => ['all'], 'uses' => 'TarificationController@create', 'as' => 'tarif_add' ]);
Route::match(array('GET','POST'), 'admin/tarifications/{id}/modifier', [ 'middleware' => ['all'], 'uses' => 'TarificationController@edit', 'as' => 'tarif_edit' ]);
Route::post('admin/tarifications/supprimer', [ 'middleware' => ['all'], 'uses' => 'TarificationController@delete', 'as' => 'tarif_delete' ]);
Route::match(array('GET','POST'), 'admin/tarifications/{id}/detail', [ 'middleware' => ['all'], 'uses' => 'TarificationController@show', 'as' => 'tarif_show' ]);

Route::get('logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');
