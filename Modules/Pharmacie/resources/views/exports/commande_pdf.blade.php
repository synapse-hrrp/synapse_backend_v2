<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Commande {{ $commande->numero }}</title>
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
        }
        .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
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
        <h1>BON DE COMMANDE</h1>
        <p>{{ $commande->numero }}</p>
    </div>

    <div class="info-section">
        <table>
            <tr>
                <td class="label">Date de commande :</td>
                <td>{{ \Carbon\Carbon::parse($commande->date_commande)->format('d/m/Y') }}</td>
                <td class="label">Date livraison prévue :</td>
                <td>{{ $commande->date_livraison_prevue ? \Carbon\Carbon::parse($commande->date_livraison_prevue)->format('d/m/Y') : 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Fournisseur :</td>
                <td>{{ $commande->fournisseur->nom ?? 'N/A' }}</td>
                <td class="label">Statut :</td>
                <td>{{ $commande->statut }}</td>
            </tr>
            @if($commande->depot)
            <tr>
                <td class="label">Dépôt :</td>
                <td>{{ $commande->depot->libelle }}</td>
                <td class="label">Type :</td>
                <td>{{ $commande->type }}</td>
            </tr>
            @endif
            @if($commande->priorite)
            <tr>
                <td class="label">Priorité :</td>
                <td>{{ $commande->priorite }}</td>
                <td class="label">Déclencheur :</td>
                <td>{{ $commande->declencheur ?? 'N/A' }}</td>
            </tr>
            @endif
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>N°</th>
                <th>Produit</th>
                <th>Code</th>
                <th>Qté commandée</th>
                <th>Prix unitaire</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($commande->lignes as $index => $ligne)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $ligne->produit->nom }}</td>
                <td>{{ $ligne->produit->code }}</td>
                <td>{{ $ligne->quantite_commandee }}</td>
                <td>{{ number_format($ligne->prix_unitaire, 2) }} FCFA</td>
                <td>{{ number_format($ligne->quantite_commandee * $ligne->prix_unitaire, 2) }} FCFA</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total">
        Total : {{ number_format($commande->lignes->sum(fn($l) => $l->quantite_commandee * $l->prix_unitaire), 2) }} FCFA
    </div>

    @if($commande->observations)
    <div style="margin-top: 30px;">
        <strong>Observations :</strong>
        <p>{{ $commande->observations }}</p>
    </div>
    @endif

    <div class="footer">
        <p>Document généré le {{ now()->format('d/m/Y à H:i') }}</p>
    </div>
</body>
</html>