import React, { useState } from "react";
import Layout from "@/Layouts/layout";
import { Head, router } from "@inertiajs/react";
import { TabView, TabPanel } from "primereact/tabview";
import { Checkbox } from "primereact/checkbox";
import { RadioButton } from "primereact/radiobutton";
import Swal from "sweetalert2";

const AllPermissions = ({ groupes = [], modules = [], sousDepartements = [], modulePermissions = [], pivotPermissions = [] }) => {
    const [activeIndex, setActiveIndex] = useState(0);
    const [loadingKey, setLoadingKey] = useState(null);

    // --- LOGIQUE ONGLET 1 : MODULES (POLYMORPHES) ---
    const checkModulePermission = (groupId, moduleCode, action) => {
        return modulePermissions.some(p => p.group_id === groupId && p.module_type === moduleCode && p.type_action === action);
    };

    const handleModuleToggle = (groupId, moduleCode, action) => {
        const key = `mod-${groupId}-${moduleCode}-${action}`;
        setLoadingKey(key);

        router.post(route('admin.permissions.modules.toggle'), {
            group_id: groupId,
            module_type: moduleCode,
            type_action: action
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setLoadingKey(null);
                Swal.fire({ icon: 'success', title: 'Droit mis à jour', toast: true, position: 'top-end', showConfirmButton: false, timer: 1000 });
            },
            onError: () => setLoadingKey(null)
        });
    };

    // --- LOGIQUE ONGLET 2 : SOUS-DEPARTEMENTS (PIVOT) ---
    const getPivotAccessLevel = (groupId, sdId) => {
        const found = pivotPermissions.find(p => p.group_id === groupId && p.sous_departement_id === sdId);
        return found ? found.niveau_acces : 'aucune';
    };

    const handlePivotChange = (groupId, sdId, newLevel) => {
        const key = `piv-${groupId}-${sdId}-${newLevel}`;
        setLoadingKey(key);

        router.post(route('admin.permissions.pivot.update'), {
            groupe_id: groupId,
            sous_departement_id: sdId,
            niveau_acces: newLevel
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setLoadingKey(null);
                Swal.fire({ icon: 'success', title: 'Périmètre mis à jour', toast: true, position: 'top-end', showConfirmButton: false, timer: 1000 });
            },
            onError: () => setLoadingKey(null)
        });
    };

    return (
        <Layout>
            <Head title="Gestion des Habilitations" />

            <div className="p-4">
                <div className="mb-4">
                    <h2 className="text-xl font-bold m-0 text-800">
                        <i className="pi pi-lock text-blue-500 mr-2"></i>
                        Matrice de Sécurité Centrale
                    </h2>
                    <p className="text-500 text-sm m-0 mt-1">
                        Gérez les accès fonctionnels globaux et le cloisonnement par sous-département/labo sur un écran unique.
                    </p>
                </div>

                <div className="card shadow-2 border-round-xl bg-white p-2">
                    <TabView activeIndex={activeIndex} onTabChange={(e) => setActiveIndex(e.index)}>
                        
                        {/* ONGLET 1 : PERMISSIONS DE MODULES */}
                        <TabPanel header="1. Droits des Modules" leftIcon="pi pi-th-large mr-2">
                            <div className="overflow-x-auto p-2">
                                <table className="w-full text-left border-collapse custom-permission-table">
                                    <thead>
                                        <tr className="surface-100">
                                            <th className="p-3 text-700 font-bold text-sm border-bottom-2 surface-border" style={{ width: '30%' }}>Modules Applicatifs</th>
                                            {groupes.map(g => (
                                                <th key={g.id} className="p-3 text-700 font-bold text-sm text-center border-bottom-2 surface-border">
                                                    {g.name}
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {modules.map(mod => (
                                            <tr key={mod.code} className="hover:surface-50 border-bottom-1 surface-border">
                                                <td className="p-3 font-semibold text-sm text-800">{mod.name}</td>
                                                {groupes.map(g => {
                                                    const isRead = checkModulePermission(g.id, mod.code, 'lecture');
                                                    const isWrite = checkModulePermission(g.id, mod.code, 'modification');
                                                    const isDelete = checkModulePermission(g.id, mod.code, 'suppression');
                                                    const currentKey = `mod-${g.id}-${mod.code}`;

                                                    return (
                                                        <td key={g.id} className="p-3 text-center">
                                                            <div className="flex justify-content-center align-items-center gap-3">
                                                                {/* LECTURE */}
                                                                <div className="flex align-items-center gap-1">
                                                                    <Checkbox 
                                                                        inputId={`${currentKey}-r`} 
                                                                        checked={isRead} 
                                                                        onChange={() => handleModuleToggle(g.id, mod.code, 'lecture')}
                                                                        disabled={loadingKey !== null}
                                                                    />
                                                                    <label htmlFor={`${currentKey}-r`} className="text-xs text-500 cursor-pointer select-none">L</label>
                                                                </div>
                                                                {/* ECRITURE */}
                                                                <div className="flex align-items-center gap-1">
                                                                    <Checkbox 
                                                                        inputId={`${currentKey}-w`} 
                                                                        checked={isWrite} 
                                                                        onChange={() => handleModuleToggle(g.id, mod.code, 'modification')}
                                                                        disabled={loadingKey !== null}
                                                                    />
                                                                    <label htmlFor={`${currentKey}-w`} className="text-xs text-500 cursor-pointer select-none">É</label>
                                                                </div>
                                                                {/* SUPPRESSION */}
                                                                <div className="flex align-items-center gap-1">
                                                                    <Checkbox 
                                                                        inputId={`${currentKey}-d`} 
                                                                        checked={isDelete} 
                                                                        onChange={() => handleModuleToggle(g.id, mod.code, 'suppression')}
                                                                        disabled={loadingKey !== null}
                                                                    />
                                                                    <label htmlFor={`${currentKey}-d`} className="text-xs text-500 cursor-pointer select-none">S</label>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    );
                                                })}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                            <div className="text-xs text-500 mt-3 pl-2">
                                * Légende : <strong>L</strong> = Lecture | <strong>É</strong> = Écriture / Modification | <strong>S</strong> = Suppression.
                            </div>
                        </TabPanel>

                        {/* ONGLET 2 : ACCES AUX SOUS-DEPARTEMENTS */}
                        <TabPanel header="2. Périmètres Sous-Départements" leftIcon="pi pi-building mr-2">
                            <div className="overflow-x-auto p-2">
                                <table className="w-full text-left border-collapse custom-permission-table">
                                    <thead>
                                        <tr className="surface-100">
                                            <th className="p-3 text-700 font-bold text-sm border-bottom-2 surface-border" style={{ width: '30%' }}>Sous-Départements / Labs</th>
                                            {groupes.map(g => (
                                                <th key={g.id} className="p-3 text-700 font-bold text-sm text-center border-bottom-2 surface-border">
                                                    {g.name}
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {sousDepartements.map(sd => (
                                            <tr key={sd.id} className="hover:surface-50 border-bottom-1 surface-border">
                                                <td className="p-3 font-semibold text-sm text-800">{sd.nom}</td>
                                                {groupes.map(g => {
                                                    const activeLevel = getPivotAccessLevel(g.id, sd.id);
                                                    const radioName = `piv-${g.id}-${sd.id}`;

                                                    return (
                                                        <td key={g.id} className="p-3 text-center">
                                                            <div className="flex justify-content-center align-items-center gap-3">
                                                                {/* AUCUN */}
                                                                <div className="flex align-items-center gap-1">
                                                                    <RadioButton 
                                                                        inputId={`${radioName}-none`} name={radioName} value="aucune" 
                                                                        checked={activeLevel === 'aucune'} 
                                                                        onChange={() => handlePivotChange(g.id, sd.id, 'aucune')}
                                                                        disabled={loadingKey !== null}
                                                                    />
                                                                    <label htmlFor={`${radioName}-none`} className="text-xs text-500 cursor-pointer select-none">Aucun</label>
                                                                </div>
                                                                {/* LECTURE */}
                                                                <div className="flex align-items-center gap-1">
                                                                    <RadioButton 
                                                                        inputId={`${radioName}-read`} name={radioName} value="lecture" 
                                                                        checked={activeLevel === 'lecture'} 
                                                                        onChange={() => handlePivotChange(g.id, sd.id, 'lecture')}
                                                                        disabled={loadingKey !== null}
                                                                    />
                                                                    <label htmlFor={`${radioName}-read`} className="text-xs text-500 cursor-pointer select-none">Lect.</label>
                                                                </div>
                                                                {/* ECRITURE */}
                                                                <div className="flex align-items-center gap-1">
                                                                    <RadioButton 
                                                                        inputId={`${radioName}-write`} name={radioName} value="ecriture" 
                                                                        checked={activeLevel === 'ecriture'} 
                                                                        onChange={() => handlePivotChange(g.id, sd.id, 'ecriture')}
                                                                        disabled={loadingKey !== null}
                                                                    />
                                                                    <label htmlFor={`${radioName}-write`} className="text-xs text-500 cursor-pointer select-none">Écrit.</label>
                                                                </div>
                                                                {/* TOTAL */}
                                                                <div className="flex align-items-center gap-1">
                                                                    <RadioButton 
                                                                        inputId={`${radioName}-total`} name={radioName} value="total" 
                                                                        checked={activeLevel === 'total'} 
                                                                        onChange={() => handlePivotChange(g.id, sd.id, 'total')}
                                                                        disabled={loadingKey !== null}
                                                                    />
                                                                    <label htmlFor={`${radioName}-total`} className="text-xs text-500 cursor-pointer select-none">Total</label>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    );
                                                })}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </TabPanel>

                    </TabView>
                </div>
            </div>

            <style>{`
                .custom-permission-table th { white-space: nowrap; }
                .custom-permission-table td { vertical-align: middle; }
                .p-tabview .p-tabview-nav li.p-highlight .p-tabview-nav-link {
                    color: #2196F3 !important;
                    border-color: #2196F3 !important;
                }
                .p-checkbox .p-checkbox-box.p-highlight {
                    background: #2196F3 !important;
                    border-color: #2196F3 !important;
                }
                .p-radiobutton .p-radiobutton-box.p-highlight {
                    background: #4CAF50 !important;
                    border-color: #4CAF50 !important;
                }
            `}</style>
        </Layout>
    );
};

export default AllPermissions;