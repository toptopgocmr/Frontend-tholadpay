<form action="{{ $type === 'add' ? route("retailoutlet_add") : route('retailoutlet_edit', $retailOutlets['id']) }}" method="post"
      class="form-horizontal">
    {{ csrf_field() }}
    <h3>Info Point de vente</h3>
    <fieldset>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group row">
                    <label for="textNom" class="col-lg-4 col-form-label">Nom du point <i class="red">*</i></label>
                    <div class="col-lg-8">
                        <input value="{{$type === 'add' ? '' : $retailOutlets['name']}}" type="text" class="form-control" required name="textNom" id="textNom">
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group row">
                    <label for="textDescription" class="col-lg-4 col-form-label">Description </label>
                    <div class="col-lg-8">
                        <input value="{{$type === 'add' ? '' : $retailOutlets['description']}}" type="text" class="form-control" name="textDescription" id="textDescription">
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group row">
                    <label for="textTown" class="col-lg-4 col-form-label">Ville <i class="red">*</i></label>
                    <div class="col-lg-8">
                        <select class="form-control select2" id="textTown" required name="textTown">
                            @if($type === 'add')<option value="">Selectionnez la ville</option> @else
                            <option value="{{$retailOutlets['town']['id']}}">{{$retailOutlets['town']['name']}}</option> @endif
                            @foreach($towns as $town)
                                <option value="{{ $town['id']}}">{{ $town['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group row">
                    <label for="textRue" class="col-lg-4 col-form-label">Rue </label>
                    <div class="col-lg-8">
                        <input value="{{$type === 'add' ? '' : $retailOutlets['rue']}}" type="text" class="form-control" name="textRue" id="textRue">
                    </div>
                </div>
            </div>
        </div>
    </fieldset>
    <fieldset><br>
        <i class="red">(*)</i> Ce sont les champs obligatoires.
        <div class="row">
            <div class="col-md-6">
            </div>
            <div class="col-md-6">
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