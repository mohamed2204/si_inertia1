<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Rapport</title>
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
        }

        .info-section {
            margin-bottom: 35px;
        }

        .info-row {
            margin-bottom: 6px;
            font-size: 14px;
        }

        /* Utilisation de float ou display: inline-block pour DomPDF */
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 100px;
        }

        /* Tableau conforme à votre image */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            border: 1.5px solid #000000;
            padding: 10px 12px;
            font-size: 14px;
            text-align: left;
            vertical-align: middle;
        }

        .data-table th {
            font-weight: bold;
        }

        /* Définition stricte des largeurs de colonnes */
        .col-date {
            width: 30%;
        }

        .col-1 {
            width: 35%;
        }

        .col-2 {
            width: 35%;
        }

        /* Sécurité pour éviter qu'une ligne du tableau se coupe maladroitement */
        tr {
            page-break-inside: avoid;
        }

        .data-table th {
            font-weight: bold;
            background-color: #f5f5f5;
            /* Si vous avez gardé le fond gris */
            text-align: center;
            /* <-- AJOUTEZ CETTE LIGNE */
        }
    </style>
</head>

<body>

    <div class="main-title">Titre</div>

    <div class="info-section">
        <div class="info-row">
            <span class="info-label">info 1</span>
            <span>: {{ $info1 }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">info 2</span>
            <span>: {{ $info2 }}</span>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th class="col-date" style="text-align: center;">date</th>
                <th class="col-1" style="text-align: center;">col 1</th>
                <th class="col-2" style="text-align: center;">col 2</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>{{ $item['date'] }}</td>
                <td>{{ $item['col1'] }}</td>
                <td>{{ $item['col2'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>