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
                            <div class="row">
                                <div class="col-6">
                                    <h4 class="mt-0 header-title">Informations sur la transaction</h4>
                                    <table id="datatable-buttons"
                                           class="table table-stripeld table-bordered dt-responsive nowrap"
                                           style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                        <tr>
                                            <th>Numéro</th>
                                            <th>{{$transaction['ranking']}}</th>
                                        </tr>
                                        <tr>
                                            <th>Type</th>
                                            <th>{!! $transaction['outbound']['bank'] === null ? 'Mobile' : 'Bancaire' !!}</th>
                                        </tr>
                                        <tr>
                                            <th>{!! $transaction['outbound']['bank'] === null ? 'Numéro Téléphone' : 'Numéro Bancaire' !!}</th>
                                            <th>{{$transaction['outbound']['bank'] === null ? $transaction['outbound']['mobile']['mobile_phone_credit'] : $transaction['outbound']['bank']['bank_account_no']}}</th>
                                        </tr>
                                        <tr>
                                            <th>Montant</th>
                                            <th>{{$transaction['amount']}} {{$transaction['from_currency']}}</th>
                                        </tr>
                                        <tr>
                                            <th>Frais d'envoi</th>
                                            <th>{{$transaction['frais_envoi']}} {{$transaction['from_currency']}}</th>
                                        </tr>
                                        <tr>
                                            <th>Montant recu le bénéficiaire</th>
                                            <th>{{$transaction['montant_beneficiaire']}} {{$transaction['to_currency']}}</th>
                                        </tr>
                                        <tr>
                                            <th>Pays</th>
                                            <th>{{strtoupper($transaction['receiving_country'])}}
                                                | {{$transaction['to_currency']}}</th>
                                        </tr>
                                        <tr>
                                            <th>Origine des fonds</th>
                                            <th>{{$transaction['transaction_reference']}}</th>
                                        </tr>
                                        <tr>
                                            <th>Raison du transfert</th>
                                            <th>{{$transaction['transaction_reason']}}</th>
                                        </tr>
                                        <tr>
                                            <th>Status de la transaction</th>
                                            <th style="color: #ffc107;">{{$transaction['transaction_status']}}</th>
                                        </tr>
                                        <tr>
                                            <th>Montant total de la transaction</th>
                                            <th>{{$transaction['amount'] + $transaction['frais_envoi']}} {{$transaction['from_currency']}}</th>
                                        </tr>
                                        <tr>
                                            <th>Description</th>
                                            <td>{{$transaction['description']}}</td>
                                        </tr>
                                        <tr>
                                            <th>Effectué le</th>
                                            <td>{{ $transaction['created_at'] !== null ? @formaterdateTime($transaction['created_at']) : '' }}</td>
                                        </tr>
                                        </thead>
                                    </table>
                                    @if($transaction['etat_transac'] !== 'New')
                                        <h4 class="mt-0 header-title">Informations Paiement</h4>
                                        <table id="datatable-buttons"
                                            class="table table-stripeld table-bordered dt-responsive nowrap"
                                            style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                            <thead>
                                            <tr>
                                                <th>Status du paiement</th>
                                                <th style="color: #ffc107; text-transform: uppercase;">
                                                    {{($transaction['etat_transac'] == 'acknowledged') ? 'Pending' : $transaction['etat_transac']}}
                                                </th>
                                            </tr>
                                            @if($transaction['observations'] !== '')
                                            <tr>
                                                <th>Observation</th>
                                                <th>{{ ucwords($transaction['observations']) }}</th>
                                            </tr>
                                            @endif
                                            </thead>
                                        </table>
                                    @endif
                                    <table id="datatable-buttons"
                                           class="table table-stripeld table-bordered dt-responsive nowrap"
                                           style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                        @if($transaction['transaction_status'] === 'waiting')                                    
                                            @if($role !== 'technical_support')                                   
                                                <tr>
                                                    <th>
                                                        <!-- <a href="#" data-target="#my_modal_val" data-toggle="modal"
                                                        data-id="{{ $transaction['id'].'|||'.$transaction['ranking'] }}"
                                                        class="btn btn-success btn-rounded waves-effect validateModal">
                                                        Valider cette transaction
                                                        </a> -->
                                                        @if($date->format('d/m/Y') === $day->format('d/m/Y')) 
                                                            <a href="{{ route('transaction_valid', $transaction['id'])}}" class="btn btn-success btn-rounded waves-effect validateModal">
                                                            <i class="mdi mdi-circle-edit-outline mr-2"></i> Valider le paiement</a>
                                                        @endif
                                                    </th>
                                                    <th>
                                                        <a href="#" data-target="#my_modal" data-toggle="modal"
                                                            data-id="{{ $transaction['id'].'|||'.$transaction['ranking'] }}"
                                                            class="btn btn-danger btn-rounded waves-effect deleteModal">
                                                            Annuler cette transaction
                                                        </a>                                            
                                                    </th>
                                                </tr>
                                            @endif
                                        @endif
                                        @if (in_array($transaction['etat_transac'], ['acknowledged', 'success']))
                                            <tr>
                                                <th colspan="2">
                                                    <a href="{{ route('transaction_receipt', $transaction['id']) }}" target="_blank"
                                                       class="btn btn-outline-primary btn-rounded waves-effect">
                                                        <i class="mdi mdi-printer mr-2"></i> Imprimer le reçu
                                                    </a>
                                                </th>
                                            </tr>
                                        @endif
                                        @if ($transaction['transaction_status'] === 'approuved' && $transaction['etat_transac'] === 'success' && $transaction['reference'] !== null)
                                            <tr>
                                                <th colspan="2" style="text-align: right">
                                                    <a href="{{ route('transaction_trace', $transaction['id']) }}">
                                                        Trace A Transaction’s Full Status <i class="mdi mdi-arrow-right-thick mr-2"></i>
                                                    </a>
                                                </th>
                                            </tr>
                                        @endif
                                        @if ($transaction['transaction_status'] === 'approuved' && $transaction['etat_transac'] !== 'success' && $transaction['etat_transac'] !== 'failed' && $transaction['reference'] !== null) 
                                            <tr>
                                                <th colspan="2"><a href="{{ route('transaction_check', $transaction['id'])}}"><i
                                                                class="mdi mdi-circle-edit-outline mr-2"></i> Check status transaction</a>
                                                </th>
                                            </tr>
                                            <!-- <tr>
                                                <th colspan="2"><a
                                                            href="{{ route('transaction_valid', $transaction['id'])}}"><i
                                                                class="mdi mdi-circle-edit-outline mr-2"></i> Valider le paiement</a>
                                                </th>
                                            </tr> -->
                                        @endif
                                        <tr>
                                            <th colspan="2"><a href="{{ route('transaction_list') }}"><i
                                                            class="mdi mdi-arrow-left-thick mr-2"></i> Retour à la liste</a>
                                            </th>
                                        </tr>
                                        </thead>
                                    </table>

                                </div>
                                <div class="col-6">
                                    <h4 class="mt-0 header-title">Informations le bénéficiaire</h4>
                                    <table id="datatable-buttons"
                                           class="table table-stripeld table-bordered dt-responsive nowrap"
                                           style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                        <tr>
                                            <th>Nom</th>
                                            <th>{{ strtoupper($transaction['recipient_first_name']) }}</th>
                                        </tr>
                                        <tr>
                                            <th>Prénom</th>
                                            <th>{{ ucwords($transaction['recipient_last_name']) }}</th>
                                        </tr>
                                        <tr>
                                            <th>Téléphone</th>
                                            <th>{{$transaction['recipient_phone']}}</th>
                                        </tr>
                                        @if( $transaction['outbound']['bank'] !== null )  
                                        <tr>
                                            <th>Nom de la banque</th>
                                            <th>{{$transaction['outbound']['bank']['organisation']}}</th>
                                        </tr>
                                        <tr>
                                            <th>Code swift</th>
                                            <th>{{$transaction['outbound']['bank']['short_code']}}</th>
                                        </tr>
                                        <tr>
                                            <th>Code IBAN</th>
                                            <th>{{$transaction['outbound']['bank']['bank_account_no']}}</th>
                                        </tr>
                                        @endif
                                        </thead>
                                    </table>
                                    <br>
                                    <h4 class="mt-0 header-title">Informations sur l'expéditeur</h4>
                                    <table id="datatable-buttons"
                                           class="table table-stripeld table-bordered dt-responsive nowrap"
                                           style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                        <tr>
                                            <th>Nom & Prénom</th>
                                            <th>{!! $transaction['sender']['sex'] === 'M' ? 'Mr.' : 'Mme/Mlle' !!} {{ strtoupper($transaction['sender']['user']['first_name']) }} {{ ucwords($transaction['sender']['user']['last_name']) }}</th>
                                        </tr>
                                        <tr>
                                            <th>Téléphone</th>
                                            <th>{{ $transaction['sender']['user']['phone_number'] }}</th>
                                        </tr>
                                        <tr>
                                            <th>{{ $transaction['sender']['type_id'] }}</th>
                                            <th>{{ $transaction['sender']['cni_number'] }}</th>
                                        </tr>
                                        <tr>
                                            <th>Date de délivrance</th>
                                            <th>{{ formaterDateString($transaction['sender']['issuer_date']) }}</th>
                                        </tr>
                                        <tr>
                                            <th>Date d'expiration</th>
                                            <th>{{ formaterDateString($transaction['sender']['date_exp_id']) }}</th>
                                        </tr>
                                        <tr>
                                            <th>Pays de délivrance</th>
                                            <th>{{ $transaction['sender']['issuer_country'] }}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                    Pièces justificatives <br> 
                                    @if( $transaction['sender']['cni_picture'] != null )
                                        <img src="{{config('keys.url_img').$transaction['sender']['cni_picture']}}" alt="" title="recto"
                                             style="max-width: 300px; max-height: 200px; border-radius:2rem!important" />
                                    @endif
                                    @if( $transaction['sender']['justif_picture'] != null )
                                        <img src="{!! config('keys.url_img').$transaction['sender']['justif_picture'] !!}" alt="" title="Verso"
                                             style="max-width: 300px; max-height: 200px; border-radius:2rem!important" />
                                    @endif
                                    
                                </div>
                            </div>

                            <div class="modal fade" id="my_modal" tabindex="-1" role="dialog"
                                 aria-labelledby="my_modalLabel">
                                <div class="modal-dialog" role="dialog">
                                    <form action="{{route('transaction_delete')}}" method="post">
                                        {{ csrf_field() }}
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h4 class="modal-title" style="color: red">Annulation de la transaction</h4>
                                            </div>
                                            <div class="modal-body">
                                                Voulez-vous annuler la transaction : <b id="ctryDel" style="color: red"></b>
                                                ?
                                                <div class="row" style="padding-top:10px;">
                                                    <div class="col-12">
                                                        <div class="form-group row">
                                                            <label for="message" class="col-5 col-form-label">Raison de l'annulation </label>
                                                            <div class="col-7">
                                                                <input type="text" value="" required class="form-control" name="note" id="note">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="idUser" id="idUser" value="{{$user['id']}}" />
                                                <input type="hidden" name="user_id" id="user_id" value="{{$transaction['user']['id']}}" />
                                                <input type="hidden" name="receive_country" id="receive_country" value="{{$transaction['receiving_country']}}" />
                                                <input type="hidden" name="numero" id="numero" value="{{$transaction['ranking']}}" />
                                                <input type="hidden" name="phone_sender" id="phone_sender" value="{{$transaction['user']['phone_number']}}" />
                                                <input type="hidden" name="id_delete" id="hiddenValue" value=""/>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="reset" class="btn btn-default" data-dismiss="modal">NON
                                                </button>
                                                <button class="btn btn-danger">OUI</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="modal fade" id="my_modal_val" tabindex="-1" role="dialog"
                                 aria-labelledby="my_modalLabel">
                                <div class="modal-dialog" role="dialog">
                                    <form action="{{route('transaction_validate')}}" method="post">
                                        {{ csrf_field() }}
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h4 class="modal-title" style="color: red">Validation de la transaction</h4>
                                            </div>
                                            <div class="modal-body">
                                                Voulez-vous valider cette transaction : <b id="ctryVal" style="color: red"></b>
                                                ?
                                                <!-- <div class="row">
                                                    <div class="col-12">
                                                        <div class="form-group row">
                                                            <label for="frais" class="col-4 col-form-label">Frais d'envoi </label>
                                                            <div class="col-8">
                                                                <input type="number" value="" class="form-control" name="frais" id="frais">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div> -->
                                                <input type="hidden" name="idUser" id="idUser" value="{{$user['id']}}" />
                                                <input type="hidden" name="phone_sender" id="phone_sender" value="{{$transaction['user']['phone_number']}}"/>
                                                <input type="hidden" name="phone_receive" id="phone_receive" value="{{$transaction['recipient_phone']}}"/>
                                                <input type="hidden" name="id_validate" id="hiddenValueVal" value=""/>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="reset" class="btn btn-default" data-dismiss="modal">NON
                                                </button>
                                                <button class="btn btn-danger">OUI</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

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
    <script type="text/javascript">
        $(function () {
            $(".deleteModal").click(function () {
                var my_id_value = $(this).data('id');
                // console.log(my_id_value);
                const id = my_id_value.split('|||')[0];
                const nom = my_id_value.split('|||')[1];
                $(".modal-body #hiddenValue").val(id);
                $(".modal-body #ctryDel").html(nom)
            });
            $(".validateModal").click(function () {
                var my_id_value = $(this).data('id');
                // console.log(my_id_value);
                const id = my_id_value.split('|||')[0];
                const nom = my_id_value.split('|||')[1];
                $(".modal-body #hiddenValueVal").val(id);
                $(".modal-body #ctryVal").html(nom)
            });
        });
    </script>
@stop

<?php
 function formaterDateString($dateS){
    // date('d F, Y');
    return $newDate = @date("d F, Y", strtotime($dateS));
 }
 function formaterdateTime($dateS){
    $heureS = @explode(' ', $dateS);
    $tab = @explode('-', $heureS[0]);
    $arr = @explode(':', $heureS[1]);
    return $newTime = $tab[2].'/'.$tab[1].'/'.$tab[0]. ' ' . $arr[0].'h'.$arr[1];
 }
?>

