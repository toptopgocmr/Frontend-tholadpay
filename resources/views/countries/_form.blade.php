<form action="{{ $type === 'add' ? route("country_add") : route('country_edit', $country['id']) }}" method="post"
      class="form-horizontal">
    {{ csrf_field() }}
    <h3>Info Pays</h3>
    <fieldset>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group row">
                    <label for="textNom" class="col-lg-4 col-form-label">Nom <i class="red">*</i></label>
                    <div class="col-lg-8">
                        <input value="{{$type === 'add' ? '' : $country['full_name']}}" type="text" class="form-control" required name="textNom" id="textNom">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group row">
                    <label for="textCodeP" class="col-lg-4 col-form-label">Code Pays | Telephone <i class="red">*</i></label>
                    <div class="col-lg-8">
                        <input value="{{$type === 'add' ? '' : $country['calling_code']}}" type="text" class="form-control" required name="textCodeP" id="textCodeP">
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group row">
                    <label for="txtNomP2" class="col-lg-4 col-form-label">Nom Pays (2 Lettres) <i class="red">*</i></label>
                    <div class="col-lg-8">
                        <input value="{{$type === 'add' ? '' : $country['iso_3166_2']}}" type="text" class="form-control" required name="txtNomP2" id="txtNomP2">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group row">
                    <label for="txtNomP3" class="col-lg-4 col-form-label">Nom Pays (3 Lettres) <i class="red">*</i></label>
                    <div class="col-lg-8">
                        <input value="{{$type === 'add' ? '' : $country['iso_3166_3']}}" type="text" class="form-control" required name="txtNomP3" id="txtNomP3">
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group row">
                    <label for="txtCurrency" class="col-lg-4 col-form-label">Monnaie <i class="red">*</i></label>
                    <div class="col-lg-8">
                        <input value="{{$type === 'add' ? '' : $country['currency']}}" type="text" class="form-control" required name="txtCurrency" id="txtCurrency">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group row">
                    <label for="txtCurrSym" class="col-lg-4 col-form-label">Symbole Monnaie (Abbréviation) <i class="red">*</i></label>
                    <div class="col-lg-8">
                        <input value="{{$type === 'add' ? '' : $country['currency_symbol']}}" type="text" class="form-control" required name="txtCurrSym" id="txtCurrSym">
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group row">
                    <label for="txtCurrCode" class="col-lg-4 col-form-label">Code Monnaie (3 caracteres) <i class="red">*</i></label>
                    <div class="col-lg-8">
                        <input value="{{$type === 'add' ? '' : $country['currency_code']}}" placeholder="Exemple : EUR" type="text" class="form-control" required name="txtCurrCode" id="txtCurrCode">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group row">
                    <label for="txtZone" class="col-lg-4 col-form-label">Choisir la zone<i class="red">*</i></label>
                    <div class="col-lg-8">
                        <select required class="form-control select2" id="txtZone" name="txtZone">
                            @if($type === 'add')<option value="">Selectionnez la zone</option> @else
                            <option value="{{@$country['zone']['id']}}">{{@$country['zone']['name']}}</option>@endif
                            @foreach($zones as $zon)
                                <option value="{{ $zon['id'] }}">{{ $zon['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            @if($type === 'add')
            <div class="col-md-6">
                <div class="form-group row">
                    <label for="txtIsExchange" class="col-lg-4 col-form-label">Taux change</label>
                    <div class="col-lg-8">
                        <select class="form-control select2" id="txtIsExchange" name="txtIsExchange">
                            <option value="0">Ajouter aux taux de change?</option>
                            <option value="0">NON</option>
                            <option value="1">OUI</option>
                        </select>
                    </div>
                </div>
            </div>
            @endif
        </div>
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
                        <span class="btn-icon-label"><i class="mdi mdi-check-all mr-2"></i></span>
                        @if($type === 'add') Enregistrer @else Modifier @endif
                    </button>
                </div>
            </div>
        </div>
    </fieldset>
</form>