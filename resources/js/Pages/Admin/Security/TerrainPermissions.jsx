import React, { useState, useMemo } from 'react'; // Ajout de useMemo
import Layout from "@/Layouts/layout";
import { Head, router } from "@inertiajs/react";
import { Checkbox } from "primereact/checkbox";
import { Dropdown } from "primereact/dropdown"; // 👈 Nouveau composant pour le filtre
import Swal from "sweetalert2";

const TerrainPermissions = ({ utilisateurs = [], sousDepartements = [], affectations = [] }) => {
    const [loadingKey, setLoadingKey] = useState(null);
    const [selectedGroup, setSelectedGroup] = useState('ALL'); // 👈 Au lieu de null

    // Trouver l'état d'un droit spécifique dans le tableau des affectations
    const getPermissionValue = (userId, sdId, permissionName) => {
        const match = affectations.find(
            a => a.user_id === userId && a.sous_departement_id === sdId
        );
        return match ? Boolean(match[permissionName]) : false;
    };

    // Déclencher la mise à jour au clic sur une des cases CRUD
    const handleToggle = (userId, sdId, permissionName, currentValue) => {
        const key = `${userId}-${sdId}-${permissionName}`;
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
                Swal.fire({
                    icon: 'success',
                    title: 'Droit mis à jour',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 900
                });
            },
            onError: () => setLoadingKey(null)
        });
    };

    // 1. Extraction des options pour le Dropdown
    const groupeOptions = useMemo(() => {
        const tousLesGroupes = [];
        const listeIds = new Set();

        utilisateurs.forEach(user => {
            // Support de "groupes" (FR) ou "groups" (EN)
            const userGroupes = user.groupes || user.groups || [];

            userGroupes.forEach(g => {
                if (g && g.id && !listeIds.has(g.id)) {
                    listeIds.add(g.id);
                    tousLesGroupes.push({ label: g.name, value: g.id });
                }
            });
        });

        return [
            { label: 'Tous les groupes', value: 'ALL' }, // 👈 On utilise une string explicite 'ALL' au lieu de null
            ...tousLesGroupes
        ];
    }, [utilisateurs]);

    // 2. Filtrage dynamique des utilisateurs
    const utilisateursFiltres = useMemo(() => {
        // 👈 Si aucun groupe n'est sélectionné ou si on a choisi 'ALL', on affiche TOUT LE MONDE
        if (!selectedGroup || selectedGroup === 'ALL') {
            return utilisateurs;
        }

        return utilisateurs.filter(user => {
            const userGroupes = user.groupes || user.groups || [];
            return userGroupes.some(g => g.id === selectedGroup);
        });
    }, [selectedGroup, utilisateurs]);
    return (
        <Layout>
            <Head title="Habilitations Terrains" />

            <div className="p-4">
                {/* En-tête avec flexbox pour aligner le titre et le filtre */}
                <div className="flex flex-column md:flex-row md:justify-content-between md:align-items-center mb-4 gap-3">
                    <div>
                        <h2 className="text-xl font-bold m-0 text-800">
                            <i className="pi pi-sitemap text-green-500 mr-2"></i>
                            Droits CRUD par Sous-Département
                        </h2>
                        <p className="text-500 text-sm m-0 mt-1">
                            Ajustez finement les actions autorisées de chaque utilisateur sur ses laboratoires rattachés.
                        </p>
                    </div>

                    {/* 🆕 Le composant Dropdown de filtrage */}
                    <div className="flex align-items-center gap-2 bg-white p-2 border-round shadow-1" style={{ minWidth: '250px' }}>
                        <i className="pi pi-filter text-blue-500 pl-1"></i>
                        <Dropdown
                            value={selectedGroup}
                            options={groupeOptions}
                            onChange={(e) => setSelectedGroup(e.value)}
                            placeholder="Filtrer par groupe"
                            className="w-full border-none text-sm"
                        />
                    </div>
                </div>

                <div className="card shadow-2 border-round-xl bg-white p-3 overflow-x-auto">
                    <table className="w-full text-left border-collapse custom-terrain-table">
                        <thead>
                            <tr className="surface-100 border-bottom-2 surface-border">
                                <th className="p-3 text-700 font-bold text-sm" style={{ width: '25%', minWidth: '200px' }}>
                                    Utilisateurs / Profils ({utilisateursFiltres.length})
                                </th>
                                {sousDepartements.map(sd => (
                                    <th key={sd.id} className="p-3 text-700 font-bold text-sm text-center border-left-1 surface-border" style={{ minWidth: '180px' }}>
                                        {sd.nom}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {/* 🆕 On boucle désormais sur 'utilisateursFiltres' au lieu de 'utilisateurs' */}
                            {utilisateursFiltres.length === 0 ? (
                                <tr>
                                    <td colSpan={sousDepartements.length + 1} className="text-center p-4 text-500 italic">
                                        Aucun utilisateur trouvé pour ce groupe.
                                    </td>
                                </tr>
                            ) : (
                                utilisateursFiltres.map(user => (
                                    <tr key={user.id} className="hover:surface-50 border-bottom-1 surface-border">
                                        <td className="p-3">
                                            <div className="font-semibold text-sm text-800">{user.name}</div>
                                            <div className="text-xs text-500 mb-1">{user.email}</div>
                                            <div className="flex flex-wrap gap-1">
                                                {user.groups?.map(g => (
                                                    <span key={g.id} className="bg-blue-50 text-blue-600 text-xs px-2 py-0.5 border-round font-medium">
                                                        {g.name}
                                                    </span>
                                                ))}
                                            </div>
                                        </td>

                                        {sousDepartements.map(sd => {
                                            const cVal = getPermissionValue(user.id, sd.id, 'can_create');
                                            const rVal = getPermissionValue(user.id, sd.id, 'can_read');
                                            const uVal = getPermissionValue(user.id, sd.id, 'can_update');
                                            const dVal = getPermissionValue(user.id, sd.id, 'can_delete');

                                            return (
                                                <td key={sd.id} className="p-3 border-left-1 surface-border bg-gray-50 text-center">
                                                    <div className="flex justify-content-center align-items-center gap-3">
                                                        <div className="flex flex-column align-items-center gap-1">
                                                            <span className="text-500 font-bold" style={{ fontSize: '10px' }}>C</span>
                                                            <Checkbox
                                                                checked={cVal}
                                                                onChange={() => handleToggle(user.id, sd.id, 'can_create', cVal)}
                                                                disabled={loadingKey !== null}
                                                            />
                                                        </div>
                                                        <div className="flex flex-column align-items-center gap-1">
                                                            <span className="text-500 font-bold" style={{ fontSize: '10px' }}>R</span>
                                                            <Checkbox
                                                                checked={rVal}
                                                                onChange={() => handleToggle(user.id, sd.id, 'can_read', rVal)}
                                                                disabled={loadingKey !== null}
                                                            />
                                                        </div>
                                                        <div className="flex flex-column align-items-center gap-1">
                                                            <span className="text-500 font-bold" style={{ fontSize: '10px' }}>U</span>
                                                            <Checkbox
                                                                checked={uVal}
                                                                onChange={() => handleToggle(user.id, sd.id, 'can_update', uVal)}
                                                                disabled={loadingKey !== null}
                                                            />
                                                        </div>
                                                        <div className="flex flex-column align-items-center gap-1">
                                                            <span className="text-500 font-bold" style={{ fontSize: '10px' }}>D</span>
                                                            <Checkbox
                                                                checked={dVal}
                                                                onChange={() => handleToggle(user.id, sd.id, 'can_delete', dVal)}
                                                                disabled={loadingKey !== null}
                                                            />
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

                <div className="text-xs text-500 mt-3 bg-blue-50 p-3 border-round-lg border-left-3 border-blue-500">
                    <strong>💡 Astuce d'utilisation :</strong> Cocher une case applique immédiatement la restriction en base de données. Si un utilisateur n'a aucune case cochée sur un sous-département, il ne verra absolument rien concernant ce dernier.
                </div>
            </div>

            <style>{`
                .custom-terrain-table td { vertical-align: middle; }
                .p-checkbox .p-checkbox-box.p-highlight {
                    background: #4f46e5 !important;
                    border-color: #4f46e5 !important;
                }
                /* Style pour enlever la bordure brute du dropdown dans notre boîte */
                .p-dropdown { border: none !important; }
                .p-dropdown:focus { shadow: none !important; }
            `}</style>
        </Layout>
    );
};

export default TerrainPermissions;