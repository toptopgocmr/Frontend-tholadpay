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
        $(function () {
            $("#datepicker-12").datepicker();
            $("#datepicker-12").datepicker("setDate", "10w+1");

            $("#datepicker-13").datepicker();
            $("#datepicker-13").datepicker("setDate", "10w+1");
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
                                            <input type="text" id="datepicker-13" name="search_start">                                            
                                        </div>
                                    </div>
                                    <div class="col-2">                                        
                                        <div style="margin: auto;" class="input-group">
                                            <input type="text" id="datepicker-12" name="search_trs">                                            
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
                            <table id="datatable-buttons"
                                   class="table table-stripeld table-bordered dt-responsive nowrap"
                                   style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Agent</th>
                                    <th>Emetteur</th>
                                    <th>Beneficiaire</th>
                                    <th>Montant <span style="font-size: 10px">(XAF)</span></th>
                                    <th>M. percu</th>
                                    <th>Frais <span style="font-size: 10px">(XAF)</span></th>
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
                                        <td>
                                            {{(isset($trans['valid']) && $trans['valid'] !== null) ? $trans['valid']['full_name'] : $trans['agent']['nom_commercial']}}
                                        </td>
                                        <td>{{ strtoupper($trans['user']['first_name']) }} {{ ucwords($trans['user']['last_name']) }}</td>
                                        <td>{{ strtoupper($trans['recipient_first_name']) }} {{ ucwords($trans['recipient_last_name']) }}</td>
                                        <td>{{$trans['amount']}}</td>
                                        <td>{{$trans['montant_beneficiaire']}}  <span style="font-size: 10px">({{$trans['to_currency']}})</span></td>
                                        <td>{{$trans['fees']}}</td>
                                        <td>{{strtoupper($trans['receiving_country'])}}</td>
                                        <td>{!! $trans['outbound']['bank'] === null ? 'Mobile' : 'Bancaire' !!}</td>
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
                                        <td colspan="8">Aucun enregistrement trouvé</td>
                                    </tr>
                                @endforelse
                                </tbody>
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