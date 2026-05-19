import React, { useState, useEffect, useCallback } from 'react';
import Layout from "@/Layouts/layout";
import { Head, Link } from "@inertiajs/react";
import api from '@/Services/api';
import { debounce } from 'lodash';
import Pagination from '../Components/Pagination'; 
import Swal from 'sweetalert2';
import { Button } from 'primereact/button';
import { DataTable } from 'primereact/datatable';
import { Column } from 'primereact/column';
import { Tag } from 'primereact/tag'; 

// Correction de la signature unique (On prend results comme état initial)
export default function Index({ initialDepartments, filters, can_create }) {
    
    // On initialise avec une structure vide propre. 
    // Axios se chargera de remplir cela dès le premier rendu via le useEffect.
    const [tableData, setTableData] = useState({
        data: [],
        links: [],
        total: 0
    });
    
    const [loading, setLoading] = useState(true); // On commence à true car Axios va charger immédiatement
    const [options, setOptions] = useState({
        departments: initialDepartments || [],
        sousDepartments: [],
        statusList: [
            { id: 'en_attente', label: 'En attente' },
            { id: 'valide', label: 'Validé' },
            { id: 'rejete', label: 'Rejeté' }
        ]
    });

    // On s'assure que les paramètres ont des valeurs par défaut si filters est vide
    const [params, setParams] = useState({
        page: filters?.page || 1,
        search: filters?.search || '',
        department_id: filters?.department_id || '',
        sous_departement_id: filters?.sous_departement_id || '',
        statut: filters?.statut || '',
        per_page: 10,
        sort_by: 'created_at',
        sort_dir: 'desc'
    });

    // Template pour la date
    const dateBodyTemplate = (rowData) => {
        return rowData.date_debut
            ? `Du ${new Date(rowData.date_debut).toLocaleDateString()}`
            : 'Date non définie';
    };

    // Template pour le statut
    const statusBodyTemplate = (rowData) => {
        return <Tag value={rowData.statut?.toUpperCase()} severity={getStatusSeverity(rowData.statut)} />;
    };

    const getStatusSeverity = (status) => {
        switch (status) {
            case 'valide': return 'success';
            case 'en_attente': return 'warning';
            case 'rejete': return 'danger';
            default: return 'secondary';
        }
    };

    // Modèle d'affichage pour la colonne des actions CRUD (Sécurisé par ligne)
    const actionBodyTemplate = (rowData) => {
        return (
            <div className="flex gap-2 justify-content-center">
                {rowData.can_edit && (
                    <Link href={`/designations/${rowData.id}/edit`}>
                        <Button icon="pi pi-pencil" className="p-button-rounded p-button-warning p-button-sm" title="Modifier" />
                    </Link>
                )}

                {rowData.can_delete && (
                    <Button
                        icon="pi pi-trash"
                        className="p-button-rounded p-button-danger p-button-sm"
                        onClick={() => handleDelete(rowData.id)}
                        title="Supprimer"
                    />
                )}

                {!rowData.can_edit && !rowData.can_delete && (
                    <i className="pi pi-lock text-400" title="Lecture seule"></i>
                )}
            </div>
        );
    };

    // --- LOGIQUE DE CHARGEMENT API ---
    const loadDesignations = async () => {
        setLoading(true); 
        try {
            const response = await api.getDesignationsIndex(params);
            // Si votre service retourne directement la pagination ou response.data
            const resData = response.data?.results ? response.data.results : response.data;
            
            setTableData({
                data: resData.data || [],
                links: resData.links || [],
                total: resData.total || 0
            });
        } catch (error) {
            console.error("Erreur lors du chargement des désignations", error);
        } finally {
            setLoading(false); 
        }
    };

    // Déclencheur de requêtes Ajax lors du changement des filtres
    useEffect(() => {
        loadDesignations();
    }, [params.page, params.departement_id, params.sous_departement_id, params.statut, params.search, params.sort_by, params.sort_dir]);

    // Recherche avec Débounce
    const debouncedSearch = useCallback(
        debounce((value) => {
            setParams(prev => ({ ...prev, search: value, page: 1 }));
        }, 500),
        []
    );

    // Changement de département
    const handleDeptChange = async (deptId) => {
        setParams(prev => ({ ...prev, department_id: deptId, sous_departement_id: '', page: 1 }));
        if (deptId) {
            const data = await api.getSousDepts(deptId);
            setOptions(prev => ({ ...prev, sousDepartments: data }));
        } else {
            setOptions(prev => ({ ...prev, sousDepartments: [] }));
        }
    };

    // Suppression avec SweetAlert2
    const handleDelete = (id) => {
        Swal.fire({
            title: 'Êtes-vous sûr ?',
            text: "Cette action est irréversible !",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Oui, supprimer !',
            cancelButtonText: 'Annuler',
            reverseButtons: true 
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    await api.deleteDesignation(id);
                    loadDesignations(); // Rechargement asynchrone
                    Swal.fire('Supprimé !', 'La désignation a été supprimée.', 'success');
                } catch (error) {
                    Swal.fire('Erreur', 'Impossible de supprimer cette donnée.', 'error');
                }
            }
        });
    };

    // Gestion du tri natif de PrimeReact envoyé au serveur
    const handleSort = (e) => {
        setParams(prev => ({
            ...prev,
            sort_by: e.sortField,
            sort_dir: e.sortOrder === 1 ? 'asc' : 'desc'
        }));
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
                            value={params.department_id}
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
                            disabled={!params.department_id}
                            onChange={(e) => setParams(prev => ({ ...prev, sous_departement_id: e.target.value, page: 1 }))}
                        >
                            <option value="">Tous les Sous-Départs</option>
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

                    {/* Barre d'outils au-dessus du tableau */}
                    <div className="flex justify-between items-center mb-4">
                        <h2 className="text-xl font-semibold text-gray-800 m-0">Liste des Désignations</h2>
                        {can_create && (
                            <Link href="/designations/create">
                                <Button label="Nouvelle Désignation" icon="pi pi-plus" className="p-button-sm p-button-success" />
                            </Link>
                        )}
                    </div>

                    {/* TABLEAU */}
                    <div className="card shadow-2 border-round-xl overflow-hidden bg-white">
                        <DataTable
                            value={tableData.data}
                            loading={loading}
                            dataKey="id"
                            className="p-datatable-sm"
                            stripedRows
                            responsiveLayout="stack"
                            breakpoint="960px"
                            emptyMessage="Aucune désignation trouvée."
                            lazy // Dit à PrimeReact que le tri se fait côté serveur
                            sortField={params.sort_by}
                            sortOrder={params.sort_dir === 'asc' ? 1 : -1}
                            onSort={handleSort}
                        >
                            <Column field="semaine_nom" header="Semaine" sortable />

                            <Column
                                header="Département / Labo"
                                field="emplacement_formate"
                                sortable
                                body={(rowData) => rowData.emplacement_formate || 'N/A'}
                            />

                            <Column
                                field="date_debut" 
                                header="Date de début"
                                body={dateBodyTemplate}
                                sortable          
                            />

                            <Column
                                header="Statut"
                                field="statut"
                                body={statusBodyTemplate}
                                sortable
                            />

                            <Column
                                header="Créateur"
                                body={(rowData) => rowData.createur?.name || 'Inconnu'}
                            />

                            <Column
                                body={actionBodyTemplate}
                                header="Actions"
                                headerStyle={{ width: '12rem', textAlign: 'center' }}
                                bodyStyle={{ textAlign: 'center', overflow: 'visible' }}
                            />
                        </DataTable>

                        {/* Pagination controlée par le state local */}
                        <div className="mt-4 flex justify-between items-center p-3 bg-gray-50 border-t">
                            <p className="text-sm text-gray-600 font-medium">Total: {tableData.total} désignations</p>
                            <Pagination
                                links={tableData.links}
                                onPageChange={(page) => setParams(prev => ({ ...prev, page }))}
                            />
                        </div>
                    </div>
                </div>
            </div>
        </Layout>
    );
}

