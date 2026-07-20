<!-- ========== Left Sidebar Start ========== -->
<div class="left side-menu" style="padding-top: 2%">
    <div class="slimscroll-menu" id="remove-scroll">

        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="metismenu" id="side-menu">
                <li class="menu-title">Overview</li>
                <li>
                    <a href="{{route('admin')}}"
                       class="waves-effect {{$menu === 'Dashboard' ? 'mm-active' : ''}}">
                        <i class="ion ion-md-speedometer"></i> <span> Dashboard </span>
                    </a>
                </li>
                @if($role === 'administrator' || $role === 'agent' || $role === 'finance_manager')
                    <li><a href="{{route('user_list')}}"
                           class="waves-effect {{$menu === 'User' ? 'mm-active' : ''}}"><i
                                    class="fas fa-users"></i><span> Utilisateurs</span></a>
                    </li>
                @endif
                <li>
                    <a href="{{route('transaction_list')}}"
                    class="waves-effect {{$menu === 'Transaction' ? 'mm-active' : ''}}"><i
                                class="fas fa-cash-register"></i><span> Transactions</span></a>
                </li>
                @if($role === 'administrator')
                    <li>
                        <a href="{{route('town_list')}}" class="waves-effect {{$menu === 'Town' ? 'mm-active' : ''}}"><i
                                    class="fas fa-building"></i><span> Villes</span></a>
                    </li>
                @endif
                @if($role === 'administrator')
                    <li>
                        <a href="{{route('country_list')}}"
                           class="waves-effect {{$menu === 'Country' ? 'mm-active' : ''}}"><i
                                    class="fas fa-flag"></i><span> Pays</span></a>
                    </li>
                @endif
                @if($role === 'administrator' || $role === 'agent' || $role === 'finance_manager')
                <li>
                    <a href="{{route('prefund_list')}}" class="waves-effect {{$menu === 'Prefund' ? 'mm-active' : ''}}"><i
                                class="fas fa-fax"></i><span> Prefunding</span></a>
                </li>
                @endif
                @if($role === 'administrator' || $role === 'retail_agent')
                <li>
                    <a href="{{route('retailoutlet_list')}}" class="waves-effect {{$menu === 'Retailoutlet' ? 'mm-active' : ''}}"><i
                                class="fas fa-eraser"></i><span> Points de vente</span>
                    </a>
                </li>
                @endif                
                @if($role === 'administrator' || $role === 'technical_support' || $role === 'cashier' || $role === 'csa')
                <li>
                    <a href="{{route('note_list')}}" class="waves-effect {{$menu === 'Note' ? 'mm-active' : ''}}"><i
                                class="fas fa-copy"></i><span> Notes</span>
                    </a>
                </li>
                @endif

                @if($role === 'administrator' || $role === 'agent' || $role === 'cashier' || $role === 'csa' || $role === 'retail_agent' || $role === 'finance_manager' || $role === 'technical_support')
                    <li><a href="{{route('customer_list')}}"
                           class="waves-effect {{$menu === 'Customer' ? 'mm-active' : ''}}"><i
                                    class="fas fa-user"></i><span> Customers</span></a>
                    </li>
                @endif
                
                @if($role === 'administrator')
                <li>
                    <a href="{{route('zone_list')}}" class="waves-effect {{$menu === 'Zone' ? 'mm-active' : ''}}"><i
                                class="fas fa-clipboard"></i><span> Zones</span>
                    </a>
                </li>
                @endif   

                @if($role === 'administrator' || $role === 'agent' || $role === 'cashier' || $role === 'finance_manager')
                <li>
                    <a href="{{route('tarif_list')}}" class="waves-effect {{$menu === 'Tarif' ? 'mm-active' : ''}}">
                        <i class="fas fa-th"></i><span> Grille tarifaire</span>
                    </a>
                </li>
                @endif 

                @if($role === 'administrator' || $role === 'agent' || $role === 'cashier' || $role === 'finance_manager')
                <li>
                    <a href="{{route('currency_list')}}" class="waves-effect {{$menu === 'Currency' ? 'mm-active' : ''}}">
                        <i class="fas fa-flag"></i><span> Taux de change</span>
                    </a>
                </li>
                @endif  

                @if($role === 'administrator' || $role === 'agent' || $role === 'cashier' || $role === 'csa')
                    <li>
                        <a href="{{route('initiate_add')}}" class="waves-effect {{$menu === 'Initiate' ? 'mm-active' : ''}}">
                           <i class="fas fa-key" aria-hidden="true"></i>
                           <span> Reset Password</span>
                        </a>
                    </li>
                @endif

                {{--                <li>--}}
                {{--                    <a href="{{ path('list_countries') }}"--}}
                {{--                       class="waves-effect {{ menu_actif == 'Country' ? 'mm-active' : '' }}"><i class="fas fa-flag"></i><span> Pays</span></a>--}}
                {{--                </li>--}}
            </ul>

        </div>
        <!-- Sidebar -->
        <div class="clearfix"></div>

    </div>
    <!-- Sidebar -left -->
</div>
<!-- Left Sidebar End -->