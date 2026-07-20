<form [action]="route("initiate_add")" method="post" class="form-horizontal">
    {{ csrf_field() }}
    <!-- <h3>Informations </h3> -->
    <fieldset>
        @if($userEdit === null)
        <div class="row">
            <div class="col-md-12">{{ $user_exist }}</div>
            <div class="col-md-6">
                <div class="form-group row">
                    <label for="textType" class="col-lg-3 col-form-label">Type envoi <i class="red">*</i></label>
                    <div class="col-lg-9">
                        <select class="form-control select2" id="textType"  name="textType" required>
                            <option value="">Selectionnez le type</option>
                                <option value="email">Email</option>
                                <option value="sms">SMS</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6" id="blockEmail" style="display: block">
                <div class="form-group row">
                    <label for="textEmail" class="col-lg-3 col-form-label">E-mail <i class="red">*</i></label>
                    <div class="col-lg-9">
                        <input value="" type="email" class="form-control" name="textEmail" id="textEmail">
                    </div>
                </div>
            </div>
            <div class="col-md-6" id="blockPhone" style="display: none">
                <div class="form-group row">
                    <label for="textPhone" class="col-lg-3 col-form-label">Téléphone <i class="red">*</i></label>
                    <div class="col-lg-3">
                        <select class="form-control select2" id="textCodePhone" name="textCodePhone" required>
                            <!-- <option value="+242">+242</option> -->
                            @foreach($countries as $coun)
                                <option value="+{{$coun['calling_code']}}" aria-selected="{{$coun['calling_code'] === '242' ? 'true' : 'false'}}">+ {{$coun['calling_code']}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-6">
                        <input value="" minlength="9" type="number" class="form-control" name="textPhone" id="textPhone">
                    </div>
                </div>
            </div>
        </div>
        @else
        <div class="row">
            <div class="col-12">
                <h4 class="mt-0 header-title">Détails de l'utilisateur</h4>
                <table id="datatable-buttons"
                        class="table table-stripeld table-bordered dt-responsive nowrap"
                        style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                    <thead>
                    <tr>
                        <th>Nom</th>
                        <td>{{ strtoupper($userEdit['first_name']) }}</td>
                    </tr>
                    <tr>
                        <th>Prénom</th>
                        <td>{{ ucwords($userEdit['last_name']) }}</td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td>{{ $userEdit['email'] }}</td>
                    </tr>
                    <tr>
                        <th>Telephone</th>
                        <td>{{ $userEdit['phone_number'] }}</td>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
        <!-- <h5>Veuillez saisir son mot de passe temporaire</h5> -->
        <h5>{{$generatedPasse}}</h5>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group row">
                    <!-- <label for="textPassword" class="col-lg-5 col-form-label">Saisir le mot de passe temporaire <i class="red">*</i></label> -->
                    <div class="col-lg-7">
                        <input value="{{$userEdit['id']}}" type="hidden" class="form-control" name="textIdUser" id="textIdUser">
                        <input value="{{$userEdit['email']}}" type="hidden" class="form-control" name="textEmailDB" id="textEmailDB">
                        <input value="{{$userEdit['phone_number']}}" type="hidden" class="form-control" name="textPhoneDB" id="textPhoneDB">
                        <input value="{{$typeEnvoi}}" type="hidden" class="form-control" name="textTypeEnvoiAfter" id="textTypeEnvoiAfter">
                        <input value="{{$generatedPasse}}" type="hidden" class="form-control" minlength="6" name="textPassword" id="textPassword">
                        <!-- <span style="color:#999999">Le mot de passe doit avoir au moins 6 caracteres</span> -->
                    </div>
                </div>
            </div>            
        </div>        
        @endif    
    </fieldset>
    <fieldset><br>
        <!-- <i class="red">(*)</i> Ce sont les champs obligatoires. -->
        <div class="row">
            <div class="col-md-8">
            </div>
            <div class="col-md-4">
                <div class="button-items" dir="ltr">
                    <!-- <button type="reset" class="btn btn-danger btn-icon btn-rounded waves-effect">
                        <span class="btn-icon-label"><i class="mdi mdi-block-helper mr-2"></i></span> Annuler
                    </button> -->
                    <button class="btn btn-primary btn-icon btn-rounded waves-effect">
                        <span class="btn-icon-label"><i class="mdi mdi-check-all mr-2"></i></span>
                        @if($userEdit === null) Valider @else Envoyer un mot de passe temporaire @endif
                    </button>
                </div>
            </div>
        </div>
    </fieldset>
</form>