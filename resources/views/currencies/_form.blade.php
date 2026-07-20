<form action="{{ $type === 'add' ? route("currency_add") : route('currency_edit', $currency['id']) }}" method="post"
      class="form-horizontal">
    {{ csrf_field() }}
    <h3>Info taux de change</h3>
    <fieldset>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group row">
                    <label for="textCode" class="col-lg-4 col-form-label">Code <i class="red">*</i></label>
                    <div class="col-lg-8">
                        <input value="{{$type === 'add' ? '' : $currency['code']}}" type="text" class="form-control" required name="textCode" id="textCode">
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group row">
                    <label for="textSymbol" class="col-lg-4 col-form-label">Symbol </label>
                    <div class="col-lg-8">
                        <input value="{{$type === 'add' ? '' : $currency['symbol']}}" type="text" class="form-control" name="textSymbol" id="textSymbol">
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group row">
                    <label for="textRate" class="col-lg-4 col-form-label">Rate </label>
                    <div class="col-lg-8">
                        <input value="{{$type === 'add' ? '' : $currency['rate']}}" type="text" class="form-control" name="textRate" id="textRate">
                        <!-- <span>Mettre en pourcentage (exemple : 0.08 pour 8%)</span> -->
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group row">
                    <label for="textFees" class="col-lg-4 col-form-label">Fees </label>
                    <div class="col-lg-8">
                        <input value="{{$type === 'add' ? '' : $currency['fees']}}" type="text" class="form-control" name="textFees" id="textFees">
                        <span>Mettre en pourcentage (exemple : 0.08 pour 8%)</span>
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