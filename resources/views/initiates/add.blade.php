@extends('layouts.base')

@section('title')
    Utilisateur - Modifier mot de passe
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
                        <h4 class="page-title">Reset Password</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{route('admin')}}"><i class="mdi mdi-home-outline"></i></a></li>
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Reset Password</a></li>
                            <li class="breadcrumb-item active">Modifier le mot de passe</li>
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
                            @if($userEdit === null)
                                <h4 class="mt-0 header-title">Modifier le mot de passe d'un utilisateur</h4>
                                <p class="text-muted mb-4">Veuillez choisir le type afin de savoir le moyen de recuperation de votre mot de passe.</p>
                            @endif
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
                            @include('initiates._form')
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

    function valueChangeListner(input){
        console.log("Valeur du type : ", input.value);
        var type = input.value;

        $("#blockEmail").attr("style", "display:block");
        $("#blockPhone").attr("style", "display:none");
        if (type === 'email') {
            $("#blockEmail").attr("style", "display:block");
            $("#blockPhone").attr("style", "display:none");
            // $("#titleInfos").attr("style", "display:block");
            // $("#fieldInfos").attr("style", "display:block");
        } else {
            $("#blockEmail").attr("style", "display:none");
            $("#blockPhone").attr("style", "display:block");
        }

    }
        $(document).ready(function(){
            $('#textType').on('change', function() {
                valueChangeListner(this);
            });
        });
    </script>
    @stop