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
                                                            {{-- FIX (2026-08-27) : valeurs live /api/get_digitwace_origin_funds
                                                                 ($dwOriginFunds, voir TransactionController::getquotation) au lieu
                                                                 de l'instantane fige du 2026-08-25 -- repli sur cet instantane
                                                                 uniquement si l'appel live a echoue (liste vide). --}}
                                                            @if(!empty($dwOriginFunds))
                                                                @foreach(array_keys($dwOriginFunds) as $o)
                                                                    <option value="{{ $o }}" {{ ($transaction['transaction_reference']) === $o ? 'selected' : '' }}>{{ $o }}</option>
                                                                @endforeach
                                                            @else
                                                                <option value="Savings" {{ ($transaction['transaction_reference']) === "Savings" ? 'selected' : '' }}>Savings</option>
                                                                <option value="Salary" {{ ($transaction['transaction_reference']) === "Salary" ? 'selected' : '' }}>Salary</option>
                                                                <option value="Lottery" {{ ($transaction['transaction_reference']) === "Lottery" ? 'selected' : '' }}>Lottery</option>
                                                                <option value="Loan" {{ ($transaction['transaction_reference']) === "Loan" ? 'selected' : '' }}>Loan</option>
                                                                <option value="Business Income" {{ ($transaction['transaction_reference']) === "Business Income" ? 'selected' : '' }}>Business Income</option>
                                                                <option value="Others" {{ ($transaction['transaction_reference']) === "Others" ? 'selected' : '' }}>Others</option>
                                                            @endif
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
                                                            {{-- FIX (2026-08-27) : valeurs live /api/get_digitwace_reasons
                                                                 ($dwReasons, voir TransactionController::getquotation) au lieu
                                                                 de l'instantane fige du 2026-08-25 -- repli sur cet instantane
                                                                 uniquement si l'appel live a echoue (liste vide). --}}
                                                            @if(!empty($dwReasons))
                                                                @foreach(array_keys($dwReasons) as $r)
                                                                    <option value="{{ $r }}" {{ ($transaction['transaction_reason']) === $r ? 'selected' : '' }}>{{ $r }}</option>
                                                                @endforeach
                                                            @else
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
                                                            @endif
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        {{-- AJOUT (2026-08-25, incident transactions #150/#144, erreur WACEPAY
                                             "Plusieurs services DigitWace de type 'wallet' sont disponibles pour
                                             CI/XOF, precisez 'digitwace_service'") : la Cote d'Ivoire a 5
                                             operateurs Mobile Money actifs (MOMO/MTN/MV/OM/WV, voir
                                             OutboundController::resolveDigitwacePayerCode) -- quand la deduction
                                             automatique depuis le numero du beneficiaire ne suffit pas a choisir
                                             un operateur unique, l'envoi etait bloque sans aucun moyen pour
                                             l'agent de preciser lui-meme. Champ affiche uniquement pour un envoi
                                             Mobile via DigitWace ; laisser sur "Automatique" quand un seul
                                             operateur est reellement disponible (cas le plus frequent hors CI). --}}
                                        @if ($partnerChoice === 'digitwace' && !empty($ob['mobile']))
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-group row">
                                                    <label for="digitwace_service" class="col-4 col-form-label">Opérateur Mobile Money
                                                        (si erreur "plusieurs services")</label>
                                                    <div class="col-8">
                                                        {{-- NOTE : pas de pre-remplissage "selected" ici -- $dwExtra
                                                             (session dw_extra_{id}) n'est pas transmis a cette vue par le
                                                             controleur (voir TransactionController::quote(), compact() sans
                                                             'dwExtra'), contrairement a receiver_relation/transaction_reference/
                                                             transaction_reason qui viennent directement de $transaction (BDD).
                                                             Laisser sur "Automatique" reste le comportement correct la
                                                             plupart du temps (un seul operateur disponible hors CI). --}}
                                                        <select class="form-control" name="digitwace_service" id="digitwace_service">
                                                            <option value="">— Automatique —</option>
                                                            <option value="MOMO">MOMO (MTN MONEY)</option>
                                                            <option value="MTN">MTN (MTN MOBILE MONEY)</option>
                                                            <option value="MV">MV (MOOV)</option>
                                                            <option value="OM">OM (ORANGE MONEY)</option>
                                                            <option value="WV">WV (WAVE)</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                        {{-- AJOUT (2026-08-26, incident transaction #161, erreur DigitWace
                                             "bank_id est requis pour DigitWace (banques trouvees : ...)") : un
                                             virement BANCAIRE via DigitWace exige un bank_id precis de la
                                             liste DigitWace du pays (doc getBankList) -- le nom de banque saisi
                                             en texte libre par le client a la creation (mobile) ne correspond
                                             pas toujours exactement (constate sur un virement vers la France,
                                             12 banques disponibles chez DigitWace). Jusqu'ici l'agent decouvrait
                                             la liste valide seulement dans le message d'erreur final, sans
                                             aucun moyen de la renseigner et reessayer. $bankList est chargee
                                             cote controleur (TransactionController::getquotation(), voir
                                             commentaire detaille la-bas) via get_digitwace_bank_list. --}}
                                        @if ($partnerChoice === 'digitwace' && !empty($ob['bank']))
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-group row">
                                                    <label for="bank_id" class="col-4 col-form-label">Banque DigitWace
                                                        (si erreur "bank_id requis")</label>
                                                    <div class="col-8">
                                                        @if (!empty($bankList))
                                                        <select class="form-control" name="bank_id" id="bank_id">
                                                            <option value="">— Sélectionner —</option>
                                                            @foreach ($bankList as $bank)
                                                                <option value="{{ $bank['BankID'] ?? '' }}">{{ $bank['BankName'] ?? ('Banque #' . ($bank['BankID'] ?? '?')) }}</option>
                                                            @endforeach
                                                        </select>
                                                        @else
                                                        <input type="text" class="form-control" name="bank_id" id="bank_id" placeholder="ID banque DigitWace (voir message d'erreur ci-dessus si déjà affiché)">
                                                        <small class="form-text text-muted">La liste des banques n'a pas pu être chargée automatiquement — laissez vide pour laisser DigitWace deviner, ou saisissez l'ID exact indiqué dans un message d'erreur précédent.</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
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