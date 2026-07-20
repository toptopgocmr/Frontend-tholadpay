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
                                    <h3>Quotation de la transaction (2/3)</h3>
                                    <h5>Informations Transaction</h5>
                                    <fieldset>
                                        <div class="row">
                                            <div class="col-4">
                                                <div class="form-group row">
                                                    <label for="numTrs"
                                                           class="col-4 col-form-label">{!! $transaction['outbound']['bank'] === null ? 'Numéro Téléphone' : 'Numéro Bancaire' !!}
                                                        <i class="red">*</i></label>
                                                    <div class="col-8">
                                                        <input type="text"
                                                               value="{{$transaction['outbound']['bank'] === null ? $transaction['outbound']['mobile']['mobile_phone_credit'] : $transaction['outbound']['bank']['bank_account_no']}}"
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
                                                               class="form-control" required name="origin" id="origin">
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
                                                               class="form-control" required name="reason" id="reason">
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