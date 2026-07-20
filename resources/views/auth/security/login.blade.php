@extends('auth.layout')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 col-xl-5">
            <div class="card shadow-none auth-card-vertical">
                <div class="card-body auth-form-col">
                    <div class="text-center auth-vertical-header">
                        <img src="{{asset('assets/images/tholadpay-transparent.png')}}" alt="logo" class="auth-vertical-logo">
                        <h4 class="auth-vertical-title">Bienvenue à bord !</h4>
                        <p class="auth-vertical-text">Identifiez-vous pour démarrer l’expérience sur la plateforme THOLADPAY.</p>
                    </div>

                    @if ($message = Session::get('success'))
                        <div class="alert alert-success alert-block">
                            <button type="button" class="close" data-dismiss="alert">×</button> <strong>{{ $message }}</strong>
                        </div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            @foreach ($errors->all() as $error)
                                {{ $error }}
                            @endforeach
                        </div>
                    @endif

                    <form action="/login" method="post" class="form-horizontal">
                        {{ csrf_field() }}

                        <div class="form-group">
                            <label for="email">Email</label>
                            <div class="auth-input-icon-group">
                                <i class="mdi mdi-email-outline"></i>
                                <input type="email" class="form-control" id="email" name="_email"
                                       value="" required="required" autocomplete="email"/>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password">Mot de passe</label>
                            <div class="auth-input-icon-group">
                                <i class="mdi mdi-lock-outline"></i>
                                <input type="password" class="form-control" id="password" name="_password"
                                       required="required" autocomplete="current-password"/>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center auth-form-row">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="remember_me" name="_remember_me"
                                       value="on"/>
                                <label for="remember_me" class="custom-control-label">Se souvenir de moi</label>
                            </div>
                            <a href="/passForgot" class="auth-forgot-link">Mot de passe oublié ?</a>
                        </div>

                        <button class="btn_round btn-primary btn-block waves-effect waves-light auth-submit-btn" id="_submit"
                                name="_submit" type="submit">
                            Connexion <i class="mdi mdi-arrow-right"></i>
                        </button>

                        <!-- <div class="text-center mt-3">
                            Nouveau ?  <a href="/register"> Creer mon compte</a>
                        </div> -->
                    </form>
                </div>
            </div>
            <div class="mt-4 text-center text-white-50">
                <p>© 2026 THOLADPAY. Crafted with <i class="mdi mdi-heart text-danger"></i> by <a href="#">Basile NGASSAKI</a>
                </p>
            </div>
        </div>
    </div>
@stop
