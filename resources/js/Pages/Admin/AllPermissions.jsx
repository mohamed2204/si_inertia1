import React, { useState, useMemo } from "react";
import Layout from "@/Layouts/layout";
import { Head, router } from "@inertiajs/react";
import { TabView, TabPanel } from "primereact/tabview";
import { Checkbox } from "primereact/checkbox";
import { Dropdown } from "primereact/dropdown";
import Swal from "sweetalert2";

const AllPermissions = ({ 
    groupes = [], 
    modules = [], 
    sousDepartements = [], 
    utilisateurs = [],       
    modulePermissions = [],  
    affectations = []        
}) => {
    const [activeIndex, setActiveIndex] = useState(0);
    const [loadingKey, setLoadingKey] = useState(null);
    const [selectedGroup, setSelectedGroup] = useState('ALL'); 
    const [selectedDepartement, setSelectedDepartement] = useState('ALL'); // 🆕 Filtre pour les colonnes de l'onglet 2

    // =========================================================================
    // --- LOGIQUE ONGLET 1 : PERMISSIONS DES MODULES (PAR GROUPE) ---
    // =========================================================================
    const checkModulePermission = (groupId, moduleCode, action) => {
        return modulePermissions.some(
            p => p.group_id === groupId && p.module_type === moduleCode && p.type_action === action
        );
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
                loadingKey === key && setLoadingKey(null);
                Swal.fire({ icon: 'success', title: 'Droit mis à jour', toast: true, position: 'top-end', showConfirmButton: false, timer: 1000 });
            },
            onError: () => setLoadingKey(null)
        });
    };

    // =========================================================================
    // --- LOGIQUE ONGLET 2 : HABILITATIONS TERRAINS (PAR UTILISATEUR) ---
    // =========================================================================
    const getPermissionValue = (userId, sdId, permissionName) => {
        const match = affectations.find(
            a => a.user_id === userId && a.sous_departement_id === sdId
        );
        return match ? Boolean(match[permissionName]) : false;
    };

    const handleTerrainToggle = (userId, sdId, permissionName, currentValue) => {
        const key = `terr-${userId}-${sdId}-${permissionName}`;
        setLoadingKey(key);

        router.post(route('admin.permissions.terrain.toggle'), {
            user_id: userId,
            sous_departement_id: sdId,
            permission: permissionName,
            value: !currentValue
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setLoadingKey(null);
                Swal.fire({ icon: 'success', title: 'Droit terrain mis à jour', toast: true, position: 'top-end', showConfirmButton: false, timer: 900 });
            },
            onError: () => setLoadingKey(null)
        });
    };

    // 🆕 FILTRE : Extraction automatique des départements uniques pour le Dropdown
    const departementOptions = useMemo(() => {
        const deps = new Set();
        sousDepartements.forEach(sd => {
            if (sd.nom && sd.nom.includes(" - ")) {
                const depName = sd.nom.split(" - ")[0];
                deps.add(depName);
            }
        });
        return [
            { label: 'Tous les départements', value: 'ALL' },
            ...Array.from(deps).sort().map(d => ({ label: d, value: d }))
        ];
    }, [sousDepartements]);

    // 🆕 FILTRE : Filtrage dynamique des colonnes (Sous-départements)
    const sousDepartementsFiltres = useMemo(() => {
        if (!selectedDepartement || selectedDepartement === 'ALL') return sousDepartements;
        return sousDepartements.filter(sd => sd.nom && sd.nom.startsWith(`${selectedDepartement} - `));
    }, [selectedDepartement, sousDepartements]);

    // FILTRE : Extraction des groupes uniques pour le Dropdown
    const groupeOptions = useMemo(() => {
        const tousLesGroupes = [];
        const listeIds = new Set();

        utilisateurs.forEach(user => {
            const userGroupes = user.groupes || user.groups || [];
            userGroupes.forEach(g => {
                if (g && g.id && !listeIds.has(g.id)) {
                    listeIds.add(g.id);
                    tousLesGroupes.push({ label: g.name, value: g.id });
                }
            });
        });

        return [
            { label: 'Tous les groupes', value: 'ALL' },
            ...tousLesGroupes
        ];
    }, [utilisateurs]);

    // FILTRE : Filtrage dynamique des lignes (Utilisateurs)
    const utilisateursFiltres = useMemo(() => {
        if (!selectedGroup || selectedGroup === 'ALL') return utilisateurs;
        return utilisateurs.filter(user => {
            const userGroupes = user.groupes || user.groups || [];
            return userGroupes.some(g => g.id === selectedGroup);
        });
    }, [selectedGroup, utilisateurs]);

    return (
        <Layout>
            <Head title="Matrice de Sécurité" />

            <div className="p-4">
                <div className="mb-4">
                    <h2 className="text-xl font-bold m-0 text-800">
                        <i className="pi pi-shield text-blue-500 mr-2"></i>
                        Matrice de Sécurité Centrale
                    </h2>
                    <p className="text-500 text-sm m-0 mt-1">
                        Configurez globalement les accès aux fonctionnalités de l'application et restreignez précisément les données par terrain.
                    </p>
                </div>

                <div className="card shadow-2 border-round-xl bg-white p-2">
                    <TabView activeIndex={activeIndex} onTabChange={(e) => setActiveIndex(e.index)}>
                        
                        {/* ================================================================= */}
                        {/* ONGLET 1 : PERMISSIONS DE MODULES */}
                        {/* ================================================================= */}
                        <TabPanel header="1. Droits des Modules" leftIcon="pi pi-th-large mr-2">
                            <div className="overflow-x-auto p-2">
                                <table className="w-full text-left border-collapse custom-matrix-table">
                                    <thead>
                                        <tr className="surface-100 border-bottom-2 surface-border">
                                            <th className="p-3 text-700 font-bold text-sm" style={{ width: '30%' }}>Modules Applicatifs</th>
                                            {groupes.map(g => (
                                                <th key={g.id} className="p-3 text-700 font-bold text-sm text-center">
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
                                                        <td key={g.id} className="p-3">
                                                            <div className="flex justify-content-center align-items-center gap-3">
                                                                <div className="flex align-items-center gap-1">
                                                                    <Checkbox 
                                                                        inputId={`${currentKey}-r`} checked={isRead} 
                                                                        onChange={() => handleModuleToggle(g.id, mod.code, 'lecture')}
                                                                        disabled={loadingKey !== null}
                                                                    />
                                                                    <label htmlFor={`${currentKey}-r`} className="text-xs text-500 cursor-pointer font-bold">L</label>
                                                                </div>
                                                                <div className="flex align-items-center gap-1">
                                                                    <Checkbox 
                                                                        inputId={`${currentKey}-w`} checked={isWrite} 
                                                                        onChange={() => handleModuleToggle(g.id, mod.code, 'modification')}
                                                                        disabled={loadingKey !== null}
                                                                    />
                                                                    <label htmlFor={`${currentKey}-w`} className="text-xs text-500 cursor-pointer font-bold">É</label>
                                                                </div>
                                                                <div className="flex align-items-center gap-1">
                                                                    <Checkbox 
                                                                        inputId={`${currentKey}-d`} checked={isDelete} 
                                                                        onChange={() => handleModuleToggle(g.id, mod.code, 'suppression')}
                                                                        disabled={loadingKey !== null}
                                                                    />
                                                                    <label htmlFor={`${currentKey}-d`} className="text-xs text-500 cursor-pointer font-bold">S</label>
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
                            <div className="text-xs text-500 mt-3 p-3 bg-blue-50 border-round border-left-3 border-blue-500">
                                📌 <strong>Légende Modules :</strong> <strong>L</strong> = Voir le menu | <strong>É</strong> = Ajouter/Modifier | <strong>S</strong> = Supprimer. Les modifications s'appliquent à tous les membres du groupe.
                            </div>
                        </TabPanel>

                        {/* ================================================================= */}
                        {/* ONGLET 2 : HABILITATIONS TERRAINS */}
                        {/* ================================================================= */}
                        <TabPanel header="2. Habilitations Terrains (CRUD)" leftIcon="pi pi-sitemap mr-2">
                            
                            {/* Barre de Filtres Double (Lignes & Colonnes) */}
                            <div className="flex justify-content-end gap-3 mb-3 p-2 flex-wrap">
                                {/* 🆕 Nouveau Filtre Département (Filtre les Colonnes) */}
                                <div className="flex align-items-center gap-2 bg-gray-50 p-2 border-round border-1 surface-border" style={{ minWidth: '240px' }}>
                                    <i className="pi pi-building text-indigo-500 pl-1"></i>
                                    <Dropdown 
                                        value={selectedDepartement} 
                                        options={departementOptions} 
                                        onChange={(e) => setSelectedDepartement(e.value)} 
                                        placeholder="Filtrer par Département parent"
                                        className="w-full border-none text-sm bg-transparent"
                                    />
                                </div>

                                {/* Filtre Groupe (Filtre les Lignes) */}
                                <div className="flex align-items-center gap-2 bg-gray-50 p-2 border-round border-1 surface-border" style={{ minWidth: '240px' }}>
                                    <i className="pi pi-filter text-blue-500 pl-1"></i>
                                    <Dropdown 
                                        value={selectedGroup} 
                                        options={groupeOptions} 
                                        onChange={(e) => setSelectedGroup(e.value)} 
                                        placeholder="Filtrer par groupe"
                                        className="w-full border-none text-sm bg-transparent"
                                    />
                                </div>
                            </div>

                            <div className="overflow-x-auto p-2">
                                <table className="w-full text-left border-collapse custom-matrix-table">
                                    <thead>
                                        <tr className="surface-100 border-bottom-2 surface-border">
                                            <th className="p-3 text-700 font-bold text-sm" style={{ width: '25%', minWidth: '220px' }}>
                                                Utilisateurs ({utilisateursFiltres.length})
                                            </th>
                                            {/* Parcourt les sous-départements filtrés */}
                                            {sousDepartementsFiltres.map(sd => (
                                                <th key={sd.id} className="p-3 text-700 font-bold text-sm text-center border-left-1 surface-border" style={{ minWidth: '180px' }}>
                                                    {sd.nom}
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {utilisateursFiltres.length === 0 ? (
                                            <tr>
                                                <td colSpan={sousDepartementsFiltres.length + 1} className="text-center p-5 text-500 italic">
                                                    Aucun utilisateur trouvé pour ce filtre.
                                                </td>
                                            </tr>
                                        ) : (
                                            utilisateursFiltres.map(user => (
                                                <tr key={user.id} className="hover:surface-50 border-bottom-1 surface-border">
                                                    <td className="p-3">
                                                        <div className="font-semibold text-sm text-800">{user.name}</div>
                                                        <div className="text-xs text-500 mb-1">{user.email}</div>
                                                        <div className="flex flex-wrap gap-1">
                                                            {(user.groupes || user.groups)?.map(g => (
                                                                <span key={g.id} className="bg-blue-50 text-blue-600 text-xs px-2 py-0.5 border-round font-medium">
                                                                    {g.name}
                                                                </span>
                                                            ))}
                                                        </div>
                                                    </td>

                                                    {/* Parcourt les cases à cocher uniquement des colonnes filtrées */}
                                                    {sousDepartementsFiltres.map(sd => {
                                                        const cVal = getPermissionValue(user.id, sd.id, 'can_create');
                                                        const rVal = getPermissionValue(user.id, sd.id, 'can_read');
                                                        const uVal = getPermissionValue(user.id, sd.id, 'can_update');
                                                        const dVal = getPermissionValue(user.id, sd.id, 'can_delete');

                                                        return (
                                                            <td key={sd.id} className="p-3 border-left-1 surface-border bg-gray-50/50 text-center">
                                                                <div className="flex justify-content-center align-items-center gap-3">
                                                                    <div className="flex flex-column align-items-center gap-1">
                                                                        <span className="text-400 font-bold" style={{ fontSize: '10px' }}>C</span>
                                                                        <Checkbox checked={cVal} onChange={() => handleTerrainToggle(user.id, sd.id, 'can_create', cVal)} disabled={loadingKey !== null} />
                                                                    </div>
                                                                    <div className="flex flex-column align-items-center gap-1">
                                                                        <span className="text-400 font-bold" style={{ fontSize: '10px' }}>R</span>
                                                                        <Checkbox checked={rVal} onChange={() => handleTerrainToggle(user.id, sd.id, 'can_read', rVal)} disabled={loadingKey !== null} />
                                                                    </div>
                                                                    <div className="flex flex-column align-items-center gap-1">
                                                                        <span className="text-400 font-bold" style={{ fontSize: '10px' }}>U</span>
                                                                        <Checkbox checked={uVal} onChange={() => handleTerrainToggle(user.id, sd.id, 'can_update', uVal)} disabled={loadingKey !== null} />
                                                                    </div>
                                                                    <div className="flex flex-column align-items-center gap-1">
                                                                        <span className="text-400 font-bold" style={{ fontSize: '10px' }}>D</span>
                                                                        <Checkbox checked={dVal} onChange={() => handleTerrainToggle(user.id, sd.id, 'can_delete', dVal)} disabled={loadingKey !== null} />
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        );
                                                    })}
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>
                            <div className="text-xs text-500 mt-3 p-3 bg-green-50 border-round border-left-3 border-green-500">
                                💡 <strong>Périmètre Terrains :</strong> C = Créer | R = Lire | U = Modifier | D = Supprimer. Si un utilisateur n'a aucune case cochée sur un sous-département, il ne pourra ni voir ni interagir avec ses données.
                            </div>
                        </TabPanel>

                    </TabView>
                </div>
            </div>

            <style>{`
                .custom-matrix-table th { white-space: nowrap; }
                .custom-matrix-table td { vertical-align: middle; }
                .p-tabview .p-tabview-nav li.p-highlight .p-tabview-nav-link {
                    color: #4f46e5 !important;
                    border-color: #4f46e5 !important;
                }
                .p-checkbox .p-checkbox-box.p-highlight {
                    background: #4f46e5 !important;
                    border-color: #4f46e5 !important;
                }
                .p-dropdown { border: none !important; }
            `}</style>
        </Layout>
    );
};

export default AllPermissions;
// import React, { useState, useMemo } from "react";
// import Layout from "@/Layouts/layout";
// import { Head, router } from "@inertiajs/react";
// import { TabView, TabPanel } from "primereact/tabview";
// import { Checkbox } from "primereact/checkbox";
// import { Dropdown } from "primereact/dropdown";
// import Swal from "sweetalert2";

// const AllPermissions = ({ 
//     groupes = [], 
//     modules = [], 
//     sousDepartements = [], 
//     utilisateurs = [],       // Reçu pour l'onglet 2
//     modulePermissions = [],  // Reçu pour l'onglet 1
//     affectations = []        // Reçu pour l'onglet 2 (remplace pivotPermissions)
// }) => {
//     const [activeIndex, setActiveIndex] = useState(0);
//     const [loadingKey, setLoadingKey] = useState(null);
//     const [selectedGroup, setSelectedGroup] = useState('ALL'); // Filtre pour l'onglet 2

//     // =========================================================================
//     // --- LOGIQUE ONGLET 1 : PERMISSIONS DES MODULES (PAR GROUPE) ---
//     // =========================================================================
//     const checkModulePermission = (groupId, moduleCode, action) => {
//         return modulePermissions.some(
//             p => p.group_id === groupId && p.module_type === moduleCode && p.type_action === action
//         );
//     };

//     const handleModuleToggle = (groupId, moduleCode, action) => {
//         const key = `mod-${groupId}-${moduleCode}-${action}`;
//         setLoadingKey(key);

//         router.post(route('admin.permissions.modules.toggle'), {
//             group_id: groupId,
//             module_type: moduleCode,
//             type_action: action
//         }, {
//             preserveScroll: true,
//             onSuccess: () => {
//                 setLoadingKey(null);
//                 Swal.fire({ icon: 'success', title: 'Droit mis à jour', toast: true, position: 'top-end', showConfirmButton: false, timer: 1000 });
//             },
//             onError: () => setLoadingKey(null)
//         });
//     };

//     // =========================================================================
//     // --- LOGIQUE ONGLET 2 : HABILITATIONS TERRAINS (PAR UTILISATEUR) ---
//     // =========================================================================
//     const getPermissionValue = (userId, sdId, permissionName) => {
//         const match = affectations.find(
//             a => a.user_id === userId && a.sous_departement_id === sdId
//         );
//         return match ? Boolean(match[permissionName]) : false;
//     };

//     const handleTerrainToggle = (userId, sdId, permissionName, currentValue) => {
//         const key = `terr-${userId}-${sdId}-${permissionName}`;
//         setLoadingKey(key);

//         router.post(route('admin.permissions.terrain.toggle'), {
//             user_id: userId,
//             sous_departement_id: sdId,
//             permission: permissionName,
//             value: !currentValue
//         }, {
//             preserveScroll: true,
//             onSuccess: () => {
//                 setLoadingKey(null);
//                 Swal.fire({ icon: 'success', title: 'Droit terrain mis à jour', toast: true, position: 'top-end', showConfirmButton: false, timer: 900 });
//             },
//             onError: () => setLoadingKey(null)
//         });
//     };

//     // Filtre : Extraction des groupes uniques pour le Dropdown
//     const groupeOptions = useMemo(() => {
//         const tousLesGroupes = [];
//         const listeIds = new Set();

//         utilisateurs.forEach(user => {
//             const userGroupes = user.groupes || user.groups || [];
//             userGroupes.forEach(g => {
//                 if (g && g.id && !listeIds.has(g.id)) {
//                     listeIds.add(g.id);
//                     tousLesGroupes.push({ label: g.name, value: g.id });
//                 }
//             });
//         });

//         return [
//             { label: 'Tous les groupes', value: 'ALL' },
//             ...tousLesGroupes
//         ];
//     }, [utilisateurs]);

//     // Filtre : Filtrage dynamique des utilisateurs
//     const utilisateursFiltres = useMemo(() => {
//         if (!selectedGroup || selectedGroup === 'ALL') return utilisateurs;
        
//         return utilisateurs.filter(user => {
//             const userGroupes = user.groupes || user.groups || [];
//             return userGroupes.some(g => g.id === selectedGroup);
//         });
//     }, [selectedGroup, utilisateurs]);

//     return (
//         <Layout>
//             <Head title="Matrice de Sécurité" />

//             <div className="p-4">
//                 <div className="mb-4">
//                     <h2 className="text-xl font-bold m-0 text-800">
//                         <i className="pi pi-shield text-blue-500 mr-2"></i>
//                         Matrice de Sécurité Centrale
//                     </h2>
//                     <p className="text-500 text-sm m-0 mt-1">
//                         Configurez globalement les accès aux fonctionnalités de l'application et restreignez précisément les données par terrain.
//                     </p>
//                 </div>

//                 <div className="card shadow-2 border-round-xl bg-white p-2">
//                     <TabView activeIndex={activeIndex} onTabChange={(e) => setActiveIndex(e.index)}>
                        
//                         {/* ----------------================--------------------------------- */}
//                         {/* ONGLET 1 : PERMISSIONS DE MODULES (FONCTIONNEL - PAR GROUPE) */}
//                         {/* ----------------================--------------------------------- */}
//                         <TabPanel header="1. Droits des Modules" leftIcon="pi pi-th-large mr-2">
//                             <div className="overflow-x-auto p-2">
//                                 <table className="w-full text-left border-collapse custom-matrix-table">
//                                     <thead>
//                                         <tr className="surface-100 border-bottom-2 surface-border">
//                                             <th className="p-3 text-700 font-bold text-sm" style={{ width: '30%' }}>Modules Applicatifs</th>
//                                             {groupes.map(g => (
//                                                 <th key={g.id} className="p-3 text-700 font-bold text-sm text-center">
//                                                     {g.name}
//                                                 </th>
//                                             ))}
//                                         </tr>
//                                     </thead>
//                                     <tbody>
//                                         {modules.map(mod => (
//                                             <tr key={mod.code} className="hover:surface-50 border-bottom-1 surface-border">
//                                                 <td className="p-3 font-semibold text-sm text-800">{mod.name}</td>
//                                                 {groupes.map(g => {
//                                                     const isRead = checkModulePermission(g.id, mod.code, 'lecture');
//                                                     const isWrite = checkModulePermission(g.id, mod.code, 'modification');
//                                                     const isDelete = checkModulePermission(g.id, mod.code, 'suppression');
//                                                     const currentKey = `mod-${g.id}-${mod.code}`;

//                                                     return (
//                                                         <td key={g.id} className="p-3">
//                                                             <div className="flex justify-content-center align-items-center gap-3">
//                                                                 <div className="flex align-items-center gap-1">
//                                                                     <Checkbox 
//                                                                         inputId={`${currentKey}-r`} checked={isRead} 
//                                                                         onChange={() => handleModuleToggle(g.id, mod.code, 'lecture')}
//                                                                         disabled={loadingKey !== null}
//                                                                     />
//                                                                     <label htmlFor={`${currentKey}-r`} className="text-xs text-500 cursor-pointer font-bold">L</label>
//                                                                 </div>
//                                                                 <div className="flex align-items-center gap-1">
//                                                                     <Checkbox 
//                                                                         inputId={`${currentKey}-w`} checked={isWrite} 
//                                                                         onChange={() => handleModuleToggle(g.id, mod.code, 'modification')}
//                                                                         disabled={loadingKey !== null}
//                                                                     />
//                                                                     <label htmlFor={`${currentKey}-w`} className="text-xs text-500 cursor-pointer font-bold">É</label>
//                                                                 </div>
//                                                                 <div className="flex align-items-center gap-1">
//                                                                     <Checkbox 
//                                                                         inputId={`${currentKey}-d`} checked={isDelete} 
//                                                                         onChange={() => handleModuleToggle(g.id, mod.code, 'suppression')}
//                                                                         disabled={loadingKey !== null}
//                                                                     />
//                                                                     <label htmlFor={`${currentKey}-d`} className="text-xs text-500 cursor-pointer font-bold">S</label>
//                                                                 </div>
//                                                             </div>
//                                                         </td>
//                                                     );
//                                                 })}
//                                             </tr>
//                                         ))}
//                                     </tbody>
//                                 </table>
//                             </div>
//                             <div className="text-xs text-500 mt-3 p-3 bg-blue-50 border-round border-left-3 border-blue-500">
//                                 📌 <strong>Légende Modules :</strong> <strong>L</strong> = Voir le menu | <strong>É</strong> = Ajouter/Modifier | <strong>S</strong> = Supprimer. Les modifications s'appliquent à tous les membres du groupe.
//                             </div>
//                         </TabPanel>

//                         {/* ----------------================--------------------------------- */}
//                         {/* ONGLET 2 : HABILITATIONS TERRAINS (FINES - PAR UTILISATEUR) */}
//                         {/* ----------------================--------------------------------- */}
//                         <TabPanel header="2. Habilitations Terrains (CRUD)" leftIcon="pi pi-sitemap mr-2">
                            
//                             {/* Filtre intégré en haut du tableau */}
//                             <div className="flex justify-content-end mb-3 p-2">
//                                 <div className="flex align-items-center gap-2 bg-gray-50 p-2 border-round border-1 surface-border" style={{ minWidth: '260px' }}>
//                                     <i className="pi pi-filter text-blue-500 pl-1"></i>
//                                     <Dropdown 
//                                         value={selectedGroup} 
//                                         options={groupeOptions} 
//                                         onChange={(e) => setSelectedGroup(e.value)} 
//                                         placeholder="Filtrer par groupe"
//                                         className="w-full border-none text-sm bg-transparent"
//                                     />
//                                 </div>
//                             </div>

//                             <div className="overflow-x-auto p-2">
//                                 <table className="w-full text-left border-collapse custom-matrix-table">
//                                     <thead>
//                                         <tr className="surface-100 border-bottom-2 surface-border">
//                                             <th className="p-3 text-700 font-bold text-sm" style={{ width: '25%', minWidth: '220px' }}>
//                                                 Utilisateurs ({utilisateursFiltres.length})
//                                             </th>
//                                             {sousDepartements.map(sd => (
//                                                 <th key={sd.id} className="p-3 text-700 font-bold text-sm text-center border-left-1 surface-border" style={{ minWidth: '180px' }}>
//                                                     {sd.nom}
//                                                 </th>
//                                             ))}
//                                         </tr>
//                                     </thead>
//                                     <tbody>
//                                         {utilisateursFiltres.length === 0 ? (
//                                             <tr>
//                                                 <td colSpan={sousDepartements.length + 1} className="text-center p-5 text-500 italic">
//                                                     Aucun utilisateur trouvé pour ce filtre.
//                                                 </td>
//                                             </tr>
//                                         ) : (
//                                             utilisateursFiltres.map(user => (
//                                                 <tr key={user.id} className="hover:surface-50 border-bottom-1 surface-border">
//                                                     <td className="p-3">
//                                                         <div className="font-semibold text-sm text-800">{user.name}</div>
//                                                         <div className="text-xs text-500 mb-1">{user.email}</div>
//                                                         <div className="flex flex-wrap gap-1">
//                                                             {(user.groupes || user.groups)?.map(g => (
//                                                                 <span key={g.id} className="bg-blue-50 text-blue-600 text-xs px-2 py-0.5 border-round font-medium">
//                                                                     {g.name}
//                                                                 </span>
//                                                             ))}
//                                                         </div>
//                                                     </td>

//                                                     {sousDepartements.map(sd => {
//                                                         const cVal = getPermissionValue(user.id, sd.id, 'can_create');
//                                                         const rVal = getPermissionValue(user.id, sd.id, 'can_read');
//                                                         const uVal = getPermissionValue(user.id, sd.id, 'can_update');
//                                                         const dVal = getPermissionValue(user.id, sd.id, 'can_delete');

//                                                         return (
//                                                             <td key={sd.id} className="p-3 border-left-1 surface-border bg-gray-50/50 text-center">
//                                                                 <div className="flex justify-content-center align-items-center gap-3">
//                                                                     <div className="flex flex-column align-items-center gap-1">
//                                                                         <span className="text-400 font-bold" style={{ fontSize: '10px' }}>C</span>
//                                                                         <Checkbox checked={cVal} onChange={() => handleTerrainToggle(user.id, sd.id, 'can_create', cVal)} disabled={loadingKey !== null} />
//                                                                     </div>
//                                                                     <div className="flex flex-column align-items-center gap-1">
//                                                                         <span className="text-400 font-bold" style={{ fontSize: '10px' }}>R</span>
//                                                                         <Checkbox checked={rVal} onChange={() => handleTerrainToggle(user.id, sd.id, 'can_read', rVal)} disabled={loadingKey !== null} />
//                                                                     </div>
//                                                                     <div className="flex flex-column align-items-center gap-1">
//                                                                         <span className="text-400 font-bold" style={{ fontSize: '10px' }}>U</span>
//                                                                         <Checkbox checked={uVal} onChange={() => handleTerrainToggle(user.id, sd.id, 'can_update', uVal)} disabled={loadingKey !== null} />
//                                                                     </div>
//                                                                     <div className="flex flex-column align-items-center gap-1">
//                                                                         <span className="text-400 font-bold" style={{ fontSize: '10px' }}>D</span>
//                                                                         <Checkbox checked={dVal} onChange={() => handleTerrainToggle(user.id, sd.id, 'can_delete', dVal)} disabled={loadingKey !== null} />
//                                                                     </div>
//                                                                 </div>
//                                                             </td>
//                                                         );
//                                                     })}
//                                                 </tr>
//                                             ))
//                                         )}
//                                     </tbody>
//                                 </table>
//                             </div>
//                             <div className="text-xs text-500 mt-3 p-3 bg-green-50 border-round border-left-3 border-green-500">
//                                 💡 <strong>Périmètre Terrains :</strong> C = Créer | R = Lire | U = Modifier | D = Supprimer. Si un utilisateur n'a aucune case cochée sur un sous-département, il ne pourra ni voir ni interagir avec ses données.
//                             </div>
//                         </TabPanel>

//                     </TabView>
//                 </div>
//             </div>

//             <style>{`
//                 .custom-matrix-table th { white-space: nowrap; }
//                 .custom-matrix-table td { vertical-align: middle; }
//                 .p-tabview .p-tabview-nav li.p-highlight .p-tabview-nav-link {
//                     color: #4f46e5 !important;
//                     border-color: #4f46e5 !important;
//                 }
//                 .p-checkbox .p-checkbox-box.p-highlight {
//                     background: #4f46e5 !important;
//                     border-color: #4f46e5 !important;
//                 }
//                 .p-dropdown { border: none !important; }
//             `}</style>
//         </Layout>
//     );
// };

// export default AllPermissions;