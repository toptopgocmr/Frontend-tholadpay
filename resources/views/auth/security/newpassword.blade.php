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
                        <h4 class="font-18 text-center">Helloo ! {{$user['full_name']}}</h4>
                        <p class="text-muted text-center mb-4">Vous avez demandé recemment le changement de votre mot de passe. Veuillez saisir votre nouveau mot de passse</p>
                        <form action="/newpassword/{{$email}}/{{$token}}" method="post" class="form-horizontal">
                            {{ csrf_field() }}
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    @foreach ($errors->all() as $error)
                                        {{ $error }}
                                    @endforeach
                                </div>
                            @endif
                            <div class="form-group">
                                <label for="_password">Nouveau mot de passe</label>
                                <input type="password" class="form-control" id="_password" name="_password"
                                    required="required" autocomplete="current-password"/>
                            </div>

                            <div class="form-group">
                                <label for="_passwordConf">Confirmer le nouveau mot de passe</label>
                                <input type="password" class="form-control" id="_passwordConf" name="_passwordConf"
                                    required="required" autocomplete="current-password"/>
                            </div>

                            <div class="mt-3">
                                <button class="btn_round btn-primary btn-block waves-effect waves-light" id="_submit"
                                        name="_submit" type="submit">
                                    Valider
                                </button>
                            </div>
                        </form>

                    </div>

                </div>
            </div>
            <div class="mt-5 text-center text-white-50">
                <p>© 2026 Send-Paz. Crafted with <i class="mdi mdi-heart text-danger"></i> by <a href="#">Basile NGASSAKI</a>
                </p>
            </div>

        </div>
    </div>
@stop