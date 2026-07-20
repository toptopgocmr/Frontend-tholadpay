@extends('layouts.base')

@section('title')
    Taux de change - Détail
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
                        <h4 class="page-title">Taux de change</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('admin') }}"><i
                                            class="mdi mdi-home-outline"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{ route('currency_list') }}">Taux de change</a></li>
                            <li class="breadcrumb-item active">Taux de change</li>
                        </ol>
                    </div>
                </div> <!-- end row -->
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-3">
                                </div>
                                <div class="col-6">
                                    <h4 class="mt-0 header-title">Détail du taux de change</h4>
                                    <table id="datatable-buttons"
                                           class="table table-stripeld table-bordered dt-responsive nowrap"
                                           style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                        <tr>
                                            <th>Code Monnaie</th>
                                            <th>{{$currency['code']}}</th>
                                        </tr>
                                        <tr>
                                            <th>Symbol de la monnaie</th>
                                            <th>{{$currency['symbol']}}</th>
                                        </tr>
                                        <tr>
                                            <th>Rate</th>
                                            <th>{{$currency['rate']}}</th>
                                        </tr>
                                        <tr>
                                            <th>Fees</th>
                                            <th>{{$currency['fees']}}</th>
                                        </tr>
                                        </thead>
                                    </table>
                                    <table id="datatable-buttons"
                                           class="table table-stripeld table-bordered dt-responsive nowrap"
                                           style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                        <tr>
                                            <th colspan="2"><a href="{{ route('currency_list') }}"><i
                                                            class="mdi mdi-arrow-left-thick mr-2"></i> Retour à la liste</a>
                                            </th>
                                        </tr>
                                        <tr>
                                            <th colspan="2"><a href="{{route('currency_edit', $currency['id'])}}"><i
                                                            class="mdi mdi-circle-edit-outline mr-2"></i> Modifier</a>
                                            </th>
                                        </tr>
                                        <tr>
                                            <th colspan="2"><a href=""><i
                                                            class="btn btn-outline-danger mr-2"></i> Supprimer</a>
                                            </th>
                                        </tr>
                                        </thead>
                                    </table>

                                </div>
                                <div class="col-3">
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

