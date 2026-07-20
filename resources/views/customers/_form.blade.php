<form action="{{ $type === 'add' ? route("customer_add") : route('customer_edit', $userEdit['id']) }}"  method="post" class="form-horizontal">
    {{ csrf_field() }}
    <!-- <section id="tabs" class="project-tab"> -->
        <div class="container11">
            <div class="row">
                <div class="col-md-12">
                    <nav>
                        <div class="nav nav-tabs nav-fill" id="nav-tab" role="tablist">
                            <a class="nav-item nav-link active" onclick="changeTabNav('info')" id="nav-info-tab" data-toggle="tab" href="#nav-info" role="tab" aria-controls="nav-info" aria-selected="true">Informations personnelle</a>
                            <a class="nav-item nav-link" onclick="changeTabNav('adresse')" id="nav-adresse-tab" data-toggle="tab" href="#nav-adresse" role="tab" aria-controls="nav-adresse" aria-selected="false">Adresse</a>
                            <a class="nav-item nav-link" onclick="changeTabNav('pieces')" id="nav-pieces-tab" data-toggle="tab" href="#nav-pieces" role="tab" aria-controls="nav-pieces" aria-selected="false">Pieces justificatives</a>
                        </div>
                    </nav>
                    <div class="tab-content" id="nav-tabContent">
                        <div class="tab-pane fade show active" id="nav-info" role="tabpanel" aria-labelledby="nav-info-tab">
                            <h3>Informations du customer</h3>
                            <!-- </section> -->
                            <fieldset> 
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group row">
                                            <label for="textSexe" class="col-lg-3 col-form-label">Sexe <i class="red">*</i></label>
                                            <div class="col-lg-9">
                                                <select class="form-control select2" id="textSexe" name="textSexe" required>
                                                    @if($type === 'add')<option value="">Selectionnez le sexe</option> @endif
                                                    @if($type === 'edit' && $userEdit['sender'] !== null)
                                                    <option value="{{ $userEdit['sender']['sex']}}">{{($userEdit['sender']['sex'] === 'F') ? 'Féminin' : 'Masculin'}}</option>@endif
                                                    @foreach($sexes as $sex)
                                                        <option value="{{ $sex['value']}}">{{$sex['name']}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group row">
                                            <label for="textCarte" class="col-lg-3 col-form-label">Piece d'identité <i class="red">*</i></label>
                                            <div class="col-lg-9">
                                                <select class="form-control select2" id="textCarte" name="textCarte" required>
                                                    @if($type === 'add')<option value="">Selectionnez la piece</option> @endif
                                                    @if($type === 'edit' && $userEdit['sender'] !== null)
                                                    <option value="{{ $userEdit['sender']['type_id']}}">{{($userEdit['sender']['type_id'] === 'CNI') ? 'Carte nationnal d\'identité ' : 'Passport'}}</option>@endif
                                                    @foreach($typeCartes as $typ)
                                                        <option value="{{ $typ['value']}}">{{$typ['name']}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
                                            <label for="textNumero" class="col-lg-3 col-form-label">Numéro d'identité <i class="red">*</i></label>
                                            <div class="col-lg-9">
                                                <input value="{{$type === 'edit' ? $userEdit['sender']['cni_number'] : ''}}" type="text" class="form-control" required name="textNumero" id="textNumero">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group row">
                                            <label for="textDateDeliv" class="col-lg-3 col-form-label">Date délivrance <i class="red">*</i></label>
                                            <div class="col-lg-9">
                                                <input value="{{$type === 'edit' ? @formaterdateTime($userEdit['sender']['issuer_date']) : ''}}" type="text" readonly class="form-control" required name="textDateDeliv" id="datepicker-13">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">                
                                        <div class="form-group row">
                                            <label for="lieuDeDeliv" class="col-lg-3 col-form-label">Pays délivrance <i class="red">*</i></label>
                                            <div class="col-lg-9">
                                                <input value="{{$type === 'edit' ? $userEdit['sender']['issuer_country'] : ''}}" type="text" class="form-control" required name="lieuDeDeliv" id="lieuDeDeliv">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group row">
                                            <label for="textDateExp" class="col-lg-3 col-form-label">Date d'expiration <i class="red">*</i></label>
                                            <div class="col-lg-9">
                                                <input value="{{$type === 'edit' ? @formaterdateTime($userEdit['sender']['date_exp_id']) : ''}}" type="text" readonly class="form-control" required name="textDateExp" id="datepicker-12">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group row">
                                            <label for="textCodePos" class="col-lg-3 col-form-label">Code postal <i class="red">*</i></label>
                                            <div class="col-lg-9">
                                                <input value="{{$type === 'edit' ? $userEdit['sender']['postal_code'] : ''}}" type="text" class="form-control" required name="textCodePos" id="textCodePos">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @if($type === 'add')
                                <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label for="txtEmail" class="col-lg-3 col-form-label">Email <i class="red">*</i></label>
                                                <div class="col-lg-9">
                                                    <input type="email" class="form-control" required name="txtEmail" id="txtEmail">
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
                                @endif
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
                            <fieldset><br>
                                <i class="red">(*)</i> Ce sont les champs obligatoires.
                                <div class="row">
                                    <div class="col-md-8">
                                    </div>
                                    <div class="col-md-4">
                                        <div class="button-items" dir="ltr"> 
                                            @if($type === 'edit')  
                                            <input type="hidden" id="textUserID" name="textUserID" value="{{$userEdit['id']}}">
                                            <input type="hidden" id="textSenderID" name="textSenderID" value="{{($userEdit['sender'] !== null) ? $userEdit['sender']['id'] : 0}}">
                                            @endif                 
                                            <input type="hidden" id="textRole" name="textRole" value="4|Customer">
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
                        </div>
                        <!-- adresse -->
                        <div class="tab-pane fade" id="nav-adresse" role="tabpanel" aria-labelledby="nav-adresse-tab">
                        <h3>Adresse</h3>
                        <fieldset>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label for="txtTowns" class="col-lg-3 col-form-label">Ville <i
                                                    class="red">*</i></label>
                                        <div class="col-lg-9">
                                            <select required class="form-control select2" id="txtTowns" name="txtTowns">
                                                @if($type === 'add')<option value="">Selectionnez la ville</option> @endif
                                                @if($type === 'edit' && count($userEdit['addresses']) > 0)
                                                <option value="{{$userEdit['addresses'][0]['town']['id']}}">{{$userEdit['addresses'][0]['town']['name']}}</option> @endif
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
                                            <input value="{{$type === 'edit' && count($userEdit['addresses']) > 0 ? $userEdit['addresses'][0]['name'] : ''}}" type="text"
                                                class="form-control" required name="txtAdresse" id="txtAdresse">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label for="txtProvince" class="col-lg-3 col-form-label">Province <i
                                                    class="red">*</i></label>
                                        <div class="col-lg-9">
                                            <input value="{{$type === 'edit' && count($userEdit['addresses']) > 0 ? $userEdit['addresses'][0]['province'] : ''}}" type="text"
                                                class="form-control" required name="txtProvince" id="txtProvince">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                        <fieldset><br>
                            <i class="red">(*)</i> Ce sont les champs obligatoires.
                            <div class="row">
                                <div class="col-md-8">
                                </div>
                                <div class="col-md-4">
                                    <div class="button-items" dir="ltr">                                            
                                        <button class="btn btn-primary btn-icon btn-rounded waves-effect">
                                            <span class="btn-icon-label"><i class="mdi mdi-check-all mr-2"></i></span> @if($type === 'add')
                                                Enregistrer @else Modifier @endif
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                        </div>
                        <!-- pieces justificatives -->
                        <div class="tab-pane fade" id="nav-pieces" role="tabpanel" aria-labelledby="nav-pieces-tab">
                            <input type="hidden" id="valueOfTab" name="valueOfTab" value="info">
                            <input type="hidden" id="imgUser" name="imgUser" value="">
                            <input type="hidden" id="imgRectos" name="imgRectos" value="">
                            <input type="hidden" id="imgVersos" name="imgVersos" value="">
                            <h3 id="titleInfos">Pieces d'identités</h3>
                            <fieldset id="fieldInfos">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group row">
                                            <label for="imgRecto" class="col-lg-4 col-form-label">Piece recto</label>
                                            <div class="col-lg-8 fallback">
                                                <input id="imgRecto" name="imgRecto" type="file" class="form-control"
                                                        accept="image/*">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group row">
                                            @if($type === 'add' || ($type === 'edit' && @$userEdit['sender']['cni_picture'] === null))
                                                <img id="recto" src="{{URL::asset('img/empty_img.png')}}" width="150px" height="150px"
                                                    style="border-radius: 50%"
                                                    alt="Piece Recto"/>
                                            @endif
                                            @if($type === 'edit' && @$userEdit['sender']['cni_picture'] !== null)
                                                <img id="recto" src="{{config('keys.url_img').$userEdit['sender']['cni_picture']}}" width="150px" height="150px"
                                                    style="border-radius: 50%"
                                                    alt="Piece Recto"/>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group row">
                                            <label for="imgVerso" class="col-lg-4 col-form-label">Piece verso</label>
                                            <div class="col-lg-8 fallback">
                                                <input id="imgVerso" name="imgVerso" type="file" class="form-control"
                                                        accept="image/*">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group row">
                                            @if($type === 'add' || ($type === 'edit' && @$userEdit['sender']['justif_picture'] === null))
                                                <img id="verso" src="{{URL::asset('img/empty_img.png')}}" width="150px" height="150px"
                                                    style="border-radius: 50%"
                                                    alt="Piece Verso"/>
                                            @endif
                                            @if($type === 'edit' && @$userEdit['sender']['justif_picture'] !== null)
                                                <img id="verso" src="{{config('keys.url_img').$userEdit['sender']['justif_picture']}}" width="150px" height="150px"
                                                    style="border-radius: 50%"
                                                    alt="Piece Verso"/>
                                            @endif
                                        </div>
                                    </div>
                                </div>        
                            </fieldset>
                            <fieldset><br>
                                <i class="red">(*)</i> Ce sont les champs obligatoires.
                                <div class="row">
                                    <div class="col-md-8">
                                    </div>
                                    <div class="col-md-4">
                                        <div class="button-items" dir="ltr">                                            
                                            <button class="btn btn-primary btn-icon btn-rounded waves-effect">
                                                <span class="btn-icon-label"><i class="mdi mdi-check-all mr-2"></i></span> @if($type === 'add')
                                                    Enregistrer @else Modifier @endif
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    
    
    <!-- @if($type === 'add')
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
                        <img id="avatar" src="{{URL::asset('img/empty_img.png')}}" width="150px" height="150px"
                             style="border-radius: 50%" alt="your image"/>
                    </div>
                </div>
            </div>
        </fieldset>
    @endif -->

</form>

<style>
.project-tab {
    padding: 10%;
    margin-top: -8%;
}
.project-tab #tabs{
    background: #007b5e;
    color: #eee;
}
.project-tab #tabs h6.section-title{
    color: #eee;
}
.project-tab #tabs .nav-tabs .nav-item.show .nav-link, .nav-tabs .nav-link.active {
    color: #0062cc;
    background-color: transparent;
    border-color: transparent transparent #f3f3f3;
    border-bottom: 3px solid !important;
    font-size: 16px;
    font-weight: bold;
}
.project-tab .nav-link {
    border: 1px solid transparent;
    border-top-left-radius: .25rem;
    border-top-right-radius: .25rem;
    color: #0062cc;
    font-size: 16px;
    font-weight: 600;
}
.project-tab .nav-link:hover {
    border: none;
}
.project-tab thead{
    background: #f3f3f3;
    color: #333;
}
.project-tab a{
    text-decoration: none;
    color: #333;
    font-weight: 600;
}
</style>
