import React, { useState } from "react";
import Layout from "@/Layouts/layout";
import { Head, router } from "@inertiajs/react";
import { DataTable } from "primereact/datatable";
import { Column } from "primereact/column";
import { ToggleButton } from "primereact/togglebutton"; // Nouveau composant
import { Badge } from "primereact/badge";
import Swal from "sweetalert2";

const GroupPermissions = ({ matrixData = [], groupes = [] }) => {
    const [updatingCell, setUpdatingCell] = useState(null); // Format: "sousDeptId-groupeId"

    // Fonction déclenchée au clic sur l'un des trois ToggleButtons
    const handleToggleChange = (sousDeptId, groupeId, buttonClicked, isCurrentlyActive) => {
        // Si le bouton était déjà actif et qu'on clique dessus, on désactive tout -> "aucune"
        // Sinon, la nouvelle valeur devient le bouton cliqué ('lecture', 'ecriture' ou 'total')
        const newLevel = isCurrentlyActive ? "aucune" : buttonClicked;

        const cellKey = `${sousDeptId}-${groupeId}`;
        setUpdatingCell(cellKey);

        router.post(route('admin.permissions.pivot.update'), {
            groupe_id: groupeId,
            sous_departement_id: sousDeptId,
            niveau_acces: newLevel
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setUpdatingCell(null);
                Swal.fire({
                    icon: 'success',
                    title: 'Droits synchronisés',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 1000
                });
            },
            onError: () => {
                setUpdatingCell(null);
                Swal.fire({ icon: 'error', title: 'Erreur', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            }
        });
    };

    return (
        <Layout>
            <Head title="Configuration des interrupteurs d'accès" />

            <div className="card p-4 shadow-2 border-round-xl bg-white">
                <div className="flex justify-content-between align-items-center mb-4 border-bottom-1 surface-border pb-3">
                    <div>
                        <h2 className="text-xl font-bold m-0 text-800 flex align-items-center gap-2">
                            <i className="pi pi-sliders-h text-blue-500"></i>
                            Matrice des Droits via ToggleButtons
                            <Badge value={matrixData.length} severity="info"></Badge>
                        </h2>
                        <p className="text-500 text-sm m-0 mt-1">
                            Activez ou désactivez individuellement les interrupteurs d'accès par groupe.
                        </p>
                    </div>
                </div>

                <DataTable 
                    value={matrixData} 
                    className="p-datatable-gridlines p-datatable-striped border-1 surface-border border-round-md overflow-hidden shadow-1"
                    paginator
                    rows={15}
                >
                    {/* Colonne fixe : Sous-départements */}
                    <Column 
                        field="nom" 
                        header="SOUS-DÉPARTEMENT / LABO" 
                        className="font-bold text-gray-800 bg-gray-50 text-sm"
                        style={{ width: '250px' }}
                        sortable
                    />
                    
                    {/* Colonnes dynamiques des groupes */}
                    {groupes.map((groupe) => (
                        <Column
                            key={groupe.id}
                            header={groupe.name.toUpperCase()}
                            alignHeader="center"
                            align="center"
                            body={(rowData) => {
                                const currentLevel = rowData.permissions[groupe.id] || "aucune";
                                const isSaving = updatingCell === `${rowData.id}-${groupe.id}`;

                                return (
                                    <div className="flex flex-column align-items-center gap-1 py-1">
                                        {/* Rangée d'interrupteurs ToggleButton */}
                                        <div className="flex gap-1 custom-toggle-group">
                                            
                                            {/* Bouton LECTURE */}
                                            <ToggleButton 
                                                checked={currentLevel === 'lecture'} 
                                                onChange={() => handleToggleChange(rowData.id, groupe.id, 'lecture', currentLevel === 'lecture')} 
                                                onLabel="Lecture" 
                                                offLabel="Lecture" 
                                                onIcon="pi pi-eye" 
                                                offIcon="pi pi-eye"
                                                disabled={isSaving}
                                                className="p-button-sm px-2 py-1"
                                            />

                                            {/* Bouton ÉCRITURE */}
                                            <ToggleButton 
                                                checked={currentLevel === 'ecriture'} 
                                                onChange={() => handleToggleChange(rowData.id, groupe.id, 'ecriture', currentLevel === 'ecriture')} 
                                                onLabel="Écriture" 
                                                offLabel="Écriture" 
                                                onIcon="pi pi-pencil" 
                                                offIcon="pi pi-pencil"
                                                disabled={isSaving}
                                                className="p-button-sm px-2 py-1"
                                            />

                                            {/* Bouton TOTAL */}
                                            <ToggleButton 
                                                checked={currentLevel === 'total'} 
                                                onChange={() => handleToggleChange(rowData.id, groupe.id, 'total', currentLevel === 'total')} 
                                                onLabel="Total" 
                                                offLabel="Total" 
                                                onIcon="pi pi-shield" 
                                                offIcon="pi pi-shield"
                                                disabled={isSaving}
                                                className="p-button-sm px-2 py-1"
                                            />
                                        </div>

                                        {isSaving && (
                                            <small className="text-blue-500 text-xs font-semibold animate-pulse">
                                                <i className="pi pi-spin pi-spinner mr-1"></i> Sauvegarde...
                                            </small>
                                        )}
                                    </div>
                                );
                            }}
                        />
                    ))}
                </DataTable>
            </div>

            {/* Design personnalisé pour donner un effet "Switch" haut de gamme */}
            <style>{`
                .custom-toggle-group .p-togglebutton {
                    font-size: 0.75rem !important;
                    background: #f8f9fa !important;
                    border: 1px solid #ced4da !important;
                    color: #495057 !important;
                    transition: all 0.2s;
                }
                /* Style lorsque le bouton est activé (coché) */
                .custom-toggle-group .p-togglebutton.p-highlight {
                    background: #2196F3 !important; /* Bleu PrimeReact */
                    border-color: #2196F3 !important;
                    color: white !important;
                    box-shadow: 0 2px 4px rgba(33, 150, 243, 0.3);
                }
                /* Effet de survol */
                .custom-toggle-group .p-togglebutton:not(.p-highlight):hover {
                    background: #e9ecef !important;
                    border-color: #bcd0e6 !important;
                }
            `}</style>
        </Layout>
    );
};

export default GroupPermissions;
// import React, { useState } from "react";
// import Layout from "@/Layouts/layout";
// import { Head, router } from "@inertiajs/react";
// import { DataTable } from "primereact/datatable";
// import { Column } from "primereact/column";
// import { SelectButton } from "primereact/selectbutton";
// import Swal from "sweetalert2";

// const GroupPermissions = ({ matrixData = [], sousDepartements = [] }) => {
//     // État pour afficher un spinner local pendant la sauvegarde d'une cellule
//     const [updatingCell, setUpdatingCell] = useState(null); // Format: "groupeId-sdId"

//     // Les options graphiques pour le niveau d'accès
//     const accessOptions = [
//         { label: "Aucun", value: "aucune", icon: "pi pi-times-circle" },
//         { label: "Lecture", value: "lecture", icon: "pi pi-eye" },
//         { label: "Écriture", value: "ecriture", icon: "pi pi-pencil" },
//         { label: "Total", value: "total", icon: "pi pi-shield" },
//     ];

//     // Déclenché au changement d'un bouton dans la matrice
//     const changeAccessLevel = (groupeId, sousDeptId, newLevel) => {
//         if (!newLevel) return; // Évite le décochage complet (PrimeReact SelectButton renvoie null si on reclique)

//         const cellKey = `${groupeId}-${sousDeptId}`;
//         setUpdatingCell(cellKey);

//         router.post(route('admin.permissions.pivot.update'), {
//             groupe_id: groupeId,
//             sous_departement_id: sousDeptId,
//             niveau_acces: newLevel
//         }, {
//             preserveScroll: true,
//             onSuccess: () => {
//                 setUpdatingCell(null);
//                 Swal.fire({
//                     icon: 'success',
//                     title: 'Niveau mis à jour',
//                     toast: true,
//                     position: 'top-end',
//                     showConfirmButton: false,
//                     timer: 1500
//                 });
//             },
//             onError: () => {
//                 setUpdatingCell(null);
//                 Swal.fire({ icon: 'error', title: 'Erreur d\'autorisation', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
//             }
//         });
//     };

//     // Générateur dynamique de colonnes pour chaque sous-département
//     const renderPivotColumns = () => {
//         return sousDepartements.map((sd) => {
//             return (
//                 <Column
//                     key={sd.id}
//                     header={sd.nom.toUpperCase()}
//                     alignHeader="center"
//                     align="center"
//                     body={(rowData) => {
//                         // On extrait le niveau actuel depuis l'objet formater ou par défaut "aucune"
//                         const currentLevel = rowData.permissions[sd.id] || "aucune";
//                         const isCurrentSaving = updatingCell === `${rowData.id}-${sd.id}`;

//                         return (
//                             <div className="flex flex-column align-items-center justify-content-center gap-1 py-2">
//                                 <SelectButton
//                                     value={currentLevel}
//                                     options={accessOptions}
//                                     disabled={isCurrentSaving}
//                                     onChange={(e) => changeAccessLevel(rowData.id, sd.id, e.value)}
//                                     itemTemplate={(option) => (
//                                         <div className="flex align-items-center gap-1 text-xs px-2 py-1">
//                                             <i className={`${option.icon} text-xs`}></i>
//                                             <span>{option.label}</span>
//                                         </div>
//                                     )}
//                                     className="p-button-sm custom-select-pivot"
//                                 />
//                                 {isCurrentSaving && (
//                                     <small className="text-blue-500 animate-pulse">
//                                         <i className="pi pi-spin pi-spinner mr-1"></i> Synchronisation...
//                                     </small>
//                                 )}
//                             </div>
//                         );
//                     }}
//                 />
//             );
//         });
//     };

//     return (
//         <Layout>
//             <Head title="Matrice des Privilèges Pivot" />

//             <div className="card p-4 shadow-2 border-round-xl bg-white">
//                 <div className="flex justify-content-between align-items-center mb-4 border-bottom-1 surface-border pb-3">
//                     <div>
//                         <h2 className="text-xl font-bold m-0 text-800">
//                             <i className="pi pi-lock text-blue-500 mr-2"></i>
//                             Matrice des Niveaux d'Accès par Sous-Département
//                         </h2>
//                         <p className="text-500 text-sm m-0 mt-1">
//                             Configurez les droits CRUD des Groupes directement sur les intersections de la table pivot.
//                         </p>
//                     </div>
//                 </div>

//                 <DataTable 
//                     value={matrixData} 
//                     className="p-datatable-gridlines p-datatable-striped border-1 surface-border border-round-md overflow-hidden shadow-1"
//                     emptyMessage="Aucun groupe configuré."
//                 >
//                     {/* Première colonne fixe : Le nom du Groupe */}
//                     <Column 
//                         field="nom" 
//                         header="GROUPE FONCTIONNEL" 
//                         className="font-bold text-blue-800 bg-gray-50"
//                         style={{ width: '200px', minWidth: '180px' }}
//                     />
                    
//                     {/* Colonnes injectées dynamiquement par rapport aux sous-départements */}
//                     {renderPivotColumns()}
//                 </DataTable>
//             </div>

//             {/* Petite personnalisation CSS pour compacter les SelectButton */}
//             <style>{`
//                 .custom-select-pivot .p-button {
//                     padding: 0.35rem 0.6rem !important;
//                     font-size: 0.75rem !important;
//                 }
//                 .custom-select-pivot .p-highlight {
//                     background: #2196F3 !important;
//                     border-color: #2196F3 !important;
//                 }
//             `}</style>
//         </Layout>
//     );
// };

// export default GroupPermissions;