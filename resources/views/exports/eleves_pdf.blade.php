<!DOCTYPE html>
<html lang="fr">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Rapport d'importation des élèves</title>
    <style>
        @page {
            margin: 1cm;
        }

        /* body {
            font-family: 'Helvetica', sans-serif;
            font-size: 11px;
        } */

        body {
            font-family: 'DejaVu Sans', sans-serif;
            /* Cette police supporte mieux l'UTF-8 que Helvetica */
            font-size: 12px;
        }

        .info-header {
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 2px solid #4a5568;
            padding-bottom: 10px;
        }

        .info-header td {
            border: none;
            padding: 4px 0;
        }

        .label {
            font-weight: bold;
            color: #4a5568;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            /* Force les colonnes à respecter la largeur */
            word-wrap: break-word;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 6px;
            text-align: left;
        }

        th {
            background-color: #4a5568;
            color: white;
        }

        .bg-red {
            background-color: #fed7d7;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: right;
            font-size: 9px;
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
            /* Ajustez la taille selon votre logo */
        }

        .title {
            text-align: right;
            font-size: 18px;
            font-weight: bold;
            color: #2d3748;
        }

        .text-right {
            text-align: right;
        }

        .font-mono {
            font-family: 'Courier', monospace;
            /* Optionnel : aide à l'alignement des chiffres */
        }
    </style>
</head>

<body>
    <!-- Header -->
    <table class="header-table">
        <tr>
            <td>
                {{-- Utilisation impérative de public_path() --}}
                <img src="{{ public_path('images/logo1.png') }}" class="logo">
            </td>
            <td class="title">
                RAPPORT D'IMPORTATION DES ÉLÈVES<br>
                <span style="font-size: 14px; font-weight: ;">Date: {{ date('d/m/Y H:i') }}</span>
            </td>
        </tr>
    </table>

    <!-- Informations sur la promotion, spécialité et phase -->
    <table class="info-header">
        <tr>
            <td width="25%">
                <span class="label">PROMOTION :</span> {{ $promotion }}
            </td>
            <td width="25%" style="text-align: center;">
                <span class="label">SPÉCIALITÉ :</span> {{ $specialite }}
            </td>
            <td width="25%" style="text-align: right;">
                <span class="label">PHASE :</span> {{ $phase }}
            </td>
            <td width="25%" style="text-align: right;">
                <span class="label">MATIERE :</span> {{ $matiere }}
            </td>
        </tr>
    </table>

    <!--  -->
    <table>
        <thead>
            <tr>
                @foreach ($headers as $header)
                    {{-- On aligne à droite uniquement si le titre contient "Note" --}}
                    <th class="{{ str_contains(strtolower($header), 'note') ? 'text-right' : '' }}">
                        {{ strtoupper(str_replace('_', ' ', $header)) }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($importData as $row)
                <tr>
                    @foreach ($row as $key => $value)
                        @php
                            $isNote = str_contains(strtolower($key), 'note');
                        @endphp
                        <td class="{{ $isNote ? 'text-right' : '' }}">
                            @if (is_numeric($value) && $isNote)
                                {{ number_format((float) $value, 2, ',', ' ') }}
                            @elseif(is_bool($value))
                                {{ $value ? 'OUI' : 'NON' }}
                            @else
                                {{ $value }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
        <!-- <thead>
            <tr>
                @foreach ($headers as $header)
<th>{{ strtoupper(str_replace('_', ' ', $header)) }}</th>
@endforeach
            </tr>
        </thead>
        <tbody> -->
        <!-- @foreach ($importData as $row)
-->
        <!-- <tr class="{{ isset($row['is_valid_eleve']) && !$row['is_valid_eleve'] ? 'bg-red' : '' }}">
                                @foreach ($row as $value)
<td>{{ is_bool($value) ? ($value ? 'OUI' : 'NON') : $value }}</td>
@endforeach
                            </tr> -->
        <!-- <tr class="{{ isset($row['is_valid_eleve']) && !$row['is_valid_eleve'] ? 'bg-red' : '' }}>
                    @foreach ($row as $key => $value)
@php
    // Détection si la colonne est une note ou un nombre
    // Vous pouvez adapter selon le nom de votre clé (ex: 'note', 'moyenne')
    $isNumeric =
        is_numeric($value) &&
        (str_contains($key, 'note') || str_contains($key, 'moyenne') || str_contains($key, 'score'));
@endphp

                       <td class=" {{ $isNumeric ? 'text-right font-mono' : '' }}">
                            @if (is_bool($value))
{{ $value ? 'OUI' : 'NON' }}
@elseif($isNumeric)
{{ number_format((float) $value, 2, ',', ' ') }}
@else
{{ $value }}
@endif
                        </td>
@endforeach
                </tr> -->
        <!--
@endforeach -->
        <!-- </tbody> -->
    </table>
    </table>

    {{-- Numérotation des pages automatique --}}
    <div class="footer">
        Page
        <script type="text/php">
        if (isset($pdf)) {
            echo $FONT_METRICS->get_canvas()->get_page_number() . " / " . $FONT_METRICS->get_canvas()->get_page_count();
        }
    </script>
    </div>
</body>

</html>
