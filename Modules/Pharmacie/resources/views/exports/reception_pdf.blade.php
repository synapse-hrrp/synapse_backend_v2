<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Réception {{ $reception->numero }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-section table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-section td {
            padding: 5px;
        }
        .info-section .label {
            font-weight: bold;
            width: 30%;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .items-table th {
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }
        .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
            font-size: 10px;
        }
        .total {
            text-align: right;
            margin-top: 20px;
            font-weight: bold;
            font-size: 14px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>BON DE RÉCEPTION</h1>
        <p>{{ $reception->numero }}</p>
    </div>

    <div class="info-section">
        <table>
            <tr>
                <td class="label">Date de réception :</td>
                <td>{{ \Carbon\Carbon::parse($reception->date_reception)->format('d/m/Y') }}</td>
                <td class="label">Bon de livraison :</td>
                <td>{{ $reception->bon_livraison ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Fournisseur :</td>
                <td>{{ $reception->fournisseur->nom ?? 'N/A' }}</td>
                <td class="label">Statut :</td>
                <td>{{ $reception->statut }}</td>
            </tr>
            @if($reception->depot)
            <tr>
                <td class="label">Dépôt :</td>
                <td>{{ $reception->depot->libelle }}</td>
                <td class="label">Facture :</td>
                <td>{{ $reception->facture_fournisseur ?? 'N/A' }}</td>
            </tr>
            @endif
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>N°</th>
                <th>Produit</th>
                <th>N° Lot</th>
                <th>Péremption</th>
                <th>Qté</th>
                <th>Prix HT</th>
                <th>Prix TTC</th>
                <th>Total TTC</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reception->lignes as $index => $ligne)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $ligne->produit->nom }}</td>
                <td>{{ $ligne->numero_lot }}</td>
                <td>{{ \Carbon\Carbon::parse($ligne->date_peremption)->format('d/m/Y') }}</td>
                <td>{{ $ligne->quantite_recue }}</td>
                <td>{{ number_format($ligne->prix_achat_unitaire_ht ?? 0, 2) }}</td>
                <td>{{ number_format($ligne->prix_achat_unitaire_ttc ?? 0, 2) }}</td>
                <td>{{ number_format($ligne->montant_achat_ttc ?? 0, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total">
        Total HT : {{ number_format($reception->montant_total_ht ?? 0, 2) }} FCFA<br>
        Total TTC : {{ number_format($reception->montant_total_ttc ?? 0, 2) }} FCFA
    </div>

    <div class="footer">
        <p>Document généré le {{ now()->format('d/m/Y à H:i') }}</p>
    </div>
</body>
</html>