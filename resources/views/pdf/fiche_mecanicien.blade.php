<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Fiche d'enrôlement</title>
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

        .documents-title {
            font-weight: bold;
            border: 2px solid #254f9b;
            padding: 5px 10px;
            text-align: center;
            font-size: 20px;
            margin-top: 30px;
            margin-bottom: 15px;
            color: #254f9b;
        }

        ul.documents {
            list-style: square;
            padding-left: 20px;
        }
    </style>
</head>

<body>
    <div class="header">
        <img src="{{ public_path('logo.png') }}" alt="Logo">
        <div class="contact-info">
            (+225) 27 22 39 65 58<br>(+225) 07 09 09 31 45
        </div>
    </div>

    <div class="title">
        FICHE D’ENROLEMENT
    </div>

    <table class="info-table">
        <tr>
            <td><strong>Nom :</strong> {{ $mecanicien->nom ?? '...' }}</td>
            <td rowspan="6" style="text-align: right">
                <div class="photo">
                    <span class="photo-text">Photo</span>
                </div>
            </td>
        </tr>
        <tr>
            <td><strong>Prénom :</strong> {{ $mecanicien->prenom ?? '...' }}</td>
        </tr>
        <tr>
            <td><strong>Contact :</strong> {{ $mecanicien->contact ?? '...' }}</td>
        </tr>
        <tr>
            <td><strong>Nom de Garage :</strong> {{ $mecanicien->garage->nom ?? '...' }}</td>
        </tr>
        <tr>
            <td><strong>Expérience professionnelle :</strong> {{ $mecanicien->experience ?? '...' }} ans</td>
        </tr>
        <tr>
            <td><strong>Marque de voiture maîtrisée :</strong> {{ $mecanicien->vehicules_maitrises ?? '...' }}</td>
        </tr>
        <tr>
            <td colspan="2"><strong>N° à contacter en cas d'urgence :</strong>
                {{ $mecanicien->contact_urgence ?? '...' }}</td>
        </tr>
    </table>

    <div class="documents-title">
        <h3>DOCUMENTS A FOURNIR</h3>
    </div>

    <ul class="documents">
        <li>Permis de conduire</li>
        <li>02 photos d’identités</li>
        <li>02 tenues</li>
        <li>Chaussure de sécurité</li>
        <li>Un logo de votre garage</li>
        <li>Un Registre de Commerce (RCCM)</li>
        <li>Une Déclaration Fiscale d’existence (DFE) ou Quittance Mairie</li>
        <li>Un Contrat d’agrément signé</li>
    </ul>
</body>

</html>