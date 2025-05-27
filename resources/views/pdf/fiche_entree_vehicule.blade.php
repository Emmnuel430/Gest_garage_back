<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title>Fiche d'entrée véhicule</title>
    <style>
        @page {
            margin: 30px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 14px;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: flex-start;
        }

        .header img {
            height: 60px;
        }

        .bar {
            width: 2px;
            height: 40px;
            background-color: #254f9b;
            margin: 0 10px;
            border-radius: 20px;
        }

        .contact-info {
            font-size: 12px;
            line-height: 1.4;
            padding-top: 3px;
        }

        .title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin: 30px 0;
            color: #254f9b;
        }

        .info-table {
            width: 100%;
            margin-bottom: 30px;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 8px;
            vertical-align: top;
        }

        .photo {
            border: 1px solid #000;
            width: 150px;
            height: 160px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .photo-text {
            font-size: 12px;
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

        body {
            margin-bottom: 60px;
            /* pour éviter que le contenu cache le footer */
        }
    </style>
</head>

<body>
    <div class="header">
        <img src="{{ public_path('logo.png') }}" alt="Logo" />
        <div class="contact-info">
            (+225) 27 22 39 65 58<br />(+225) 07 09 09 31 45
        </div>
    </div>

    <div class="title">
        <h2>FICHE D’ENTRÉE</h2>
    </div>

    <table class="info-table">
        <tr>
            <td style="text-align: right;">
                <strong>Date d’arrivée :</strong> {{
    \Carbon\Carbon::parse($reception->date_arrivee)->format('d/m/Y à H:i')
    ?? '...' }}
            </td>
        </tr>
        <tr>
            <td>
                <strong>Enregistré par :</strong> {{ $reception->gardien->first_name
    ?? '...' }} {{ $reception->gardien->last_name ?? '' }}
            </td>
        </tr>
        <tr>
            <td>
                <strong>Mecanicien :</strong>
                {{ $reception->vehicule->mecanicien->nom . ' ' . $reception->vehicule->mecanicien->prenom ?? '...' }}
            </td>
        </tr>
        <tr>
            <td>
                <strong>Numéro de téléphone :</strong> {{ $reception->vehicule->mecanicien->contact ?? '...' }}
            </td>
        </tr>
        <tr>
            <td>
                <strong>Marque du véhicule :</strong> {{ $reception->vehicule->marque
    ?? '...' }}
            </td>
        </tr>
        <tr>
            <td>
                <strong>Modèle du véhicule :</strong> {{ $reception->vehicule->modele
    ?? '...' }}
            </td>
        </tr>
        <tr>
            <td>
                <strong>Immatriculation :</strong> {{
    $reception->vehicule->immatriculation ?? '...' }}
            </td>
        </tr>
        <tr>
            <td>
                <strong>Motif de la visite :</strong> {{ $reception->motif_visite ??
    '...' }}
            </td>
        </tr>

    </table>

    <table border="1" cellspacing="0" cellpadding="10" style="margin: 0 auto; width: 80%; text-align: left">
        <tr>
            <td colspan="2" style="height: 80px">
                <h3><strong>Observation :</strong></h3>
            </td>
        </tr>
        <tr>
            <td style="height: 100px; vertical-align: top; text-align: center">
                <strong>Visa d'entrée :</strong><br />
            </td>
            <td style="height: 100px; vertical-align: top; text-align: center">
                <strong>Visa de sortie :</strong><br />
            </td>
        </tr>
    </table>
    <footer>
        <div>
            <p>Les faits et gestes et réparations sur les véhicules ne sont pas de notre responsabilité</p>
        </div>
    </footer>

</body>

</html>