<form action="{{ $type === 'add' ? route("prefund_add") : route('prefund_edit', $prefund['id']) }}" method="post"
      class="form-horizontal">
    {{ csrf_field() }}
    <h3>Info Préfunding</h3>
    <fieldset>
        @if($role === 'administrator' || $role === 'finance_manager')
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group row">
                        <label for="status" class="col-lg-4 col-form-label">Status <i class="red">*</i></label>
                        <div class="col-lg-8">
                            <select required class="form-control select2" id="status" name="status">
                                <option value="">Selectionnez le status</option>
                                <option value="New">New</option>
                                <option value="Canceled">Canceled</option>
                                <option value="Received">Received</option>
                                <option value="Validated">Validated</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        <div class="row">
            <div class="col-md-6">
                <div class="form-group row">
                    <label for="typeP" class="col-lg-4 col-form-label">Type <i class="red">*</i></label>
                    <div class="col-lg-8">
                        <select required class="form-control select2" id="typeP" name="typeP">
                            <option value="">Selectionnez le type</option>
                            @if($type === 'add')
                                <option value="Bancaire">Bancaire</option>
                            @elseif($type !== 'add' && $prefund['paiement_type'] === 'Bancaire')
                                <option value="Bancaire" selected>Bancaire</option> @else
                                <option value="Bancaire">Bancaire</option>@endif
                            @if($type === 'add')
                                <option value="Mobile">Mobile</option>
                            @elseif($type !== 'add' && $prefund['paiement_type'] === 'Mobile')
                                <option value="Mobile" selected>Mobile</option> @else
                                <option value="Mobile">Mobile</option>@endif
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group row">
                    <label for="amount" class="col-lg-4 col-form-label">Montant <i class="red">*</i></label>
                    <div class="col-lg-8">
                        @if($type === 'add')
                            <input value="{{$type === 'add' ? '' : $prefund['amount']}}" type="number"
                                   class="form-control" required name="amount" id="amount">
                        @else
                            <input value="{{$type === 'add' ? '' : $prefund['amount']}}" disabled type="number"
                                   class="form-control" required name="amount" id="amount">
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group row">
                    <label for="description" class="col-lg-4 col-form-label">Description <i class="red">*</i></label>
                    <div class="col-lg-8">
                        <textarea
                                  class="form-control" required name="description" id="description"
                                  rows="4">{{$type === 'add' ? '' : $prefund['description']}}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </fieldset>
    <h3>Preuve</h3>
    <fieldset>
        <div class="row">
            @if($type === 'add')
                <div class="col-md-12">
                    <div class="form-group row">
                        <label for="imgProve" class="col-4 col-form-label">Preuve de paiement <i
                                    class="red">*</i></label>
                        <div class="col-8 fallback">
                            <input required id="imgProve" name="imgProve" type="file" class="form-control"
                                   accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group row">
                        <img id="avatar" src="{{URL::asset('img/empty_img.png')}}" width="350px" height="200px"
                             style="border-radius: 10%"
                             alt="your image"/>
                    </div>
                </div>
            @else
                <div class="col-md-12">
                    <div class="form-group row">
                        <input type="hidden" id="avatar_url" value="{!! config('keys.url_img').$prefund['prove'] !!}">
                        <img id="avatar" src="{!! config('keys.url_img').$prefund['prove'] !!}" width="350px" height="200px"
                             style="border-radius: 10%; cursor: pointer"
                             alt="your image"/>
                    </div>
                </div>
            @endif
        </div>
    </fieldset>
    <input type="hidden" id="imgPr" name="imgPr" value="">
    <fieldset><br>
        <i class="red">(*)</i> Ce sont les champs obligatoires.
        <div class="row">
            <div class="col-6">
            </div>
            <div class="col-6">
                <div class="button-items" dir="ltr">
                    @if($type === 'add')
                        <button type="reset" class="btn btn-danger btn-icon btn-rounded waves-effect">
                            <span class="btn-icon-label"><i class="mdi mdi-block-helper mr-2"></i></span> Annuler
                        </button>
                    @endif
                    <button class="btn btn-primary btn-icon btn-rounded waves-effect">
                        <span class="btn-icon-label"><i class="mdi mdi-check-all mr-2"></i></span>
                        Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </fieldset>
</form>