<style>
    body {
        font-family: sans-serif;
        font-size: 11px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    th,
    td {
        border: 1px solid #000;
        padding: 5px;
        text-align: center;
    }

    th {
        background-color: #f2f2f2;
    }

    .nom-eleve {
        text-align: left;
        width: 200px;
    }

    .note-alerte {
        color: red;
        font-weight: bold;
    }

    .moyenne-cell {
        background-color: #eee;
        font-weight: bold;
    }

    .header-section {
        text-align: center;
        margin-bottom: 20px;
    }
</style>

<div class="header-section">
    <h1>PROCES-VERBAL DES RESULTATS : {{ strtoupper($phase->nom) }}</h1>
    <p>Spécialité : {{ $specialite->nom }}</p>
</div>

<table>
    <thead>
        <tr>
            <th class="nom-eleve">Nom & Prénoms</th>
            @foreach ($programmeMatiere as $pm)
                @php
                    $abrvMatiere = strtoupper(substr($pm->matiere->nom, 0, 4)); // Abréviation de la matière 4 lettres
                @endphp
                <th> {{ $abrvMatiere }} <br><small>{{ $pm->code }}</small><br><small>Coeff: {{ $pm->coefficient }}</small></th>
            @endforeach
            <th>MOYENNE</th>
            <th>RANG</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($dataEleves as $data)
            <tr>
                <td class="nom-eleve">{{ $data['obj']->nom }} {{ $data['obj']->prenom }}</td>
                @foreach ($programmeMatiere as $pm)
                    @php
                        $note = $data['obj']->notes->firstWhere('programme_matiere_id', $pm->id);
                        $val = $note ? (float) $note->valeur : null;
                    @endphp
                    <td class="{{ $val !== null && $val < 12 ? 'note-alerte' : '' }}">
                        {{ $val !== null ? number_format($val, 2) : 'NS' }}
                    </td>
                @endforeach
                <td class="moyenne-cell {{ $data['moyenne'] < 12 ? 'note-alerte' : '' }}">
                    {{ number_format($data['moyenne'], 2) }}
                </td>
                <td>
                    {{ $data['rang'] }}{{ $data['rang'] == 1 ? 'er' : 'ème' }}
                    {{ $data['is_ex'] ? 'ex' : '' }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
