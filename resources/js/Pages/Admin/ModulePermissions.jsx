import React, { useState } from "react";
import Layout from "@/Layouts/layout";
import { Head, router } from "@inertiajs/react";
import { ToggleButton } from "primereact/togglebutton";
import { Card } from "primereact/card";
import { Badge } from "primereact/badge";
import Swal from "sweetalert2";

const ModulePermissions = ({ groupes = [], modules = [] }) => {
    const [loadingKey, setLoadingKey] = useState(null); // Format: "groupId-moduleCode-action"

    // Fonction déclenchée au clic sur un interrupteur d'action globale
    const handleActionToggle = (groupId, moduleCode, actionName) => {
        const key = `${groupId}-${moduleCode}-${actionName}`;
        setLoadingKey(key);

        router.post(route('admin.permissions.modules.toggle'), {
            group_id: groupId,
            module_type: moduleCode,
            type_action: actionName
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setLoadingKey(null);
                Swal.fire({
                    icon: 'success',
                    title: 'Permission mise à jour',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 1000
                });
            },
            onError: () => {
                setLoadingKey(null);
                Swal.fire({ icon: 'error', title: 'Erreur', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            }
        });
    };

    // Vérifie si un groupe possède une permission spécifique
    const hasPermission = (groupPermissions, moduleCode, actionName) => {
        return groupPermissions.some(p => p.module === moduleCode && p.action === actionName);
    };

    return (
        <Layout>
            <Head title="Habilitations Fonctionnelles des Modules" />

            <div className="p-4">
                <div className="mb-4">
                    <h2 className="text-xl font-bold m-0 text-800">
                        <i className="pi pi-shield text-blue-500 mr-2"></i>
                        Permissions Globales par Modules (Polymorphes)
                    </h2>
                    <p className="text-500 text-sm m-0 mt-1">
                        Attribuez les droits généraux d'accès aux fonctionnalités principales pour chaque groupe.
                    </p>
                </div>

                <div className="grid">
                    {groupes.map((groupe) => (
                        <div key={groupe.id} className="col-12 md:col-6 lg:col-4 mb-3">
                            <Card 
                                title={
                                    <div className="flex justify-content-between align-items-center border-bottom-1 surface-border pb-2">
                                        <span className="text-lg font-bold text-gray-800">{groupe.name}</span>
                                        <Badge value={groupe.code.toUpperCase()} severity="secondary"></Badge>
                                    </div>
                                } 
                                className="shadow-2 border-round-xl bg-white h-full"
                            >
                                <div className="flex flex-column gap-3 mt-2">
                                    {modules.map((mod) => {
                                        const readActive = hasPermission(groupe.permissions, mod.code, 'lecture');
                                        const writeActive = hasPermission(groupe.permissions, mod.code, 'modification');
                                        const deleteActive = hasPermission(groupe.permissions, mod.code, 'suppression');

                                        return (
                                            <div key={mod.code} className="surface-100 p-2 border-round-md">
                                                <div className="font-semibold text-sm text-700 mb-2">{mod.name}</div>
                                                
                                                <div className="flex gap-1 justify-content-between custom-module-toggles">
                                                    {/* LECTURE */}
                                                    <ToggleButton
                                                        checked={readActive}
                                                        onChange={() => handleActionToggle(groupe.id, mod.code, 'lecture')}
                                                        onLabel="Voir" offLabel="Voir"
                                                        onIcon="pi pi-eye" offIcon="pi pi-eye"
                                                        disabled={loadingKey === `${groupe.id}-${mod.code}-lecture`}
                                                        className="p-button-sm px-2 py-1 flex-1 text-xs"
                                                    />

                                                    {/* MODIFICATION / ECRITURE */}
                                                    <ToggleButton
                                                        checked={writeActive}
                                                        onChange={() => handleActionToggle(groupe.id, mod.code, 'modification')}
                                                        onLabel="Écrire" offLabel="Écrire"
                                                        onIcon="pi pi-pencil" offIcon="pi pi-pencil"
                                                        disabled={loadingKey === `${groupe.id}-${mod.code}-modification`}
                                                        className="p-button-sm px-2 py-1 flex-1 text-xs"
                                                    />

                                                    {/* SUPPRESSION */}
                                                    <ToggleButton
                                                        checked={deleteActive}
                                                        onChange={() => handleActionToggle(groupe.id, mod.code, 'suppression')}
                                                        onLabel="Suppr." offLabel="Suppr."
                                                        onIcon="pi pi-trash" offIcon="pi pi-trash"
                                                        disabled={loadingKey === `${groupe.id}-${mod.code}-suppression`}
                                                        className="p-button-sm px-2 py-1 flex-1 text-xs"
                                                    />
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </Card>
                        </div>
                    ))}
                </div>
            </div>

            {/* Custom Styles pour l'esthétique des boutons par carte */}
            <style>{`
                .custom-module-toggles .p-togglebutton {
                    font-size: 0.7rem !important;
                    background: #ffffff !important;
                    border: 1px solid #ced4da !important;
                    color: #495057 !important;
                }
                .custom-module-toggles .p-togglebutton.p-highlight {
                    background: #4CAF50 !important; /* Vert pour signifier que le droit global est actif */
                    border-color: #4CAF50 !important;
                    color: white !important;
                }
            `}</style>
        </Layout>
    );
};

export default ModulePermissions;