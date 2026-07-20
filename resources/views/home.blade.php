@extends('layouts.base')
@section('title')
    Dashboard
@stop
@section('stylesheets')
    <!-- datepicker -->
    <link href="{{ asset('assets/plugins/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css') }}"
          rel="stylesheet">
    <!-- jvectormap -->
    <link href="{{ asset('assets/plugins/jvectormap/jquery-jvectormap-2.0.2.css') }}" rel="stylesheet">
@stop
@section('content')
    <div class="content dasboard-content">
        <div class="container-fluid">
            <div class="page-title-box">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <h4 class="page-title">Tableau</h4>
                        <ol class="breadcrumb">
                            <!-- <li class="breadcrumb-item"><a href="javascript:void(0);"><i class="mdi mdi-home-outline"></i></a></li> -->
                            <li class="breadcrumb-item active">Bon retour parmi nous!</li>
                        </ol>
                    </div>
                </div> <!-- end row -->
            </div>
            <!-- end page-title -->
            @if($role === 'administrator' || $role === 'finance_manager')
                <div class="row">
                    <div class="col-lg-4">
                        <div class="card mini-stat bg-pattern">
                            <div class="card-body mini-stat-img">
                                <div class="mini-stat-icon">
                                    <i class="dripicons-broadcast bg-soft-primary text-primary float-right h4"></i>
                                </div>
                                <h6 class="text-uppercase mb-3 mt-0">Utilisateurs</h6>
                                <h5 class="mb-3">{{$admins}}</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card mini-stat bg-pattern">
                            <div class="card-body mini-stat-img">
                                <div class="mini-stat-icon">
                                    <i class="dripicons-broadcast bg-soft-primary text-primary float-right h4"></i>
                                </div>
                                <h6 class="text-uppercase mb-3 mt-0">Partner Managing Agent</h6>
                                <h5 class="mb-3">{{$agents}}</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card mini-stat bg-pattern">
                            <div class="card-body mini-stat-img">
                                <div class="mini-stat-icon">
                                    <i class="dripicons-broadcast bg-soft-primary text-primary float-right h4"></i>
                                </div>
                                <h6 class="text-uppercase mb-3 mt-0">Partner Service Agent</h6>
                                <h5 class="mb-3">{{$cashiers}}</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card mini-stat bg-pattern">
                            <div class="card-body mini-stat-img">
                                <div class="mini-stat-icon">
                                    <i class="dripicons-box bg-soft-primary text-primary float-right h4"></i>
                                </div>
                                <h6 class="text-uppercase mb-3 mt-0">Total montant envoyé</h6>
                                <h5 class="mb-3">{{$revenues}} XAF</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card mini-stat bg-pattern">
                            <div class="card-body mini-stat-img">
                                <div class="mini-stat-icon">
                                    <i class="dripicons-tags bg-soft-primary text-primary float-right h4"></i>
                                </div>
                                <h6 class="text-uppercase mb-3 mt-0">Total des frais</h6>
                                <h5 class="mb-3">{{$fraisEnvoi}} XAF</h5>
                            </div>
                        </div>
                    </div>                    
                    <div class="col-lg-4">
                        <div class="card mini-stat bg-pattern">
                            <div class="card-body mini-stat-img">
                                <div class="mini-stat-icon">
                                    <i class="dripicons-broadcast bg-soft-primary text-primary float-right h4"></i>
                                </div>
                                <h6 class="text-uppercase mb-3 mt-0">Transactions reussies</h6>
                                <h5 class="mb-3">{{$trans}}</h5>
                            </div>
                        </div>
                    </div>
                    <!-- <div class="col-lg-4">
                        <div class="card mini-stat bg-pattern">
                            <div class="card-body mini-stat-img">
                                <div class="mini-stat-icon">
                                    <i class="dripicons-tags bg-soft-primary text-primary float-right h4"></i>
                                </div>
                                <h6 class="text-uppercase mb-3 mt-0">Moyenne Montant Transactions</h6>
                                <h5 class="mb-3">{{$moy}} XAF</h5>
                            </div>
                        </div>
                    </div> -->
                    @if($role === 'administrator')
                        <div class="col-lg-4">
                            <div class="card mini-stat bg-pattern">
                                <div class="card-body mini-stat-img">
                                    <div class="mini-stat-icon">
                                        <i class="dripicons-broadcast bg-soft-primary text-primary float-right h4"></i>
                                    </div>
                                    <h6 class="text-uppercase mb-3 mt-0">Transactions en attente</h6>
                                    <h5 class="mb-3">{{$transAttente}}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card mini-stat bg-pattern">
                                <div class="card-body mini-stat-img">
                                    <div class="mini-stat-icon">
                                        <i class="dripicons-box bg-soft-primary text-primary float-right h4"></i>
                                    </div>
                                    <h6 class="text-uppercase mb-3 mt-0">Transactions annulées</h6>
                                    <h5 class="mb-3">{{$transCancel}}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card mini-stat bg-pattern">
                                <div class="card-body mini-stat-img">
                                    <div class="mini-stat-icon">
                                        <i class="dripicons-box bg-soft-primary text-primary float-right h4"></i>
                                    </div>
                                    <h6 class="text-uppercase mb-3 mt-0">Transactions échouées</h6>
                                    <h5 class="mb-3">{{$transEchec}}</h5>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card mini-stat bg-pattern">
                                <div class="card-body mini-stat-img">
                                    <div class="mini-stat-icon">
                                        <i class="dripicons-box bg-soft-primary text-primary float-right h4"></i>
                                    </div>
                                    <h6 class="text-uppercase mb-3 mt-0">Total solde disponible</h6>
                                    <h5 class="mb-3">{{$totalSoldesDisponible}} XAF</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card mini-stat bg-pattern">
                                <div class="card-body mini-stat-img">
                                    <div class="mini-stat-icon">
                                        <i class="dripicons-wallet bg-soft-primary text-primary float-right h4"></i>
                                    </div>
                                    <h6 class="text-uppercase mb-3 mt-0">Solde compte Peex</h6>
                                    @if($peexSolde !== null)
                                        <h5 class="mb-3">{{$peexSolde}} XAF
                                            @if($peexActive === false)
                                                <span class="badge badge-danger" style="font-size: 11px; vertical-align: middle;">Compte inactif</span>
                                            @endif
                                        </h5>
                                    @else
                                        <h5 class="mb-3 text-muted" style="font-size: 14px;">Indisponible (Peex injoignable)</h5>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card mini-stat bg-pattern">
                                <div class="card-body mini-stat-img">
                                    <div class="mini-stat-icon">
                                        <i class="dripicons-tags bg-soft-primary text-primary float-right h4"></i>
                                    </div>
                                    <h6 class="text-uppercase mb-3 mt-0">Finance managers</h6>
                                    <h5 class="mb-3">{{$nbreFinances}}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card mini-stat bg-pattern">
                                <div class="card-body mini-stat-img">
                                    <div class="mini-stat-icon">
                                        <i class="dripicons-broadcast bg-soft-primary text-primary float-right h4"></i>
                                    </div>
                                    <h6 class="text-uppercase mb-3 mt-0">Les clients</h6>
                                    <h5 class="mb-3">{{$nbreCustomers}}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card mini-stat bg-pattern">
                                <div class="card-body mini-stat-img">
                                    <div class="mini-stat-icon">
                                        <i class="dripicons-box bg-soft-primary text-primary float-right h4"></i>
                                    </div>
                                    <h6 class="text-uppercase mb-3 mt-0">Total Prefundings validés</h6>
                                    <h5 class="mb-3">{{$prefundValid}} XAF</h5>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    @if($role === 'finance_manager')
                        <div class="col-lg-4">
                            <div class="card mini-stat bg-pattern">
                                <div class="card-body mini-stat-img">
                                    <div class="mini-stat-icon">
                                        <i class="dripicons-box bg-soft-primary text-primary float-right h4"></i>
                                    </div>
                                    <h6 class="text-uppercase mb-3 mt-0">Prefunding Validé</h6>
                                    <h5 class="mb-3">{{$prefundValid}} XAF</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card mini-stat bg-pattern">
                                <div class="card-body mini-stat-img">
                                    <div class="mini-stat-icon">
                                        <i class="dripicons-box bg-soft-primary text-primary float-right h4"></i>
                                    </div>
                                    <h6 class="text-uppercase mb-3 mt-0">Prefunding en attente</h6>
                                    <h5 class="mb-3">{{$prefundAnnul}} XAF</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card mini-stat bg-pattern">
                                <div class="card-body mini-stat-img">
                                    <div class="mini-stat-icon">
                                        <i class="dripicons-tags bg-soft-primary text-primary float-right h4"></i>
                                    </div>
                                    <h6 class="text-uppercase mb-3 mt-0">Prefunding échoué</h6>
                                    <h5 class="mb-3">{{$prefundEchec}} XAF</h5>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
            @if($role === 'agent' || $role === 'csa' || $role === 'cashier')
                <div class="row">
                    @if($role === 'agent')
                    <div class="col-lg-4">
                        <div class="card mini-stat bg-pattern">
                            <div class="card-body mini-stat-img">
                                <div class="mini-stat-icon">
                                    <i class="dripicons-broadcast bg-soft-primary text-primary float-right h4"></i>
                                </div>
                                <h6 class="text-uppercase mb-3 mt-0">Partner Service Agent</h6>
                                <h5 class="mb-3">{{$cashiers}}</h5>
                            </div>
                        </div>
                    </div>
                    @endif
                    <div class="col-lg-4">
                        <div class="card mini-stat bg-pattern">
                            <div class="card-body mini-stat-img">
                                <div class="mini-stat-icon">
                                    <i class="dripicons-box bg-soft-primary text-primary float-right h4"></i>
                                </div>
                                <h6 class="text-uppercase mb-3 mt-0">Solde Disponible</h6>
                                <h5 class="mb-3">{{$totalSoldesDisponible}} XAF</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card mini-stat bg-pattern">
                            <div class="card-body mini-stat-img">
                                <div class="mini-stat-icon">
                                    <i class="dripicons-tags bg-soft-primary text-primary float-right h4"></i>
                                </div>
                                <h6 class="text-uppercase mb-3 mt-0">Moyenne Transactions</h6>
                                <h5 class="mb-3">{{$moy}} XAF</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card mini-stat bg-pattern">
                            <div class="card-body mini-stat-img">
                                <div class="mini-stat-icon">
                                    <i class="dripicons-box bg-soft-primary text-primary float-right h4"></i>
                                </div>
                                <h6 class="text-uppercase mb-3 mt-0">Total montant envoyé</h6>
                                <h5 class="mb-3">{{$revenues}} XAF</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card mini-stat bg-pattern">
                            <div class="card-body mini-stat-img">
                                <div class="mini-stat-icon">
                                    <i class="dripicons-tags bg-soft-primary text-primary float-right h4"></i>
                                </div>
                                <h6 class="text-uppercase mb-3 mt-0">Total des frais</h6>
                                <h5 class="mb-3">{{$fraisEnvoi}} XAF</h5>
                            </div>
                        </div>
                    </div>
                    @if($role === 'cashier')
                        <div class="col-lg-4">
                            <div class="card mini-stat bg-pattern">
                                <div class="card-body mini-stat-img">
                                    <div class="mini-stat-icon">
                                        <i class="dripicons-tags bg-soft-primary text-primary float-right h4"></i>
                                    </div>
                                    <h6 class="text-uppercase mb-3 mt-0">Mes clients</h6>
                                    <h5 class="mb-3">{{$nbreCustomers}}</h5>
                                </div>
                            </div>
                        </div>
                    @endif
                    @if($role === 'agent')
                        <div class="col-lg-4">
                            <div class="card mini-stat bg-pattern">
                                <div class="card-body mini-stat-img">
                                    <div class="mini-stat-icon">
                                        <i class="dripicons-broadcast bg-soft-primary text-primary float-right h4"></i>
                                    </div>
                                    <h6 class="text-uppercase mb-3 mt-0">Transactions réussies</h6>
                                    <h5 class="mb-3">{{$trans}}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card mini-stat bg-pattern">
                                <div class="card-body mini-stat-img">
                                    <div class="mini-stat-icon">
                                        <i class="dripicons-broadcast bg-soft-primary text-primary float-right h4"></i>
                                    </div>
                                    <h6 class="text-uppercase mb-3 mt-0">Transactions en attente</h6>
                                    <h5 class="mb-3">{{$transAttente}}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card mini-stat bg-pattern">
                                <div class="card-body mini-stat-img">
                                    <div class="mini-stat-icon">
                                        <i class="dripicons-box bg-soft-primary text-primary float-right h4"></i>
                                    </div>
                                    <h6 class="text-uppercase mb-3 mt-0">Transactions annulées</h6>
                                    <h5 class="mb-3">{{$transCancel}}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card mini-stat bg-pattern">
                                <div class="card-body mini-stat-img">
                                    <div class="mini-stat-icon">
                                        <i class="dripicons-box bg-soft-primary text-primary float-right h4"></i>
                                    </div>
                                    <h6 class="text-uppercase mb-3 mt-0">Transactions échouées</h6>
                                    <h5 class="mb-3">{{$transEchec}}</h5>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
            @if($role === 'technical_support')
                <div class="row">                    
                    <div class="col-lg-4">
                        <div class="card mini-stat bg-pattern">
                            <div class="card-body mini-stat-img">
                                <div class="mini-stat-icon">
                                    <i class="dripicons-box bg-soft-primary text-primary float-right h4"></i>
                                </div>
                                <h6 class="text-uppercase mb-3 mt-0">Total montant envoyé</h6>
                                <h5 class="mb-3">{{$revenues}} XAF</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card mini-stat bg-pattern">
                            <div class="card-body mini-stat-img">
                                <div class="mini-stat-icon">
                                    <i class="dripicons-tags bg-soft-primary text-primary float-right h4"></i>
                                </div>
                                <h6 class="text-uppercase mb-3 mt-0">Total des frais</h6>
                                <h5 class="mb-3">{{$fraisEnvoi}} XAF</h5>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            <!-- end row -->

            <div class="row">
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="mt-0 header-title mb-4">Dernières Transactions</h4>
                            <div class="table-responsive">
                                <table id="datatable-buttons"
                                       class="table table-stripeld table-bordered dt-responsive nowrap"
                                       style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Emetteur</th>
                                        <th>Beneficiaire</th>
                                        <th>Montant <span style="font-size: 10px">(XAF)</span></th>
                                        <th>M. percu</th>
                                        <th>Frais <span style="font-size: 10px">(XAF)</span></th>
                                        <th>Pays</th>
                                        <th>Status</th>
                                    </tr>
                                    @forelse($transactions as $trn)
                                        @if($loop->index < 10)
                                        <tr>
                                            <td>{!! $trn['outbound']['bank'] === null ? 'Mobile' : 'Bancaire' !!}</td>
                                            <td>{{ strtoupper($trn['user']['first_name']) }} {{ ucwords($trn['user']['last_name']) }}</td>
                                            <td>{{ strtoupper($trn['recipient_first_name']) }} {{ ucwords($trn['recipient_last_name']) }}</td>
                                            <td>{{ $trn['amount'] }}</td>
                                            <td>{{ $trn['montant_beneficiaire']}} <span style="font-size: 10px">({{$trn['to_currency']}})</span></td>
                                            <td>{{ $trn['fees'] }}</td>
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
                                        <th colspan="6"><a href="{{ route('transaction_list') }}"><i
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
            </div>
            <!-- end row -->

        </div>
        <!-- container-fluid -->

    </div>
