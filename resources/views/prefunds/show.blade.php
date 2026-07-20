@extends('layouts.base')

@section('title')
    Préfinancement - Détail
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
                        <h4 class="page-title">Préfinancement</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('admin') }}"><i
                                            class="mdi mdi-home-outline"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{ route('prefund_list') }}">Préfinancements</a></li>
                            <li class="breadcrumb-item active">Préfinancement</li>
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
                                    <h4 class="mt-0 header-title">Détail du Préfinancement</h4>
                                    <table id="datatable-buttons"
                                           class="table table-stripeld table-bordered dt-responsive nowrap"
                                           style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                        @if($role == 'administrator')
                                            <tr>
                                                <th>Type</th>
                                                <th>{{$prefund['agent']['nom_commercial']}}</th>
                                            </tr> @endif
                                        <tr>
                                            <th>Type</th>
                                            <th>{{$prefund['paiement_type']}}</th>
                                        </tr>
                                        <tr>
                                            <th>Montant</th>
                                            <th>{{$prefund['amount']}} XAF</th>
                                        </tr>
                                        <tr>
                                            <th>Description</th>
                                            <th>{{$prefund['description']}}</th>
                                        </tr>
                                        <tr>
                                            <th>Status</th>
                                            <th>{{$prefund['status']}}</th>
                                        </tr>
                                        <tr>
                                            <th>Date de paiement</th>
                                            <th>{{$prefund['date_paiement']}}</th>
                                        </tr>
                                        <tr>
                                            <th>Preuve de paiement</th>
                                            <th>
                                                @if( $prefund['prove'] != null ) 
                                                    <input type="hidden" id="avatar_url" value="{!! 'http://localhost:4500/'.$prefund['prove'] !!}">
                                                    <img id="avatar" 
                                                        src="{!! 'http://localhost:4500/'.$prefund['prove'] !!}"
                                                        width="350px" height="200px"
                                                        style="border-radius: 10%; cursor: pointer"
                                                        alt="your image"/>
                                                @endif
                                            </th>
                                        </tr>
                                        </thead>
                                    </table>
                                    <table id="datatable-buttons"
                                           class="table table-stripeld table-bordered dt-responsive nowrap"
                                           style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                        <tr>
                                            <th colspan="2"><a href="{{ route('prefund_list') }}"><i
                                                            class="mdi mdi-arrow-left-thick mr-2"></i> Retour à la liste</a>
                                            </th>
                                        </tr>
                                        @if($role == 'administrator')
                                            <tr>
                                                <th colspan="2"><a href="{{route('prefund_edit', $prefund['id'])}}"><i
                                                                class="mdi mdi-circle-edit-outline mr-2"></i>
                                                        Modifier</a>
                                                </th>
                                            </tr>
                                        @endif
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
@section('javascripts')
    <script type="text/javascript">
        $(document).ready(function () {
            $('#avatar').on('click', function () {
                openProve();
            });
        });
        function openProve() {
            const url = $("#avatar_url").val();
            window.open(url, '_blank');
        }
    </script>
@stop
