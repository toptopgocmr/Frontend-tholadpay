@extends('layouts.base')

@section('title')
    Transaction - Détail
@stop

@section('stylesheets')
    <!-- DataTables -->
    <link href="{{ asset('assets/plugins/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet"
          type="text/css"/>
    <link href="{{ asset('assets/plugins/datatables/buttons.bootstrap4.min.css') }}" rel="stylesheet" type="text/css"/>
    <!-- Responsive datatable examples -->
    <link href="{{ asset('assets/plugins/datatables/responsive.bootstrap4.min.css') }}" rel="stylesheet"
          type="text/css"/>
@stop

@section('content')
    <!-- Start content -->
    <div class="content">
        <div class="container-fluid">
            <div class="page-title-box">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <h4 class="page-title">Transaction</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('admin') }}"><i
                                            class="mdi mdi-home-outline"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{ route('transaction_list') }}">Transactions</a></li>
                            <li class="breadcrumb-item active">Transaction</li>
                        </ol>
                    </div>
                </div> <!-- end row -->
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            @if ($message = Session::get('success'))
                                <div class="alert alert-success alert-block">
                                    <button type="button" class="close" data-dismiss="alert">×</button>
                                    <strong>{{ $message }}</strong>
                                </div>
                            @endif
                            @if ($message = Session::get('error'))
                                <div class="alert alert-danger alert-block">
                                    <button type="button" class="close" data-dismiss="alert">×</button>
                                    <strong>{{ $message }}</strong>
                                </div>
                            @endif
                            <form action="{{ route("transaction_valid", $transaction['id']) }}" method="post" class="form-horizontal">
                                {{ csrf_field() }}                                
                                
                                <div id="step1" style="display: block">
                                    <h3>Validation Bénéficiaire & Expéditeur (1/3)</h3>
                                    <h5>Partenaire d'acheminement</h5>
                                    <fieldset>
                                        <div class="row">
                                            <div class="col-4">
                                                <div class="form-group row">
                                                    <label for="partner" class="col-4 col-form-label">Partenaire <i
                                                                class="red">*</i></label>
                                    @php
                                        // AJOUT (2026-08-08) : Cash Pickup (retrait en espèces), saisi dès la
                                        // création mobile — Peex ne le propose pas du tout, on désactive donc
                                        // cette option pour éviter toute confusion (le serveur l'ignorerait de
                                        // toute façon si elle était soumise).
                                        // FIX (2026-08-08) : "Interne (réseau Send-Paz)" était ÉGALEMENT
                                        // désactivé ici pour un Cash Pickup, et TransactionController::update()/
                                        // sendtransaction() forçaient 'digitwace' sans exception dès que
                                        // outbound.cash existait — impossible de router un Cash Pickup vers le
                                        // réseau interne, même en le sélectionnant explicitement (l'admin le
                                        // voyait revenir sur DigitWace à chaque fois, sans code de retrait
                                        // généré). Le réseau interne fonctionne pourtant pour N'IMPORTE quel
                                        // type de compte (le bénéficiaire retire en espèces avec un code de
                                        // toute façon) — seul Peex reste réellement incompatible avec Cash
                                        // Pickup. Option réactivée ; voir les deux méthodes citées pour le
                                        // pendant serveur de ce correctif.
                                        $isCashPickup = isset($transaction['outbound']['cash']) && $transaction['outbound']['cash'] !== null;
                                        // AJOUT (2026-08-20) : PawaPay n'est intégré que pour le mobile money
                                        // (voir OutboundController::sendPawapayRemittance, qui rejette
                                        // explicitement banque/espèces) — option désactivée pour ces deux cas,
                                        // même logique que Peex désactivé sur Cash Pickup ci-dessus.
                                        $isBankTx = isset($transaction['outbound']['bank']) && $transaction['outbound']['bank'] !== null;
                                        $pawapayDisabled = $isCashPickup || $isBankTx;
                                    @endphp
                                                    <div class="col-8">
                                                        <select class="form-control" name="partner" id="partner" onchange="tholadpayToggleDigitwaceFields()">
                                                            <option value="peex" {{ $isCashPickup ? 'disabled' : '' }} {{ (($partnerChoice ?? 'peex') === 'peex') ? 'selected' : '' }}>Peex</option>
                                                            <option value="digitwace" {{ (($partnerChoice ?? 'peex') === 'digitwace') ? 'selected' : '' }}>DigitWace</option>
                                                            <option value="pawapay" {{ $pawapayDisabled ? 'disabled' : '' }} {{ (($partnerChoice ?? 'peex') === 'pawapay') ? 'selected' : '' }}>PawaPay</option>
                                                            {{-- Transfert 100% interne (sans Peex ni DigitWace) — voir
                                                                 InternalTransferController. Le bénéficiaire retire en espèces avec
                                                                 un code, chez n'importe quel agent Send-Paz du pays destinataire. --}}
                                                            <option value="internal" {{ (($partnerChoice ?? 'peex') === 'internal') ? 'selected' : '' }}>Interne (réseau Send-Paz)</option>
                                                        </select>
                                                        @if($isCashPickup)
                                                            <small class="text-muted">Retrait en espèces : via DigitWace, ou en interne (réseau Send-Paz, sans partenaire externe).</small>
                                                        @elseif($isBankTx)
                                                            <small class="text-muted">Virement bancaire : PawaPay non disponible (mobile money uniquement).</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            @if($isCashPickup)
                                                <div class="row">
                                                    <div class="col-8 offset-4">
                                                        <div class="alert alert-info" style="margin-top: 10px;">
                                                            <strong>Retrait en espèces</strong><br>
                                                            Ville de retrait : {{ $transaction['outbound']['cash']['receiver_city'] ?? '—' }}<br>
                                                            Question de sécurité : {{ $transaction['outbound']['cash']['security_question'] ?? '—' }}
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                        {{-- Champs propres à DigitWace (doc §VI/§VIII/§X/§XVI) : sans équivalent
                                             chez Peex, donc masqués par défaut et affichés uniquement quand
                                             "DigitWace" est sélectionné ci-dessus (voir script en bas de page).
                                             Non bloquants côté HTML : un message d'erreur clair du backend
                                             (OutboundController::createDigitwaceBeneficiary/
                                             requireDigitwaceReferenceFields) guide l'agent s'il valide sans les
                                             remplir. --}}
                                        <div id="digitwaceFields" style="{{ (($partnerChoice ?? 'peex') === 'digitwace') ? '' : 'display: none' }}">
                                            <div class="row">
                                                <div class="col-4">
                                                    <div class="form-group row">
                                                        <label for="receiver_id_number" class="col-4 col-form-label">N° pièce bénéficiaire</label>
                                                        <div class="col-8">
                                                            <input type="text" class="form-control" name="receiver_id_number" id="receiver_id_number" value="{{ $transaction['receiver_id_number'] ?? '' }}">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="form-group row">
                                                        <label for="receiver_id_type" class="col-4 col-form-label">Type de pièce</label>
                                                        <div class="col-8">
                                                            {{-- AJOUT (2026-08-08) : voir mobile transaction.page.html — même liste
                                                                 partout (codes non confirmés par DigitWace au-delà de PP/CNI). --}}
                                                            <select class="form-control" name="receiver_id_type" id="receiver_id_type">
                                                                <option value="PP" {{ (($transaction['receiver_id_type'] ?? 'PP') === 'PP') ? 'selected' : '' }}>Passeport</option>
                                                                <option value="CNI" {{ (($transaction['receiver_id_type'] ?? '') === 'CNI') ? 'selected' : '' }}>Carte Nationale d'Identité</option>
                                                                <option value="PERMIS" {{ (($transaction['receiver_id_type'] ?? '') === 'PERMIS') ? 'selected' : '' }}>Permis de conduire</option>
                                                                <option value="NIU" {{ (($transaction['receiver_id_type'] ?? '') === 'NIU') ? 'selected' : '' }}>NIU (Numéro d'Identifiant Unique)</option>
                                                                <option value="RESIDENCE" {{ (($transaction['receiver_id_type'] ?? '') === 'RESIDENCE') ? 'selected' : '' }}>Carte de résidence</option>
                                                                <option value="CONSULAIRE" {{ (($transaction['receiver_id_type'] ?? '') === 'CONSULAIRE') ? 'selected' : '' }}>Carte consulaire</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="form-group row">
                                                        <label for="relation" class="col-4 col-form-label">Relation avec le bénéficiaire</label>
                                                        <div class="col-8">
                                                            {{-- FIX (2026-08-25, v2, incident transactions #133/#135/#151) : un
                                                                 champ texte libre + datalist n'empeche pas de valider une valeur
                                                                 hors liste (la datalist n'est qu'une suggestion HTML, jamais une
                                                                 contrainte) -- exactement le meme mecanisme qui a ensuite touche
                                                                 "reason" sur la transaction #151 malgre la datalist deja corrigee
                                                                 le jour meme. Remplace par un <select> natif : seules les 47
                                                                 valeurs reelles de /api/get_digitwace_relations sont
                                                                 selectionnables. --}}
                                                            <select class="form-control" required name="relation" id="relation">
                                                                <option value="">— Sélectionner —</option>
                                                            {{-- FIX (2026-08-27) : valeurs live /api/get_digitwace_relations
                                                                 ($dwRelations, voir TransactionController::update) au lieu de
                                                                 l'instantane fige du 2026-08-25 -- repli sur cet instantane
                                                                 uniquement si l'appel live a echoue (liste vide). --}}
                                                            @if(!empty($dwRelations))
                                                                @foreach(array_keys($dwRelations) as $rel)
                                                                    <option value="{{ $rel }}" {{ ($transaction['receiver_relation'] ?? '') === $rel ? 'selected' : '' }}>{{ $rel }}</option>
                                                                @endforeach
                                                            @else
                                                            <option value="Brother" {{ ($transaction['receiver_relation'] ?? '') === "Brother" ? 'selected' : '' }}>Brother</option>
                                                            <option value="Mother" {{ ($transaction['receiver_relation'] ?? '') === "Mother" ? 'selected' : '' }}>Mother</option>
                                                            <option value="Sister" {{ ($transaction['receiver_relation'] ?? '') === "Sister" ? 'selected' : '' }}>Sister</option>
                                                            <option value="Self" {{ ($transaction['receiver_relation'] ?? '') === "Self" ? 'selected' : '' }}>Self</option>
                                                            <option value="Father" {{ ($transaction['receiver_relation'] ?? '') === "Father" ? 'selected' : '' }}>Father</option>
                                                            <option value="Spouse" {{ ($transaction['receiver_relation'] ?? '') === "Spouse" ? 'selected' : '' }}>Spouse</option>
                                                            <option value="Son" {{ ($transaction['receiver_relation'] ?? '') === "Son" ? 'selected' : '' }}>Son</option>
                                                            <option value="Daughter" {{ ($transaction['receiver_relation'] ?? '') === "Daughter" ? 'selected' : '' }}>Daughter</option>
                                                            <option value="Friend" {{ ($transaction['receiver_relation'] ?? '') === "Friend" ? 'selected' : '' }}>Friend</option>
                                                            <option value="Employer" {{ ($transaction['receiver_relation'] ?? '') === "Employer" ? 'selected' : '' }}>Employer</option>
                                                            <option value="Guardian" {{ ($transaction['receiver_relation'] ?? '') === "Guardian" ? 'selected' : '' }}>Guardian</option>
                                                            <option value="Husband" {{ ($transaction['receiver_relation'] ?? '') === "Husband" ? 'selected' : '' }}>Husband</option>
                                                            <option value="Wife" {{ ($transaction['receiver_relation'] ?? '') === "Wife" ? 'selected' : '' }}>Wife</option>
                                                            <option value="In-Laws" {{ ($transaction['receiver_relation'] ?? '') === "In-Laws" ? 'selected' : '' }}>In-Laws</option>
                                                            <option value="Cousin" {{ ($transaction['receiver_relation'] ?? '') === "Cousin" ? 'selected' : '' }}>Cousin</option>
                                                            <option value="Partners" {{ ($transaction['receiver_relation'] ?? '') === "Partners" ? 'selected' : '' }}>Partners</option>
                                                            <option value="Aunt" {{ ($transaction['receiver_relation'] ?? '') === "Aunt" ? 'selected' : '' }}>Aunt</option>
                                                            <option value="Uncle" {{ ($transaction['receiver_relation'] ?? '') === "Uncle" ? 'selected' : '' }}>Uncle</option>
                                                            <option value="Bank Notes" {{ ($transaction['receiver_relation'] ?? '') === "Bank Notes" ? 'selected' : '' }}>Bank Notes</option>
                                                            <option value="Own Account" {{ ($transaction['receiver_relation'] ?? '') === "Own Account" ? 'selected' : '' }}>Own Account</option>
                                                            <option value="Sponsor" {{ ($transaction['receiver_relation'] ?? '') === "Sponsor" ? 'selected' : '' }}>Sponsor</option>
                                                            <option value="Director" {{ ($transaction['receiver_relation'] ?? '') === "Director" ? 'selected' : '' }}>Director</option>
                                                            <option value="Authorized Signatory" {{ ($transaction['receiver_relation'] ?? '') === "Authorized Signatory" ? 'selected' : '' }}>Authorized Signatory</option>
                                                            <option value="Corporate Representative" {{ ($transaction['receiver_relation'] ?? '') === "Corporate Representative" ? 'selected' : '' }}>Corporate Representative</option>
                                                            <option value="Owner" {{ ($transaction['receiver_relation'] ?? '') === "Owner" ? 'selected' : '' }}>Owner</option>
                                                            <option value="Brother In Law" {{ ($transaction['receiver_relation'] ?? '') === "Brother In Law" ? 'selected' : '' }}>Brother In Law</option>
                                                            <option value="Business" {{ ($transaction['receiver_relation'] ?? '') === "Business" ? 'selected' : '' }}>Business</option>
                                                            <option value="Corporate" {{ ($transaction['receiver_relation'] ?? '') === "Corporate" ? 'selected' : '' }}>Corporate</option>
                                                            <option value="Daughter In Law" {{ ($transaction['receiver_relation'] ?? '') === "Daughter In Law" ? 'selected' : '' }}>Daughter In Law</option>
                                                            <option value="Family" {{ ($transaction['receiver_relation'] ?? '') === "Family" ? 'selected' : '' }}>Family</option>
                                                            <option value="Father In Law" {{ ($transaction['receiver_relation'] ?? '') === "Father In Law" ? 'selected' : '' }}>Father In Law</option>
                                                            <option value="Friend's Father" {{ ($transaction['receiver_relation'] ?? '') === "Friend's Father" ? 'selected' : '' }}>Friend's Father</option>
                                                            <option value="Friend's Mother" {{ ($transaction['receiver_relation'] ?? '') === "Friend's Mother" ? 'selected' : '' }}>Friend's Mother</option>
                                                            <option value="Grand Daughter" {{ ($transaction['receiver_relation'] ?? '') === "Grand Daughter" ? 'selected' : '' }}>Grand Daughter</option>
                                                            <option value="Grand Father" {{ ($transaction['receiver_relation'] ?? '') === "Grand Father" ? 'selected' : '' }}>Grand Father</option>
                                                            <option value="Grand Mother" {{ ($transaction['receiver_relation'] ?? '') === "Grand Mother" ? 'selected' : '' }}>Grand Mother</option>
                                                            <option value="Grand Son" {{ ($transaction['receiver_relation'] ?? '') === "Grand Son" ? 'selected' : '' }}>Grand Son</option>
                                                            <option value="Mother In Law" {{ ($transaction['receiver_relation'] ?? '') === "Mother In Law" ? 'selected' : '' }}>Mother In Law</option>
                                                            <option value="Neighbour" {{ ($transaction['receiver_relation'] ?? '') === "Neighbour" ? 'selected' : '' }}>Neighbour</option>
                                                            <option value="Nephew" {{ ($transaction['receiver_relation'] ?? '') === "Nephew" ? 'selected' : '' }}>Nephew</option>
                                                            <option value="Niece" {{ ($transaction['receiver_relation'] ?? '') === "Niece" ? 'selected' : '' }}>Niece</option>
                                                            <option value="Parent" {{ ($transaction['receiver_relation'] ?? '') === "Parent" ? 'selected' : '' }}>Parent</option>
                                                            <option value="Relative" {{ ($transaction['receiver_relation'] ?? '') === "Relative" ? 'selected' : '' }}>Relative</option>
                                                            <option value="Sister In Law" {{ ($transaction['receiver_relation'] ?? '') === "Sister In Law" ? 'selected' : '' }}>Sister In Law</option>
                                                            <option value="Son In Law" {{ ($transaction['receiver_relation'] ?? '') === "Son In Law" ? 'selected' : '' }}>Son In Law</option>
                                                            <option value="Step Child" {{ ($transaction['receiver_relation'] ?? '') === "Step Child" ? 'selected' : '' }}>Step Child</option>
                                                            <option value="Colleague" {{ ($transaction['receiver_relation'] ?? '') === "Colleague" ? 'selected' : '' }}>Colleague</option>
                                                            @endif
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            {{-- AJOUT (2026-08-20, incident transaction #90) : DigitWace exige aussi la
                                                 date de naissance et la date d'expiration de la pièce du bénéficiaire
                                                 sur /beneficiary/create (voir OutboundController::createDigitwaceBeneficiary) —
                                                 champs absents jusqu'ici du formulaire, l'envoi échouait donc
                                                 systématiquement pour tout partenaire DigitWace. --}}
                                            <div class="row">
                                                <div class="col-4">
                                                    <div class="form-group row">
                                                        <label for="receiver_dob" class="col-4 col-form-label">Date de naissance bénéficiaire</label>
                                                        <div class="col-8">
                                                            <input type="date" class="form-control" name="receiver_dob" id="receiver_dob" value="{{ $transaction['receiver_dob'] ?? '' }}">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="form-group row">
                                                        <label for="receiver_expire_date" class="col-4 col-form-label">Expiration pièce bénéficiaire</label>
                                                        <div class="col-8">
                                                            <input type="date" class="form-control" name="receiver_expire_date" id="receiver_expire_date" value="{{ $transaction['receiver_expire_date'] ?? '' }}">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            {{-- AJOUT (2026-08-26, demande explicite : "le formulaire beneficiaire
                                                 avec tous les champs manquants") : DigitWace (doc §VI Create
                                                 Beneficiary) accepte address/city/email du bénéficiaire — le backend
                                                 (OutboundController::createDigitwaceBeneficiary) les lisait déjà,
                                                 mais ce formulaire ne les proposait jamais, d'où un repli
                                                 systématique sur "Any City" / le pays de réception comme adresse /
                                                 email toujours vide. Voir migration
                                                 add_receiver_address_city_email_to_transactions_table. --}}
                                            <div class="row">
                                                <div class="col-4">
                                                    <div class="form-group row">
                                                        <label for="receiver_address" class="col-4 col-form-label">Adresse bénéficiaire</label>
                                                        <div class="col-8">
                                                            <input type="text" class="form-control" name="receiver_address" id="receiver_address" value="{{ $transaction['receiver_address'] ?? '' }}">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="form-group row">
                                                        {{-- NB : name="receiver_city", PAS receiver_city_benef ici — côté admin
                                                             il n'y a pas de champ concurrent "ville de retrait" sur ce formulaire
                                                             (le Cash Pickup a le sien, saisi côté mobile et affiché en lecture
                                                             seule ailleurs sur cette page), donc pas de collision de nom. --}}
                                                        <label for="receiver_city" class="col-4 col-form-label">Ville bénéficiaire</label>
                                                        <div class="col-8">
                                                            <input type="text" class="form-control" name="receiver_city" id="receiver_city" value="{{ $transaction['receiver_city'] ?? '' }}">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="form-group row">
                                                        <label for="receiver_email" class="col-4 col-form-label">Email bénéficiaire</label>
                                                        <div class="col-8">
                                                            <input type="email" class="form-control" name="receiver_email" id="receiver_email" value="{{ $transaction['receiver_email'] ?? '' }}">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="text-muted">DigitWace impose des valeurs précises (relation / origine des fonds / raison — voir étape 2) : utilisez les suggestions proposées.</p>
                                        </div>
                                        {{-- Champs propres à PawaPay (doc "Initiate remittance" — API Remittance,
                                             consultée le 2026-08-20) : opérateur mobile money + motif/origine des
                                             fonds, imposés par des enums stricts côté PawaPay (voir
                                             OutboundController::PAWAPAY_PURPOSE_OF_FUNDS / PAWAPAY_SOURCE_OF_FUNDS).
                                             Masqués par défaut, affichés uniquement quand "PawaPay" est sélectionné
                                             ci-dessus (voir script en bas de page). Contrairement à DigitWace,
                                             PawaPay n'est intégré que pour le Congo-Brazzaville mobile money — pas
                                             de virement bancaire ni de retrait en espèces (voir option désactivée
                                             ci-dessus). --}}
                                        <div id="pawapayFields" style="{{ (($partnerChoice ?? 'peex') === 'pawapay') ? '' : 'display: none' }}">
                                            <div class="row">
                                                <div class="col-4">
                                                    <div class="form-group row">
                                                        <label for="pawapay_operator" class="col-4 col-form-label">Opérateur mobile money</label>
                                                        <div class="col-8">
                                                            <select class="form-control" name="pawapay_operator" id="pawapay_operator">
                                                                <option value="AIRTEL" {{ (($pawapayExtra['pawapay_operator'] ?? '') === 'AIRTEL') ? 'selected' : '' }}>Airtel Money</option>
                                                                <option value="MTN" {{ (($pawapayExtra['pawapay_operator'] ?? '') === 'MTN') ? 'selected' : '' }}>MTN Mobile Money</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="form-group row">
                                                        <label for="pawapay_purpose_of_funds" class="col-4 col-form-label">Motif du transfert</label>
                                                        <div class="col-8">
                                                            <select class="form-control" name="pawapay_purpose_of_funds" id="pawapay_purpose_of_funds">
                                                                @php
                                                                    $pawapayPurposes = [
                                                                        'FAMILY_SUPPORT' => 'Soutien familial', 'MEDICAL_EXPENSES' => 'Frais médicaux',
                                                                        'TUITION_FEES' => 'Frais de scolarité', 'EDUCATION_SUPPORT' => 'Soutien éducatif',
                                                                        'GIFT_AND_OTHER_DONATIONS' => 'Don / cadeau', 'HOME_IMPROVEMENT' => 'Amélioration du logement',
                                                                        'DEBT_SETTLEMENT' => 'Remboursement de dette', 'REAL_ESTATE' => 'Immobilier',
                                                                        'TAXES' => 'Impôts / taxes', 'SALARY' => 'Salaire', 'SAVINGS' => 'Épargne',
                                                                        'PERSONAL_TRANSFER' => 'Transfert personnel', 'OTHER' => 'Autre',
                                                                    ];
                                                                @endphp
                                                                @foreach($pawapayPurposes as $value => $label)
                                                                    <option value="{{ $value }}" {{ (($pawapayExtra['pawapay_purpose_of_funds'] ?? 'PERSONAL_TRANSFER') === $value) ? 'selected' : '' }}>{{ $label }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="form-group row">
                                                        <label for="pawapay_source_of_funds" class="col-4 col-form-label">Origine des fonds</label>
                                                        <div class="col-8">
                                                            <select class="form-control" name="pawapay_source_of_funds" id="pawapay_source_of_funds">
                                                                @php
                                                                    $pawapaySources = [
                                                                        'SALARY' => 'Salaire', 'SAVINGS' => 'Épargne', 'LOTTERY' => 'Loterie / gain',
                                                                        'LOAN' => 'Prêt', 'BUSINESS_INCOME' => 'Revenu d\'entreprise', 'GIFT' => 'Don / cadeau',
                                                                        'OTHER' => 'Autre',
                                                                    ];
                                                                @endphp
                                                                @foreach($pawapaySources as $value => $label)
                                                                    <option value="{{ $value }}" {{ (($pawapayExtra['pawapay_source_of_funds'] ?? 'SALARY') === $value) ? 'selected' : '' }}>{{ $label }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </fieldset>
                                    <h5>Informations Bénéficiaire</h5>
                                    <fieldset>
                                        <div class="row">
                                            <div class="col-4">
                                                <div class="form-group row">
                                                    <label for="country" class="col-4 col-form-label">Pays <i
                                                                class="red">*</i></label>
                                                    <div class="col-8">
                                                        <input type="text" disabled
                                                               value="{{$transaction['receiving_country']}}"
                                                               class="form-control" required name="country"
                                                               id="country">
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
                                                               value="{{strtoupper($transaction['recipient_first_name'])}}"
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
                                                               value="{{ucwords($transaction['recipient_last_name'])}}"
                                                               class="form-control" required name="prenomB"
                                                               id="prenomB">
                                                    </div>
                                                </div>
                                            </div>
                                            {{-- FIX (2026-08-08) : voir transactions/index.blade.php — même bug. --}}
                                            @php
                                                $ob = $transaction['outbound'] ?? null;
                                                if (!empty($ob['bank'])) {
                                                    $numLabel = 'Numéro Bancaire';
                                                    $numValue = $ob['bank']['bank_account_no'] ?? '';
                                                } elseif (!empty($ob['cash'])) {
                                                    $numLabel = 'Ville de retrait';
                                                    $numValue = $ob['cash']['receiver_city'] ?? '';
                                                } else {
                                                    $numLabel = 'Téléphone';
                                                    $numValue = $ob['mobile']['mobile_phone_credit'] ?? '';
                                                }
                                            @endphp
                                            <div class="col-4">
                                                <div class="form-group row">
                                                    <label for="phoneB"
                                                           class="col-4 col-form-label">{{ $numLabel }}
                                                        <i
                                                                class="red">*</i></label>
                                                    <div class="col-8">
                                                        <input type="text"
                                                               value="{{ $numValue }}"
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
                                                    <label for="nomE" class="col-4 col-form-label">Nom <i
                                                                class="red">*</i></label>
                                                    <div class="col-8">
                                                        <input type="text"
                                                               value="{{strtoupper($transaction['user']['first_name'])}}"
                                                               class="form-control" required name="nomE" id="nomE">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="form-group row">
                                                    <label for="prenomE" class="col-4 col-form-label">Prénom <i
                                                                class="red">*</i></label>
                                                    <div class="col-8">
                                                        <input type="text"
                                                               value="{{ucwords($transaction['user']['last_name'])}}"
                                                               class="form-control" required name="prenomE"
                                                               id="prenomE">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="form-group row">
                                                    <label for="phoneE" class="col-4 col-form-label">Téléphone <i
                                                                class="red">*</i></label>
                                                    <div class="col-8">
                                                        <input type="text"
                                                               value="{{$transaction['user']['phone_number']}}"
                                                               class="form-control" required name="phoneE" id="phoneE">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4">
                                                <div class="form-group row">
                                                    <label for="numI"
                                                           class="col-4 col-form-label">Numéro {{ $transaction['sender']['type_id'] }}
                                                        <i class="red">*</i></label>
                                                    <div class="col-8">
                                                        <input type="text"
                                                               value="{{ $transaction['sender']['cni_number'] }}"
                                                               class="form-control" required name="numI" id="numI">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="form-group row">
                                                    <label for="dateExp" class="col-4 col-form-label">Date
                                                        d'expiration <i class="red">*</i></label>
                                                    <div class="col-8">
                                                        <input type="text" readonly
                                                               value="{{ $transaction['sender']['date_exp_id'] }}"
                                                               class="form-control" required name="dateExp"
                                                               id="dateExp">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
										Pièces justificatives <br>
										@if( $transaction['sender']['cni_picture'] != null )
											<button type="button" class="doc-thumb-btn" style="width:150px;height:100px;margin-right:8px"
													onclick="docViewerOpen('{{config('keys.url_img').$transaction['sender']['cni_picture']}}', 'Pièce d\'identité - Recto')">
												<img src="{!! config('keys.url_img').$transaction['sender']['cni_picture'] !!}"
													 alt="Recto"
													 style="width:100%;height:100%;object-fit:cover">
												<span class="doc-thumb-zoom-icon"><i class="mdi mdi-magnify-plus-outline"></i></span>
											</button>
										@endif
										@if( $transaction['sender']['justif_picture'] != null )
											<button type="button" class="doc-thumb-btn" style="width:150px;height:100px"
													onclick="docViewerOpen('{{config('keys.url_img').$transaction['sender']['justif_picture']}}', 'Pièce d\'identité - Verso')">
												<img src="{!! config('keys.url_img').$transaction['sender']['justif_picture'] !!}"
													 alt="Verso"
													 style="width:100%;height:100%;object-fit:cover">
												<span class="doc-thumb-zoom-icon"><i class="mdi mdi-magnify-plus-outline"></i></span>
											</button>
										@endif
										@include('partials.image_zoom_viewer')
                                        <input type="hidden" value="{{$clientName}}" id="nom_api">
                                        <input type="hidden" value="{{$client_id}}" id="client_id">
                                        <input type="hidden" name="sender_id" value="{{$transaction['sender']['id']}}">
                                        <input type="hidden" name="transaction_id" value="{{$transaction['id']}}">
                                        <input type="hidden" name="user_id" value="{{$transaction['user']['id']}}">
                                    </fieldset>
                                    <div class="row">
                                        <div class="col-6">
                                            <!-- <p style="color: red" id="error1"></p>
                                            <p style="color: #006EB6" id="success1"></p> -->
                                        </div>
                                        <div class="col-2">
                                            <img id="loading1" src="{{ asset('assets/images/loading.gif') }}" alt=""
                                                 style="max-width: 25px; display: none; max-height: 25px">
                                        </div>
                                        <div class="col-4">
                                            <div class="button-items" dir="ltr">
                                                <button type="submit" id="btnStep1"
                                                        class="btn btn-primary btn-icon btn-rounded waves-effect">
                                                    <span class="btn-icon-label"><i class="mdi mdi-check-all mr-2"></i></span>
                                                    Validation du compte
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>                                
                            </form>
                        </div>
                    </div>
                </div> <!-- end col -->
            </div> <!-- end row -->
            
        </div>
        <!-- container-fluid -->
    </div>
    <!-- content -->
@stop

@section('javascripts')
    <script type="text/javascript">
        function tholadpayToggleDigitwaceFields() {
            var partnerSelect = document.getElementById('partner');
            var dwBlock = document.getElementById('digitwaceFields');
            var ppBlock = document.getElementById('pawapayFields');
            if (!partnerSelect) { return; }
            if (dwBlock) { dwBlock.style.display = (partnerSelect.value === 'digitwace') ? '' : 'none'; }
            if (ppBlock) { ppBlock.style.display = (partnerSelect.value === 'pawapay') ? '' : 'none'; }
        }
        document.addEventListener('DOMContentLoaded', tholadpayToggleDigitwaceFields);
    </script>
@stop