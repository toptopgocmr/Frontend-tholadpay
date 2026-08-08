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
                                {{-- AJOUT (2026-08-08) : liste de TOUS les transferts internes (tous
                                     statuts), avec filtres — demande utilisateur : "l'admin doit voir
                                     toutes les transactions en liste, et mettre des filtres pour les
                                     rechercher, code, date, nom de l'agence etc." L'agent clique
                                     directement une ligne "En attente" pour lancer la vérification, sans
                                     ressaisir le code. --}}
                                <h5>Transactions internes</h5>
                                @if ($needsAgentPicker)
                                    <div class="form-group row">
                                        <label for="payer_agent_selector" class="col-2 col-form-label">Agence qui encaisse <i class="red">*</i></label>
                                        <div class="col-4">
                                            <select class="form-control" id="payer_agent_selector">
                                                <option value="">— Sélectionner avant de vérifier une ligne —</option>
                                                @foreach ($agentsList as $agt)
                                                    <option value="{{ $agt['id'] }}" {{ (string) $payerAgentId === (string) $agt['id'] ? 'selected' : '' }}>
                                                        {{ ($agt['user']['full_name'] ?? '') . ' [' . ($agt['nom_commercial'] ?? '') . ']' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                @endif

                                <form action="{{ route('internal_payout') }}" method="get" class="form-horizontal mb-3">
                                    <div class="form-row align-items-end">
                                        <div class="form-group col-md-2">
                                            <label for="f_code">Code de retrait</label>
                                            <input type="text" class="form-control text-uppercase" name="f_code" id="f_code" value="{{ $filterCode }}" placeholder="XXXX-XXXX">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label for="f_date_from">Date d'envoi de</label>
                                            <input type="date" class="form-control" name="f_date_from" id="f_date_from" value="{{ $filterDateFrom }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label for="f_date_to">à</label>
                                            <input type="date" class="form-control" name="f_date_to" id="f_date_to" value="{{ $filterDateTo }}">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label for="f_agency">Agence expéditrice</label>
                                            <input type="text" class="form-control" name="f_agency" id="f_agency" value="{{ $filterAgency }}" placeholder="Nom de l'agence">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label for="f_status">Statut</label>
                                            <select class="form-control" name="f_status" id="f_status">
                                                <option value="">— Tous —</option>
                                                <option value="AwaitingPickup" {{ $filterStatus === 'AwaitingPickup' ? 'selected' : '' }}>En attente</option>
                                                <option value="success" {{ $filterStatus === 'success' ? 'selected' : '' }}>Payé</option>
                                                <option value="Rejected" {{ $filterStatus === 'Rejected' ? 'selected' : '' }}>Rejeté</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-2">
                                            <button type="submit" class="btn btn-primary btn-block">
                                                <i class="mdi mdi-filter-outline mr-1"></i> Filtrer
                                            </button>
                                        </div>
                                    </div>
                                    <div>
                                        @if ($filterCode || $filterDateFrom || $filterDateTo || $filterAgency || $filterStatus)
                                            <a href="{{ route('internal_payout') }}" class="small">Réinitialiser les filtres</a> &middot;
                                        @endif
                                        {{-- AJOUT (2026-08-08) : export CSV (ouvrable dans Excel) de la liste
                                             filtrée — voir TransactionController::payoutInternalExport. --}}
                                        <a href="{{ route('internal_payout_export', request()->query()) }}" class="small">
                                            <i class="mdi mdi-file-excel-outline mr-1"></i>Exporter en Excel (CSV)
                                        </a>
                                    </div>
                                </form>

                                @if (count($pendingList) === 0)
                                    <p class="text-muted">Aucune transaction interne ne correspond à ces critères.</p>
                                @else
                                    <div class="table-responsive mb-2">
                                        <table class="table table-sm table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Statut</th>
                                                    <th>Référence</th>
                                                    <th>Code</th>
                                                    <th>Bénéficiaire</th>
                                                    <th>Montant</th>
                                                    <th>Pays</th>
                                                    <th>Expéditeur</th>
                                                    <th>Envoyé le</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($pendingList as $tx)
                                                    @php
                                                        $etat = $tx['etat_transac'] ?? '';
                                                        $statusLabel = 'Statut : ' . ($etat ?: '—');
                                                        $statusClass = 'badge-secondary';
                                                        if ($etat === 'AwaitingPickup') { $statusLabel = !empty($tx['beneficiary_checked_in_at']) ? 'En attente (consulté)' : 'En attente'; $statusClass = 'badge-info'; }
                                                        elseif ($etat === 'success') { $statusLabel = 'Payé'; $statusClass = 'badge-success'; }
                                                        elseif ($etat === 'Rejected') { $statusLabel = 'Rejeté'; $statusClass = 'badge-danger'; }
                                                    @endphp
                                                    <tr>
                                                        <td><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                                                        <td><strong>{{ $tx['ranking'] ?? '—' }}</strong></td>
                                                        <td><code>{{ $tx['internal_pickup_code'] ?? '—' }}</code></td>
                                                        <td>{{ strtoupper($tx['recipient_first_name'] ?? '') }} {{ ucwords($tx['recipient_last_name'] ?? '') }}</td>
                                                        <td>{{ number_format($tx['montant_beneficiaire'] ?? 0, 0, ',', ' ') }} {{ $tx['to_currency'] ?? '' }}</td>
                                                        <td>{{ $tx['receiving_country'] ?? '—' }}</td>
                                                        <td>{{ $tx['user']['first_name'] ?? '' }} {{ $tx['user']['last_name'] ?? '' }} <span class="text-muted">({{ $tx['agent']['nom_commercial'] ?? '—' }}, {{ $tx['agent']['country']['name'] ?? '—' }})</span></td>
                                                        <td>{{ !empty($tx['created_at']) ? \Carbon\Carbon::parse($tx['created_at'])->format('d/m/Y H:i') : '—' }}</td>
                                                        <td>
                                                            @if ($etat === 'AwaitingPickup')
                                                                <form action="{{ route('internal_payout') }}" method="post" class="d-inline quick-verify-form">
                                                                    {{ csrf_field() }}
                                                                    <input type="hidden" name="pickup_code" value="{{ $tx['internal_pickup_code'] ?? '' }}">
                                                                    @if ($needsAgentPicker)
                                                                        <input type="hidden" name="payer_agent_id" class="payer_agent_hidden" value="{{ $payerAgentId }}">
                                                                    @endif
                                                                    <button type="submit" class="btn btn-sm btn-primary waves-effect">
                                                                        <i class="mdi mdi-magnify mr-1"></i> Vérifier
                                                                    </button>
                                                                </form>
                                                            @endif
                                                            {{-- AJOUT (2026-08-08) : voir/imprimer le reçu — demande utilisateur
                                                                 du 2026-08-08. TransactionController::show()/receipt() ont dû
                                                                 être ajustés pour autoriser tout agent sur un transfert interne
                                                                 (par construction, l'agence qui paie n'est jamais celle qui a
                                                                 envoyé, voir la règle anti-fraude ajoutée le même jour). --}}
                                                            <a href="{{ route('transaction_show', $tx['id']) }}" title="Détails" class="btn btn-sm btn-info waves-effect">
                                                                <i class="mdi mdi-information-variant"></i>
                                                            </a>
                                                            <a href="{{ route('transaction_receipt', $tx['id']) }}" title="Reçu" target="_blank" class="btn btn-sm btn-secondary waves-effect">
                                                                <i class="mdi mdi-printer"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @if ($listMeta['last_page'] > 1)
                                        <nav class="mb-4">
                                            <ul class="pagination pagination-sm">
                                                @for ($p = 1; $p <= $listMeta['last_page']; $p++)
                                                    <li class="page-item {{ $p == $listMeta['current_page'] ? 'active' : '' }}">
                                                        <a class="page-link" href="{{ route('internal_payout', array_merge(request()->query(), ['list_page' => $p])) }}">{{ $p }}</a>
                                                    </li>
                                                @endfor
                                            </ul>
                                        </nav>
                                    @endif
                                @endif

                                <h5>Étape 1 — Code de retrait</h5>
                                <p class="text-muted">Ou saisissez directement le code communiqué par le bénéficiaire (format XXXX-XXXX).</p>
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
                                            <h6 class="text-muted text-uppercase">Bénéficiaire</h6>
                                            <table class="table table-sm table-bordered">
                                                <tr><td class="text-muted">Référence</td><td><strong>{{ $lookup['ranking'] ?? '—' }}</strong></td></tr>
                                                <tr><td class="text-muted">Bénéficiaire</td><td><strong>{{ strtoupper($lookup['beneficiary_first_name'] ?? '') }} {{ ucwords($lookup['beneficiary_last_name'] ?? '') }}</strong></td></tr>
                                                <tr><td class="text-muted">Téléphone bénéficiaire</td><td>{{ $lookup['beneficiary_phone'] ?? '—' }}</td></tr>
                                                <tr><td class="text-muted">Pays</td><td>{{ $lookup['receiving_country'] ?? '—' }}</td></tr>
                                                <tr><td class="text-muted">Montant à remettre</td><td><strong style="color:#12709E; font-size:16px;">{{ number_format($lookup['amount_to_pay'] ?? 0, 0, ',', ' ') }} {{ $lookup['currency'] ?? '' }}</strong></td></tr>
                                            </table>
                                        </div>
                                        {{-- AJOUT (2026-08-08) : informations expéditeur / provenance (demande
                                             utilisateur du 2026-08-08) — voir InternalTransferController::
                                             lookup_internal_transaction. --}}
                                        <div class="col-6">
                                            <h6 class="text-muted text-uppercase">Expéditeur</h6>
                                            <table class="table table-sm table-bordered">
                                                <tr><td class="text-muted">Expéditeur</td><td><strong>{{ strtoupper($lookup['sender_first_name'] ?? '') }} {{ ucwords($lookup['sender_last_name'] ?? '') }}</strong></td></tr>
                                                <tr><td class="text-muted">Téléphone expéditeur</td><td>{{ $lookup['sender_phone'] ?? '—' }}</td></tr>
                                                <tr><td class="text-muted">Pays de provenance</td><td>{{ $lookup['sending_country'] ?? '—' }}</td></tr>
                                                <tr><td class="text-muted">Agence expéditrice</td><td>{{ $lookup['sending_agency'] ?? '—' }}</td></tr>
                                                <tr><td class="text-muted">Montant envoyé</td><td>{{ number_format($lookup['amount_sent'] ?? 0, 0, ',', ' ') }} {{ $lookup['sent_currency'] ?? '' }}</td></tr>
                                                <tr><td class="text-muted">Motif</td><td>{{ $lookup['transaction_reason'] ?? '—' }}</td></tr>
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

                                    {{-- AJOUT (2026-08-08) : rejet du retrait (bénéficiaire non conforme, pièce
                                         d'identité invalide, ou autre motif) — demande utilisateur du 2026-08-08.
                                         Voir InternalTransferController::reject_internal_transaction. Aucun montant
                                         n'est déplacé automatiquement : le rejet marque juste la transaction. --}}
                                    <hr>
                                    <h6 class="text-muted">Ou rejeter ce retrait</h6>
                                    <form action="{{ route('internal_payout') }}" method="post" class="form-horizontal">
                                        {{ csrf_field() }}
                                        <input type="hidden" name="reject" value="1">
                                        <input type="hidden" name="pickup_code" value="{{ $pickupCode }}">
                                        @if ($needsAgentPicker)
                                            <input type="hidden" name="payer_agent_id" value="{{ $payerAgentId }}">
                                        @endif
                                        <div class="form-group row">
                                            <label for="rejection_reason" class="col-2 col-form-label">Motif du rejet <i class="red">*</i></label>
                                            <div class="col-4">
                                                <select class="form-control" name="rejection_reason" id="rejection_reason" required>
                                                    <option value="">— Sélectionner —</option>
                                                    <option value="name_mismatch">Nom du bénéficiaire non conforme</option>
                                                    <option value="invalid_id">Pièce d'identité invalide ou non conforme</option>
                                                    <option value="other">Autre motif</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="rejection_note" class="col-2 col-form-label">Précisions</label>
                                            <div class="col-4">
                                                <textarea class="form-control" name="rejection_note" id="rejection_note" rows="2" placeholder="Facultatif, sauf si 'Autre motif'"></textarea>
                                            </div>
                                        </div>
                                        <div class="button-items" dir="ltr">
                                            <button type="submit" class="btn btn-danger btn-icon btn-rounded waves-effect"
                                                    onclick="return confirm('Confirmer le rejet de ce retrait ?');">
                                                <span class="btn-icon-label"><i class="mdi mdi-close-circle mr-2"></i></span>
                                                Rejeter le retrait
                                            </button>
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

@if ($step === 'lookup' && $needsAgentPicker)
    @section('javascripts')
        <script>
            // AJOUT (2026-08-08) : les boutons "Vérifier" de la liste des transactions
            // entrantes sont des mini-formulaires séparés du sélecteur d'agence — on
            // synchronise leur champ caché payer_agent_id sur la sélection courante,
            // et on bloque l'envoi tant qu'aucune agence n'est choisie (nécessaire pour
            // administrateur/finance_manager/technical_support, qui n'ont pas d'agence
            // propre en session).
            (function () {
                var selector = document.getElementById('payer_agent_selector');
                if (!selector) return;
                function sync() {
                    var val = selector.value;
                    document.querySelectorAll('.payer_agent_hidden').forEach(function (el) {
                        el.value = val;
                    });
                }
                selector.addEventListener('change', sync);
                sync();
                document.querySelectorAll('.quick-verify-form').forEach(function (form) {
                    form.addEventListener('submit', function (e) {
                        if (!selector.value) {
                            e.preventDefault();
                            alert('Veuillez d\'abord sélectionner l\'agence qui encaisse.');
                        }
                    });
                });
            })();
        </script>
    @stop
@endif
