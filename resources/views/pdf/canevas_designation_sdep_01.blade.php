<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Rapport de Planification</title>
    <style>
        /* Configuration de la page pour DomPDF */
        @page {
            size: a4;
            margin: 20mm;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #000000;
            line-height: 1.4;
        }

        .main-title {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .info-section {
            margin-bottom: 35px;
        }

        .info-row {
            margin-bottom: 8px;
            font-size: 14px;
        }

        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
            /* Légèrement augmenté pour s'ajuster aux intitulés */
        }

        /* Tableau conforme à votre structure */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .data-table th,
        .data-table td {
            border: 1.5px solid #000000;
            padding: 10px 12px;
            font-size: 13px;
            vertical-align: middle;
        }

        .data-table th {
            font-weight: bold;
            background-color: #f5f5f5;
            text-align: center;
        }

        /* Définition stricte des largeurs de colonnes */
        .col-date {
            width: 30%;
            text-align: center;
        }

        .col-labo {
            width: 35%;
            text-align: left;
        }

        .col-membre {
            width: 35%;
            text-align: left;
        }

        /* Sécurité anti-coupure de ligne sous DomPDF */
        tr {
            page-break-inside: avoid;
        }
    </style>
</head>

<body>

    <div class="main-title">Rapport de Planification</div>
    <div class="main-title">{{ $info2 }}</div>

    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Resp 1</span>
            <span>: {{ $info1 }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Resp 2</span>
            <span>: {{ $info2 }}</span>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th class="col-date">Jour / Date</th>
                <th class="col-labo">Entete 1</th>
                <th class="col-membre">Entete 2</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr class="{{ $item['type'] === 'fixe' ? 'bg-orange-50 font-bold' : '' }}">
                <td class="col-date">
                    <strong>{{ $item['jour'] }}</strong><br>
                    <strong>{{ $item['date'] }}</strong>
                </td>
                <td class="col-membre">{{ $item['prenom'] }} - {{ $item['membre'] }}</td>
                <td class="col-membre">{{ $item['prenom'] }} - {{ $item['membre'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>