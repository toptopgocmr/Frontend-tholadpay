@extends('layouts.base')

@section('title')
    Transaction - Détail
@stop

@section('stylesheets')
    <!-- DataTables -->
    <link href="{{ asset('assets/plugins/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet"
          type="text/css"/>
    <link href="{{ asset('assets/plugins/datatables/buttons.bootstrap4.min.css') }}" rel="stylesheet" type="text/css"/>
    <!-- Responsive datatable examples -->
    <link href="{{ asset('assets/plugins/datatables/responsive.bootstrap4.min.css') }}" rel="stylesheet"
          type="text/css"/>
@stop

@section('content')
    <!-- Start content -->
    <div class="content">
        <div class="container-fluid">
            <div class="page-title-box">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <h4 class="page-title">Transaction</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('admin') }}"><i
                                            class="mdi mdi-home-outline"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{ route('transaction_list') }}">Transactions</a></li>
                            <li class="breadcrumb-item active">Transaction</li>
                        </ol>
                    </div>
                </div> <!-- end row -->
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            @if ($message = Session::get('success'))
                                <div class="alert alert-success alert-block">
                                    <button type="button" class="close" data-dismiss="alert">×</button>
                                    <strong>{{ $message }}</strong>
                                </div>
                            @endif
                            @if ($message = Session::get('error'))
                                <div class="alert alert-danger alert-block">
                                    <button type="button" class="close" data-dismiss="alert">×</button>
                                    <strong>{{ $message }}</strong>
                                </div>
                            @endif
                            {{-- DIAGNOSTIC TEMPORAIRE (2026-07-04, voir §4.24/§4.28 du rapport) : la
                                 cotation ($quote) transitait par une clé de session flash qui expirait
                                 trop tôt, affichant "Montant à Percevoir : 0 XAF" à tort. Corrigé en
                                 passant par une session persistante (quote_{id}). Ce bandeau rend le
                                 problème immédiatement visible à l'écran s'il devait malgré tout se
                                 reproduire (build pas rechargé, opcache, etc.), au lieu de se contenter
                                 d'un silencieux "0 XAF" indiscernable d'une vraie valeur nulle. À retirer
                                 une fois le correctif confirmé stable sur plusieurs tests. --}}
                            @if (!isset($quote) || $quote === null || !isset($quote['receivingAmount']))
                                <div class="alert alert-warning alert-block">
                                    <strong>Attention :</strong> aucune cotation valide trouvée en session pour cette transaction — "Montant à Percevoir" affiche 0 par défaut, ce n'est probablement PAS le vrai montant. Retournez à l'étape "Cotation" pour recalculer, et si le problème persiste, vérifiez que le serveur admin a bien été redémarré après la dernière mise à jour du code.
                                </div>
                            @endif
                            <form action="{{ route("transaction_transac", $transaction['id']) }}" method="post" class="form-horizontal">
                                {{ csrf_field() }}                                
                               
                                <div style="display: block">
                                    <h3>Validation Transaction (3/3)</h3>
                                    <h5>Transaction</h5>
                                    <fieldset>
                                        <div class="row">
                                            <div class="col-4">
                                                <div class="form-group row">
                                                    <label class="col-5 col-form-label">Pays</label>
                                                    <label class="col-7 col-form-label" id="recapPays" style="font-weight: bold">{{$transaction['receiving_country']}}</label>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="form-group row">
                                                    <label class="col-5 col-form-label">Nom Bénéficiaire</label>
                                                    <label class="col-7 col-form-label" id="recapBenef" style="font-weight: bold">{{$transaction['recipient_first_name']}} {{$transaction['recipient_last_name']}}</label>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="form-group row">
                                                    <label class="col-5 col-form-label">Montant Envoyé</label>
                                                    <label class="col-7 col-form-label" id="recapSend" style="font-weight: bold">{{$transaction['amount']}} {{$transaction['from_currency']}}</label>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="form-group row">
                                                    <label class="col-5 col-form-label">Montant à Percevoir</label>
                                                    <label class="col-7 col-form-label" id="recapPercu" style="font-weight: bold">{{$quote['receivingAmount'] ?? 0}} {{$transaction['to_currency']}}</label>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="form-group row">
                                                    <label class="col-5 col-form-label">Type Transaction</label>
                                                    <label class="col-7 col-form-label" id="recapType" style="font-weight: bold">
                                                    {!! $transaction['outbound']['bank'] === null ? 'Mobile' : 'Bancaire' !!}
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="form-group row">
                                                    <label class="col-5 col-form-label">Numéro à Créditer</label>
                                                    <label class="col-7 col-form-label" id="recapAccount" style="font-weight: bold">{{$transaction['recipient_phone']}}</label>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="form-group row">
                                                    <label class="col-5 col-form-label">Téléphone bénéficiaire</label>
                                                    <label class="col-7 col-form-label" id="recapTel" style="font-weight: bold">{{$transaction['recipient_phone']}}</label>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="form-group row">
                                                    <label class="col-5 col-form-label">Origin des fonds</label>
                                                    <label class="col-7 col-form-label" id="recapOrigin" style="font-weight: bold">{{$transaction['transaction_reference']}}</label>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="form-group row">
                                                    <label class="col-5 col-form-label">Raison du transfert</label>
                                                    <label class="col-7 col-form-label" id="recapReason" style="font-weight: bold">{{$transaction['transaction_reason']}}</label>
                                                </div>
                                            </div>
                                        </div>
                                    </fieldset>
                                    <h5>Expediteur</h5>
                                    <fieldset>
                                        <div class="row">
                                            <div class="col-4">
                                                <div class="form-group row">
                                                    <label class="col-5 col-form-label">Nom</label>
                                                    <label class="col-7 col-form-label" id="recapNomE" style="font-weight: bold">{{$transaction['user']['first_name']}}</label>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="form-group row">
                                                    <label class="col-5 col-form-label">Prenom</label>
                                                    <label class="col-7 col-form-label" id="recapPrenomE" style="font-weight: bold">{{$transaction['user']['last_name']}}</label>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="form-group row">
                                                    <label class="col-5 col-form-label">Telephone</label>
                                                    <label class="col-7 col-form-label" id="recapPhoneE" style="font-weight: bold">{{$transaction['user']['phone_number']}}</label>
                                                </div>
                                            </div>
                                        </div>
                                        <input type="hidden" name="montant" value="{{$quote['receivingAmount'] ?? ''}}">
                                        <input type="hidden" name="quote_id" value="{{$quote['quoteId'] ?? ''}}">
                                        <input type="hidden" name="fxRate" value="{{$quote['fxRate'] ?? ''}}">
                                    </fieldset>
                                    <div class="row">
                                    <div class="col-5">
                                            <p style="color: red" id="error3"></p>
                                        </div>
                                        <div class="col-3">
                                            <img id="loading3" src="{{ asset('assets/images/loading.gif') }}" alt=""
                                                 style="max-width: 25px; display: none; max-height: 25px">
                                        </div>
                                        <div class="col-4">
                                            <div class="button-items" dir="ltr">
                                                <!-- id="btnSumit" -->
                                                <button id="btnStep3" type="submit" class="btn btn-primary btn-icon btn-rounded waves-effect">
                                                    <span class="btn-icon-label"><i class="mdi mdi-check-all mr-2"></i></span>
                                                    Confirmer la transaction
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div> <!-- end col -->
            </div> <!-- end row -->

        </div>
        <!-- container-fluid -->
    </div>
    <!-- content -->
@stop