<!DOCTYPE html>
<html>
<head>
    
    <meta charset="utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <title>Send-Paz | Login</title>
    <meta content="Threestars la solution parfaite pour vos transactions" name="description"/>
    <meta content="TchidTechnologies" name="author"/>
    <link rel="shortcut icon" href="{{ asset('assets/images/tholadpay.png') }}">
    <link href="{{ asset('assets/font-noirPro/stylesheet.css') }}" rel="stylesheet" type="text/css">

    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/css/metismenu.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/css/icons.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/css/console-theme.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/css/login-theme.css') }}" rel="stylesheet" type="text/css">
</head>
<body class="auth-bg-payments">

<!-- Fond clair avec des formes géométriques plates dispersées (carrés arrondis
     pivotés), aux couleurs de la marque — remplace le halo/globe précédent. -->
<div class="auth-bg-shapes" aria-hidden="true">
    <span class="auth-shape auth-shape-blue-lg"></span>
    <span class="auth-shape auth-shape-gold-sm"></span>
    <span class="auth-shape auth-shape-grey-lg"></span>
    <span class="auth-shape auth-shape-grey-md"></span>
    <span class="auth-shape auth-shape-gold-xs"></span>
    <span class="auth-shape auth-shape-teal-sm"></span>
</div>

<div class="account-pages my-4 pt-5">
    <div class="container">
        @yield('content')
    </div>
</div>

<!-- jQuery  -->
<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/metismenu.min.js') }}"></script>
<script src="{{ asset('assets/js/jquery.slimscroll.js') }}"></script>
<script src="{{ asset('assets/js/waves.min.js') }}"></script>

<!-- App js -->
<script src="{{ asset('assets/js/app.js') }}"></script>

</body>

</html>