// import React, { useState, useEffect, useCallback } from 'react';
// import Layout from "@/Layouts/layout";
// import { Head, Link } from "@inertiajs/react";
// import api from '@/Services/api';
// import { debounce } from 'lodash';
// import Pagination from '../Components/Pagination'; // Composant à créer ci-dessous
// import Swal from 'sweetalert2';
// import { Button } from 'primereact/button';
// import { DataTable } from 'primereact/datatable';
// import { Column } from 'primereact/column';
// //import { Button } from 'primereact/button';
// import { Tag } from 'primereact/tag'; // Pour un plus beau rendu du statut

// export default function Index({ initialDepartments, can_create }) {export default function Index({ results, initialDepartments, filters, can_create }) {
    
//     // results.data contient le tableau de vos lignes de désignations mutées en PHP
//     const designations = results.data;
    
//     const [tableData, setTableData] = useState({ data: [], links: [], total: 0 });
//     const [loading, setLoading] = useState(false);
//     const [options, setOptions] = useState({
//         departments: initialDepartments || [],
//         sousDepartments: [],
//         statusList: [
//             { id: 'en_attente', label: 'En attente' },
//             { id: 'valide', label: 'Validé' },
//             { id: 'rejete', label: 'Rejeté' }
//         ]
//     });

//     const [params, setParams] = useState({
//         page: 1,
//         search: '',
//         department_id: '',
//         sous_departement_id: '',
//         statut: '',
//         per_page: 10,
//         sort_by: 'created_at',
//         sort_dir: 'desc'
//     });

