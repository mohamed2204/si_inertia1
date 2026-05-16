import React, { useState } from "react";
import Layout from "@/Layouts/layout";
import { Head, router } from "@inertiajs/react";
import { DataTable } from "primereact/datatable";
import { Column } from "primereact/column";
import { SelectButton } from "primereact/selectbutton";
import Swal from "sweetalert2";

const GroupPermissions = ({ matrixData = [], sousDepartements = [] }) => {
    // État pour afficher un spinner local pendant la sauvegarde d'une cellule
    const [updatingCell, setUpdatingCell] = useState(null); // Format: "groupeId-sdId"

    // Les options graphiques pour le niveau d'accès
    const accessOptions = [
        { label: "Aucun", value: "aucune", icon: "pi pi-times-circle" },
        { label: "Lecture", value: "lecture", icon: "pi pi-eye" },
        { label: "Écriture", value: "ecriture", icon: "pi pi-pencil" },
        { label: "Total", value: "total", icon: "pi pi-shield" },
    ];

    // Déclenché au changement d'un bouton dans la matrice
    const changeAccessLevel = (groupeId, sousDeptId, newLevel) => {
        if (!newLevel) return; // Évite le décochage complet (PrimeReact SelectButton renvoie null si on reclique)

        const cellKey = `${groupeId}-${sousDeptId}`;
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
                    title: 'Niveau mis à jour',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 1500
                });
            },
            onError: () => {
                setUpdatingCell(null);
                Swal.fire({ icon: 'error', title: 'Erreur d\'autorisation', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            }
        });
    };

    // Générateur dynamique de colonnes pour chaque sous-département
    const renderPivotColumns = () => {
        return sousDepartements.map((sd) => {
            return (
                <Column
                    key={sd.id}
                    header={sd.nom.toUpperCase()}
                    alignHeader="center"
                    align="center"
                    body={(rowData) => {
                        // On extrait le niveau actuel depuis l'objet formater ou par défaut "aucune"
                        const currentLevel = rowData.permissions[sd.id] || "aucune";
                        const isCurrentSaving = updatingCell === `${rowData.id}-${sd.id}`;

                        return (
                            <div className="flex flex-column align-items-center justify-content-center gap-1 py-2">
                                <SelectButton
                                    value={currentLevel}
                                    options={accessOptions}
                                    disabled={isCurrentSaving}
                                    onChange={(e) => changeAccessLevel(rowData.id, sd.id, e.value)}
                                    itemTemplate={(option) => (
                                        <div className="flex align-items-center gap-1 text-xs px-2 py-1">
                                            <i className={`${option.icon} text-xs`}></i>
                                            <span>{option.label}</span>
                                        </div>
                                    )}
                                    className="p-button-sm custom-select-pivot"
                                />
                                {isCurrentSaving && (
                                    <small className="text-blue-500 animate-pulse">
                                        <i className="pi pi-spin pi-spinner mr-1"></i> Synchronisation...
                                    </small>
                                )}
                            </div>
                        );
                    }}
                />
            );
        });
    };

    return (
        <Layout>
            <Head title="Matrice des Privilèges Pivot" />

            <div className="card p-4 shadow-2 border-round-xl bg-white">
                <div className="flex justify-content-between align-items-center mb-4 border-bottom-1 surface-border pb-3">
                    <div>
                        <h2 className="text-xl font-bold m-0 text-800">
                            <i className="pi pi-lock text-blue-500 mr-2"></i>
                            Matrice des Niveaux d'Accès par Sous-Département
                        </h2>
                        <p className="text-500 text-sm m-0 mt-1">
                            Configurez les droits CRUD des Groupes directement sur les intersections de la table pivot.
                        </p>
                    </div>
                </div>

                <DataTable 
                    value={matrixData} 
                    className="p-datatable-gridlines p-datatable-striped border-1 surface-border border-round-md overflow-hidden shadow-1"
                    emptyMessage="Aucun groupe configuré."
                >
                    {/* Première colonne fixe : Le nom du Groupe */}
                    <Column 
                        field="nom" 
                        header="GROUPE FONCTIONNEL" 
                        className="font-bold text-blue-800 bg-gray-50"
                        style={{ width: '200px', minWidth: '180px' }}
                    />
                    
                    {/* Colonnes injectées dynamiquement par rapport aux sous-départements */}
                    {renderPivotColumns()}
                </DataTable>
            </div>

            {/* Petite personnalisation CSS pour compacter les SelectButton */}
            <style>{`
                .custom-select-pivot .p-button {
                    padding: 0.35rem 0.6rem !important;
                    font-size: 0.75rem !important;
                }
                .custom-select-pivot .p-highlight {
                    background: #2196F3 !important;
                    border-color: #2196F3 !important;
                }
            `}</style>
        </Layout>
    );
};

export default GroupPermissions;