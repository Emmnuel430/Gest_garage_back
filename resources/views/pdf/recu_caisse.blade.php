<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <title>Reçu de Caisse</title>
    <style>
        @page {
            margin: 25px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 14px;
            margin: 0;
            padding: 0;
            color: #002060;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo img {
            height: 60px;
        }

        .title {
            color: #002060;
            font-size: 18px;
            margin: 10px auto;
            font-weight: bold;
            padding: 6px 14px;
            border-radius: 10px;
            text-align: center;
            display: flex;
            justify-content: center;
            width: fit-content;
        }

        .info-box {
            border: 2px solid #002060;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .info-box p {
            margin: 6px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 2px solid #002060;
            padding: 8px;
            text-align: center;
        }

        th {
            background: #002060;
            color: white;
        }

        .totals {
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .totals div {
            font-weight: bold;
            font-size: 16px;
        }

        footer {
            position: fixed;
            bottom: 10px;
            left: 0;
            right: 0;
            height: 40px;
            text-align: center;
            font-size: 16px;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="logo">
            <img src="{{ public_path('logo.png') }}" alt="logo" />
            <div>
                <p>(+225) 27 22 39 65 58<br />07 09 09 31 45</p>
            </div>
        </div>
    </div>
    <div class="title">REÇU DE CAISSE</div>

    <div style="text-align: right; font-size: 14px">
        <strong>date :</strong>
        {{ \Carbon\Carbon::now()->format('d/m/Y à H:i') ?? '...' }}
    </div>

    <div class="info-box">
        <p style="text-align: right">
            <strong>Date d’arrivée :</strong>
            {{ \Carbon\Carbon::parse($reception->date_arrivee)->format('d/m/Y à
        H:i') ?? '...' }}
        </p>

        <p>
            <strong>Enregistré par :</strong>
            {{ $reception->gardien->first_name ?? '...' }} {{
    $reception->gardien->last_name ?? '' }}
        </p>

        <p>
            <strong>Chef Atelier :</strong>
            {{ $chefAtelier->first_name ?? '...' }} {{
    $chefAtelier->last_name ?? '' }}
        </p>

        <p>
            <strong>Mécanicien :</strong>
            {{ $reception->vehicule->mecanicien->nom ?? '' }} {{
    $reception->vehicule->mecanicien->prenom ?? '...' }}
        </p>

        <p>
            <strong>Numéro de téléphone :</strong>
            {{ $reception->vehicule->mecanicien->contact ?? '...' }}
        </p>
    </div>

    <table style="
        width: 100%;
        border-collapse: collapse;
        font-family: Arial, sans-serif;
      ">
        <thead>
            <tr style="background-color: #f2f2f2">
                <th style="padding: 10px; border: 1px solid #ddd; text-align: left">
                    Désignation
                </th>
                <th style="padding: 10px; border: 1px solid #ddd; text-align: right">
                    Durée (en jours)
                </th>
                <th style="padding: 10px; border: 1px solid #ddd; text-align: right">
                    Prix Unitaire (par jour)
                </th>
                <th style="padding: 10px; border: 1px solid #ddd; text-align: right">
                    Montant
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd">
                    {{ $reception->vehicule->marque ?? '...' }} - {{
    $reception->vehicule->modele ?? '...' }}<br />
                    Immatriculation : {{ $reception->vehicule->immatriculation ?? '...'
            }}<br />
                </td>
                <td style="padding: 10px; border: 1px solid #ddd; text-align: right">
                    {{ $nbJours ?? 0 }}
                </td>
                <td style="padding: 10px; border: 1px solid #ddd; text-align: right">
                    {{ number_format($montantJournalier, 0, ',', ' ') }} FCFA
                </td>
                <td style="padding: 10px; border: 1px solid #ddd; text-align: right">
                    {{ number_format($montantTotal, 0, ',', ' ') }} FCFA
                </td>
            </tr>
            <tr>
                <td colspan="3" style="padding: 10px; border: 1px solid #ddd; text-align: right">
                    <strong>Imposition</strong>
                </td>
                <td style="padding: 10px; border: 1px solid #ddd; text-align: right">
                    0 FCFA
                </td>
            </tr>
            <tr>
                <td colspan="3" style="padding: 10px; border: 1px solid #ddd; text-align: right">
                    <strong>Total TTC</strong>
                </td>
                <td style="padding: 10px; border: 1px solid #ddd; text-align: right">
                    <strong>{{ number_format($montantTotal, 0, ',', ' ') }} FCFA</strong>
                </td>
            </tr>
        </tbody>
    </table>

    <div style="text-align: right; margin-top: 20px">
        <em>Visa payé</em>
    </div>

    <footer>
        Les faits et gestes et réparations sur les véhicules ne sont pas de notre
        responsabilité.
    </footer>
</body>

</html>