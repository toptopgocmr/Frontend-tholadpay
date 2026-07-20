@extends('layouts.base')

@section('title')
    Transaction - Trace A Transaction’s Full Status
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
                        <h4 class="page-title">Trace A Transaction’s Full Status</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('admin') }}"><i
                                            class="mdi mdi-home-outline"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{ route('transaction_list') }}">Transactions</a></li>
                            <li class="breadcrumb-item active">Trace A Transaction’s Full Status</li>
                        </ol>
                    </div>
                </div> <!-- end row -->
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-7">
                                    <h4 class="mt-0 header-title">Trace A Transaction’s Full Status</h4>
                                    <table id="datatable-buttons"
                                           class="table table-stripeld table-bordered dt-responsive nowrap"
                                           style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                        <tr>
                                            <th>Reférence</th>
                                            <th>{{$transaction['ranking']}}</th>
                                        </tr>
                                        <tr>
                                            <th>Montant de la transaction</th>
                                            <th>{{$transaction['amount'] + $transaction['frais_envoi']}} {{$transaction['from_currency']}}</th>
                                        </tr>
                                        <tr>
                                            <th>Status de la transaction</th>
                                            <th style="color: #ffc107">{{$transaction['transaction_status']}}</th>
                                        </tr>
                                        <tr>
                                            <th>Transaction créé par </th>
                                            <th>{!! $transaction['sender']['sex'] === 'M' ? 'Mr.' : 'Mme/Mlle' !!} {{ strtoupper($transaction['sender']['user']['first_name']) }} {{ ucwords($transaction['sender']['user']['last_name']) }}</th>
                                        </tr>
                                        <tr>
                                            <th>Transaction créé le</th>
                                            <th>{{$transaction['created_at']}}</th>
                                        </tr>                                        
                                        <tr>
                                            <th>Transaction {!! $transaction['validate'] === 1 ? 'validé ' : 'annulé ' !!} par </th>
                                            <th>{{ strtoupper($admin['first_name']) }} {{ ucwords($admin['last_name']) }}</th>
                                        </tr>
                                        <tr>
                                            <th>Transaction {!! $transaction['validate'] === 1 ? 'validé ' : 'annulé ' !!} le </th>
                                            <th>{{ $transaction['validate_at'] }}</th>
                                        </tr>

                                        <tr>
                                            <th>Date de paiement</th>
                                            <th>{{ $transaction['date_complete'] }}</th>
                                        </tr>
                                        <tr>
                                            <th>Nom du syst. paiement</th>
                                            <th>{{$transaction['nom_api']}}</th>
                                        </tr>
                                        </thead>
                                    </table>
                                    
                                    <table id="datatable-buttons"
                                           class="table table-stripeld table-bordered dt-responsive nowrap"
                                           style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>                                       
                                            <tr>
                                                <th colspan="2"><a href="{{ route('transaction_list') }}"><i
                                                                class="mdi mdi-arrow-left-thick mr-2"></i> Retour à la liste</a>
                                                </th>
                                            </tr>
                                        </thead>
                                    </table>

                                </div>
                                <!-- <div class="col-6">
                                    
                                </div> -->
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