//     // Template pour la date
//     const dateBodyTemplate = (rowData) => {
//         return rowData.date_debut
//             ? `Du ${new Date(rowData.date_debut).toLocaleDateString()}`
//             : 'Date non définie';
//     };

//     // Template pour le statut (Look Sakai)
//     const statusBodyTemplate = (rowData) => {
//         return <Tag value={rowData.statut} severity={getStatusSeverity(rowData.statut)} />;
//     };

//     // Fonction helper pour la couleur du tag
//     const getStatusSeverity = (status) => {
//         switch (status) {
//             case 'valide': return 'success';
//             case 'en_attente': return 'warning';
//             case 'rejete': return 'danger';
//             default: return 'secondary';

//         }
//     };

//     // Template pour les ACTIONS (Look image 1000136776.jpg)

//     // Modèle d'affichage pour la colonne des actions CRUD
//     const actionBodyTemplate = (rowData) => {
//         return (
//             <div className="flex gap-2 justify-content-center">
//                 {/* Le bouton Modifier ne s'affiche QUE si can_edit est vrai */}
//                 {rowData.can_edit && (
//                     <Link href={route('designations.edit', rowData.id)}>
//                         <Button icon="pi pi-pencil" className="p-button-rounded p-button-warning p-button-sm" title="Modifier" />
//                     </Link>
//                 )}

//                 {/* Le bouton Supprimer ne s'affiche QUE si can_delete est vrai */}
//                 {rowData.can_delete && (
//                     <Button
//                         icon="pi pi-trash"
//                         className="p-button-rounded p-button-danger p-button-sm"
//                         onClick={() => handleDelete(rowData.id)}
//                         title="Supprimer"
//                     />
//                 )}

//                 {/* Optionnel : Si l'utilisateur n'a aucun droit, on montre un cadenas */}
//                 {!rowData.can_edit && !rowData.can_delete && (
//                     <i className="pi pi-lock text-400" title="Lecture seule"></i>
//                 )}
//             </div>
//         );
//     };
//     // const actionBodyTemplate = (rowData) => {
//     //     return (
//     //         <div className="flex justify-end gap-2">
//     //             <Button icon="pi pi-search" rounded severity="info"
//     //                 onClick={() => window.location.href = `/api/designations/${rowData.id}`} />

//     //             <Button icon="pi pi-pencil" rounded severity="success"
//     //                 onClick={() => window.location.href = `/designations/${rowData.id}/edit`} />

//     //             <Button icon="pi pi-trash" rounded severity="warning"
//     //                 onClick={() => handleDelete(rowData.id)} />
//     //         </div>
//     //     );
//     // };

//     // --- LOGIQUE DE CHARGEMENT ---
//     const loadDesignations = async () => {
//         setLoading(true); // On affiche le spinner
//         try {
//             const response = await api.getDesignationsIndex(params);
//             // console.log("JSON reçu :", response); // Vérifie ici dans la console du navigateur
//             setTableData(response.data);
//         } catch (error) {
//             // console.error("Erreur API :", error);
//         } finally {
//             setLoading(false); // On cache le spinner
//         }
//     };

//     useEffect(() => {
//         loadDesignations();
//     }, [params.page, params.departement_id, params.sous_departement_id, params.statut, params.search]);

//     // --- GESTIONNAIRES D'ÉVÉNEMENTS ---

