@extends('layouts.base')

@section('title')
    Role - Détail
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
                        <h4 class="page-title">Roles</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('admin') }}"><i
                                            class="mdi mdi-home-outline"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{ route('role_list') }}">Roles</a></li>
                            <li class="breadcrumb-item active">role</li>
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
                                    <h4 class="mt-0 header-title">Détail du role</h4>
                                    <table id="datatable-buttons"
                                           class="table table-stripeld table-bordered dt-responsive nowrap"
                                           style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>{{$role['name']}}</th>
                                        </tr>
                                        <tr>
                                            <th>Display Name</th>
                                            <th>{{$role['display_name']}}</th>
                                        </tr>
                                        <tr>
                                            <th>Description</th>
                                            <th>{{$role['description']}}</th>
                                        </tr>
                                        </thead>
                                    </table>
                                    <table id="datatable-buttons"
                                           class="table table-stripeld table-bordered dt-responsive nowrap"
                                           style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                        <tr>
                                            <th colspan="2"><a href="{{ route('role_list') }}"><i
                                                            class="mdi mdi-arrow-left-thick mr-2"></i> Retour à la liste</a>
                                            </th>
                                        </tr>
                                        <tr>
                                            <th colspan="2"><a href="{{route('role_edit', $role['id'])}}"><i
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

