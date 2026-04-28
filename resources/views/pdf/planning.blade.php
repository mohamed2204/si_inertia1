<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Planning - {{ $promotion->nom }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f4f4f4;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header-table {
            width: 100%;
            border: none;
            margin-bottom: 20px;
        }

        .header-table td {
            border: none;
            vertical-align: middle;
        }

        .logo {
            width: 120px;
        }

        .company-info {
            text-align: right;
            font-size: 10px;
            color: #666;
        }

        /* ... vos autres styles ... */
    </style>
</head>

<body>
    <div class="header">
        <h1>Emploi du Temps : {{ $promotion->nom }}</h1>
        <p>Généré le {{ now()->format('d/m/Y') }}</p>
    </div>

    <table class="header-table">
        <tr>
            <td>
                @php
                    $logoPath = public_path('images/logo.jpg');
                    $logoData = base64_encode(file_get_contents($logoPath));
                    $logoSrc = 'data:image/png;base64,' . $logoData;
                @endphp
                <img src="{{ $logoSrc }}" class="logo">
            </td>
            <td class="company-info">
                <strong>NOM DE VOTRE ÉTABLISSEMENT</strong><br>
                123 Rue de la Formation<br>
                75000 Paris<br>
                contact@ecole.fr
            </td>
        </tr>
    </table>
    <div class="header">
        <h2 style="text-transform: uppercase; border-bottom: 2px solid #333; padding-bottom: 5px;">
            Emploi du Temps : {{ $promotion->nom }}
        </h2>
    </div>
    <table>
        <thead>
            <tr>
                <th>Phase / Spécialité</th>
                <th>Date de début</th>
                <th>Date de fin</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($phases as $phase)
                <tr>
                    <td>
                        {{-- <span
                            style="display: inline-block; width: 10px; height: 10px; background-color: {{ $phase->specialite->couleur }}; margin-right: 5px;">
                        </span> --}}
                        {{ $phase->specialite->nom }}
                    </td>
                    <td>{{ $phase->date_debut->format('d/m/Y') }}</td>
                    <td>{{ $phase->date_fin->format('d/m/Y') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
