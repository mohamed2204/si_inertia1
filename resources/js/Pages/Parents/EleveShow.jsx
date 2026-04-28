export default function EleveShow({ eleve }) {
    return (
        <div>
            <h1>{eleve.nom} {eleve.prenom}</h1>

            <ul>
                {eleve.inscriptions.map(i => (
                    <li key={i.id}>
                        {i.classe.nom} – {i.annee_scolaire}
                    </li>
                ))}
            </ul>
        </div>
    )
}
