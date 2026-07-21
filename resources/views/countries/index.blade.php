@extends('layouts.base')

@section('title')
    Pays
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
                        <h4 class="page-title"> Pays </h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('admin') }}"><i
                                            class="mdi mdi-home-outline"></i></a></li>
                            <li class="breadcrumb-item active">Pays</li>
                        </ol>
                    </div>
                    <div class="col-sm-6">
                        <div class="float-right d-none d-md-block">
                            <button class="btn_round_add btn-primary arrow-none waves-effect waves-light">
                                <i class="mdi mdi-plus mr-2"></i> <a style="color: #fff"
                                                                     href="{{ route('country_add') }}">Ajouter un
                                    pays</a>
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
                            <h4 class="mt-0 header-title">Listing</h4>
                            <p class="text-muted mb-4">
                                Liste de tous les pays où Send-Paz est succeptible d'opéré les oprérations de transfert
                                d'argent.
                            </p>
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
                                    <th>Nom</th>
                                    <th>Monnaie</th>
                                    <th>Code Telephone</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>

                                <tbody>
                                @forelse ($countries as $c)
                                    <tr>
                                        <td> {{ $c['full_name'] === null ? $c['name'] : $c['full_name'] }}</td>
                                        <td> {{ $c['currency'] }} ({{ $c['currency_code'] }})</td>
                                        <td> {{ $c['calling_code'] }}</td>
                                        <td>
                                            <div class="btn-group mt-4 mt-md-0 button-items"
                                                 dir="ltr" role="group"
                                                 aria-label="Basic example">
                                                <a href="{{route('country_show', $c['id'])}}"
                                                   class="btn btn-info btn-rounded waves-effect"><i
                                                            class="mdi mdi-information-variant"></i></a>
                                                <a href="{{route('country_edit', $c['id'])}}"
                                                   class="btn btn-warning btn-rounded waves-effect"><i
                                                            class="mdi mdi-circle-edit-outline"></i></a>
                                                <a href="#" data-target="#my_modal" data-toggle="modal"
                                                   data-id="{{ $c['id'].'|||'.$c['name'] }}"
                                                   class="btn btn-danger btn-rounded waves-effect deleteModal"><i
                                                            class="mdi mdi-block-helper"></i></a>
{{--                                                            class="mdi mdi-block-helper"></i>{{ $c['id'].'|||'.$c['name'] }}</a>--}}
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
                                    <form action="{{route('country_delete')}}" method="post">
                                        {{ csrf_field() }}
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h4 class="modal-title" style="color: red">Suppression du pays</h4>
                                            </div>
                                            <div class="modal-body">
                                                Voulez-vous supprimer le pays : <b id="ctryDel" style="color: red"></b>
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
                    </div> <!-- end col -->
                </div> <!-- end row -->
            </div>
        </div>
        <!-- content -->
    </div>
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
            })
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