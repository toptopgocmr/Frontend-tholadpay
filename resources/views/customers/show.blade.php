@extends('layouts.base')

@section('title')
    Customer - Détail
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
                            Customer
                        </h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('admin') }}"><i
                                            class="mdi mdi-home-outline"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{ route('customer_list') }}">Customers</a></li>
                            <li class="breadcrumb-item active">Customer</li>
                        </ol>
                    </div>
                </div> <!-- end row -->
            </div>

        <!-- {!! $rle = count($userV['user_roles']) > 0 ? ucwords($userV['user_roles'][0]['role']['display_name']) : ''  !!} -->
        <!-- end page-title -->
            @if($rle == 'agent' || $rle == 'cashier' || $rle == 'csa')
                <div class="row">
                    <div class="col-3">
                        <div class="card mini-stat bg-pattern">
                            <div class="card-body mini-stat-img">
                                <div class="mini-stat-icon">
                                    <i class="dripicons-card bg-soft-primary text-primary float-right h5"></i>
                                </div>
                                <h6 class="text-uppercase mb-6 mt-0">Solde courant</h6>
                                <h5 class="mb-auto">{{$userV['agent']['solde']}} XAF</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="card mini-stat bg-pattern">
                            <div class="card-body mini-stat-img">
                                <div class="mini-stat-icon">
                                    <i class="dripicons-card bg-soft-primary text-primary float-right h5"></i>
                                </div>
                                <h6 class="text-uppercase mb-6 mt-0">Solde Utilisable</h6>
                                <h5 class="mb-auto">{{$userV['agent']['solde_utilisable']}} XAF</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="card mini-stat bg-pattern">
                            <div class="card-body mini-stat-img">
                                <div class="mini-stat-icon">
                                    <i class="dripicons-list bg-soft-primary text-primary float-right h5"></i>
                                </div>
                                <h6 class="text-uppercase mb-6 mt-0">Nombre transactions</h6>
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
                                <h6 class="text-uppercase mb-6 mt-0">Montant transaction</h6>
                                <h5 class="mb-auto">0.00 XAF</h5>
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
                                    <h4 class="mt-0 header-title">Détails du customer</h4>
                                    <table id="datatable-buttons"
                                           class="table table-stripeld table-bordered dt-responsive nowrap"
                                           style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                        <tr>
                                            <th>Role</th>
                                            <th>{{ $rle }}</th>
                                        </tr>
                                        @if($rle == 'cashier')
                                            <tr>
                                                <th>Agent</th>
                                                <th>{{ strtoupper($userV['agent']['agent']['nom_commercial']) }}</th>
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
                                            @if ($userV['status'] !== 'Rejected')
                                            <!-- <td style="color:#ECA820">{{ $userV['is_active'] == 0 ? 'Inactif' : 'Actif' }}</td> -->
                                            <td style="color:#ECA820">{{$sender['status']}}</td>
                                            @endif
                                            @if ($userV['status'] === 'Rejected')
                                            <td style="color:#ECA820">Rejected</td>
                                            @endif

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
                                        @if ($userV['status'] !== 'Rejected')
                                            @if ($sender['status'] === 'waiting')                                      
                                            <tr>
                                                <th>
                                                    <a href="#" data-target="#my_modal_val" data-toggle="modal"
                                                    data-id="{{ $userV['id'].'|||'.$userV['full_name'] }}" data-ids="{{$sender['id']}}"
                                                    class="btn btn-success btn-rounded waves-effect validateModal">
                                                    Approuver ce customer
                                                    </a>
                                                </th>
                                                <th>
                                                    <a href="#" data-target="#my_modal" data-toggle="modal"
                                                        data-id="{{ $user['id'].'|||'.$user['full_name'] }}" data-ids="{{$sender['id']}}"
                                                        class="btn btn-danger btn-rounded waves-effect deleteModal">
                                                        Rejeter ce customer
                                                    </a>                                            
                                                </th>
                                            </tr>
                                            @endif
                                        @endif
                                        <tr>
                                            <th colspan="2"><a href="{{ route('customer_list') }}"><i
                                                            class="mdi mdi-arrow-left-thick mr-2"></i> Retour à la liste</a>
                                            </th>
                                        </tr>
                                        <!-- <tr>
                                            <th colspan="2"><a
                                                        href="{{ route('customer_show', ['id' => $userV['id']]) }}"><i
                                                            class="mdi mdi-circle-edit-outline mr-2"></i> Modifier</a>
                                            </th>
                                        </tr> -->
                                        </thead>
                                    </table>

                                </div>
                                <div class="col-6">
                                    @if($rle != 'administrator')
                                        <h4 class="mt-0 header-title">Informations du compte</h4>
                                        <table id="datatable-buttons"
                                               class="table table-stripeld table-bordered dt-responsive nowrap"
                                               style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                            <thead>
                                            @if($rle == 'agent')
                                                <tr>
                                                    <th>Logo</th>
                                                    <td>
                                                        @if($userV['agent']['logo'] == ' ')
                                                            <img src="{{ asset('assets/images/avatar.png') }}"
                                                                 alt="logo"
                                                                 class="rounded-circle"
                                                                 style="width: 50px; height: 50px">
                                                        @else
                                                            <img src="{!! config('keys.url_img').$userV['agent']['logo'] !!}"
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
                                                <td>
                                                    {{ $userV['agent']['solde'] ?? 0 }} XAF
                                                </td>
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
                                            <th title="Montant percu">M. percu</th>
                                            <th>Pays</th>
                                            <th>Status</th>
                                        </tr>
                                        @forelse($transactions as $trn)
                                            <tr>
                                                <td>{!! $trn['outbound']['bank'] === null ? 'Mobile' : 'Bancaire' !!}</td>
                                                <td>{{ strtoupper($trn['sender']['user']['first_name']) }} {{ ucwords($trn['sender']['user']['last_name']) }}</td>
                                                <td>{{ strtoupper($trn['recipient_first_name']) }} {{ ucwords($trn['recipient_last_name']) }}</td>
                                                <td>{{ $trn['amount'] }} XAF</td>
                                                <td>{{ $trn['montant_beneficiaire'] }} <span style="font-size: 10px">({{$trn['to_currency']}})</span></td>
                                                <td>{{ strtoupper($trn['receiving_country']) }}</td>
                                                <td>{{ $trn['etat_transac'] }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8">Aucun enregistrement trouvé</td>
                                            </tr>
                                        @endforelse
                                        <tr>
                                            <th colspan="7"><a href="{{route('customer_transac', $userV['id'])}}"><i
                                                            class="mdi mdi-arrow-left-thick mr-2"></i> Voir plus</a>
                                            </th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

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
@stop