//     // Recherche avec Débounce
//     const debouncedSearch = useCallback(
//         debounce((value) => {
//             setParams(prev => ({ ...prev, search: value, page: 1 }));
//         }, 500),
//         []
//     );

//     // Changement de département (charge les sous-départs via API avec cache)
//     const handleDeptChange = async (deptId) => {
//         setParams(prev => ({ ...prev, departement_id: deptId, sous_departement_id: '', page: 1 }));
//         if (deptId) {
//             const data = await api.getSousDepts(deptId);
//             setOptions(prev => ({ ...prev, sousDepartments: data }));
//         } else {
//             setOptions(prev => ({ ...prev, sousDepartments: [] }));
//         }
//     };

//     // const handleSousDeptChange = async (sousDeptId) => {
//     //     // 1. On met à jour les paramètres pour filtrer la table
//     //     setParams(prev => ({
//     //         ...prev,
//     //         sous_department_id: sousDeptId,
//     //         lab_id: '', // On réinitialise le lab si on change de sous-département
//     //         page: 1
//     //     }));

//     //     // 2. On charge les laboratoires liés à ce sous-département
//     //     if (sousDeptId) {
//     //         try {
//     //             const data = await api.getLabs(sousDeptId); // Ton appel API centralisé
//     //             setOptions(prev => ({
//     //                 ...prev,
//     //                 labs: Array.isArray(data) ? data : []
//     //             }));
//     //         } catch (error) {
//     //             setOptions(prev => ({ ...prev, labs: [] }));
//     //         }
//     //     } else {
//     //         setOptions(prev => ({ ...prev, labs: [] }));
//     //     }
//     // };

//     // Fonction pour supprimer

//     // ... à l'intérieur de votre composant Index ...

//     const handleDelete = (id) => {
//         Swal.fire({
//             title: 'Êtes-vous sûr ?',
//             text: "Cette action est irréversible !",
//             icon: 'warning',
//             showCancelButton: true,
//             confirmButtonColor: '#d33', // Rouge pour la suppression
//             cancelButtonColor: '#3085d6',
//             confirmButtonText: 'Oui, supprimer !',
//             cancelButtonText: 'Annuler',
//             reverseButtons: true // Met l'annulation à gauche (standard UX)
//         }).then(async (result) => {
//             if (result.isConfirmed) {
//                 try {
//                     // Appel à votre service API
//                     await api.deleteDesignation(id);

//                     // Rechargement de la table MySQL
//                     loadDesignations();

//                     // Notification de succès
//                     Swal.fire(
//                         'Supprimé !',
//                         'La désignation a été supprimée avec succès.',
//                         'success'
//                     );
//                 } catch (error) {
//                     Swal.fire(
//                         'Erreur',
//                         'Impossible de supprimer cette donnée.',
//                         'error'
//                     );
//                 }
//             }
//         });
//     };

//     // Fonction pour dupliquer (très utile pour gagner du temps)
//     const handleDuplicate = async (id) => {
//         try {
//             await api.duplicateDesignation(id);
//             loadDesignations();
//             alert("Semaine dupliquée !");
//         } catch (error) {
//             alert("Erreur lors de la duplication");
//         }
//     };


//     return (
//         <Layout>
//             <div className="p-6 bg-gray-50 min-h-screen">
//                 <Head title="Liste des Désignations" />

//                 <div className="max-w-7xl mx-auto">
//                     <h1 className="text-2xl font-bold mb-6">Gestion des Désignations</h1>

//                     {/* BARRE DE FILTRES */}
//                     <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 bg-white p-4 rounded-lg shadow-sm">
//                         <input
//                             type="text"
//                             placeholder="Rechercher une semaine..."
//                             className="border rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500"
//                             onChange={(e) => debouncedSearch(e.target.value)}
//                         />

//                         <select
//                             className="border rounded-md px-3 py-2"
//                             value={params.departement_id}
//                             onChange={(e) => handleDeptChange(e.target.value)}
//                         >
//                             <option value="">Tous les Départements</option>
//                             {options.departments.map(d => (
//                                 <option key={d.id} value={d.id}>{d.nom}</option>
//                             ))}
//                         </select>

