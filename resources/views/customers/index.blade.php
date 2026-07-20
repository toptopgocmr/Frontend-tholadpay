@extends('layouts.base')

@section('title')
    customers
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
                        <!-- <h4 class="page-title">
                            @if($role ==='administrator') Utilisateur @else Caissier @endif
                        </h4> -->
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('admin') }}"><i
                                            class="mdi mdi-home-outline"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{ route('customer_list') }}">Customers</a></li>
                            <li class="breadcrumb-item active">Customers</li>
                        </ol>
                    </div>
                    <div class="col-sm-6">
                        <!-- <div class="float-right d-none d-md-block">
                            <button class="btn_round_add btn-primary arrow-none waves-effect waves-light">
                                <i class="mdi mdi-plus mr-2"></i> <a style="color: #fff" href="{{ route('customer_add') }}">
                                    Ajouter un customer</a>
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
                            <p class="text-muted mb-4">Liste de tous les Clients</p>
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
                                    <!-- <th>Rôle</th> -->
                                    <th>Nom & Prénom</th>
                                    <th>Email</th>
                                    <th>Telephone</th>
                                    <th>Adresse</th>
                                    <th>Enabled</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>

                                <tbody>
                                @forelse ($customers as $cust)
                                    <tr>
                                        <td> {{ strtoupper($cust['user']['first_name']) }} {{ ucwords($cust['user']['last_name']) }} </td>
                                        <td title="{{$cust['user']['email']}}"> {{ \Illuminate\Support\Str::limit($cust['user']['email'], $limit=20, $end='...') }}</td>
                                        <td> {{ $cust['user']['phone_number'] }}</td>
                                        <td> {{ count($cust['user']['addresses']) > 0 ? \Illuminate\Support\Str::limit($cust['user']['addresses'][0]['name'], $limit=15, $end='...') : '' }}</td>
                                        <td>
                                            @if ($cust['user']['status'] !== 'Rejected')
                                                <!-- {{ $cust['user']['is_active'] === 0 ? 'Inactif' : 'Actif' }} -->
                                                {{$cust['sender']['status']}}
                                            @endif
                                            @if ($cust['user']['status'] === 'Rejected') 
                                                Rejected
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group mt-4 mt-md-0 button-items"
                                                 dir="ltr" role="group"
                                                 aria-label="Basic example">
                                                <a title="Voir le détail du customer" href="{{route('customer_show', $cust['user']['id'])}}"
                                                   class="btn btn-info btn-rounded waves-effect"><i
                                                            class="mdi mdi-information-variant"></i></a>
                                                @if ($cust['user']['status'] !== 'Rejected')
                                                    @if ($role === 'administrator')
                                                        <a title="Modifier le customer" href="{{route('customer_edit', $cust['user']['id'])}}"
                                                        class="btn btn-warning btn-rounded waves-effect"><i
                                                                    class="mdi mdi-circle-edit-outline"></i>
                                                        </a>
                                                    @endif
                                                    @if ($cust['sender']['status'] === 'waiting')
                                                    <a title="Approuver le customer" href="#" data-target="#my_modal_val" data-toggle="modal"
                                                    data-id="{{ $cust['user']['id'].'|||'.$cust['user']['full_name'] }}" data-ids="{{$cust['sender']['id']}}"
                                                    class="btn btn-success btn-rounded waves-effect validateModal"><i
                                                                class="mdi mdi-block-helper"></i></a>
                                                    <a title="Rejeter le customer" href="#" data-target="#my_modal" data-toggle="modal"
                                                    data-id="{{ $cust['user']['id'].'|||'.$cust['user']['full_name'] }}" data-ids="{{$cust['sender']['id']}}"
                                                    class="btn btn-danger btn-rounded waves-effect deleteModal"><i
                                                                class="mdi mdi-block-helper"></i></a>
                                                    @endif
                                                    <a title="Lister les transations" href="{{route('customer_transac', $cust['user']['id'])}}"
                                                    class="btn btn-primary btn-rounded waves-effect"><i
                                                                class="fas fa-cash-register"></i></a>
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
                                    <form action="{{route('customer_delete')}}" method="post">
                                        {{ csrf_field() }}
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h4 class="modal-title" style="color: red">Suppression d'un customer</h4>
                                            </div>
                                            <div class="modal-body">
                                                Voulez-vous supprimer le customer : <b id="ctryDel" style="color: red"></b>
                                                ?
                                                <input type="hidden" name="id_delete" id="hiddenValue" value=""/>
                                                <input type="hidden" name="id_deleteS" id="hiddenValueS" value=""/>
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
                                    <form action="{{route('customer_validate')}}" method="post">
                                        {{ csrf_field() }}
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h4 class="modal-title" style="color: red">Validation d'un customer</h4>
                                            </div>
                                            <div class="modal-body">
                                                Voulez-vous valider le customer : <b id="ctryVal" style="color: red"></b>
                                                ?
                                                <input type="hidden" name="id_validate" id="hiddenValueVal" value=""/>
                                                <input type="hidden" name="id_validateS" id="hiddenValueValS" value=""/>
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
                var my_id_valueS = $(this).data('ids');
                console.log(my_id_valueS);
                const id = my_id_value.split('|||')[0];
                const nom = my_id_value.split('|||')[1];
                $(".modal-body #hiddenValue").val(id);
                $(".modal-body #hiddenValueS").val(my_id_valueS);
                $(".modal-body #ctryDel").html(nom)
            });
            $(".validateModal").click(function () {
                var my_id_value = $(this).data('id');
                var my_id_valueS = $(this).data('ids');
                // console.log(my_id_value);
                const id = my_id_value.split('|||')[0];
                const nom = my_id_value.split('|||')[1];
                $(".modal-body #hiddenValueVal").val(id);
                $(".modal-body #hiddenValueValS").val(my_id_valueS);
                $(".modal-body #ctryVal").html(nom)
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
    <!-- Responsive examples -->
    <script src="{{ asset('assets/plugins/datatables/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables/responsive.bootstrap4.min.js') }}"></script>

    <!-- Datatable init js -->
    <script src="{{ asset('assets/pages/datatables.init.js') }}"></script>
@stop