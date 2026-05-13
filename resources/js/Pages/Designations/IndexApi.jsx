import React, { useState, useEffect, useCallback } from 'react';
import Layout from "@/Layouts/layout";
import { Head } from '@inertiajs/react';
import api from '@/Services/api';
import { debounce } from 'lodash';
import Pagination from '../Components/Pagination'; // Composant à créer ci-dessous
import Swal from 'sweetalert2';


export default function Index({ initialDepartments }) {
    // --- ÉTATS ---
    const [tableData, setTableData] = useState({ data: [], links: [], total: 0 });
    const [loading, setLoading] = useState(false);
    const [options, setOptions] = useState({
        departments: initialDepartments || [],
        sousDepartments: [],
        statusList: [
            { id: 'en_attente', label: 'En attente' },
            { id: 'valide', label: 'Validé' },
            { id: 'rejete', label: 'Rejeté' }
        ]
    });

    const [params, setParams] = useState({
        page: 1,
        search: '',
        department_id: '',
        sous_departement_id: '',
        statut: '',
        per_page: 10,
        sort_by: 'created_at',
        sort_dir: 'desc'
    });

    // --- LOGIQUE DE CHARGEMENT ---
    const loadDesignations = async () => {
        setLoading(true); // On affiche le spinner
        try {
            const response = await api.getDesignationsIndex(params);
            // console.log("JSON reçu :", response); // Vérifie ici dans la console du navigateur
            setTableData(response.data);
        } catch (error) {
            // console.error("Erreur API :", error);
        } finally {
            setLoading(false); // On cache le spinner
        }
    };

    useEffect(() => {
        loadDesignations();
    }, [params.page, params.departement_id, params.sous_departement_id, params.statut, params.search]);

    // --- GESTIONNAIRES D'ÉVÉNEMENTS ---

    // Recherche avec Débounce
    const debouncedSearch = useCallback(
        debounce((value) => {
            setParams(prev => ({ ...prev, search: value, page: 1 }));
        }, 500),
        []
    );

    // Changement de département (charge les sous-départs via API avec cache)
    const handleDeptChange = async (deptId) => {
        setParams(prev => ({ ...prev, departement_id: deptId, sous_departement_id: '', page: 1 }));
        if (deptId) {
            const data = await api.getSousDepts(deptId);
            setOptions(prev => ({ ...prev, sousDepartments: data }));
        } else {
            setOptions(prev => ({ ...prev, sousDepartments: [] }));
        }
    };

    // const handleSousDeptChange = async (sousDeptId) => {
    //     // 1. On met à jour les paramètres pour filtrer la table
    //     setParams(prev => ({
    //         ...prev,
    //         sous_department_id: sousDeptId,
    //         lab_id: '', // On réinitialise le lab si on change de sous-département
    //         page: 1
    //     }));

    //     // 2. On charge les laboratoires liés à ce sous-département
    //     if (sousDeptId) {
    //         try {
    //             const data = await api.getLabs(sousDeptId); // Ton appel API centralisé
    //             setOptions(prev => ({
    //                 ...prev,
    //                 labs: Array.isArray(data) ? data : []
    //             }));
    //         } catch (error) {
    //             setOptions(prev => ({ ...prev, labs: [] }));
    //         }
    //     } else {
    //         setOptions(prev => ({ ...prev, labs: [] }));
    //     }
    // };

    // Fonction pour supprimer

    // ... à l'intérieur de votre composant Index ...

    const handleDelete = (id) => {
        Swal.fire({
            title: 'Êtes-vous sûr ?',
            text: "Cette action est irréversible !",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33', // Rouge pour la suppression
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Oui, supprimer !',
            cancelButtonText: 'Annuler',
            reverseButtons: true // Met l'annulation à gauche (standard UX)
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    // Appel à votre service API
                    await api.deleteDesignation(id);

                    // Rechargement de la table MySQL
                    loadDesignations();

                    // Notification de succès
                    Swal.fire(
                        'Supprimé !',
                        'La désignation a été supprimée avec succès.',
                        'success'
                    );
                } catch (error) {
                    Swal.fire(
                        'Erreur',
                        'Impossible de supprimer cette donnée.',
                        'error'
                    );
                }
            }
        });
    };

    // Fonction pour dupliquer (très utile pour gagner du temps)
    const handleDuplicate = async (id) => {
        try {
            await api.duplicateDesignation(id);
            loadDesignations();
            alert("Semaine dupliquée !");
        } catch (error) {
            alert("Erreur lors de la duplication");
        }
    };
    return (
        <Layout>
            <div className="p-6 bg-gray-50 min-h-screen">
                <Head title="Liste des Désignations" />

                <div className="max-w-7xl mx-auto">
                    <h1 className="text-2xl font-bold mb-6">Gestion des Désignations</h1>

                    {/* BARRE DE FILTRES */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 bg-white p-4 rounded-lg shadow-sm">
                        <input
                            type="text"
                            placeholder="Rechercher une semaine..."
                            className="border rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500"
                            onChange={(e) => debouncedSearch(e.target.value)}
                        />

                        <select
                            className="border rounded-md px-3 py-2"
                            value={params.departement_id}
                            onChange={(e) => handleDeptChange(e.target.value)}
                        >
                            <option value="">Tous les Départements</option>
                            {options.departments.map(d => (
                                <option key={d.id} value={d.id}>{d.nom}</option>
                            ))}
                        </select>

                        <select
                            className="border rounded-md px-3 py-2"
                            value={params.sous_departement_id}
                            disabled={!params.departement_id}
                            onChange={(e) => setParams(prev => ({ ...prev, sous_departement_id: e.target.value, page: 1 }))}
                        >
                            <option value="">Tous les Sous-Départs</option>
                            {/* Ajout du chaînage optionnel et vérification de tableau */}
                            {Array.isArray(options.sousDepartments) && options.sousDepartments.map(sd => (
                                <option key={sd.id} value={sd.id}>{sd.nom}</option>
                            ))}
                        </select>

                        <select
                            className="border rounded-md px-3 py-2"
                            value={params.statut}
                            onChange={(e) => setParams(prev => ({ ...prev, statut: e.target.value, page: 1 }))}
                        >
                            <option value="">Tous les Statuts</option>
                            {options.statusList.map(s => (
                                <option key={s.id} value={s.id}>{s.label}</option>
                            ))}
                        </select>
                    </div>

                    {/* TABLEAU */}
                    <div className="bg-white rounded-lg shadow overflow-hidden">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-100">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Semaine</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sous-Département</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dates</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Créateur</th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {loading ? (
                                    <tr>
                                        <td colSpan="6" className="text-center py-10 text-gray-500">
                                            <span className="flex justify-center items-center gap-2">
                                                <svg className="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Chargement des données...
                                            </span>
                                        </td>
                                    </tr>
                                ) : (tableData?.data && tableData.data.length > 0) ? (
                                    tableData.data.map((item) => (
                                        <tr key={item.id} className="hover:bg-gray-50 transition-colors">
                                            <td className="px-6 py-4 font-medium text-gray-900">{item.semaine_nom}</td>
                                            {/* Utilisation du chaînage optionnel pour éviter les erreurs sur les relations */}
                                            <td className="px-6 py-4 text-gray-600">{item.sous_departement?.nom || 'N/A'}</td>
                                            <td className="px-6 py-4 text-sm text-gray-600">
                                                {item.date_debut ? `Du ${new Date(item.date_debut).toLocaleDateString()}` : 'Date non définie'}
                                            </td>
                                            <td className="px-6 py-4">
                                                <span className={`px-2 py-1 rounded-full text-xs font-semibold ${getStatusClass(item.statut)}`}>
                                                    {item.statut}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-600">{item.createur?.name || 'Inconnu'}</td>
                                            <td className="px-6 py-4 text-right">
                                                <div className="flex justify-end items-center gap-2">
                                                    {/* BOUTON VOIR */}
                                                    <button
                                                        onClick={() => window.location.href = `/api/designations/${item.id}`}
                                                        className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors duration-200"
                                                        title="Voir les détails"
                                                    >
                                                        <svg className="h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                    </button>

                                                    {/* BOUTON MODIFIER */}
                                                    <button
                                                        onClick={() => window.location.href = `/api/designations/${item.id}/edit`}
                                                        className="p-2 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors duration-200"
                                                        title="Modifier"
                                                    >
                                                        <svg className="h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                    </button>

                                                    {/* BOUTON DUPLIQUER */}
                                                    <button
                                                        onClick={() => handleDuplicate(item.id)}
                                                        className="p-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors duration-200"
                                                        title="Dupliquer"
                                                    >
                                                        <svg className="h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                                                        </svg>
                                                    </button>

                                                    {/* BOUTON SUPPRIMER */}
                                                    <button
                                                        onClick={() => handleDelete(item.id)}
                                                        className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors duration-200"
                                                        title="Supprimer"
                                                    >
                                                        <svg className="h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="6" className="text-center py-10 text-gray-500 italic">
                                            Aucune désignation trouvée.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* PAGINATION */}
                    <div className="mt-4 flex justify-between items-center">
                        <p className="text-sm text-gray-600">Total: {tableData.total} désignations</p>
                        <Pagination
                            links={tableData.links}
                            onPageChange={(page) => setParams(prev => ({ ...prev, page }))}
                        />
                    </div>
                </div>
            </div>
        </Layout>
    );
}

// Helper pour les couleurs de statut
const getStatusClass = (status) => {
    switch (status) {
        case 'valide': return 'bg-green-100 text-green-800';
        case 'en_attente': return 'bg-yellow-100 text-yellow-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};