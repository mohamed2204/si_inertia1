<div class="border border-gray-200 rounded-xl overflow-hidden">
    <table class="w-full text-left divide-y divide-gray-200">
        <thead class="bg-gray-50">
        <tr>
            <th class="px-4 py-2">Matricule</th>
            <th class="px-4 py-2">Nom & Prénom</th>
            <th class="px-4 py-2">Statut / Erreur</th>
        </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
        @foreach($data as $index => $row)
            @php $error = $errors[$index] ?? null; @endphp
            <tr class="{{ $error ? 'bg-danger-50' : '' }}">
                <td class="px-4 py-2">{{ $row['matricule'] }}</td>
                <td class="px-4 py-2">{{ $row['nom'] }} {{ $row['prenom'] }}</td>
                <td class="px-4 py-2">
                    @if($error)
{{--                        <span class="text-danger-600 font-medium">❌ {{ $error }}</span>--}}
                    @else
                        <span class="text-success-600 font-medium">✅ Prêt</span>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
