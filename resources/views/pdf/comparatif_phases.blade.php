<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: sans-serif;
            font-size: 10px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
        }

        th {
            background-color: #f2f2f2;
        }

        .nom-col {
            text-align: left;
            padding-left: 8px;
            font-weight: bold;
        }

        .red {
            color: red;
        }

        .moy-gen {
            background-color: #eee;
            font-weight: bold;
        }

        .text-ns {
            color: #808080;
            font-size: 0.9em;
        }
    </style>
    <title>Comparatif par phase</title>
</head>
<body>
<div class="header">
    <h2>RÉCAPITULATIF GÉNÉRAL DES MOYENNES PAR PHASE</h2>
    <p>Spécialité : {{ $specialite->nom }} | Promotion : {{ $promotion->nom }}</p>
</div>

<table>
    <thead>
    <tr>
        <th rowspan="2" width="25%">NOM ET PRÉNOMS</th>
        {{-- Chaque phase n'occupe plus qu'une seule colonne (Moy) --}}
        @foreach($phases as $phase)
            <th>{{ strtoupper($phase->nom) }}</th>
        @endforeach
        <th colspan="2" class="moy-gen">GÉNÉRAL</th>
    </tr>
    <tr>
        {{-- Sous-titres pour les phases --}}
        @foreach($phases as $phase)
            <th>Moy</th>
        @endforeach
        <th class="moy-gen">Moy</th>
        <th class="moy-gen">Rg</th>
    </tr>
    </thead>
    <tbody>
    @foreach($donneesEleves as $eleve)
        <tr>
            <td class="nom-col">{{ $eleve['nom'] }}</td>

            {{-- Boucle des phases : uniquement la moyenne --}}
            @foreach($phases as $phase)
                @php
                    $moyenne = $eleve['phases'][$phase->id]['moy'] ?? 'NS';
                @endphp
                <td class="{{ is_numeric($moyenne) && $moyenne < 12 ? 'red' : '' }} {{ !is_numeric($moyenne) ? 'text-ns' : '' }}"
                    style="text-align: center;">
                    @if(is_numeric($moyenne))
                        {{ number_format((float)$moyenne, 2) }}
                    @else
                        <span style="font-style: italic; color: #808080;">{{ $moyenne }}</span>
                    @endif
                </td>
            @endforeach

            {{-- Colonnes Générales : Moyenne + Rang --}}
            <td class="moy-gen" style="text-align: center; font-weight: bold;">
                {{ number_format($eleve['moyenne_generale'], 2) }}
            </td>
            <td class="moy-gen" style="text-align: center; font-weight: bold;">
                {{ $eleve['rang_general'] }}
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