//                         <select
//                             className="border rounded-md px-3 py-2"
//                             value={params.sous_departement_id}
//                             disabled={!params.departement_id}
//                             onChange={(e) => setParams(prev => ({ ...prev, sous_departement_id: e.target.value, page: 1 }))}
//                         >
//                             <option value="">Tous les Sous-Départs</option>
//                             {/* Ajout du chaînage optionnel et vérification de tableau */}
//                             {Array.isArray(options.sousDepartments) && options.sousDepartments.map(sd => (
//                                 <option key={sd.id} value={sd.id}>{sd.nom}</option>
//                             ))}
//                         </select>

//                         <select
//                             className="border rounded-md px-3 py-2"
//                             value={params.statut}
//                             onChange={(e) => setParams(prev => ({ ...prev, statut: e.target.value, page: 1 }))}
//                         >
//                             <option value="">Tous les Statuts</option>
//                             {options.statusList.map(s => (
//                                 <option key={s.id} value={s.id}>{s.label}</option>
//                             ))}
//                         </select>
//                     </div>
//                     {/* Barre d'outils au-dessus du tableau */}
//                     <div className="flex justify-content-between align-items-center mb-4">
//                         <h2 className="text-xl font-semibold text-gray-800 m-0">Liste des Désignations</h2>
//                         {/* Le bouton de création globale ne s'affiche que si l'utilisateur a le droit d'ajouter au moins quelque part */}
//                         {can_create && (
//                             <Link href={route('designations.create')}>
//                                 <Button label="Nouvelle Désignation" icon="pi pi-plus" className="p-button-sm p-button-success" />
//                             </Link>
//                         )}
//                         {/* <Button
//                             label="Nouvelle Désignation"
//                             icon="pi pi-plus"
//                             severity="primary"
//                             className="p-button-raised border-round-lg"
//                             onClick={() => window.location.href = '/designations/create'}
//                         /> */}
//                     </div>

//                     {/* TABLEAU */}
//                     <div className="card shadow-2 border-round-xl overflow-hidden">
//                         <DataTable
//                             value={tableData?.data || []}
//                             loading={loading}
//                             dataKey="id"
//                             className="p-datatable-sm"
//                             stripedRows
//                             // On désactive la pagination interne si on utilise celle du serveur
//                             responsiveLayout="stack"
//                             breakpoint="960px"
//                             emptyMessage="Aucune désignation trouvée."
//                             // --- AJOUT DES PROPRIÉTÉS DE TRI PAR DÉFAUT ---
//                             sortField="date_debut"
//                             sortOrder={-1}
//                         >
//                             <Column field="semaine_nom" header="Semaine" sortable font-medium />

//                             <Column
//                                 header="Sous-Département"
//                                 body={(rowData) => rowData.sous_departement?.nom || 'N/A'}
//                             />

//                             <Column
//                                 field="date_debut" // <-- INDISPENSABLE pour lier le tri au champ
//                                 header="Date de début"
//                                 body={dateBodyTemplate}
//                                 sortable          // <-- Permet d'activer l'icône de tri au clic
//                             />

//                             <Column
//                                 header="Statut"
//                                 body={statusBodyTemplate}
//                             />

//                             <Column
//                                 header="Créateur"
//                                 body={(rowData) => rowData.createur?.name || 'Inconnu'}
//                             />

//                             <Column
//                                 body={actionBodyTemplate}
//                                 headerStyle={{ width: '12rem', textAlign: 'right' }}
//                                 bodyStyle={{ textAlign: 'right', overflow: 'visible' }}
//                             />
//                         </DataTable>

//                         {/* On garde votre composant Pagination externe pour gérer le côté serveur d'Inertia */}
//                         <div className="mt-4 flex justify-between items-center p-3 bg-gray-50 border-top-1 ">
//                             <p className="text-sm text-gray-600 font-medium">Total: {tableData.total} désignations</p>
//                             <Pagination
//                                 links={tableData.links}
//                                 onPageChange={(page) => setParams(prev => ({ ...prev, page }))}
//                             />
//                         </div>
//                     </div>
//                 </div>
//             </div>
//         </Layout>
//     );
// }

// // Helper pour les couleurs de statut
// const getStatusClass = (status) => {
//     switch (status) {
//         case 'publiee': return 'bg-green-100 text-green-800';
//         case 'en_attente': return 'bg-yellow-100 text-yellow-800';
//         default: return 'bg-gray-100 text-gray-800';
//     }
// };