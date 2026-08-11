<form action="{{ $type === 'add' ? route("tax_add") : route('tax_edit', $tax['id']) }}" method="post"
      class="form-horizontal">
    {{ csrf_field() }}
    <h3>Info taxe</h3>
    <fieldset>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group row">
                    <label for="txtCode" class="col-lg-4 col-form-label">Code <i class="red">*</i></label>
                    <div class="col-lg-8">
                        <select required class="form-control select2" id="txtCode" name="txtCode">
                            @php
                                $currentCode = $type === 'add' ? '' : $tax['code'];
                            @endphp
                            <option value="">Selectionnez le code</option>
                            @foreach(['TTF', 'COBAC', 'TVA', 'TIMBRE'] as $codeOpt)
                                <option value="{{ $codeOpt }}" {{ $currentCode === $codeOpt ? 'selected' : '' }}>{{ $codeOpt }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group row">
                    <label for="txtLibelle" class="col-lg-4 col-form-label">Libellé <i class="red">*</i></label>
                    <div class="col-lg-8">
                        <input value="{{$type === 'add' ? '' : $tax['libelle']}}" type="text" class="form-control" required name="txtLibelle" id="txtLibelle">
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group row">
                    <label for="txtType" class="col-lg-4 col-form-label">Type <i class="red">*</i></label>
                    <div class="col-lg-8">
                        <select required class="form-control select2" id="txtType" name="txtType">
                            @php
                                $currentType = $type === 'add' ? 'pourcentage' : $tax['type'];
                            @endphp
                            <option value="pourcentage" {{ $currentType === 'pourcentage' ? 'selected' : '' }}>Pourcentage</option>
                            <option value="fixe" {{ $currentType === 'fixe' ? 'selected' : '' }}>Fixe</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group row">
                    <label for="txtValeur" class="col-lg-4 col-form-label">Valeur <i class="red">*</i></label>
                    <div class="col-lg-8">
                        <input value="{{$type === 'add' ? '' : $tax['valeur']}}" type="number" step="any" class="form-control" required name="txtValeur" id="txtValeur">
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group row">
                    <label for="txtAssiette" class="col-lg-4 col-form-label">Assiette</label>
                    <div class="col-lg-8">
                        @php
                            $currentAssiette = $type === 'add' ? '' : ($tax['assiette'] ?? '');
                        @endphp
                        <select class="form-control select2" id="txtAssiette" name="txtAssiette" {{ ($type !== 'add' && $tax['type'] === 'fixe') ? 'disabled' : '' }}>
                            <option value="montant" {{ $currentAssiette === 'montant' ? 'selected' : '' }}>Montant</option>
                            <option value="frais" {{ $currentAssiette === 'frais' ? 'selected' : '' }}>Frais</option>
                        </select>
                        <small class="text-muted">Pertinent uniquement si le type est "Pourcentage".</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group row">
                    <label for="txtZone" class="col-lg-4 col-form-label">Zone</label>
                    <div class="col-lg-8">
                        <select class="form-control select2" id="txtZone" name="txtZone">
                            <option value="">Toutes zones</option>
                            @if($type === 'edit' && !empty($tax['zone']))
                                <option value="{{ $tax['zone']['id'] }}" selected>{{ $tax['zone']['name'] }}</option>
                            @endif
                            @foreach($zones as $zon)
                                <option value="{{ $zon['id'] }}">{{ $zon['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group row">
                    <label for="txtStatut" class="col-lg-4 col-form-label">Statut <i class="red">*</i></label>
                    <div class="col-lg-8">
                        @php
                            $currentStatut = $type === 'add' ? true : !empty($tax['statut']);
                        @endphp
                        <select required class="form-control select2" id="txtStatut" name="txtStatut">
                            <option value="1" {{ $currentStatut ? 'selected' : '' }}>Actif</option>
                            <option value="0" {{ !$currentStatut ? 'selected' : '' }}>Inactif</option>
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

<script type="text/javascript">
    $(function () {
        function toggleAssiette() {
            if ($('#txtType').val() === 'fixe') {
                $('#txtAssiette').prop('disabled', true);
            } else {
                $('#txtAssiette').prop('disabled', false);
            }
        }
        toggleAssiette();
        $('#txtType').on('change', toggleAssiette);
    });
</script>
