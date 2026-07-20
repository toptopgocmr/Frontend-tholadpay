<form action="{{ $type === 'add' ? route("transaction_add") : route('transaction_edit', $transaction['id']) }}" method="post"
      class="form-horizontal">
    {{ csrf_field() }}
    <h3>Info transaction</h3>
    <div id="step1">
        <!-- <h3>Validation Bénéficiaire & Expéditeur (1/3)</h3> -->
        <h5>Informations Bénéficiaire</h5>
        <fieldset>
            <div class="row">
                <div class="col-4">
                    <div class="form-group row">
                        <label for="country" class="col-4 col-form-label">Pays <i
                                    class="red">*</i></label>
                        <div class="col-8">
                            <!-- <input type="text"
                                    value=""
                                    class="form-control" required name="country"
                                    id="country"> -->
                            <select class="form-control select2" id="country" name="country">
                                <option value="">Selectionnez le pays</option>
                                @foreach($countries as $coun)
                                    <option value="{{ $coun['id'].'|'.$coun['currency_code'].'|'.$coun['name'] }}">{{ $coun['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-4">
                    <div class="form-group row">
                        <label for="nomB" class="col-4 col-form-label">Nom <i
                                    class="red">*</i></label>
                        <div class="col-8">
                            <input type="text"
                                    value="{{$type === 'add' ? '' : strtoupper($transaction['recipient_first_name'])}}"
                                    class="form-control" required name="nomB" id="nomB">
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group row">
                        <label for="prenomB" class="col-4 col-form-label">Prénom <i
                                    class="red">*</i></label>
                        <div class="col-8">
                            <input type="text"
                                    value="{{$type === 'add' ? '' : ucwords($transaction['recipient_last_name'])}}"
                                    class="form-control" required name="prenomB"
                                    id="prenomB">
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group row">
                        <label for="phoneB"
                                class="col-4 col-form-label">Téléphone ou Numéro
                            <i
                                    class="red">*</i></label>
                        <div class="col-8">
                            <input type="number"
                                    value="{{$type === 'add' ? '' : $transaction['recipient_phone']}}"
                                    class="form-control" required name="phoneB" id="phoneB">
                        </div>
                    </div>
                </div>
            </div>
        </fieldset>
        <h5>Informations Expéditeur</h5>
        <fieldset>
            <div class="row">
                <div class="col-4">
                    <div class="form-group row">
                        <label for="userId" class="col-4 col-form-label">Expéditeur <i
                                    class="red">*</i></label>
                        <div class="col-8">
                            <select class="form-control select2" id="userId" name="userId">
                                <option value="">Selectionnez l'expéditeur</option>
                                @foreach($users as $usr)
                                    <option value="{{ $usr['id'] }}">{{ $usr['full_name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group row">
                        <label for="numI"
                                class="col-4 col-form-label">Numéro CNI
                            <i class="red">*</i></label>
                        <div class="col-8">
                            <input type="text"
                                    value="{{$type === 'add' ? '' : $transaction['sender']['cni_number']}}"
                                    class="form-control" required name="numI" id="numI">
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group row">
                        <label for="dateExp" class="col-4 col-form-label">Date
                            d'expiration <i class="red">*</i></label>
                        <div class="col-8">
                            <!-- {{$type === 'add' ? '' : $transaction['sender']['date_exp_id']}} -->
                            <input type="date"
                                    value=""
                                    class="form-control" required name="dateExp"
                                    id="dateExp">
                        </div>
                    </div>
                </div>
            </div>
            Pièces justificatives <br>
        </fieldset>
        <div class="row">
            <div class="col-6">
                <p style="color: red" id="error1"></p>
            </div>
            <div class="col-2">
                <img id="loading1" src="{{ asset('assets/images/loading.gif') }}" alt=""
                        style="max-width: 25px; display: none; max-height: 25px">
            </div>
            <div class="col-4">
                <div class="button-items" dir="ltr">
                    <button type="button" id="btnStep1"
                            class="btn btn-primary btn-icon btn-rounded waves-effect">
                        <span class="btn-icon-label"><i class="mdi mdi-check-all mr-2"></i></span>
                        Ajouter la transaction
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div id="step2">
        <!-- <h3>Quotation de la transaction (2/3)</h3> -->
        <h5>Informations Transaction</h5>
        <fieldset>
            <div class="row">

                <div class="col-4">
                    <div class="form-group row">
                        <label for="typeEnvoi"
                                class="col-4 col-form-label">Type transaction
                            <i class="red">*</i></label>
                        <div class="col-8">
                            <select class="form-control select2" id="typeEnvoi" name="typeEnvoi">
                                <!-- <option value="">Choisir le type transaction</option> -->
                                @foreach($typeTransactions as $trans)
                                    <option value="{{ $trans['value']}}">{{ $trans['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-4">
                    <div class="form-group row">
                        <label for="numTrs"
                                class="col-4 col-form-label">Numéro
                            <i class="red">*</i></label>
                        <div class="col-8">
                            <input type="number"
                                    value=""
                                    class="form-control" required name="numTrs" id="numTrs">
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group row">
                        <label for="amount" class="col-4 col-form-label">Montant (XAF) <i
                                    class="red">*</i></label>
                        <div class="col-8">
                            <input type="number" value="{{$type === 'add' ? '' : $transaction['amount']}}"
                                    class="form-control" required name="amount" id="amount">
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-4">
                    <div class="form-group row">
                        <label for="origin" class="col-4 col-form-label">Origine des
                            fonds <i class="red">*</i></label>
                        <div class="col-8">
                            <input type="text"
                                    value="{{$type === 'add' ? '' : $transaction['transaction_reference']}}"
                                    class="form-control" required name="origin" id="origin">
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group row">
                        <label for="reason" class="col-4 col-form-label">Raison du
                            transfert <i class="red">*</i></label>
                        <div class="col-8">
                            <input type="text"
                                    value="{{$type === 'add' ? '' : $transaction['transaction_reason']}}"
                                    class="form-control" required name="reason" id="reason">
                        </div>
                    </div>
                </div>
            </div>
        </fieldset>
        <div class="row">
            <div class="col-6">
                <p style="color: red" id="error2"></p>
            </div>
            <div class="col-2">
                <img id="loading2" src="{{ asset('assets/images/loading.gif') }}" alt=""
                        style="max-width: 25px; display: none; max-height: 25px">
            </div>
            <div class="col-4">
                <div class="button-items" dir="ltr">
                    <button type="button" id="btnStep2"
                            class="btn btn-primary btn-icon btn-rounded waves-effect">
                        <span class="btn-icon-label"><i class="mdi mdi-check-all mr-2"></i></span>
                        Quotation de la transaction
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div id="step3">
        <h3>Validation Transaction (3/3)</h3>
        <h5>Transaction</h5>
        <fieldset>
            <div class="row">
                <div class="col-4">
                    <div class="form-group row">
                        <label class="col-5 col-form-label">Pays</label>
                        <label class="col-7 col-form-label" id="recapPays" style="font-weight: bold"></label>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group row">
                        <label class="col-5 col-form-label">Nom Benfeciaire</label>
                        <label class="col-7 col-form-label" id="recapBenef" style="font-weight: bold"></label>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group row">
                        <label class="col-5 col-form-label">Montant Envoyé</label>
                        <label class="col-7 col-form-label" id="recapSend" style="font-weight: bold"></label>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group row">
                        <label class="col-5 col-form-label">Montant à Percevoir</label>
                        <label class="col-7 col-form-label" id="recapPercu" style="font-weight: bold"></label>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group row">
                        <label class="col-5 col-form-label">Type Transaction</label>
                        <label class="col-7 col-form-label" id="recapType" style="font-weight: bold"></label>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group row">
                        <label class="col-5 col-form-label">Numéro à Créditer</label>
                        <label class="col-7 col-form-label" id="recapAccount" style="font-weight: bold"></label>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group row">
                        <label class="col-5 col-form-label">Telephone Beneficiaire</label>
                        <label class="col-7 col-form-label" id="recapTel" style="font-weight: bold"></label>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group row">
                        <label class="col-5 col-form-label">Origin des fonds</label>
                        <label class="col-7 col-form-label" id="recapOrigin" style="font-weight: bold"></label>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group row">
                        <label class="col-5 col-form-label">Raison du transfert</label>
                        <label class="col-7 col-form-label" id="recapReason" style="font-weight: bold"></label>
                    </div>
                </div>
            </div>
        </fieldset>
        <h5>Expediteur</h5>
        <fieldset>
            <div class="row">
                <div class="col-4">
                    <div class="form-group row">
                        <label class="col-5 col-form-label">Nom</label>
                        <label class="col-7 col-form-label" id="recapNomE" style="font-weight: bold"></label>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group row">
                        <label class="col-5 col-form-label">Prenom</label>
                        <label class="col-7 col-form-label" id="recapPrenomE" style="font-weight: bold"></label>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group row">
                        <label class="col-5 col-form-label">Telephone</label>
                        <label class="col-7 col-form-label" id="recapPhoneE" style="font-weight: bold"></label>
                    </div>
                </div>
            </div>
        </fieldset>
        <div class="row">
            <div class="col-8">
            </div>
            <div class="col-4">
                <div class="button-items" dir="ltr">
                    <button id="btnSumit" type="submit" class="btn btn-primary btn-icon btn-rounded waves-effect">
                        <span class="btn-icon-label"><i class="mdi mdi-check-all mr-2"></i></span>
                        Confirmer la transaction
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>