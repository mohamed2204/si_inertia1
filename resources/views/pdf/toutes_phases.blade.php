<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: sans-serif;
            font-size: 11px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .phase-title {
            background: #444;
            color: #fff;
            padding: 5px;
            margin-top: 20px;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px;
        }

        th {
            background-color: #f2f2f2;
        }

        .text-center {
            text-align: center;
        }

        .red {
            color: red;
            font-weight: bold;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>BILAN GLOBAL PAR PHASE</h1>
        <h3>Spécialité : {{ $specialite->nom }} | Promotion : {{ $promotion->nom }}</h3>
    </div>

    @foreach($donneesPhases as $data)
        <div class="phase-title">Phase : {{ $data['phase']->nom }}</div>
        <table>
            <thead>
                <tr>
                    <th width="10%">Rang</th>
                    <th width="60%">Nom et Prénoms</th>
                    <th width="30%">Moyenne / 20</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['eleves'] as $eleve)
                    <tr>
                        <td class="text-center">{{ $eleve->rang }}{{ $eleve->rang == 1 ? 'er' : 'ème' }}</td>
                        <td>{{ $eleve->nom }} {{ $eleve->prenom }}</td>
                        <td class="text-center {{ $eleve->moyenne_phase < 12 ? 'red' : '' }}">
                            {{ number_format($eleve->moyenne_phase, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{-- Optionnel : <div class="page-break"></div> si vous voulez une page par phase --}}
    @endforeach
</body>

</html>
