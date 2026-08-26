@extends('layouts.base')

@section('title')
    Transactions
@stop

@section('stylesheets')

    <link href="{{ asset('assets/plugins/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css') }}"
          rel="stylesheet">
    <link href="{{ asset('assets/plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css') }}" rel="stylesheet">
    <!-- DataTables -->
    <link href="{{ asset('assets/plugins/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet"
          type="text/css"/>
    <link href="{{ asset('assets/plugins/datatables/buttons.bootstrap4.min.css') }}" rel="stylesheet" type="text/css"/>
    <!-- Responsive datatable examples -->
    <link href="{{ asset('assets/plugins/datatables/responsive.bootstrap4.min.css') }}" rel="stylesheet"
          type="text/css"/>
    <link href="https://code.jquery.com/ui/1.10.4/themes/ui-lightness/jquery-ui.css"
          rel="stylesheet">
    <script src="https://code.jquery.com/jquery-1.10.2.js"></script>
    <script src="https://code.jquery.com/ui/1.10.4/jquery-ui.js"></script>

    <!-- Javascript -->
    <script>
        // FIX (2026-07-22) : "10w+1" n'est pas une syntaxe relative valide pour
        // bootstrap-datepicker (il manque le signe sur "10w" et l'unité sur "+1"),
        // donc le plugin ne pouvait pas la parser et retombait sur la date du jour
        // à CHAQUE chargement de page — y compris juste après une recherche par
        // date, écrasant visuellement la plage choisie par l'agent alors que le
        // filtre côté serveur (TransactionController::search()) fonctionnait déjà
        // correctement. Les champs sont maintenant pré-remplis côté serveur (voir
        // value="{{ $date_start->format('m/d/Y') }}" / "{{ $date->format('m/d/Y') }}"
        // ci-dessous) ; on initialise juste le widget sans forcer de date.
        $(function () {
            $("#datepicker-12").datepicker();
            $("#datepicker-13").datepicker();
        });
    </script>
@stop

