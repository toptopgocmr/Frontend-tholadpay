<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu — {{ $transaction['ranking'] ?? '' }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 24px;
            background: #f2f2f2;
        }
        .receipt {
            max-width: 620px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 32px 36px;
        }
        .receipt-header {
            text-align: center;
            border-bottom: 2px solid #12709E;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }
        .receipt-header img {
            max-height: 60px;
            margin-bottom: 8px;
        }
        .receipt-header h1 {
            font-size: 18px;
            margin: 4px 0 0;
            color: #12709E;
            letter-spacing: 1px;
        }
        .status-badge {
            display: inline-block;
            margin-top: 8px;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .status-badge.success { background: #e5f6ea; color: #1e8a3c; }
        .status-badge.acknowledged { background: #fff3d6; color: #b9790a; }
        .status-badge.failed { background: #fde8e8; color: #c0392b; }
        .status-badge.cancelled { background: #ececec; color: #666; }
        .status-badge.AwaitingPickup { background: #e6f0fa; color: #12709E; }
        .pickup-code-box {
            margin-top: 14px;
            padding: 16px 20px;
            background: #e6f0fa;
            border: 1px dashed #12709E;
            border-radius: 8px;
            text-align: center;
        }
        .pickup-code-box .label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #12709E;
        }
        .pickup-code-box .code {
            font-size: 26px;
            font-weight: bold;
            letter-spacing: 3px;
            color: #12709E;
            margin-top: 4px;
        }
        .section-title {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #999999;
            font-weight: bold;
            margin: 22px 0 8px;
        }
        table.info {
            width: 100%;
            border-collapse: collapse;
        }
        table.info td {
            padding: 6px 0;
            font-size: 14px;
            border-bottom: 1px solid #f0f0f0;
        }
        table.info td.label {
            color: #777;
            width: 45%;
        }
        table.info td.value {
            text-align: right;
            font-weight: bold;
            color: #222;
        }
        .amount-box {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px 20px;
            margin-top: 10px;
        }
        .amount-box .total td.value {
            color: #12709E;
            font-size: 17px;
        }
        .rejected-notice {
            margin-top: 14px;
            padding: 10px 14px;
            background: #fdf2f2;
            border: 1px solid #f5c6c6;
            border-radius: 6px;
            font-size: 12px;
            color: #922b21;
            text-align: center;
        }
        .receipt-verify {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 26px;
            padding-top: 18px;
            border-top: 1px dashed #ccc;
        }
        .qr-block {
            text-align: center;
        }
        .qr-block img {
            width: 110px;
            height: 110px;
            display: block;
        }
        .qr-block .qr-caption {
            font-size: 10px;
            color: #999;
            margin-top: 4px;
            letter-spacing: 0.5px;
        }
        .stamp {
            transform: rotate(-9deg);
            opacity: 0.88;
        }
        .receipt-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 14px;
            font-size: 12px;
            color: #999;
        }
        .print-bar {
            max-width: 620px;
            margin: 0 auto 16px;
            text-align: right;
        }
        .print-bar button {
            background: #12709E;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 10px 22px;
            font-size: 14px;
            cursor: pointer;
        }
        .print-bar a {
            margin-right: 12px;
            font-size: 14px;
            color: #666;
            text-decoration: none;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .print-bar { display: none; }
            .receipt { border: none; max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="print-bar">
        <a href="{{ route('transaction_show', $transaction['id']) }}">&larr; Retour à la transaction</a>
        <button onclick="window.print()">Imprimer / Enregistrer en PDF</button>
    </div>

    <div class="receipt">
        <div class="receipt-header">
            <img src="{{ asset('assets/images/tholadpay-transparent.png') }}" alt="Send-Paz">
            <h1>Reçu de transaction</h1>
            @php
                $statusLabels = [
                    'success' => 'Paiement réussi',
                    'acknowledged' => 'Paiement en attente',
                    'failed' => 'Transaction rejetée',
                    'cancelled' => 'Transaction annulée',
                    'AwaitingPickup' => 'En attente de retrait',
                ];
                $statusClass = in_array($etat, array_keys($statusLabels)) ? $etat : 'acknowledged';
            @endphp
            <span class="status-badge {{ $statusClass }}">
                {{ $statusLabels[$statusClass] ?? 'Statut inconnu' }}
            </span>
        </div>
        @if(in_array($etat, ['failed', 'cancelled']))
            <div class="rejected-notice">
                Ce reçu atteste qu'aucun montant n'a été livré au bénéficiaire pour cette transaction. Les fonds débités, le cas échéant, font l'objet d'un remboursement selon la procédure en vigueur.
            </div>
        @endif

        @php
            // AJOUT (2026-08-08) : corridor_id=3 = transfert interne (retrait en
            // espèces par code, voir InternalTransferController) — l'outbound de ces
            // transactions n'a ni bank ni mobile renseigné, d'où ce cas à part.
            $isInternalReceipt = (string) ($transaction['corridor_id'] ?? '') === '3';
        @endphp
        <table class="info">
            <tr><td class="label">Numéro de transaction</td><td class="value">{{ $transaction['ranking'] ?? '—' }}</td></tr>
            <tr><td class="label">Date</td><td class="value">{{ $transaction['created_at'] ? \Carbon\Carbon::parse($transaction['created_at'])->format('d/m/Y H:i') : '—' }}</td></tr>
            <tr><td class="label">Type de transaction</td><td class="value">
                @if($isInternalReceipt)
                    Retrait en espèces (réseau interne Send-Paz)
                @else
                    {{ ($transaction['outbound']['bank'] ?? null) === null ? 'Mobile money' : 'Virement bancaire' }}
                @endif
            </td></tr>
        </table>

        @if($isInternalReceipt && !empty($transaction['internal_pickup_code']) && $etat !== 'success')
            <div class="pickup-code-box">
                <div class="label">Code de retrait à présenter en agence</div>
                <div class="code">{{ $transaction['internal_pickup_code'] }}</div>
            </div>
        @endif

        <div class="section-title">Expéditeur</div>
        <table class="info">
            <tr>
                <td class="label">Nom</td>
                <td class="value">
                    {{ strtoupper($transaction['sender']['user']['first_name'] ?? '') }}
                    {{ ucwords($transaction['sender']['user']['last_name'] ?? '') }}
                </td>
            </tr>
            <tr><td class="label">Téléphone</td><td class="value">{{ $transaction['sender']['user']['phone_number'] ?? '—' }}</td></tr>
        </table>

        <div class="section-title">Bénéficiaire</div>
        <table class="info">
            <tr>
                <td class="label">Nom</td>
                <td class="value">
                    {{ strtoupper($transaction['recipient_first_name'] ?? '') }}
                    {{ ucwords($transaction['recipient_last_name'] ?? '') }}
                </td>
            </tr>
            <tr><td class="label">Téléphone</td><td class="value">{{ $transaction['recipient_phone'] ?? '—' }}</td></tr>
            <tr><td class="label">Pays</td><td class="value">{{ strtoupper($transaction['receiving_country'] ?? '') }}</td></tr>
            @if($isInternalReceipt)
                <tr><td class="label">Mode de retrait</td><td class="value">Espèces, n'importe quelle agence Send-Paz du pays</td></tr>
            @elseif(($transaction['outbound']['bank'] ?? null) !== null)
                <tr><td class="label">Banque</td><td class="value">{{ $transaction['outbound']['bank']['organisation'] ?? '—' }}</td></tr>
                <tr><td class="label">IBAN</td><td class="value">{{ $transaction['outbound']['bank']['bank_account_no'] ?? '—' }}</td></tr>
            @else
                <tr><td class="label">Numéro mobile money</td><td class="value">{{ $transaction['outbound']['mobile']['mobile_phone_credit'] ?? '—' }}</td></tr>
            @endif
        </table>

        <div class="section-title">Montants</div>
        <div class="amount-box">
            <table class="info">
                <tr><td class="label">Montant envoyé</td><td class="value">{{ number_format($transaction['amount'] ?? 0, 0, ',', ' ') }} {{ $transaction['from_currency'] ?? '' }}</td></tr>
                <tr><td class="label">Frais d'envoi</td><td class="value">{{ number_format($transaction['frais_envoi'] ?? 0, 0, ',', ' ') }} {{ $transaction['from_currency'] ?? '' }}</td></tr>
                <tr class="total"><td class="label">Montant total débité</td><td class="value">{{ number_format(($transaction['amount'] ?? 0) + ($transaction['frais_envoi'] ?? 0), 0, ',', ' ') }} {{ $transaction['from_currency'] ?? '' }}</td></tr>
                <tr><td class="label">Montant reçu par le bénéficiaire</td><td class="value">{{ number_format($transaction['montant_beneficiaire'] ?? 0, 0, ',', ' ') }} {{ $transaction['to_currency'] ?? '' }}</td></tr>
            </table>
        </div>

        <div class="receipt-verify">
            <div class="qr-block">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=130x130&data={{ urlencode($transaction['ranking'] ?? '') }}"
                     alt="QR code de vérification">
                <div class="qr-caption">Scanner pour vérifier<br>{{ $transaction['ranking'] ?? '' }}</div>
            </div>
            <svg class="stamp" viewBox="0 0 200 200" width="140" height="140" xmlns:xlink="http://www.w3.org/1999/xlink">
                <defs>
                    <path id="stampCirclePath" fill="none"
                          d="M 100,100 m -78,0 a 78,78 0 1,1 156,0 a 78,78 0 1,1 -156,0" />
                </defs>
                <circle cx="100" cy="100" r="94" fill="none" stroke="#12709E" stroke-width="2.5"/>
                <circle cx="100" cy="100" r="66" fill="none" stroke="#12709E" stroke-width="1.5"/>
                <text font-size="13.5" font-weight="bold" fill="#12709E" letter-spacing="3">
                    <textPath href="#stampCirclePath" xlink:href="#stampCirclePath" startOffset="1%">
                        ★ SEND-PAZ ★ DIRECTION COMMERCIALE ★ SEND-PAZ ★ DIRECTION COMMERCIALE
                    </textPath>
                </text>
                <text x="100" y="94" text-anchor="middle" font-size="24" font-weight="bold" fill="#12709E">SEND-PAZ</text>
                <text x="100" y="116" text-anchor="middle" font-size="9.5" fill="#FF751F" letter-spacing="1.5">TRANSFERT D'ARGENT</text>
            </svg>
        </div>

        <div class="receipt-footer">
            Send-Paz &middot; Reçu généré le {{ \Carbon\Carbon::now()->format('d/m/Y à H:i') }}<br>
            Ce document confirme l'état de la transaction au moment de son édition.
        </div>
    </div>

    <script>
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
</body>
</html>
