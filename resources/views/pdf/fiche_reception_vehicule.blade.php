<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8" />
  <title>Fiche de reception</title>
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
    <h3>FICHE DE RECEPTION</h3>
  </div>

  <table class="info-table">
    <tr>
      <td style="text-align: right">
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
        {{ $reception->vehicule->mecanicien->nom . ' ' .
  $reception->vehicule->mecanicien->prenom ?? '...' }}
      </td>
    </tr>
    <tr>
      <td>
        <strong>Numéro de téléphone :</strong> {{
  $reception->vehicule->mecanicien->contact ?? '...' }}
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

  <table border="1" cellspacing="0" cellpadding="4" style="width: 100%; border-collapse: collapse; font-size: 12px">
    <thead>
      <tr style="background-color: #f2f2f2; text-align: center">
        <th style="width: 60%">Désignations</th>
        <th style="width: 20%">Oui</th>
        <th style="width: 20%">Non</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>Pneu de secours</td>
        <td style="text-align: center">
          @if($check->pneus_secours) ✔️ @endif
        </td>
        <td style="text-align: center">
          @if(!$check->pneus_secours) ✔️ @endif
        </td>
      </tr>
      <tr>
        <td>Cric de roue</td>
        <td style="text-align: center">@if($check->cric) ✔️ @endif</td>
        <td style="text-align: center">@if(!$check->cric) ✔️ @endif</td>
      </tr>
      <tr>
        <td>État de pare-brise avant</td>
        <td style="text-align: center">
          @if($check->vitres_avant) ✔️ @endif
        </td>
        <td style="text-align: center">
          @if(!$check->vitres_avant) ✔️ @endif
        </td>
      </tr>
      <tr>
        <td>État de pare-brise arrière</td>
        <td style="text-align: center">
          @if($check->vitres_arriere) ✔️ @endif
        </td>
        <td style="text-align: center">
          @if(!$check->vitres_arriere) ✔️ @endif
        </td>
      </tr>
      <tr>
        <td>État de phare avant</td>
        <td style="text-align: center">
          @if($check->phares_avant) ✔️ @endif
        </td>
        <td style="text-align: center">
          @if(!$check->phares_avant) ✔️ @endif
        </td>
      </tr>
      <tr>
        <td>État de phare arrière</td>
        <td style="text-align: center">
          @if($check->phares_arriere) ✔️ @endif
        </td>
        <td style="text-align: center">
          @if(!$check->phares_arriere) ✔️ @endif
        </td>
      </tr>
      <tr>
        <td>État de peinture</td>
        <td style="text-align: center">@if($check->peinture) ✔️ @endif</td>
        <td style="text-align: center">@if(!$check->peinture) ✔️ @endif</td>
      </tr>
      <tr>
        <td>Rétroviseur</td>
        <td style="text-align: center">@if($check->retroviseur) ✔️ @endif</td>
        <td style="text-align: center">
          @if(!$check->retroviseur) ✔️ @endif
        </td>
      </tr>
      <tr>
        <td>Boîte à pharmacie</td>
        <td style="text-align: center">
          @if($check->kit_pharmacie) ✔️ @endif
        </td>
        <td style="text-align: center">
          @if(!$check->kit_pharmacie) ✔️ @endif
        </td>
      </tr>
      <tr>
        <td>
          Triangle de Signalisation <br />
          <small>(en cas de panne)</small>
        </td>
        <td style="text-align: center">@if($check->triangle) ✔️ @endif</td>
        <td style="text-align: center">@if(!$check->triangle) ✔️ @endif</td>
      </tr>
      <tr>
        <td colspan="3" style="height: 80px">
          <strong>Observation :</strong><br />
          {{ $check->remarques ?? 'Néant' }}
        </td>
      </tr>
    </tbody>
  </table>

  <footer>
    <div>
      <p>
        Les faits et gestes et réparations sur les véhicules ne sont pas de
        notre responsabilité
      </p>
    </div>
  </footer>
</body>

</html>