@section('content')
    <!-- Start content -->
    <div class="content">
        <div class="container-fluid">
            <div class="page-title-box">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <h4 class="page-title">Transactions</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('admin') }}"><i
                                            class="mdi mdi-home-outline"></i></a></li>
                            <li class="breadcrumb-item active">Transactions</li>
                        </ol>
                    </div>
                    <div class="col-sm-6">
                        <!-- <div class="float-right d-none d-md-block">
                           <button class="btn_round_add btn-primary arrow-none waves-effect waves-light">
                                <i class="mdi mdi-plus mr-2"></i> <a style="color: #fff" href="{{ route('transaction_add') }}">
                                Effectuer une transaction</a>
                           </button>
                        </div> -->
                    </div>
                </div> <!-- end row -->
            </div>
            <!-- end page-title -->

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="mt-0 header-title">Listing</h4>
                            <p class="text-muted mb-4">Liste de toutes les transactions éffectuer via le système Send-Paz
                                par les Agents.</p>
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
                            <form action="{{ route("transaction_search") }}" method="post" class="form-horizontal">
                                {{ csrf_field() }}
                                <div class="row">
                                    <div class="col-4">
                                        <p style="margin: auto;">Transactions éffectuées le : {{ $date->format('d/m/Y') }}</p>
                                    </div>
                                    <div class="col-2">
                                        <div style="margin: auto; margin-right: 20px; " class="input-group">
                                            <input type="text" id="datepicker-13" name="search_start" value="{{ $date_start->format('m/d/Y') }}">
                                        </div>
                                    </div>
                                    <div class="col-2">
                                        <div style="margin: auto;" class="input-group">
                                            <input type="text" id="datepicker-12" name="search_trs" value="{{ $date->format('m/d/Y') }}">
                                        </div><!-- input-group -->
                                    </div>
                                    <div class="col-3">
                                        <div class="input-group-append">
                                            <button type="submit" class="input-group-text">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            {{--                                : {{ $date->format('l jS F Y ') }}</p>--}}
                            {{-- AJOUT (2026-08-25, demande explicite : "sur taxes egalement merci
                                 de les lister et laisser aussi le total figurer") : totaux calculés sur
                                 la liste actuellement affichée (mêmes filtres que le tableau/l'export
                                 Excel ci-dessous), affichés en pied de tableau (voir <tfoot> plus bas). --}}
                            @php
                                $totalMontant = collect($transactions)->sum(function ($t) { return (float) ($t['amount'] ?? 0); });
                                $totalPercu = collect($transactions)->sum(function ($t) { return (float) ($t['montant_beneficiaire'] ?? 0); });
                                $totalFrais = collect($transactions)->sum(function ($t) { return (float) ($t['fees'] ?? 0); });
                                // AJOUT (2026-08-26, correctif : "Frais (XAF)" ci-dessus est NOTRE frais
                                // interne (tarif Send-Paz), pas la commission reellement facturee par le
                                // partenaire -- constat terrain : transaction WPPX133328099158366, dashboard
                                // WACEPAY "Total Fees: 675.00 XAF" contre 6600 XAF affiches ici avant ce
                                // correctif (= notre 'fees'). partner_fee (nouvelle colonne, voir migration
                                // add_partner_fee_to_transactions_table) est desormais capturee separement
                                // depuis la reponse DigitWace/WACEPAY (transaction.feeds|fees) ; reste vide
                                // pour Peex tant que Peex n'expose pas de champ equivalent.
                                $totalPartnerFee = collect($transactions)->sum(function ($t) { return (float) ($t['partner_fee'] ?? 0); });
                                $totalTaxes = collect($transactions)->sum(function ($t) { return (float) ($t['total_taxes'] ?? 0); });
                            @endphp
                            <table id="datatable-buttons"
                                   class="table table-stripeld table-bordered dt-responsive nowrap"
                                   style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                <tr>
                                    <th>Code</th>
                                    <!-- AJOUT (2026-08-25, demande explicite : "l'ID de la transaction
                                         chez le partenaire manque dans l'exportation") : $trans['reference']
                                         est déjà alimenté avec l'identifiant renvoyé par le partenaire
                                         (Peex/DigitWace/PawaPay) une fois l'envoi confirmé — voir
                                         TransactionController::sendtransaction(), $tran2Update['reference']
                                         = $p['track_id'] ?? $p['reference'] ?? ... — mais n'était affiché
                                         nulle part. 'Code' (ci-dessus) reste NOTRE référence interne
                                         ($trans['ranking']), inchangée. -->
                                    <th>ID Transaction Partenaire</th>
                                    <!-- AJOUT (2026-08-26, demande explicite) : quel partenaire (DigitWace/
                                         Peex/PawaPay/interne) a traite la transaction -- $trans['nom_api']
                                         est deja alimente (voir TransactionController::sendtransaction(),
                                         'nom_api' => $partner['client']['name']) mais n'etait affiche nulle
                                         part avant. Rend la colonne "Frais (XAF)" juste apres (la commission
                                         effectivement facturee par ce partenaire) directement lisible. -->
                                    <th>Partenaire</th>
                                    <th>Agent</th>
                                    <th>Emetteur</th>
                                    <th>Beneficiaire</th>
                                    <th>Montant <span style="font-size: 10px">(XAF)</span></th>
                                    <th>M. percu</th>
                                    <th>Frais <span style="font-size: 10px">(XAF)</span></th>
                                    <!-- AJOUT (2026-08-26, correctif : voir commentaire sur $totalPartnerFee
                                         ci-dessus -- "Frais" (juste avant) reste notre frais interne, cette
                                         colonne est la VRAIE commission facturee par DigitWace/WACEPAY (lue
                                         via transaction.feeds a la creation ou transaction.fees via
                                         check_transaction_status, voir OutboundController::
                                         normalizeDigitwaceTransactionResponse). Vide ("—") pour Peex, qui
                                         n'expose actuellement aucun champ equivalent. -->
                                    <th>Commission Partenaire <span style="font-size: 10px">(XAF)</span></th>
                                    <!-- AJOUT (2026-08-26, demande explicite) : detail des 4 taxes deja
                                         calculees automatiquement par TaxCalculationService/Transaction.ttf,
                                         commission_cobac, timbre_electronique, tva (voir migration
                                         add_taxes_to_transactions_table) -- jusqu'ici seule leur somme
                                         (total_taxes, colonne "Taxes (XAF)" juste apres) etait exportee. -->
                                    <th>TTF <span style="font-size: 10px">(XAF)</span></th>
                                    <th>Commission BEAC <span style="font-size: 10px">(XAF)</span></th>
                                    <th>Timbre Electronique <span style="font-size: 10px">(XAF)</span></th>
                                    <th>TVA <span style="font-size: 10px">(XAF)</span></th>
                                    <th>Taxes <span style="font-size: 10px">(XAF)</span></th>
                                    <th>Pays</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>

                                <tbody>
                                @forelse ($transactions as $trans)
                                    <tr>
                                        <td>{{$trans['ranking']}}</td>
                                        <td>{{ $trans['reference'] ?? '—' }}</td>
                                        <td>{{ $trans['nom_api'] ?? '—' }}</td>
                                        <td>
                                            {{(isset($trans['valid']) && $trans['valid'] !== null) ? $trans['valid']['full_name'] : $trans['agent']['nom_commercial']}}
                                        </td>
                                        <td>{{ strtoupper($trans['user']['first_name']) }} {{ ucwords($trans['user']['last_name']) }}</td>
                                        <td>{{ strtoupper($trans['recipient_first_name']) }} {{ ucwords($trans['recipient_last_name']) }}</td>
                                        <td>{{$trans['amount']}}</td>
                                        <td>{{$trans['montant_beneficiaire']}}  <span style="font-size: 10px">({{$trans['to_currency']}})</span></td>
                                        <td>{{$trans['fees']}}</td>
                                        <td>{{ isset($trans['partner_fee']) && $trans['partner_fee'] !== null ? $trans['partner_fee'] : '—' }}</td>
                                        <td>{{$trans['ttf'] ?? 0}}</td>
                                        <td>{{$trans['commission_cobac'] ?? 0}}</td>
                                        <td>{{$trans['timbre_electronique'] ?? 0}}</td>
                                        <td>{{$trans['tva'] ?? 0}}</td>
                                        <td>{{$trans['total_taxes'] ?? 0}}</td>
                                        <td>{{strtoupper($trans['receiving_country'])}}</td>
                                        {{-- FIX (2026-08-08) : ce ternaire (outbound.bank null => 'Mobile') datait
                                             d'avant l'ajout de Cash Pickup (DigitWace) et des transferts internes —
                                             il étiquetait donc à tort tout Cash Pickup ou transfert interne comme
                                             'Mobile'. Voir aussi home.blade.php, customers/*, users/show.blade.php,
                                             transactions/show.blade.php, transactions/transaction.blade.php (même bug
                                             corrigé partout). --}}
                                        @php
                                            $ob = $trans['outbound'] ?? null;
                                            if (($trans['corridor_id'] ?? null) == 3) {
                                                $typeLabel = 'Interne';
                                            } elseif (!empty($ob['bank'])) {
                                                $typeLabel = 'Bancaire';
                                            } elseif (!empty($ob['cash'])) {
                                                $typeLabel = 'Cash Pickup';
                                            } elseif (!empty($ob['mobile'])) {
                                                $typeLabel = 'Mobile';
                                            } else {
                                                $typeLabel = '—';
                                            }
                                        @endphp
                                        <td>{{ $typeLabel }}</td>
                                        <td>{{ $trans['created_at'] !== null ? @formaterdateTime($trans['created_at']) : '' }}</td>
                                        
                                        <td>
                                            {{($trans['etat_transac'] == 'acknowledged') ? 'Pending' : $trans['etat_transac']}}
                                        </td>
                                        
                                        <td>
                                            <div class="btn-group mt-4 mt-md-0 button-items"
                                                 dir="ltr" role="group"
                                                 aria-label="Basic example">
                                                <a href="{{route('transaction_show', $trans['id'])}}" title="Détail de la transaction"
                                                   class="btn btn-info btn-rounded waves-effect"><i
                                                            class="mdi mdi-information-variant"></i></a>
                                                @if($trans['transaction_status'] === 'waiting')
                                                    <a href="{{route('transaction_valid', $trans['id'])}}" title="Valider le paiement (envoi à Peex)"
                                                       class="btn btn-warning btn-rounded waves-effect"><i
                                                                class="mdi mdi-circle-edit-outline"></i></a>
                                                @endif
                                                @if(isset($trans['isnote']) &&$trans['isnote'] === '1')
                                                    <a href="{{route('transaction_notes', $trans['id'])}}" title="Lister les notes"
                                                    class="btn btn-primary btn-rounded waves-effect"><i
                                                                class="fas fa-copy"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="20">Aucun enregistrement trouvé</td>
                                    </tr>
                                @endforelse
                                </tbody>
                                <!-- FIX (2026-08-26, demande explicite : "retirer les total, total") :
                                     le bouton Excel (voir datatables.init.js) exportait ce <tfoot> en
                                     aplatissant le colspan="5" -- "TOTAL" se retrouvait repete tel quel
                                     dans CHAQUE colonne texte (Code/ID Transaction Partenaire/Partenaire/
                                     Agent/Emetteur/Beneficiaire), illisible dans le fichier telecharge.
                                     footer:false desormais cote export (voir datatables.init.js) ; ce
                                     <tfoot> reste affiche a l'ecran (utile pour parcourir la liste), mis
                                     a jour pour les 6 nouvelles colonnes (colspan total = 20, dont la colonne Commission Partenaire ajoutee separement le meme jour). -->
                                <tfoot>
                                <tr>
                                    <th colspan="6" style="text-align: right;">TOTAL</th>
                                    <th>{{ number_format($totalMontant, 2, ',', ' ') }}</th>
                                    <th>{{ number_format($totalPercu, 2, ',', ' ') }}</th>
                                    <th>{{ number_format($totalFrais, 2, ',', ' ') }}</th>
                                    <th>{{ number_format($totalPartnerFee, 2, ',', ' ') }}</th>
                                    <th colspan="4"></th>
                                    <th>{{ number_format($totalTaxes, 2, ',', ' ') }}</th>
                                    <th colspan="5"></th>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div> <!-- end col -->
            </div> <!-- end row -->

        </div>
        <!-- container-fluid -->

    </div>
    <!-- content -->
@stop

@section('javascripts')
    <!-- Required datatable js -->
    <script src="{{ asset('assets/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables/dataTables.bootstrap4.min.js') }}"></script>
    <!-- Buttons examples -->
    <script src="{{ asset('assets/plugins/datatables/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables/buttons.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables/jszip.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables/pdfmake.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables/vfs_fonts.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables/buttons.print.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables/buttons.colVis.min.js') }}"></script>

    <script src="{{ asset('assets/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js') }}"></script>
    <script src="{{ asset('assets/plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js') }}"></script>
    <!-- Responsive examples -->
    <script src="{{ asset('assets/plugins/datatables/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/bootstrap-datepicker/bootstrap-datepicker.js') }}"></script>

    <!-- Datatable init js -->
    <script src="{{ asset('assets/pages/datatables.init.js') }}"></script>
@stop

<?php
 function formaterdateTime($dateS){
    $heureS = @explode(' ', $dateS);
    $tab = @explode('-', $heureS[0]);
    $arr = @explode(':', $heureS[1]);
    return $newTime = $tab[2].'/'.$tab[1].'/'.$tab[0]. ' ' . $arr[0].'h'.$arr[1];
 }
?>