<form action="{{ $type === 'add' ? route("note_add") : route('note_edit', $note['id']) }}" method="post"
      class="form-horizontal">
    {{ csrf_field() }}
    <h3>Info Notes</h3>
    <fieldset>
        <div class="col-md-12">
            <div class="form-group row">
                <label for="textTransaction" class="col-lg-3 col-form-label">Choisir la ransaction <i class="red">*</i></label>
                <div class="col-lg-9">
                    <select class="form-control select2" id="textTransaction" name="textTransaction" required>
                        <!-- <option value="">Selectionnez la transation</option> -->
                        @if($type === 'add')<option value="">Selectionnez la transation</option> @else
                            <option value="{{$transactionNote['id']}}">{{ $transactionNote['ranking'].' | '.$transactionNote['recipient_first_name'].' '.$transactionNote['recipient_last_name'].' | '.$transactionNote['transaction_reference'] }}</option> @endif
                        @foreach($transactions as $transac)
                            <option value="{{$transac['id']}}">{{ $transac['ranking'].' | '.$transac['recipient_first_name'].' '.$transac['recipient_last_name'].' | '.$transac['transaction_reference'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group row">
                    <label for="textNote" class="col-lg-3 col-form-label">Entrer la note <i class="red">*</i></label>
                    <div class="col-lg-9">
                        <textarea  cols="40" rows="4" type="text" class="form-control" required name="textNote" id="textNote">
                        {{$type === 'add' ? '' : $note['detail']}}
                        </textarea>
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
                        <span class="btn-icon-label"><i class="mdi mdi-check-all mr-2"></i></span> Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </fieldset>
</form>