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
                        <h4 class="page-title">Transactions customer's</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('admin') }}"><i
                                            class="mdi mdi-home-outline"></i></a></li>
                            <li class="breadcrumb-item active">Transactions of customer </li>
                        </ol>
                    </div>
                    <!-- <div class="col-sm-6">
                        <div class="float-right d-none d-md-block">
                           <button class="btn_round_add btn-primary arrow-none waves-effect waves-light">
                                <i class="mdi mdi-plus mr-2"></i> <a style="color: #fff" href="{{ route('transaction_add') }}">
                                Effectuer une transaction</a>
                           </button>
                        </div>
                    </div> -->
                </div> <!-- end row -->
            </div>
            <!-- end page-title -->

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="mt-0 header-title">Listing</h4>
                            <p class="text-muted mb-4">Liste de toutes les transactions éffectuer par <b>{{$userV['full_name']}}</b> </p>
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
                                    <th>Code</th>
                                    <th>Agent</th>
                                    <!-- <th>Emetteur</th> -->
                                    <th>Beneficiaire</th>
                                    <th>Montant</th>
                                    <th>M. percu</th>
                                    <th>Frais</th>
                                    <th>Pays</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>

                                <tbody>
                                @forelse ($transactions as $trans)
                                    <tr>
                                        <td>{{$trans['ranking']}}</td>
                                        <td>{{$trans['agent']['nom_commercial']}}</td>
                                        <!-- <td>{{$trans['user']['full_name']}}</td> -->
                                        <!-- <td>{{ strtoupper($trans['sender']['user']['first_name']) }} {{ ucwords($trans['sender']['user']['last_name']) }}</td> -->
                                        <td>{{ strtoupper($trans['recipient_first_name']) }} {{ ucwords($trans['recipient_last_name']) }}</td>
                                        <td>{{$trans['amount']}} <span style="font-size: 10px">XAF</span></td>
                                        <td>{{$trans['montant_beneficiaire']}} <span style="font-size: 10px">{{$trans['to_currency']}}</span></td>
                                        <td>{{$trans['fees']}} <span style="font-size: 10px">XAF</span></td>
                                        <td>{{strtoupper($trans['receiving_country'])}}</td>
                                        <td>{!! $trans['outbound']['bank'] === null ? 'Mobile' : 'Bancaire' !!}</td>
                                        <td>{{$trans['etat_transac']}}</td>
                                        <td>
                                            <div class="btn-group mt-4 mt-md-0 button-items"
                                                 dir="ltr" role="group"
                                                 aria-label="Basic example">
                                                <a href="{{route('transaction_show', $trans['id'])}}"
                                                   class="btn btn-info btn-rounded waves-effect"><i
                                                            class="mdi mdi-information-variant"></i></a>
                                                @if ($trans['transaction_status'] === 'waiting')            
                                                <a title="Cancel transaction" href="#" data-target="#my_modal" data-toggle="modal"
                                                   data-id="{{ $trans['id'].'|||'.$trans['recipient_first_name'] }}"
                                                   class="btn btn-danger btn-rounded waves-effect deleteModal"><i
                                                            class="mdi mdi-block-helper"></i>
                                                </a>
                                                @endif
                                                @if($trans['transaction_status'] === 'waiting')
                                                    <a href="{{route('transaction_valid', $trans['id'])}}" title="Valider le paiement (envoi à Peex)"
                                                       class="btn btn-warning btn-rounded waves-effect"><i
                                                                class="mdi mdi-circle-edit-outline"></i></a>
                                                @endif
                                                @if(isset($trans['isnote']) && $trans['isnote'] === '1')
                                                    <a href="{{route('transaction_notes', $trans['id'])}}" title="Lister les notes"
                                                    class="btn btn-primary btn-rounded waves-effect"><i
                                                                class="mdi mdi-information-variant"></i>
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

                            <div class="modal fade" id="my_modal" tabindex="-1" role="dialog"
                                 aria-labelledby="my_modalLabel">
                                <div class="modal-dialog" role="dialog">
                                    <form action="{{route('customer_transac_cancel')}}" method="post">
                                        {{ csrf_field() }}
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h4 class="modal-title" style="color: red">Annulation de la transaction</h4>
                                            </div>
                                            <div class="modal-body">
                                                Voulez-vous annuler cette transaction : <b id="ctryDel" style="color: red"></b>
                                                ?
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
        });
    </script>
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