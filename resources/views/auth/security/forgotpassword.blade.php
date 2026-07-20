@extends('auth.layout')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 col-xl-5">
            <div class="card bg-pattern shadow-none">
                <div class="card-body">
                    <div class="text-center">
                        <img src="{{asset('assets/images/tholadpay.png')}}" style="max-width: 50%"
                             alt="logo">
                    </div>
                    <div class="p-3">
                        <h4 class="font-18 text-center">Helloo !</h4>
                        <p class="text-muted text-center mb-4">Vous avez oublié votre mot de passe, veuillez saisir votre adresse email.</p>
                        <form action="/passForgot" method="post" class="form-horizontal">
                            {{ csrf_field() }}
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    @foreach ($errors->all() as $error)
                                        {{ $error }}
                                    @endforeach
                                </div>
                            @endif
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="_email"
                                       value="" required="required" autocomplete="email"/>
                            </div>

                            <div class="mt-3">
                                <button class="btn_round btn-primary btn-block waves-effect waves-light" id="_submit"
                                        name="_submit" type="submit">
                                    Valider
                                </button>
                            </div>

                            <div class="text-center">
                                Vous avez déjá un compte, <a href="/login"><i class="mdi mdi-lock"></i> Se connecter</a>
                            </div>
                            <!-- <div class="text-center">
                                Nouveau ?  <a href="/register"> Creer mon compte</a>
                            </div> -->
                        </form>

                    </div>

                </div>
            </div>
            <div class="mt-5 text-center text-white-50">
                <p>© 2026 THOLADPAY. Crafted with <i class="mdi mdi-heart text-danger"></i> by <a href="#">Basile NGASSAKI</a>
                </p>
            </div>

        </div>
    </div>
@stop