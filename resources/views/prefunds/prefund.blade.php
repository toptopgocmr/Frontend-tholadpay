@extends('layouts.base')

@section('title')
    Préfinancements - Agent
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
                        <h4 class="page-title">Préfinancement</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{route('admin')}}"><i class="mdi mdi-home-outline"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{route('prefund_list')}}">Préfinancements</a></li>
{{--                            <li class="breadcrumb-item active">Ajouter</li>--}}
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
                <div class="col-2"></div>
                <div class="col-8">
                    <div class="card">
                        <div class="card-body">

                            <h4 class="mt-0 header-title">
                                @if($role === 'administrator') Ajout d'un nouveau Préfinancement @else Préfinancement de votre caissier @endif
                            </h4>
                            <p class="text-muted mb-4">@if($role === 'administrator') Agent : {{$agent['nom_commercial']}}. @else Caissier : {{ucwords($agent['user']['full_name'])}} @endif</p>
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
{{--                            @include('prefunds._form')--}}
                            <form action="{{ route('prefund_account', $agent['id']) }}" method="post"
                                  class="form-horizontal">
                                {{ csrf_field() }}
                                <h3>Info Préfunding</h3>
                                <fieldset>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group row">
                                                <label for="soldeP" class="col-lg-4 col-form-label">@if($role === 'administrator') Solde Principale @else Votre solde courant @endif<i class="red">*</i></label>
                                                <div class="col-lg-8">
                                                    <input value="{{$role === 'administrator' ? $agent['solde'] : $agt['solde_utilisable']}}" disabled type="number"
                                                           class="form-control" required name="soldeP" id="soldeP">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group row">
                                                <label for="nomC" class="col-lg-4 col-form-label">Nom Commercial <i class="red">*</i></label>
                                                <div class="col-lg-8">
                                                    <input value="{{$agent['nom_commercial']}}" disabled type="text"
                                                           class="form-control" required name="nomC" id="nomC">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group row">
                                                <label for="soldeC" class="col-lg-4 col-form-label">Solde Courant (Utilisable) <i class="red">*</i></label>
                                                <div class="col-lg-8">
                                                    <input value="{{$agent['solde_utilisable']}}" disabled type="number"
                                                           class="form-control" required name="soldeC" id="soldeC">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group row">
                                                <label for="amount" class="col-lg-4 col-form-label">Montant du Préfund <i class="red">*</i></label>
                                                <div class="col-lg-8">
                                                    <input value="0" type="number"
                                                           class="form-control" required name="amount" id="amount">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                                <input type="hidden" value="{{$agent['solde_utilisable']}}" id="reelAmt">
                                <input type="hidden" value="{{$role === 'administrator' ? $agent['solde'] : $agt['solde_utilisable']}}" id="reelAmtP">
                                <fieldset><br>
                                    <i class="red">(*)</i> Ce sont les champs obligatoires.
                                    <div class="row">
                                        <div class="col-6">
                                        </div>
                                        <div class="col-6">
                                            <div class="button-items" dir="ltr">
                                                <button id="btnPref" class="btn btn-primary btn-icon btn-rounded waves-effect">
                                                    <span class="btn-icon-label"><i class="mdi mdi-check-all mr-2"></i></span> Enregistrer
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                            </form>
                        </div>
                    </div>
                </div> <!-- end col -->
                <div class="col-2"></div>
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
            $('#amount').on('keyup', function() {
                // console.log('hey');
                checkValue(this);
            });
        });

        function checkValue(input) {
            var amount = parseFloat(input.value);
            console.log(amount);
            var soldeP = parseFloat($('#reelAmtP').val());
            var soldeC = parseFloat($('#reelAmt').val());
            console.log(soldeC);
            console.log(soldeP);
            if (amount > 0) {
                if (amount > soldeP) {
                    $('#soldeC').val(soldeC);
                    $('#soldeP').val(soldeP);
                    $('#amount').val(0);
                } else {
                    $('#btnPref').attr("disabled", false);
                    var amt = amount + soldeC;
                    var amtP = soldeP - amount;
                    $('#soldeC').val(amt+'');
                    $('#soldeP').val(amtP+'');
                }
            } else {
                // $('#soldeC').val(soldeC);
                // $('#amount').val(0);
            }

        }
    </script>
@stop