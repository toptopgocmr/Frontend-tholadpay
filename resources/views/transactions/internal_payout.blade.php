@extends('layouts.base')

{{--
    AJOUT (2026-08-08) : écran "payer un retrait interne" (Task #21, voir
    TransactionController::payoutInternal et InternalTransferController côté
    backend). Formulaire en 2 étapes pilotées côté serveur (pas d'AJAX, même
    esprit que le wizard de validation existant) :
      - step = 'lookup'  : simple champ code de retrait.
      - step = 'confirm' : montant/nom affichés (lecture seule) + saisie de la
                            pièce d'identité présentée par le bénéficiaire.
--}}

@section('title')
    Retrait interne
@stop

@section('content')
    <div class="content">
        <div class="container-fluid">
            <div class="page-title-box">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <h4 class="page-title">Retrait interne (réseau TholadPay)</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('admin') }}"><i class="mdi mdi-home-outline"></i></a></li>
                            <li class="breadcrumb-item active">Retrait interne</li>
                        </ol>
                    </div>
                </div>
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
                            @if ($message = Session::get('warning'))
                                <div class="alert alert-warning alert-block">
                                    <button type="button" class="close" data-dismiss="alert">×</button>
                                    <strong>{{ $message }}</strong>
                                </div>
                            @endif

                            @if ($step === 'lookup')
                                <h5>Étape 1 — Code de retrait</h5>
                                <p class="text-muted">Saisissez le code communiqué par le bénéficiaire (format XXXX-XXXX).</p>
                                <form action="{{ route('internal_payout') }}" method="post" class="form-horizontal">
                                    {{ csrf_field() }}
                                    {{-- AJOUT (2026-08-08) : un administrateur/finance_manager/technical_support
                                         n'a pas d'agence propre — il doit choisir laquelle encaisse, sinon
                                         payout_internal_transaction refuse (agent_id manquant). --}}
                                    @if ($needsAgentPicker)
                                        <div class="form-group row">
                                            <label for="payer_agent_id" class="col-2 col-form-label">Agence qui encaisse <i class="red">*</i></label>
                                            <div class="col-4">
                                                <select class="form-control" name="payer_agent_id" id="payer_agent_id" required>
                                                    <option value="">— Sélectionner —</option>
                                                    @foreach ($agentsList as $agt)
                                                        <option value="{{ $agt['id'] }}" {{ (string) $payerAgentId === (string) $agt['id'] ? 'selected' : '' }}>
                                                            {{ ($agt['user']['full_name'] ?? '') . ' [' . ($agt['nom_commercial'] ?? '') . ']' }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    @endif
                                    <div class="form-group row">
                                        <label for="pickup_code" class="col-2 col-form-label">Code de retrait <i class="red">*</i></label>
                                        <div class="col-4">
                                            <input type="text" class="form-control text-uppercase" required
                                                   name="pickup_code" id="pickup_code" placeholder="XXXX-XXXX"
                                                   style="letter-spacing:2px; font-weight:bold;">
                                        </div>
                                    </div>
                                    <div class="button-items" dir="ltr">
                                        <button type="submit" class="btn btn-primary btn-icon btn-rounded waves-effect">
                                            <span class="btn-icon-label"><i class="mdi mdi-magnify mr-2"></i></span>
                                            Rechercher
                                        </button>
                                    </div>
                                </form>
                            @else
                                <h5>Étape 2 — Vérification et paiement</h5>
                                @if ($lookup)
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <table class="table table-sm table-bordered">
                                                <tr><td class="text-muted">Référence</td><td><strong>{{ $lookup['ranking'] ?? '—' }}</strong></td></tr>
                                                <tr><td class="text-muted">Bénéficiaire</td><td><strong>{{ strtoupper($lookup['beneficiary_first_name'] ?? '') }} {{ ucwords($lookup['beneficiary_last_name'] ?? '') }}</strong></td></tr>
                                                <tr><td class="text-muted">Téléphone bénéficiaire</td><td>{{ $lookup['beneficiary_phone'] ?? '—' }}</td></tr>
                                                <tr><td class="text-muted">Pays</td><td>{{ $lookup['receiving_country'] ?? '—' }}</td></tr>
                                                <tr><td class="text-muted">Montant à remettre</td><td><strong style="color:#12709E; font-size:16px;">{{ number_format($lookup['amount_to_pay'] ?? 0, 0, ',', ' ') }} {{ $lookup['currency'] ?? '' }}</strong></td></tr>
                                            </table>
                                        </div>
                                    </div>

                                    <form action="{{ route('internal_payout') }}" method="post" class="form-horizontal">
                                        {{ csrf_field() }}
                                        <input type="hidden" name="confirm" value="1">
                                        <input type="hidden" name="pickup_code" value="{{ $pickupCode }}">
                                        @if ($needsAgentPicker)
                                            <input type="hidden" name="payer_agent_id" value="{{ $payerAgentId }}">
                                        @endif
                                        <div class="form-group row">
                                            <label for="payout_id_type" class="col-2 col-form-label">Type de pièce</label>
                                            <div class="col-4">
                                                <select class="form-control" name="payout_id_type" id="payout_id_type">
                                                    <option value="CNI">Carte d'identité</option>
                                                    <option value="Passeport">Passeport</option>
                                                    <option value="Permis">Permis de conduire</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="payout_id_number" class="col-2 col-form-label">N° de la pièce présentée <i class="red">*</i></label>
                                            <div class="col-4">
                                                <input type="text" class="form-control" required
                                                       name="payout_id_number" id="payout_id_number">
                                            </div>
                                        </div>
                                        <div class="button-items" dir="ltr">
                                            <button type="submit" class="btn btn-success btn-icon btn-rounded waves-effect"
                                                    onclick="return confirm('Confirmer le paiement de ' + '{{ number_format($lookup['amount_to_pay'] ?? 0, 0, ',', ' ') }} {{ $lookup['currency'] ?? '' }}' + ' à ce bénéficiaire ?');">
                                                <span class="btn-icon-label"><i class="mdi mdi-cash-check mr-2"></i></span>
                                                Confirmer le paiement
                                            </button>
                                            <a href="{{ route('internal_payout') }}" class="btn btn-secondary btn-rounded waves-effect">Annuler</a>
                                        </div>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
