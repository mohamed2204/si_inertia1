export default function Dashboard({ eleves }) {
    return (
        <div className="min-h-screen bg-gray-100 p-6">
            <div className="max-w-5xl mx-auto">
                {/* Titre */}
                <h1 className="text-2xl font-bold text-gray-800 mb-6">
                    Mes enfants
                </h1>

                {/* Aucun élève */}
                {eleves.length === 0 && (
                    <div className="bg-white p-6 rounded-lg shadow text-gray-500">
                        Aucun élève associé à ce compte parent.
                    </div>
                )}

                {/* Liste des élèves */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {eleves.map(eleve => (
                        <div
                            key={eleve.id}
                            className="bg-white rounded-xl shadow-sm border border-gray-200 p-5 hover:shadow-md transition"
                        >
                            <h3 className="text-lg font-semibold text-gray-900">
                                {eleve.nom} {eleve.prenom}
                            </h3>

                            <p className="mt-2 text-sm text-gray-600">
                                <span className="font-medium text-gray-700">
                                    Classe :
                                </span>{' '}
                                {eleve.inscriptions?.[0]?.classe?.nom ?? 'Non affectée'}
                            </p>

                            <div className="mt-4">
                                <span className="inline-block bg-blue-100 text-blue-800 text-xs font-medium px-3 py-1 rounded-full">
                                    Élève actif
                                </span>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    )
}
