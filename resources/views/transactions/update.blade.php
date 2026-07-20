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
                            <form action="{{ route("transaction_valid", $transaction['id']) }}" method="post" class="form-horizontal">
                                {{ csrf_field() }}                                
                                
                                <div id="step1" style="display: block">
                                    <h3>Validation Bénéficiaire & Expéditeur (1/3)</h3>
                                    <h5>Informations Bénéficiaire</h5>
                                    <fieldset>
                                        <div class="row">
                                            <div class="col-4">
                                                <div class="form-group row">
                                                    <label for="country" class="col-4 col-form-label">Pays <i
                                                                class="red">*</i></label>
                                                    <div class="col-8">
                                                        <input type="text" disabled
                                                               value="{{$transaction['receiving_country']}}"
                                                               class="form-control" required name="country"
                                                               id="country">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4">
                                                <div class="form-group row">
                                                    <label for="nomB" class="col-4 col-form-label">Nom <i
                                                                class="red">*</i></label>
                                                    <div class="col-8">
                                                        <input type="text"
                                                               value="{{strtoupper($transaction['recipient_first_name'])}}"
                                                               class="form-control" required name="nomB" id="nomB">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="form-group row">
                                                    <label for="prenomB" class="col-4 col-form-label">Prénom <i
                                                                class="red">*</i></label>
                                                    <div class="col-8">
                                                        <input type="text"
                                                               value="{{ucwords($transaction['recipient_last_name'])}}"
                                                               class="form-control" required name="prenomB"
                                                               id="prenomB">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="form-group row">
                                                    <label for="phoneB"
                                                           class="col-4 col-form-label">{!! $transaction['outbound']['bank'] === null ? 'Téléphone' : 'Numéro Bancaire' !!}
                                                        <i
                                                                class="red">*</i></label>
                                                    <div class="col-8">
                                                        <input type="text"
                                                               value="{{$transaction['outbound']['bank'] === null ? $transaction['outbound']['mobile']['mobile_phone_credit'] : $transaction['outbound']['bank']['bank_account_no']}}"
                                                               class="form-control" required name="phoneB" id="phoneB">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </fieldset>
                                    <h5>Informations Expéditeur</h5>
                                    <fieldset>
                                        <div class="row">
                                            <div class="col-4">
                                                <div class="form-group row">
                                                    <label for="nomE" class="col-4 col-form-label">Nom <i
                                                                class="red">*</i></label>
                                                    <div class="col-8">
                                                        <input type="text"
                                                               value="{{strtoupper($transaction['user']['first_name'])}}"
                                                               class="form-control" required name="nomE" id="nomE">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="form-group row">
                                                    <label for="prenomE" class="col-4 col-form-label">Prénom <i
                                                                class="red">*</i></label>
                                                    <div class="col-8">
                                                        <input type="text"
                                                               value="{{ucwords($transaction['user']['last_name'])}}"
                                                               class="form-control" required name="prenomE"
                                                               id="prenomE">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="form-group row">
                                                    <label for="phoneE" class="col-4 col-form-label">Téléphone <i
                                                                class="red">*</i></label>
                                                    <div class="col-8">
                                                        <input type="text"
                                                               value="{{$transaction['user']['phone_number']}}"
                                                               class="form-control" required name="phoneE" id="phoneE">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4">
                                                <div class="form-group row">
                                                    <label for="numI"
                                                           class="col-4 col-form-label">Numéro {{ $transaction['sender']['type_id'] }}
                                                        <i class="red">*</i></label>
                                                    <div class="col-8">
                                                        <input type="text"
                                                               value="{{ $transaction['sender']['cni_number'] }}"
                                                               class="form-control" required name="numI" id="numI">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="form-group row">
                                                    <label for="dateExp" class="col-4 col-form-label">Date
                                                        d'expiration <i class="red">*</i></label>
                                                    <div class="col-8">
                                                        <input type="text" readonly
                                                               value="{{ $transaction['sender']['date_exp_id'] }}"
                                                               class="form-control" required name="dateExp"
                                                               id="dateExp">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
										Pièces justificatives <br>
										@if( $transaction['sender']['cni_picture'] != null )
											<img src="{!! config('keys.url_img').$transaction['sender']['cni_picture'] !!}"
												 alt=""
												 style="max-width: 300px; max-height: 200px; border-radius:2rem!important">
										@endif
										@if( $transaction['sender']['justif_picture'] != null )
											<img src="{!! config('keys.url_img').$transaction['sender']['justif_picture'] !!}"
												 alt=""
												 style="max-width: 300px; max-height: 200px; border-radius:2rem!important">
										@endif
                                        <input type="hidden" value="{{$clientName}}" id="nom_api">
                                        <input type="hidden" value="{{$client_id}}" id="client_id">
                                        <input type="hidden" name="sender_id" value="{{$transaction['sender']['id']}}">
                                        <input type="hidden" name="transaction_id" value="{{$transaction['id']}}">
                                        <input type="hidden" name="user_id" value="{{$transaction['user']['id']}}">
                                    </fieldset>
                                    <div class="row">
                                        <div class="col-6">
                                            <!-- <p style="color: red" id="error1"></p>
                                            <p style="color: #006EB6" id="success1"></p> -->
                                        </div>
                                        <div class="col-2">
                                            <img id="loading1" src="{{ asset('assets/images/loading.gif') }}" alt=""
                                                 style="max-width: 25px; display: none; max-height: 25px">
                                        </div>
                                        <div class="col-4">
                                            <div class="button-items" dir="ltr">
                                                <button type="submit" id="btnStep1"
                                                        class="btn btn-primary btn-icon btn-rounded waves-effect">
                                                    <span class="btn-icon-label"><i class="mdi mdi-check-all mr-2"></i></span>
                                                    Validation du compte
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