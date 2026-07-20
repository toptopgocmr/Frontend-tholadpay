<form action="{{ $type === 'add' ? route("zone_add") : route('zone_edit', $zone['id']) }}" method="post"
      class="form-horizontal">
    {{ csrf_field() }}
    <h3>Info Zone</h3>
    <fieldset>
        <div class="row">
            @if($type === 'add')
                <div class="col-md-12">
                    <div class="form-group row">
                        <label for="txtNomZone" class="col-lg-4 col-form-label">Name <i class="red">*</i></label>
                        <div class="col-lg-8">
                            <input value="" type="text" class="form-control" required name="txtNomZone" id="txtNomZone">
                        </div>
                    </div>
                </div>
            @endif
            <div class="col-md-12">
                <div class="form-group row">
                    <label for="txtDescriptionZone" class="col-lg-4 col-form-label">Description <i class="red">*</i></label>
                    <div class="col-lg-8">
                        <input value="{{$type === 'add' ? '' : $zone['description']}}" type="text" class="form-control" required name="txtDescriptionZone" id="txtDescriptionZone">
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group row">
                    <label for="txtLimitPerDay" class="col-lg-4 col-form-label">Limit amount per day <i class="red">*</i></label>
                    <div class="col-lg-8">
                        <input value="{{$type === 'add' ? '' : $zone['limit_transac_day']}}" type="number" class="form-control" required name="txtLimitPerDay" id="txtLimitPerDay">
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