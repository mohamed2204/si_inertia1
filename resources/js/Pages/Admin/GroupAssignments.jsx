import React, { useState } from "react";
import Layout from "@/Layouts/layout";
import { Head, router } from "@inertiajs/react";
import { DataTable } from "primereact/datatable";
import { Column } from "primereact/column";
import { MultiSelect } from "primereact/multiselect";
import { Tag } from "primereact/tag";
import Swal from "sweetalert2";
import { Badge } from 'primereact/badge';

const GroupAssignments = ({ users = [], groups = [] }) => {
    // État local pour suivre les modifications temporaires par utilisateur avant la sauvegarde
    const [savingId, setSavingId] = useState(null);

    // Fonction déclenchée dès qu'on change les groupes d'une ligne
    const handleGroupChange = (userId, selectedGroupIds) => {
        setSavingId(userId);

        // On envoie directement la mise à jour via Inertia
        router.put(route('admin.assignments.update', userId), {
            group_ids: selectedGroupIds
        }, {
            preserveScroll: true, // Évite que la page remonte au sommet
            onSuccess: () => {
                setSavingId(null);
                Swal.fire({ icon: 'success', title: 'Mis à jour !', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
            },
            onError: () => {
                setSavingId(null);
                Swal.fire({ icon: 'error', title: 'Erreur lors de la sauvegarde', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            }
        });
    };

    // Template pour afficher le MultiSelect dans la colonne "Groupes"
    const groupsTemplate = (rowData) => {
        // On extrait les IDs des groupes actuels de l'utilisateur
        const currentGroupIds = rowData.groups.map(g => g.id);

        return (
            <div className="flex align-items-center gap-2">
                <MultiSelect
                    value={currentGroupIds}
                    options={groups}
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Aucun groupe"
                    fixedPlaceholder
                    display="chip" // Affiche sous forme de jolis badges (Chips)
                    className="w-full max-w-24rem"
                    disabled={savingId === rowData.id}
                    onChange={(e) => handleGroupChange(rowData.id, e.value)}
                />
                {savingId === rowData.id && <i className="pi pi-spin pi-spinner text-blue-500"></i>}
            </div>
        );
    };

    return (
        <Layout>
            <Head title="Affectation des Groupes" />

            <div className="card p-4 shadow-2 border-round-xl">
                <div className="flex justify-content-between align-items-center mb-4 border-bottom-1 surface-border pb-3">
                    <h2 className="text-xl font-bold m-0 text-800 flex align-items-center gap-2">
                        <i className="pi pi-users text-blue-500"></i>
                        Gestion des Affectations
                        <Badge value={users.length} severity="info"></Badge>
                    </h2>
                    {/* <Tag severity="info" value={`${users.length} Utilisateurs`} /> */}
                </div>

                <p className="text-500 mb-4 mt-0">
                    Modifiez directement les groupes d'un utilisateur dans le sélecteur. La sauvegarde est automatique.
                </p>

                <DataTable
                    value={users}
                    paginator
                    rows={10}
                    rowsPerPageOptions={[10, 25, 50]}
                    className="p-datatable-striped border-1 surface-border border-round-md overflow-hidden"
                    emptyMessage="Aucun utilisateur trouvé."
                >
                    <Column field="name" header="NOM" sortable className="font-semibold text-700" style={{ width: '25%' }} />
                    <Column field="email" header="EMAIL" sortable text-600 style={{ width: '30%' }} />
                    <Column header="GROUPES ASSIGNÉS" body={groupsTemplate} style={{ width: '45%' }} />
                </DataTable>
            </div>
        </Layout>
    );
};

export default GroupAssignments;