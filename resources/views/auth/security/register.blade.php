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
                        <h4 class="font-18 text-center">Hello !</h4>
                        <p class="text-muted text-center mb-4">Veuillez vous inscrire gratuitement.</p>
                        <form action="/register" method="post" class="form-horizontal">
                            {{ csrf_field() }}
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    @foreach ($errors->all() as $error)
                                        {{ $error }}
                                    @endforeach
                                </div>
                            @endif

                            <div class="form-group">
                                <label for="first_name">First name <i class="red">*</i></label></label>
                                <input type="text" class="form-control" id="first_name" name="_first_name"
                                       value="" required="required" autocomplete="first_name"/>
                            </div>

                            <div class="form-group">
                                <label for="last_name"> Last name</label>
                                <input type="text" class="form-control" id="last_name" name="_last_name"
                                    value="" autocomplete="last_name"/>
                            </div>

                            <div class="form-group">
                                <label for="phone_number">Phone number <i class="red">*</i></label>
                                <input type="number" class="form-control" id="phone_number" name="_phone_number"
                                       value="" autocomplete="phone_number"/>
                            </div>

                            <div class="form-group">
                                <label for="email">Email <i class="red">*</i></label></label>
                                <input type="email" class="form-control" id="email" name="_email"
                                       value="" required="required" autocomplete="email"/>
                            </div>

                            <div class="form-group">
                                <label for="password">Mot de passe <i class="red">*</i></label></label>
                                <input type="password" class="form-control" id="password" name="_password"
                                       required="required" autocomplete="current-password"/>
                            </div>                            

                            <div class="form-group">
                                <label for="conf_password">Confirmer mot de passe <i class="red">*</i></label></label>
                                <input type="password" class="form-control" id="conf_password" name="_conf_password"
                                       required="required" autocomplete="conf_password"/>
                            </div>                           

                            <div class="mt-3">
                                <button class="btn_round btn-secondary btn-block waves-effect waves-light" id="_submit"
                                        name="_submit" type="submit">
                                    S'inscrire
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