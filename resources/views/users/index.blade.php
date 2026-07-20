@extends('layouts.base')

@section('title')
    Utilisateurs
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
                        <h4 class="page-title">
                            @if($role ==='administrator' || $role ==='finance_manager') Utilisateur @else Partner Agent @endif
                        </h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('admin') }}"><i
                                            class="mdi mdi-home-outline"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{ route('user_list') }}">Utilisateurs</a></li>
                            <li class="breadcrumb-item active">@if($role ==='administrator' || $role ==='finance_manager') Utilisateur @else
                                    Partner Agent @endif</li>
                        </ol>
                    </div>
                    <div class="col-sm-6">
                        <div class="float-right d-none d-md-block">
                            <button class="btn_round_add btn-primary arrow-none waves-effect waves-light">
                                <i class="mdi mdi-plus mr-2"></i> <a style="color: #fff" href="{{ route('user_add') }}">
                                    {{($role==='administrator' || $role==='finance_manager') ? 'Ajouter un Utilisateur' : 'Ajouter un Partner Service'}}</a>
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
                                @if($role ==='administrator' || $role==='finance_manager') Liste de tous les utilisateurs enregistrés dans le
                                système. @else
                                    Liste de tous vos Partner Service Agent. @endif</p>
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
                                    <th>Rôle</th>
                                    <th>Nom & Prénom</th>
                                    <th>Email</th>
                                    <th>Telephone</th>
                                    <th>Adresse</th>
                                    <th>Enabled</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>

                                <tbody>
                                @forelse ($users as $usr)
                                    <tr>
                                        <td>{{ count($usr['user_roles']) > 0 ? ucwords($usr['user_roles'][0]['role']['display_name']) : '' }}</td>
                                        <td> {{ strtoupper($usr['first_name']) }} {{ ucwords($usr['last_name']) }} </td>
                                        <td> {{ \Illuminate\Support\Str::limit($usr['email'], $limit=15, $end='...') }}</td>
                                        <td> {{ $usr['phone_number'] }}</td>
                                        <td> {{ count($usr['addresses']) > 0 ? \Illuminate\Support\Str::limit($usr['addresses'][0]['name'], $limit=15, $end='...') : '' }}</td>
                                        <td>{{ $usr['is_active'] == 0 ? 'Inactif' : 'Actif' }}</td>
                                        <td>
                                            <div class="btn-group mt-4 mt-md-0 button-items"
                                                 dir="ltr" role="group"
                                                 aria-label="Basic example">
                                                <a href="{{route('user_show', $usr['id'])}}"
                                                   class="btn btn-info btn-rounded waves-effect"><i
                                                            class="mdi mdi-information-variant"></i></a>
                                                @if (count($usr['user_roles']) > 0 && $usr['user_roles'][0]['role']['name'] !== 'customer')
                                                <a href="{{route('user_edit', $usr['id'])}}"
                                                   class="btn btn-warning btn-rounded waves-effect"><i
                                                            class="mdi mdi-circle-edit-outline"></i></a>
                                                @endif
                                                @if (count($usr['user_roles']) > 0 && $usr['user_roles'][0]['role']['name'] === 'customer')
                                                <a href="{{route('customer_edit', $usr['id'])}}"
                                                    class="btn btn-warning btn-rounded waves-effect"><i
                                                                class="mdi mdi-circle-edit-outline"></i></a>
                                                @endif
                                                <a href="#" data-target="#my_modal" data-toggle="modal"
                                                   data-id="{{ $usr['id'].'|||'.$usr['full_name'] }}" data-idrole="{{count($usr['user_roles']) > 0 ? $usr['user_roles'][0]['role']['name'] : ''}}"
                                                   class="btn btn-danger btn-rounded waves-effect deleteModal"><i
                                                            class="mdi mdi-block-helper"></i></a>
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
                                    <form action="{{route('user_delete')}}" method="post">
                                        {{ csrf_field() }}
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h4 class="modal-title" style="color: red">Suppression d'un utilisateur</h4>
                                            </div>
                                            <div class="modal-body">
                                                Voulez-vous supprimer l'utilisateur : <b id="ctryDel" style="color: red"></b>
                                                ?
                                                <input type="hidden" name="id_delete" id="hiddenValue" value=""/>
                                                <input type="hidden" name="id_role" id="hiddenValueRole" value=""/>
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
                var my_id_value_role = $(this).data('idrole');
                // console.log(my_id_value_role);
                const id = my_id_value.split('|||')[0];
                const nom = my_id_value.split('|||')[1];
                $(".modal-body #hiddenValue").val(id);
                $(".modal-body #hiddenValueRole").val(my_id_value_role);
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