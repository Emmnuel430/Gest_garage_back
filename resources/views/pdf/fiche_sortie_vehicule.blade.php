<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <title>Billet de Sortie</title>
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

        .main-table {
            border: 2px solid #002060;
            border-radius: 20px;
            border-collapse: separate;
            border-spacing: 0;
            padding: 15px;
            width: 90%;
        }

        .main-table td {
            vertical-align: top;
            padding: 15px;
        }

        .left-box,
        .right-box {
            height: 100%;
        }

        .left-box {
            width: 70%;
        }

        .right-box {
            width: 30%;
            text-align: center;
            justify-content: space-between;
            margin: 0 auto;
        }

        .left-box p,
        .right-box p {
            margin: 6px 0;
        }

        .right-box h3 {
            text-decoration: underline;
            margin-bottom: 60px;
            color: #002060;
        }

        .right-box .date-sortie {
            font-weight: bold;
        }

        footer {
            position: fixed;
            bottom: 10px;
            left: 0;
            right: 0;
            height: 40px;
            text-align: center;
            font-size: 14px;
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

    <div class="title">BILLET DE SORTIE</div>

    <!-- Tableau d'informations -->
    <table class="main-table" style="margin: 0 auto">
        <tr>
            <td>
                <p style="text-align: right">
                    <strong>Date d’arrivée :</strong>
                    {{ \Carbon\Carbon::parse($reception->date_arrivee)->format('d/m/Y à
            H:i') ?? '...' }}
                </p>
                <p>
                    <strong>Enregistré par :</strong> {{ $reception->gardien->first_name
    ?? '...' }} {{ $reception->gardien->last_name ?? '' }}
                </p>
                <p>
                    <strong>Chef Atelier :</strong> {{
    $chefAtelier->first_name ?? '...' }} {{
    $chefAtelier->last_name ?? '' }}
                </p>
                <p>
                    <strong>Mécanicien :</strong> {{
    $reception->vehicule->mecanicien->nom ?? '' }} {{
    $reception->vehicule->mecanicien->prenom ?? '...' }}
                </p>
                <p>
                    <strong>Numéro de téléphone :</strong> {{
    $reception->vehicule->mecanicien->contact ?? '...' }}
                </p>
                <p>
                    <strong>Marque du véhicule :</strong> {{
    $reception->vehicule->marque ?? '...' }}
                </p>
                <p>
                    <strong>Modèle du véhicule :</strong> {{
    $reception->vehicule->modele ?? '...' }}
                </p>
                <p>
                    <strong>Immatriculation :</strong> {{
    $reception->vehicule->immatriculation ?? '...' }}
                </p>
                <p>
                    <strong>Motif de la visite :</strong> {{ $reception->motif_visite ??
    '...' }}
                </p>
            </td>
        </tr>
    </table>

    <!-- Espace entre les deux -->
    <div style="height: 30px"></div>

    <!-- Tableau du visa -->
    <table class="main-table" style="width: 60%; margin: 0 auto">
        <tr>
            <td style="text-align: center; padding-bottom: 40px">
                <h3 style="text-decoration: underline">Visa de sortie</h3>
            </td>
        </tr>
        <tr>
            <td style="text-align: center">
                <h4 style="margin-bottom: 5px">Date de sortie</h4>
                <p style="font-weight: bold">
                    {{
    \Carbon\Carbon::parse($billetSortie->date_generation)->format('d/m/Y
            à H:i') ?? '...' }}
                </p>
            </td>
        </tr>
    </table>

    <footer>
        Les faits et gestes et réparations sur les véhicules ne sont pas de notre
        responsabilité.
    </footer>
</body>

</html>