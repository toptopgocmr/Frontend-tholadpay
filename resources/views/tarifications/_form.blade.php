<form action="{{ $type === 'add' ? route("tarif_add") : route('tarif_edit', $tarification['id']) }}" method="post"
      class="form-horizontal">
    {{ csrf_field() }}
    <h3>Info Grille tarifaire</h3>
    <fieldset>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group row">
                    <label for="txtTarif" class="col-lg-4 col-form-label">Tarif A <i class="red">*</i></label>
                    <div class="col-lg-8">
                        <input value="{{$type === 'add' ? '' : $tarification['tarif_1']}}" type="number" class="form-control" required name="txtTarif" id="txtTarif">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group row">
                    <label for="txtTarif2" class="col-lg-4 col-form-label">Tarif B <i class="red">*</i></label>
                    <div class="col-lg-8">
                        <input value="{{$type === 'add' ? '' : $tarification['tarif_2']}}" type="number" class="form-control" required name="txtTarif2" id="txtTarif2">
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group row">
                    <label for="txtFrais" class="col-lg-4 col-form-label">Frais <i class="red">*</i></label>
                    <div class="col-lg-8">
                        <input value="{{$type === 'add' ? '' : $tarification['frais']}}" type="number" class="form-control" required name="txtFrais" id="txtFrais">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group row">
                    <label for="txtZone" class="col-lg-4 col-form-label">Choisir la zone<i class="red">*</i></label>
                    <div class="col-lg-8">
                        <select required class="form-control select2" id="txtZone" name="txtZone">
                            @if($type === 'add')<option value="">Selectionnez la zone</option> @else
                            <option value="{{@$tarification['zone']['id']}}">{{@$tarification['zone']['name']}}</option>@endif
                            @foreach($zones as $zon)
                                <option value="{{ $zon['id'] }}">{{ $zon['name'] }}</option>
                            @endforeach
                        </select>
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