@extends('layouts.base')

@section('title')
    Utilisateurs - Détail
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
                            @if($role ==='administrator') Utilisateur @else Partner Agent @endif
                        </h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('admin') }}"><i
                                            class="mdi mdi-home-outline"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{ route('user_list') }}">Utilisateurs</a></li>
                            <li class="breadcrumb-item active">@if($role ==='administrator') Utilisateur @else
                                    {{$roleUser['display_name']}} @endif</li>
                        </ol>
                    </div>
                    <div class="col-sm-6">
                        <div class="float-right d-none d-md-block">
                            {{--                            {{$userV['user_roles'][0]['role']['name']}}--}}
                            @if($userV['user_roles'][0]['role']['name'] === 'administrator' ||
                            ($userV['user_roles'][0]['role']['name'] === 'cashier' && $role !== 'agent'))
                            @else
                            <!-- <button class="btn_round_add btn-primary arrow-none waves-effect waves-light">
                                <i class="mdi mdi-plus mr-2"></i> <a style="color: #fff"
                                                                     href="{{ route('prefund_account', $userV['agent']['id']) }}"> -->
                            {{--      {{$role==='administrator' ? 'Préfunding Compte Agent' : 'Préfunding Compte Caissier'}} --}}
                                    <!-- </a>
                            </button> -->
                            @endif
                            {{--                                                        {{env('URL_IMG')}}--}}
                            {{--                            {{$userV['picture']}}--}}
                            <!-- <img src="{!! 'https://digipaybackend-env.tmmbpwi52f.us-east-1.elasticbeanstalk.com/'.$userV['picture'] !!}" alt="user"
                                 class="rounded-circle" style="width: 75px; height: 75px"> -->
                        </div>
                    </div>
                </div> <!-- end row -->
            </div>

        <!-- {!! $rle = count($userV['user_roles']) > 0 ? ucwords($userV['user_roles'][0]['role']['name']) : ''  !!} -->
        <!-- end page-title -->
            @if($rle == 'Agent' || $rle == 'Cashier' || $rle == 'Csa' || $rle == 'Finance_manager')
                <div class="row">
                    <div class="col-3">
                        <div class="card mini-stat bg-pattern">
                            <div class="card-body mini-stat-img">
                                <div class="mini-stat-icon">
                                    <i class="dripicons-card bg-soft-primary text-primary float-right h5"></i>
                                </div>
                                <h6 class="text-uppercase mb-6 mt-0">Solde courant</h6>
                                <h5 class="mb-auto">{{$agent['solde']}} XAF</h5>
                            </div>
                        </div>
                    </div>
                    <!-- <div class="col-3">
                        <div class="card mini-stat bg-pattern">
                            <div class="card-body mini-stat-img">
                                <div class="mini-stat-icon">
                                    <i class="dripicons-card bg-soft-primary text-primary float-right h5"></i>
                                </div>
                                <h6 class="text-uppercase mb-6 mt-0">Solde Utilisable</h6>
                                <h5 class="mb-auto">{{$agent['solde_utilisable']}} XAF</h5>
                            </div>
                        </div>
                    </div> -->
                    <div class="col-3">
                        <div class="card mini-stat bg-pattern">
                            <div class="card-body mini-stat-img">
                                <div class="mini-stat-icon">
                                    <i class="dripicons-list bg-soft-primary text-primary float-right h5"></i>
                                </div>
                                <h6 class="text-uppercase mb-6 mt-0">Transactions reussies</h6>
                                <h5 class="mb-auto">{{$nbreTransReussies}}</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="card mini-stat bg-pattern">
                            <div class="card-body mini-stat-img">
                                <div class="mini-stat-icon">
                                    <i class="dripicons-list bg-soft-primary text-primary float-right h5"></i>
                                </div>
                                <h6 class="text-uppercase mb-6 mt-0">total transactions</h6>
                                <h5 class="mb-auto">{{count($transactions)}}</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="card mini-stat bg-pattern">
                            <div class="card-body mini-stat-img">
                                <div class="mini-stat-icon">
                                    <i class="dripicons-card bg-soft-primary text-primary float-right h5"></i>
                                </div>
                                <h6 class="text-uppercase mb-6 mt-0">Total transactions reussies + frais</h6>
                                <h5 class="mb-auto">{{$montantTransReussies}} XAF</h5>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <h4 class="mt-0 header-title">Détails de l'utilisateur</h4>
                                    <table id="datatable-buttons"
                                           class="table table-stripeld table-bordered dt-responsive nowrap"
                                           style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                        <tr>
                                            <th>Role</th>
                                            <th>{{$rle}}</th>
                                        </tr>
                                        @if($rle === 'Cashier')
                                            <tr>
                                                <th>Agent</th>
                                                <th>{{ strtoupper($agent['nom_commercial']) }}</th>
                                            </tr>
                                        @endif                                        
                                        <tr>
                                            <th>Nom</th>
                                            <td>{{ strtoupper($userV['first_name']) }}</td>
                                        </tr>
                                        <tr>
                                            <th>Prenom</th>
                                            <td>{{ ucwords($userV['last_name']) }}</td>
                                        </tr>
                                        <tr>
                                            <th>Email</th>
                                            <td>{{ $userV['email'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Telephone</th>
                                            <td>{{ $userV['phone_number'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Adresse</th>
                                            <td>{{ count($userV['addresses']) > 0 ? $userV['addresses'][0]['name'] : '' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Ville</th>
                                            <td>{{ count($userV['addresses']) > 0 ? $userV['addresses'][0]['town']['name'] : '' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Status</th>
                                            <td>{{ $userV['is_active'] === 0 ? 'Inactif' : 'Actif' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Dernière Connexion</th>
                                            <td>{{ $userV['last_login'] !== null ? $userV['last_login'].date('Y-m-d H:i:s') : '' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Date Création</th>
                                            <td>{{ $userV['created_at'] !== null ? $userV['created_at'] : '' }}</td>
                                            <!-- <td>{{ $userV['created_at'] != null ? $userV['created_at'].date('Y-m-d H:i:s') : '' }}</td> -->
                                        </tr>
                                        </thead>
                                    </table>
                                    <table id="datatable-buttons"
                                           class="table table-stripeld table-bordered dt-responsive nowrap"
                                           style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                        <tr>
                                            <th colspan="2"><a href="{{ route('user_list') }}"><i
                                                            class="mdi mdi-arrow-left-thick mr-2"></i> Retour à la liste</a>
                                            </th>
                                        </tr>
                                        <tr>
                                            <th colspan="2"><a
                                                        href="{{ route('user_show', ['id' => $userV['id']]) }}"><i
                                                            class="mdi mdi-circle-edit-outline mr-2"></i> Modifier</a>
                                            </th>
                                        </tr>
                                        </thead>
                                    </table>

                                </div>
                                <div class="col-6">
                                    @if($rle !== 'Administrator')
                                        <h4 class="mt-0 header-title">Informations du compte</h4>
                                        <table id="datatable-buttons"
                                               class="table table-stripeld table-bordered dt-responsive nowrap"
                                               style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                            <thead>
                                            @if($rle == 'Agent' || $rle == 'Finance_manager')
                                                <tr>
                                                    <th>Logo</th>
                                                    <td>
                                                        @if($userV['agent']['logo'] == ' ')
                                                            <img src="{{ asset('assets/images/avatar.png') }}"
                                                                 alt="logo"
                                                                 class="rounded-circle"
                                                                 style="width: 50px; height: 50px">
                                                        @else
                                                            <img src="{!! 'http://localhost:4500/'.$userV['agent']['logo'] !!}"
                                                                 alt="logo"
                                                                 class="rounded-circle"
                                                                 style="width: 50px; height: 50px">
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Nom Commercial</th>
                                                    <td>{{ ucwords($userV['agent']['nom_commercial']) }}</td>
                                                </tr>
                                            @endif
                                            <tr>
                                                <th>Dépôt total</th>
                                                <td>{{$agent['solde']}} XAF</td>
                                            </tr>
                                            </thead>
                                        </table>
                                    @endif
                                    <h4 class="mt-0 header-title">Les dernières transactions</h4>
                                    <table id="datatable-buttons"
                                           class="table table-stripeld table-bordered dt-responsive nowrap"
                                           style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Emetteur</th>
                                            <th>Beneficiaire</th>
                                            <th>Montant</th>
                                            <th>M. Percu</th>
                                            <th>Pays</th>
                                            <th>Status</th>
                                        </tr>
                                        @forelse($transactions as $trn)
                                            @if($loop->index < $nbreParPage)
                                                <tr>
                                                    <td>{!! $trn['outbound']['bank'] === null ? 'Mobile' : 'Bancaire' !!}</td>
                                                    <td>{{ strtoupper($trn['sender']['user']['first_name']) }} {{ ucwords($trn['sender']['user']['last_name']) }}</td>
                                                    <td>{{ strtoupper($trn['recipient_first_name']) }} {{ ucwords($trn['recipient_last_name']) }}</td>
                                                    <td>{{ $trn['amount'] }} XAF</td>
                                                    <td>{{ $trn['montant_beneficiaire'] }} <span style="font-size: 10px">({{$trn['to_currency']}})</span></td>
                                                    <td>{{ strtoupper($trn['receiving_country']) }}</td>
                                                    <td>{{($trn['etat_transac'] == 'acknowledged') ? 'Pending' : $trn['etat_transac']}}</td>
                                                </tr>
                                            @endif
                                        @empty
                                            <tr>
                                                <td colspan="8">Aucun enregistrement trouvé</td>
                                            </tr>
                                        @endforelse
                                        <tr>
                                            <th colspan="7"><a href="{{ route('transaction_list') }}"><i
                                                            class="mdi mdi-arrow-left-thick mr-2"></i> Voir plus</a>
                                            </th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
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