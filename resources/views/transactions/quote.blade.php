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
                                                        {{-- FIX (2026-08-25, incident transaction #151, erreur WACEPAY "The
                                                             reason does not exist or is disabled") : un champ texte libre +
                                                             datalist n'empeche PAS de taper/valider une valeur hors liste --
                                                             la datalist n'est qu'une suggestion, jamais une contrainte HTML.
                                                             Ce champ etait en outre pre-rempli avec transaction_reference
                                                             (valeur Peex, jamais garantie valide cote DigitWace), ce que
                                                             l'agent validait tel quel sans le remarquer. Remplace par un
                                                             <select> natif : seules les 6 valeurs reelles de
                                                             /api/get_digitwace_origin_funds sont selectionnables. --}}
                                                        <select class="form-control" required name="origin" id="origin">
                                                            <option value="">— Sélectionner —</option>
                                                            <option value="Savings" {{ ($transaction['transaction_reference']) === "Savings" ? 'selected' : '' }}>Savings</option>
                                                            <option value="Salary" {{ ($transaction['transaction_reference']) === "Salary" ? 'selected' : '' }}>Salary</option>
                                                            <option value="Lottery" {{ ($transaction['transaction_reference']) === "Lottery" ? 'selected' : '' }}>Lottery</option>
                                                            <option value="Loan" {{ ($transaction['transaction_reference']) === "Loan" ? 'selected' : '' }}>Loan</option>
                                                            <option value="Business Income" {{ ($transaction['transaction_reference']) === "Business Income" ? 'selected' : '' }}>Business Income</option>
                                                            <option value="Others" {{ ($transaction['transaction_reference']) === "Others" ? 'selected' : '' }}>Others</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="form-group row">
                                                    <label for="reason" class="col-4 col-form-label">Raison du
                                                        transfert <i class="red">*</i></label>
                                                    <div class="col-8">
                                                        {{-- FIX (2026-08-25, incident transaction #151, erreur WACEPAY "The
                                                             reason does not exist or is disabled") : meme cause que "origin"
                                                             ci-dessus -- champ texte libre pre-rempli avec transaction_reason
                                                             (valeur Peex libre, ex. "Aide familiale" en francais) que la
                                                             datalist ne bloquait pas. Remplace par un <select> natif : seules
                                                             les 13 valeurs reelles de /api/get_digitwace_reasons sont
                                                             selectionnables. --}}
                                                        <select class="form-control" required name="reason" id="reason">
                                                            <option value="">— Sélectionner —</option>
                                                            <option value="Gift" {{ ($transaction['transaction_reason']) === "Gift" ? 'selected' : '' }}>Gift</option>
                                                            <option value="Salary" {{ ($transaction['transaction_reason']) === "Salary" ? 'selected' : '' }}>Salary</option>
                                                            <option value="Debt Settlement" {{ ($transaction['transaction_reason']) === "Debt Settlement" ? 'selected' : '' }}>Debt Settlement</option>
                                                            <option value="Family Maintainance" {{ ($transaction['transaction_reason']) === "Family Maintainance" ? 'selected' : '' }}>Family Maintainance</option>
                                                            <option value="Business Travel" {{ ($transaction['transaction_reason']) === "Business Travel" ? 'selected' : '' }}>Business Travel</option>
                                                            <option value="Business Profits to Parents" {{ ($transaction['transaction_reason']) === "Business Profits to Parents" ? 'selected' : '' }}>Business Profits to Parents</option>
                                                            <option value="Medical Expenses" {{ ($transaction['transaction_reason']) === "Medical Expenses" ? 'selected' : '' }}>Medical Expenses</option>
                                                            <option value="Education Support" {{ ($transaction['transaction_reason']) === "Education Support" ? 'selected' : '' }}>Education Support</option>
                                                            <option value="Real Estate" {{ ($transaction['transaction_reason']) === "Real Estate" ? 'selected' : '' }}>Real Estate</option>
                                                            <option value="Taxes" {{ ($transaction['transaction_reason']) === "Taxes" ? 'selected' : '' }}>Taxes</option>
                                                            <option value="Tuition Fees" {{ ($transaction['transaction_reason']) === "Tuition Fees" ? 'selected' : '' }}>Tuition Fees</option>
                                                            <option value="Home Improvement" {{ ($transaction['transaction_reason']) === "Home Improvement" ? 'selected' : '' }}>Home Improvement</option>
                                                            <option value="Savings" {{ ($transaction['transaction_reason']) === "Savings" ? 'selected' : '' }}>Savings</option>
                                                        </select>
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