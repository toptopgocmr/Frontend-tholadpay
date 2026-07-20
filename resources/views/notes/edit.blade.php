@extends('layouts.base')

@section('title')
    Note - Mofifier
@stop

@section('stylesheets')
    <link href="{{ asset('assets/plugins/select2/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/plugins/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/bootstrap-touchspin/css/jquery.bootstrap-touchspin.min.css') }}" rel="stylesheet" />
@stop

@section('content')
    <!-- Start content -->
    <div class="content">
        <div class="container-fluid">
            <div class="page-title-box">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <h4 class="page-title">Notes</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('admin') }}"><i
                                            class="mdi mdi-home-outline"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{ route('country_list') }}">Notes</a></li>
                            <li class="breadcrumb-item active">Notes</li>
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

                            <h4 class="mt-0 header-title">Modifier une note</h4>
                            <p class="text-muted mb-4">Veuillez renseigner tous les champs du formulaire.</p>
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
                            @include('notes._form')
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
            $('#imgLogo').on('change', function() {
                readPathLogo(this);
            });
            $('#textRole').on('change', function() {
                role(this);
            });
        });
        $("#textRePassword").focusout(function(){
            $("#match_pass").html("");
            $("#wrong_pass").html("");
            if ($("#textPassword").val().length < 5) {
                $("#textRePassword").val('');
                $("#wrong_pass").html("Mot de passe doit avoir plus de 5 caractères.");
            }
            if ($("#textPassword").val().length !== $("#textRePassword").val().length ) {
                $("#textRePassword").val('');
                $("#match_pass").html("Les Mots de passe ne correspondent pas.");
            }
        });
    </script>
    @stop