@php
    // Libellés de rôle en français pour l'affichage (les rôles bruts viennent
    // de l'API sous forme de clés anglaises : administrator, agent, etc.)
    $roleLabels = [
        'administrator' => 'Administrateur',
        'agent' => 'Agent',
        'cashier' => 'Caissier',
        'csa' => 'CSA',
        'finance_manager' => 'Gestionnaire financier',
        'technical_support' => 'Support technique',
        'retail_agent' => 'Agent point de vente',
    ];
    $roleLabel = $roleLabels[$role ?? ''] ?? strtoupper(str_replace('_', ' ', $role ?? ''));
@endphp
<!-- Top Bar Start -->
<div class="topbar">

    <!-- LOGO -->
    <div class="topbar-left">
        <a href="#" class="logo">
                        <span class="logo-light">
                            <img src="{{ asset('assets/images/tholadpay-transparent.png') }}" alt=""
                                 style="max-height: 42px; width: auto;">
                        </span>
            <span class="logo-sm">
                                <img src="{{ asset('assets/images/tholadpay-icon.png') }}" alt=""
                                     style="max-height: 34px; width: auto;">
                            </span>
        </a>
    </div>

    <nav class="navbar-custom">
        <ul class="navbar-right list-inline float-right mb-0">
            <li class="dropdown notification-list list-inline-item d-none d-md-inline-block">
                <form role="search" class="app-search">
                    <div class="form-group mb-0">
                        <input type="text" class="form-control" placeholder="Search..">
                        <button type="submit"><i class="fa fa-search"></i></button>
                    </div>
                </form>
            </li>

            <!-- full screen -->
            <li class="dropdown notification-list list-inline-item d-none d-md-inline-block">
                <a class="nav-link waves-effect" href="#" id="btn-fullscreen">
                    <i class="ion ion-md-qr-scanner noti-icon"></i>
                </a>
            </li>

            <li class="dropdown notification-list list-inline-item">
                <div class="dropdown notification-list nav-pro-img">
                    <a class="dropdown-toggle nav-link arrow-none nav-user topbar-user-trigger" data-toggle="dropdown"
                       href="#" role="button" aria-haspopup="false" aria-expanded="false">
                        @if( !empty($user['picture']) )
                            <img src="{{ config('keys.url_img') . $user['picture'] }}" alt="avatar"
                                 class="rounded-circle topbar-avatar">
                        @else
                            <img src="{{asset('uploads/avatars/avatar.png')}}" alt="avatar"
                                 class="rounded-circle topbar-avatar">
                        @endif
                        <span class="topbar-user-meta d-none d-md-inline-block">
                            <span class="topbar-user-role">{{ strtoupper($roleLabel) }}</span>
                        </span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right profile-dropdown ">
                        <div class="dropdown-item-text topbar-user-card">
                            <div class="topbar-user-card-name">{{ strtoupper($user['first_name'] ?? '') }} {{ ucwords($user['last_name'] ?? '') }}</div>
                            <div class="topbar-user-card-role">{{ strtoupper($roleLabel) }}</div>
                            @if(session('login_time'))
                                <div class="topbar-user-card-login">
                                    <i class="mdi mdi-calendar-outline"></i> {{ session('login_time')->format('d/m/Y') }}
                                    &nbsp;·&nbsp;
                                    <i class="mdi mdi-clock-outline"></i> {{ session('login_time')->format('H:i') }}
                                </div>
                            @endif
                        </div>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#"><i class="mdi mdi-account-circle"></i> Profile</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="{{ url('logout') }}"><i
                                    class="mdi mdi-power text-danger"></i> Logout</a>
                    </div>
                </div>
            </li>

        </ul>

        <ul class="list-inline menu-left mb-0">
            <li class="float-left">
                <button class="button-menu-mobile open-left waves-effect">
                    <i class="mdi mdi-menu"></i>
                </button>
            </li>
        </ul>

    </nav>

</div>
<!-- Top Bar End -->