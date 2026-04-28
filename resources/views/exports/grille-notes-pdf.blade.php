<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            text-align: center;
        }

        .text-center {
            text-align: center;
        }

        .red {
            color: red;
            font-weight: bold;
        }

        .signatures {
            margin-top: 50px;
            width: 100%;
        }

        .sig-box {
            width: 33%;
            float: left;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>PROCÈS-VERBAL DES MOYENNES</h2>
        <h3>PHASE : {{ strtoupper($phase->nom) }}</h3>
        <p>Spécialité : {{ $specialite->nom }} | Promotion : {{ $promotion->nom }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="10%">RANG</th>
                <th width="60%">NOM ET PRÉNOMS</th>
                <th width="30%">MOYENNE / 20</th>
            </tr>
        </thead>
        <tbody>
            @foreach($eleves as $eleve)
                <tr>
                    <td class="text-center">{{ $eleve->rang }}{{ $eleve->rang == 1 ? 'er' : 'ème' }}</td>
                    <td>{{ $eleve->nom }} {{ $eleve->prenom }}</td>
                    <td class="text-center {{ $eleve->moyenne_calculee < 12 ? 'red' : '' }}">
                        {{ number_format($eleve->moyenne_calculee, 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signatures">
        <div class="sig-box">Le Formateur<br><br>..........</div>
        <div class="sig-box">Le Jury<br><br>..........</div>
        <div class="sig-box">La Direction<br><br>..........</div>
    </div>
</body>

</html>