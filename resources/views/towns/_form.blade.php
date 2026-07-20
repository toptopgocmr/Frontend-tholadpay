<form action="{{ $type === 'add' ? route("town_add") : route('town_edit', $town['id']) }}" method="post"
      class="form-horizontal">
    {{ csrf_field() }}
    <h3>Info Ville</h3>
    <fieldset>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group row">
                    <label for="textNom" class="col-lg-4 col-form-label">Nom <i class="red">*</i></label>
                    <div class="col-lg-8">
                        <input value="{{$type === 'add' ? '' : $town['name']}}" type="text" class="form-control" required name="textNom" id="textNom">
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