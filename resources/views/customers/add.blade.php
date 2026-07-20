@extends('layouts.base')

@section('title')
    Customer - Ajout
@stop

@section('stylesheets')
    <link href="{{ asset('assets/plugins/select2/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/plugins/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/bootstrap-touchspin/css/jquery.bootstrap-touchspin.min.css') }}" rel="stylesheet" />

    
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
                        <h4 class="page-title">Customer</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{route('admin')}}"><i class="mdi mdi-home-outline"></i></a></li>
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Customer</a></li>
                            <li class="breadcrumb-item active">Ajouter</li>
                        </ol>
                    </div>
                    <div class="col-sm-6">
                        <div class="float-right d-none d-md-block">
                        </div>
                    </div>
                </div> <!-- end row -->
            </div>
            <!-- end page-title -->

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">

                            <h4 class="mt-0 header-title">Ajout d'un nouveau customer</h4>
                            <p class="text-muted mb-4">Remplissez ces champs pour créer des customers devant interagir dans l'app mobile.</p>
                            @if ($message = Session::get('success'))
                            <div class="alert alert-success alert-block">
                                <button type="button" class="close" data-dismiss="alert">×</button> <strong>{{ $message }}</strong>
                            </div>
                            @endif
                            @if ($message = Session::get('error'))
                            <div class="alert alert-danger alert-block">
                                <button type="button" class="close" data-dismiss="alert">×</button> <strong>{{ $message }}</strong>
                            </div>
                            @endif
                            @include('customers._form')
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

    <script src="{{ asset('assets/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js') }}"></script>
    <script src="{{ asset('assets/plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js') }}"></script>
    <!-- Responsive examples -->
    <script src="{{ asset('assets/plugins/datatables/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/bootstrap-datepicker/bootstrap-datepicker.js') }}"></script>

    <!-- Datatable init js -->
    <script src="{{ asset('assets/pages/datatables.init.js') }}"></script>

    <script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
    <script src="{{ asset('assets/pages/form-advanced.init.js') }}"></script>
    <script type="text/javascript">
        $(document).ready(function(){
            $("#txtNomC").val(' ');
            $("#idAgent").val(' ');
            $("#match_pass").html("");
            $("#wrong_pass").html("");
            $('#imgAvatar').on('change', function() {
                readPath(this);
            });
            $('#imgRecto').on('change', function() {
                readPathRecto(this);
            });
            
            $('#imgVerso').on('change', function() {
                readPathVerso(this);
            });
            $('#textRole').on('change', function() {
                role(this);
            });
        });
        function readPath(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    $('#avatar').attr('src', e.target.result);
                    $('#imgUser').attr('value', e.target.result);
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        function readPathRecto(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    $('#recto').attr('src', e.target.result);
                    $('#imgRectos').attr('value', e.target.result);
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        function readPathVerso(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    $('#verso').attr('src', e.target.result);
                    $('#imgVersos').attr('value', e.target.result);
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        function role(input) {
            console.log(input.value);
            var role = input.value;
            $("#idAgent").val(' ');
            $("#blockAgent").attr("style", "display:none");
            if (role.split('|')[1] === 'Agent') {
                $("#txtNomC").val('');
                $("#titleInfos").attr("style", "display:block");
                $("#fieldInfos").attr("style", "display:block");
            } else {
                $("#txtNomC").val(' ');
                $("#titleInfos").attr("style", "display:none");
                $("#fieldInfos").attr("style", "display:none");
                if (role.split('|')[1] === 'Caissier') {
                    $("#idAgent").val('');
                    $("#blockAgent").attr("style", "display:block");
                }
            }
        }
        $("#textRePassword").focusout(function(){
            $("#match_pass").html("");
            $("#wrong_pass").html("");
            if ($("#textPassword").val().length < 7) {
                $("#textRePassword").val('');
                $("#wrong_pass").html("Mot de passe doit avoir au moins 8 caractères.");
            }
            if ($("#textPassword").val().length !== $("#textRePassword").val().length ) {
                $("#textRePassword").val('');
                $("#match_pass").html("Les Mots de passe ne correspondent pas.");
            }
        });
    </script>
    @stop