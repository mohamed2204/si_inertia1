<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11pt;
            margin: 0;
            padding: 0;
        }

        .header {
            width: 100%;
            margin-bottom: 20px;
        }

        .left-header {
            float: left;
            width: 45%;
            text-align: center;
        }

        .right-header {
            float: right;
            width: 45%;
            text-align: center;
        }

        .clear {
            clear: both;
        }

        .title {
            text-align: center;
            text-decoration: underline;
            font-weight: bold;
            font-size: 14pt;
            margin: 20px 0;
        }

        .info-block {
            margin-bottom: 15px;
            line-height: 1.6;
        }

        .table-eleves {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .table-eleves th, .table-eleves td {
            border: 1px solid black;
            padding: 4px;
            font-size: 9pt;
            text-align: center;
        }

        .table-eleves th {
            background-color: #f2f2f2;
        }

        .nom-cell {
            text-align: left !important;
            padding-left: 10px !important;
        }

        .footer-sig {
            margin-top: 30px;
            width: 100%;
        }

        .footer-sig td {
            width: 50%;
            text-align: center;
            font-weight: bold;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
<div class="header">
    <div class="left-header">
        REPUBLIQUE FRANCAISE [cite: 1]<br>
        UNIVERSITE DE GRENOBLE [cite: 2]<br>
        N° ________ /D.E [cite: 5]
    </div>
    <div class="right-header">
        REPUBLIQUE FRANCAISE [cite: 3]<br>
        UNIVERSITE DE GRENOBLE [cite: 4]<br>
        N° ________ /DEP.SCO [cite: 6]
    </div>
</div>
<div class="clear"></div>

<div class="title">FICHE DE DEBUT DE FORMATION [cite: 7]</div>

<div class="info-block">
    <strong>Certificat :</strong> Elémentaire [cite: 8]<br>
    <strong>Phases :</strong> {{ $phases_noms }} [cite: 9]<br>
    <strong>Spécialité :</strong> {{ $specialite->nom }} [cite: 10]<br>
    <strong>Promotion :</strong> {{ $promotion->nom }} [cite: 13] &nbsp;&nbsp;&nbsp; <strong>Total Élèves
        :</strong> {{ $eleves->count() }} [cite: 13]<br>
    <strong>Date de début :</strong> {{ $promotion->date_debut }} [cite: 14] &nbsp;&nbsp;&nbsp; <strong>Date de fin
        :</strong> .......... [cite: 14]
</div>

<table class="table-eleves">
    <thead>
    <tr>
        <th>N°</th>
        <th>TITRE</th>
        <th>PRÉNOM</th>
        <th>NOM</th>
        <th>NUMÉRO IDENTITÉ</th>
        <th>NATION</th>
    </tr>
    </thead>
    <tbody>
    @foreach($eleves as $index => $eleve)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $eleve->titre ?? '' }}</td>
            <td class="nom-cell">{{ $eleve->prenom }}</td>
            <td class="nom-cell">{{ $eleve->nom }}</td>
            <td>{{ $eleve->identite ?? '..........' }}</td>
            <td>{{ $eleve->nationalite ?? 'FRA' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<table class="footer-sig">
    <tr>
        <td>Le Directeur [cite: 19]</td>
        <td>- Délégation [cite: 21]</td>
    </tr>
</table>
</body>
</html>