@stop

@section('body_right')
    <div class="right-sidebar d-none d-xl-block">
        <div class="slimscroll-menu">
            <div class="px-4 pt-4">
                <div class="card user-wid text-center overflow-hidden">
                    <div class="p-4 bg-lighten-danger"></div>
                    <div class="mx-3">
                        <div class="bg-white user-wid-content p-1 rounded">
                            <div class="user-img">
                                @if( $user['picture'] != null )
                                    <img src="{{ config('keys.url_img') . $user['picture'] }}" alt="user"
                                         class="rounded-circle thumb-md img-fluid">
                                @else
                                    <img src="{{asset('uploads/avatars/avatar.png')}}" alt="user"
                                         class="rounded-circle thumb-md img-fluid">
                                @endif
                            </div>
                            <h5 class="font-14 mb-1">
                                <a href="javascript: void(0);">{{ strtoupper($user['first_name']) }} {{ ucwords($user['last_name']) }}</a>
                            </h5>
                            @if(count($user['roles']) > 0)
                                <p class="text-muted mb-2"><small>{{ strtoupper($user['roles'][0]['display_name']) }}</small></p>
                            @else
                                <p class="text-muted mb-2"><small>{{ strtoupper($role) }}</small></p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h5 class="font-14">Calendrier</h5>

                    <div class="dashboard-date-pick" id="date-pick-widget" data-provide="datepicker-inline"></div>
                </div>

            </div>
        </div>
    </div>
@stop


@section('javascripts')
    <!-- datepicker -->
    <script src="{{ asset('assets/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js') }}"></script>
    <!-- vector map  -->
    <script src="{{ asset('assets/plugins/jvectormap/jquery-jvectormap-2.0.2.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/jvectormap/jquery-jvectormap-world-mill-en.js') }}"></script>

    <!-- apexcharts -->
    <script src="{{ asset('assets/plugins/apexcharts/apexcharts.min.js') }}"></script>

    <!-- Peity JS -->
    <script src="{{ asset('assets/plugins/peity-chart/jquery.peity.min.js') }}"></script>

    <script src="{{ asset('assets/pages/dashboard.js') }}"></script>
@stop