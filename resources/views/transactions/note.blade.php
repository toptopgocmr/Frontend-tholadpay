@extends('layouts.base')

@section('title')
    Transactions - notes
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
                            <li class="breadcrumb-item active">Transactions note's</li>
                        </ol>
                    </div>
                    <div class="col-sm-6">
                        <div class="float-right d-none d-md-block">
                           <button class="btn_round_add btn-primary arrow-none waves-effect waves-light">
                                <i class="mdi mdi-plus mr-2"></i> <a style="color: #fff" href="{{ route('note_add') }}">
                                Ajouter une note</a>
                           </button>
                        </div>
                    </div>
                </div> <!-- end row -->
            </div>
            <!-- end page-title -->

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="mt-0 header-title">Informations sur la transaction</h4>
                            <table
                                    class="table table-stripeld table-bordered dt-responsive nowrap"
                                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                <tr>
                                    <th>Numéro</th>
                                    <th>{{$transaction['ranking']}}</th>
                                </tr>
                                <tr>
                                    <th>Montant</th>
                                    <th>{{$transaction['amount']}} XAF</th>
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
                                </thead>
                            </table>
                            <h4 class="mt-0 header-title">Listing</h4>
                            <p class="text-muted mb-4">Liste des notes de la transaction</p>
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
                            
                            <table id="datatable-buttons"
                                   class="table table-stripeld table-bordered dt-responsive nowrap"
                                   style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                <tr>
                                    <th>Notes</th>
                                    <!-- <th>user</th> -->
                                    <th>Status</th>
                                    <!-- <th>Actions</th> -->
                                </tr>
                                </thead>

                                <tbody>
                                @forelse ($notes as $trans)
                                    <tr>
                                        <td>{{$trans['detail']}}</td>
                                        <!-- <td>{{$trans['user_id']}}</td> -->
                                        <td>{{$trans['status']}}</td>
                                        <!-- <td>
                                            <div class="btn-group mt-4 mt-md-0 button-items"
                                                 dir="ltr" role="group"
                                                 aria-label="Basic example">
                                                <a href="{{route('transaction_show', $trans['id'])}}"
                                                   class="btn btn-info btn-rounded waves-effect"><i
                                                            class="mdi mdi-information-variant"></i></a>
                                                    <a href="{{route('transaction_valid', $trans['id'])}}"
                                                       class="btn btn-warning btn-rounded waves-effect"><i
                                                                class="mdi mdi-circle-edit-outline"></i></a>
                                                    <a href="{{route('transaction_notes', $trans['id'])}}" title="Lister les notes"
                                                    class="btn btn-primary btn-rounded waves-effect"><i
                                                                class="mdi mdi-information-variant"></i>
                                                    </a>
                                            </div>
                                        </td> -->
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