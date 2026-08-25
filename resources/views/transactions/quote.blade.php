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
                            <form action="{{ route("transaction_quote", $transaction['id']) }}" method="post" class="form-horizontal">
                                {{ csrf_field() }}
                                <div id="step2" style="display: block">
                                    <h3>Quotation de la transaction (2/3) — Partenaire : {{ $partner['client']['name'] ?? 'Peex' }}</h3>
                                    <h5>Informations Transaction</h5>
                                    <fieldset>
                                        <div class="row">
                                            {{-- FIX (2026-08-08) : voir transactions/index.blade.php — l'ancien
                                                 ternaire plantait (clé 'mobile' absente) ou affichait le mauvais
                                                 libellé pour un Cash Pickup. --}}
                                            @php
                                                $ob = $transaction['outbound'] ?? null;
                                                if (!empty($ob['bank'])) {
                                                    $numLabel = 'Numéro Bancaire';
                                                    $numValue = $ob['bank']['bank_account_no'] ?? '';
                                                } elseif (!empty($ob['cash'])) {
                                                    $numLabel = 'Ville de retrait';
                                                    $numValue = $ob['cash']['receiver_city'] ?? '';
                                                } else {
                                                    $numLabel = 'Numéro Téléphone';
                                                    $numValue = $ob['mobile']['mobile_phone_credit'] ?? '';
                                                }
                                            @endphp
                                            <div class="col-4">
                                                <div class="form-group row">
                                                    <label for="numTrs"
                                                           class="col-4 col-form-label">{{ $numLabel }}
                                                        <i class="red">*</i></label>
                                                    <div class="col-8">
                                                        <input type="text"
                                                               value="{{ $numValue }}"
                                                               class="form-control" required name="numTrs" id="numTrs">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="form-group row">
                                                    <label for="amount" class="col-4 col-form-label">Montant (XAF) <i
                                                                class="red">*</i></label>
                                                    <div class="col-8">
                                                        <input type="number" value="{{$transaction['amount']}}"
                                                               class="form-control" required name="amount" id="amount">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4">
                                                <div class="form-group row">
                                                    <label for="origin" class="col-4 col-form-label">Origine des
                                                        fonds <i class="red">*</i></label>
                                                    <div class="col-8">
                                                        <input type="text"
                                                               value="{{$transaction['transaction_reference']}}"
                                                               class="form-control" required name="origin" id="origin"
                                                               list="dwOriginFunds">
                                                        {{-- Si DigitWace est le partenaire choisi (étape 1), ce champ
                                                             est réutilisé comme "originFund" — DigitWace impose une
                                                             valeur parmi une liste fermée (doc §XVII) ; suggestions
                                                             ci-dessous à titre indicatif. --}}
                                                        {{-- FIX (2026-08-25, meme incident que "relation" ci-dessus) : cette
                                                             liste contenait "Business Profit" et "Settlement", deux valeurs
                                                             absentes de la liste WACEPAY reelle (/api/get_digitwace_origin_funds),
                                                             ce qui aurait provoque la meme erreur "does not exist or is disabled"
                                                             si un agent les avait choisies. Liste complete (6 valeurs) recopiee
                                                             depuis la reponse live de cet endpoint. --}}
                                                        <datalist id="dwOriginFunds">
                                                            <option value="Savings"><option value="Salary"><option value="Lottery"><option value="Loan"><option value="Business Income"><option value="Others">
                                                        </datalist>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="form-group row">
                                                    <label for="reason" class="col-4 col-form-label">Raison du
                                                        transfert <i class="red">*</i></label>
                                                    <div class="col-8">
                                                        <input type="text"
                                                               value="{{$transaction['transaction_reason']}}"
                                                               class="form-control" required name="reason" id="reason"
                                                               list="dwReasons">
                                                        {{-- Idem pour "reason" (doc §XVIII) si DigitWace est choisi. --}}
                                                        {{-- FIX (2026-08-25, meme incident que "relation" ci-dessus) : liste
                                                             quasi entierement inventee -- seules "Family Maintainance" et
                                                             "Business Travel" existaient reellement cote WACEPAY. Les 6 autres
                                                             valeurs ("Charitable Donation", "Employee Compensation", etc.)
                                                             auraient provoque la meme erreur "does not exist or is disabled".
                                                             Liste complete (13 valeurs) recopiee depuis /api/get_digitwace_reasons. --}}
                                                        <datalist id="dwReasons">
                                                            <option value="Gift"><option value="Salary"><option value="Debt Settlement">
                                                            <option value="Family Maintainance"><option value="Business Travel"><option value="Business Profits to Parents">
                                                            <option value="Medical Expenses"><option value="Education Support"><option value="Real Estate">
                                                            <option value="Taxes"><option value="Tuition Fees"><option value="Home Improvement">
                                                            <option value="Savings">
                                                        </datalist>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </fieldset>
                                    <div class="row">
                                        <div class="col-6">
                                            <p style="color: red" id="error2"></p>
                                        </div>
                                        <div class="col-2">
                                            <img id="loading2" src="{{ asset('assets/images/loading.gif') }}" alt=""
                                                 style="max-width: 25px; display: none; max-height: 25px">
                                        </div>
                                        <div class="col-4">
                                            <div class="button-items" dir="ltr">
                                                <button type="submit" id="btnStep2"
                                                        class="btn btn-primary btn-icon btn-rounded waves-effect">
                                                    <span class="btn-icon-label"><i class="mdi mdi-check-all mr-2"></i></span>
                                                    Quotation de la transaction
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