<form action="{{ $type === 'add' ? ($role === 'administrator' || $role === 'finance_manager' || $role === 'agent') ? route("user_add") : route("user_add_agent") : route("user_edit", $userEdit['id']) }}"
      method="post"
      class="form-horizontal">
    {{ csrf_field() }}
    <h3>Informations Compte</h3>

  <div class="tab-content">
    <fieldset>
        @if($type === 'add')
            <div class="row">
                @if($role === 'administrator' || $role === 'finance_manager')
                    <div class="col-md-6">
                        <div class="form-group row">
                            <label for="textRole" class="col-lg-3 col-form-label">Rôle <i class="red">*</i></label>
                            <div class="col-lg-9">
                                <select class="form-control select2" id="textRole" name="textRole" required>
                                    <option value="">Selectionnez le rôle</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role['id'].'|'.$role['name'] }}">{{ $role['display_name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="col-md-6" id="blockAgent" style="display: none">
                    <div class="form-group row">
                        <label for="idAgent" class="col-lg-3 col-form-label">Partner Agent <i class="red">*</i></label>
                        <div class="col-lg-9">
                            <select class="form-control select2" id="idAgent" name="idAgent">
                                <option value="">Selectionnez le Partner Managing Agent</option>
                                @foreach($agents as $agt)
                                    <option value="{{ $agt['id'] }}">{{ $agt['user']['full_name'].'['.$agt['nom_commercial'].']' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-6" id="blockFinance" style="display: none">
                    <div class="form-group row">
                        <label for="idFinance" class="col-lg-3 col-form-label">Finance Manager <i class="red">*</i></label>
                        <div class="col-lg-9">
                            <select class="form-control select2" id="idFinance" name="idFinance">
                                <option value="">Sélectionnez le finance manager</option>
                                @foreach($financesM as $finance)
                                    <option value="{{ $finance['id'] }}">{{ $finance['user']['full_name'].'['.$finance['nom_commercial'].']' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="row" style="display: none">
                @if($role === 'administrator')
                    <div class="col-md-6">
                        <div class="form-group row">
                            <label for="textRole" class="col-lg-3 col-form-label">Rôle <i class="red">*</i></label>
                            <div class="col-lg-9">
                                <select class="form-control select2" id="textRole" name="textRole">
                                    <option value="">Sélectionnez le rôle</option>
                                </select>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="col-md-6" id="blockAgent" style="display: none">
                    <div class="form-group row">
                        <label for="idAgent" class="col-lg-3 col-form-label">Partner Agent <i class="red">*</i></label>
                        <div class="col-lg-9">
                            <select class="form-control select2" id="idAgent" name="idAgent">
                                <option value="">Selectionnez le Partner Managing Agent</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            @if($role === 'administrator' || $role === 'agent')
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group row">
                        <label for="textStatus" class="col-lg-3 col-form-label">Status <i class="red">*</i></label>
                        <div class="col-lg-9">
                            <select class="form-control select2" id="textStatus" name="textStatus">
                                <option value="1">Activer</option>
                                <option value="0">Desactiver</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        @endif
        <div class="row">
            <div class="col-md-6">
                <div class="form-group row">
                    <label for="textNom" class="col-lg-3 col-form-label">Nom <i class="red">*</i></label>
                    <div class="col-lg-9">
                        <input value="{{$type === 'edit' ? $userEdit['first_name'] : ''}}" minlength="3" type="text"
                               class="form-control" required name="textNom" id="textNom">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group row">
                    <label for="textPrenom" class="col-lg-3 col-form-label">Prenom <i class="red">*</i></label>
                    <div class="col-lg-9">
                        <input value="{{$type === 'edit' ? $userEdit['last_name'] : ''}}" minlength="3" type="text"
                               class="form-control" required name="textPrenom"
                               id="textPrenom">
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group row">
                    <label for="txtEmail" class="col-lg-3 col-form-label">Email <i class="red">*</i></label>
                    <div class="col-lg-9">
                        <input type="email" value="{{$type === 'edit' ? $userEdit['email'] : ''}}" class="form-control" required name="txtEmail" id="txtEmail">
                        <span class="red">{{ $email_exist }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group row">
                    <label for="txtPhone" class="col-lg-3 col-form-label">Telephone (Sans le code du pays) <i
                                class="red">*</i></label>
                    <div class="col-lg-9">
                        <input value="{{$type === 'edit' ? $userEdit['phone_number'] : ''}}" minlength="5" maxlength="9"
                               type="number" class="form-control" required name="txtPhone"
                               id="txtPhone">
                        <span class="red">{{ $phone_exist }}</span>
                    </div>
                </div>
            </div>
        </div>
        @if($type === 'add')
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group row">
                        <label for="textPassword" class="col-lg-3 col-form-label">Mot de passe <i
                                    class="red">*</i></label>
                        <div class="col-lg-9">
                            <input value="{{$type === 'edit' ? $userEdit['phone_number'] : ''}}" minlength="5"
                                   type="password" class="form-control" required name="textPassword"
                                   id="textPassword">
                            <span id="wrong_pass" class="red"></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group row">
                        <label for="textRePassword" class="col-lg-3 col-form-label">Repeter Mot de passe <i
                                    class="red">*</i></label>
                        <div class="col-lg-9">
                            <input value="{{$type === 'edit' ? $userEdit['phone_number'] : ''}}" minlength="5"
                                   type="password" class="form-control" required name="textRePassword"
                                   id="textRePassword">
                            <span id="match_pass" class="red"></span>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </fieldset>
    <h3>Adresse</h3>
    <fieldset>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group row">
                    <label for="txtTowns" class="col-lg-3 col-form-label">Ville <i
                                class="red">*</i></label>
                    <div class="col-lg-9">
                        <select required class="form-control select2" id="txtTowns" name="txtTowns">
                            @if($type === 'add')<option value="">Selectionnez la ville</option> @else
                            <option value="{{@$userEdit['addresses'][0]['town']['id']}}">{{@$userEdit['addresses'][0]['town']['name']}}</option>@endif
                            @foreach($towns as $town)
                                <option value="{{ $town['id'] }}">{{ $town['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group row">
                    <label for="txtAdresse" class="col-lg-3 col-form-label">Adresse <i
                                class="red">*</i></label>
                    <div class="col-lg-9">
                        <input value="{{$type === 'edit' ? @$userEdit['addresses'][0]['name'] : $monAdresse}}" type="text"
                               class="form-control" required name="txtAdresse" id="txtAdresse">
                    </div>
                </div>
            </div>
        </div>

    </fieldset>
    <h3>Avatar</h3>
    <fieldset>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group row">
                    <label for="imgAvatar" class="col-lg-3 col-form-label">Avatar</label>
                    <div class="col-lg-9 fallback">
                        <input id="imgAvatar" name="imgAvatar" type="file" class="form-control" accept="image/*">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group row">
                    <img id="avatar" src="{{($type === 'edit' && $userEdit['picture'] !== null) ? config('keys.url_img').$userEdit['picture'] : URL::asset('img/empty_img.png')}}" width="150px" height="150px"
                            style="border-radius: 50%" alt="your image"/>
                </div>
            </div>
        </div>
    </fieldset>
    <input type="hidden" id="imgUser" name="imgUser" value="">
    <input type="hidden" id="imgLogos" name="imgLogos" value="">
    <h3 style="display: none" id="titleInfos">Informations supplementaires</h3>
    <fieldset style="display: none" id="fieldInfos">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group row">
                    <label for="txtNomC" class="col-lg-4 col-form-label">Nom Commercial <i
                                class="red">*</i></label>
                    <div class="col-lg-8">
                        <input value="{{$type === 'edit' && isset($userEdit['agent']) && $userEdit['agent'] !== null ?
                        $userEdit['agent']['nom_commercial'] : ''}}" type="text" class="form-control" required
                               name="txtNomC"
                               id="txtNomC">
                    </div>
                </div>
            </div>
        </div>
        @if($type === 'add')
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group row">
                        <label for="imgLogo" class="col-lg-4 col-form-label">Logo Commercial</label>
                        <div class="col-lg-8 fallback">
                            <input id="imgLogo" name="imgLogo" type="file" class="form-control"
                                   accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group row">
                        <img id="logo" src="{{URL::asset('img/empty_img.png')}}" width="150px" height="150px"
                             style="border-radius: 50%"
                             alt="your image"/>
                    </div>
                </div>
            </div>
        @endif
    </fieldset>
    <fieldset><br>
        <i class="red">(*)</i> Ce sont les champs obligatoires.
        <div class="row">
            <div class="col-md-8">
            </div>
            <div class="col-md-4">
                <div class="button-items" dir="ltr">
                    @if($type === 'add')
                        <button type="reset" class="btn btn-danger btn-icon btn-rounded waves-effect">
                            <span class="btn-icon-label"><i class="mdi mdi-block-helper mr-2"></i></span> Annuler
                        </button>
                    @endif
                    <button class="btn btn-primary btn-icon btn-rounded waves-effect">
                        <span class="btn-icon-label"><i class="mdi mdi-check-all mr-2"></i></span> @if($type === 'add')
                            Enregistrer @else Modifier @endif
                    </button>
                </div>
            </div>
        </div>
    </fieldset>
</form>