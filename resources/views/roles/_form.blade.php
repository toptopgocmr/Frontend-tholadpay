<form action="{{ $type === 'add' ? route("role_add") : route('role_edit', $mRole['id']) }}" method="post"
      class="form-horizontal">
    {{ csrf_field() }}
    <h3>Info role</h3>
    <fieldset>
        <div class="row">
            @if($type === 'add')
            <div class="col-md-12">
                <div class="form-group row">
                    <label for="textName" class="col-lg-4 col-form-label">Name <i class="red">*</i></label>
                    <div class="col-lg-8">
                        <input value="{{$type === 'add' ? '' : $mRole['name']}}" type="text" class="form-control" required name="textName" id="textName">
                    </div>
                </div>
            </div>
            @endif
            <div class="col-md-12">
                <div class="form-group row">
                    <label for="textDisplayName" class="col-lg-4 col-form-label">Display Name <i class="red">*</i></label>
                    <div class="col-lg-8">
                        <input value="{{$type === 'add' ? '' : $mRole['display_name']}}" type="text" class="form-control" required name="textDisplayName" id="textDisplayName">
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group row">
                    <label for="description" class="col-lg-4 col-form-label">Description <i class="red">*</i></label>
                    <div class="col-lg-8">
                        <input value="{{$type === 'add' ? '' : $mRole['description']}}" type="text" class="form-control" required name="description" id="description">
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
                        <span class="btn-icon-label"><i class="mdi mdi-check-all mr-2"></i></span> Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </fieldset>
</form>