import React, { useState, useEffect, useRef } from 'react';
import Layout from '@/Layouts/layout';
import { useForm, usePage } from '@inertiajs/react';
import { TabView, TabPanel } from 'primereact/tabview';
import { MultiSelect } from 'primereact/multiselect';
import { Dropdown } from 'primereact/dropdown';
import { InputText } from 'primereact/inputtext';
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { Toast } from 'primereact/toast';
import { DataTable } from 'primereact/datatable';
import { Column } from 'primereact/column';
import { Calendar } from 'primereact/calendar';
import { Badge } from 'primereact/badge';

const DesignationsPage = ({ designations, departements, sousDepartements, laboratoires, membres }) => {
    const [visible, setVisible] = useState(false);
    const toast = useRef(null);
    //const { flash } = usePage().props;

    const { data, setData, post, put, processing, reset, errors } = useForm({
        id: null,
        semaine_nom: '',
        departement_id: null,
        sous_departement_id: null,
        date_debut: null,
        affectations: {} // { labId: [ {role_id: X, membres: [ids]} ] }
    });

    // --- FILTRAGE DES DEPENDANCES ---
    const filteredSousDepts = sousDepartements.filter(sd => sd.departement_id === data.departement_id);
    const activeLabs = laboratoires.filter(lab => lab.sous_departement_id === data.sous_departement_id);

    const onMemberChange = (labId, roleId, selectedIds, event) => {
        const current = { ...data.affectations };


        if (!current[labId]) current[labId] = [];

        const roleIndex = current[labId].findIndex(r => r.role_id === roleId);
        if (roleIndex > -1) {
            current[labId][roleIndex].membres = selectedIds;
        } else {
            current[labId].push({ role_id: roleId, membres: selectedIds });
        }
        setData('affectations', current);
    };

    const editDesignation = (row) => {
        if (!row) return;

        // 1. Trouver le département parent à partir du sous-département de la ligne
        const parentDept = sousDepartements.find(sd => sd.id === row.sous_departement_id);
        const departementId = parentDept ? parentDept.departement_id : null;

        // console.log('Données de la désignation : ' + JSON.stringify(row)); // Debug: Affiche les données reçues
        // console.log('Données des affectations : ' + JSON.stringify(row.items)); // Debug: Affiche les données des affectations

        // 2. Formater les affectations (votre logique actuelle est correcte)
        // const formattedAffectations = {};
        // (row.items || []).forEach(item => {
        //     if (!formattedAffectations[item.laboratoire_id]) {
        //         formattedAffectations[item.laboratoire_id] = [];
        //     }
        //     formattedAffectations[item.laboratoire_id].push({
        //         role_id: item.role_tache_id,
        //         membres: (item.membres || []).map(m => m.id)
        //     });
        // });

        // const formattedAffectations = {};
        // (row.items || []).forEach(item => {
        //     // On s'assure d'utiliser l'ID du lab comme clé
        //     if (!formattedAffectations[item.laboratoire_id]) {
        //         formattedAffectations[item.laboratoire_id] = [];
        //     }

        //     formattedAffectations[item.laboratoire_id].push({
        //         // Forcer le format Number si nécessaire pour la correspondance
        //         role_id: Number(item.role_tache_id),
        //         // Extraire les IDs des membres et s'assurer qu'ils sont des nombres
        //         membres: (item.membres || []).map(m => Number(m.id))
        //     });
        // });

        // // 3. Mettre à jour TOUTES les clés nécessaires à l'affichage
        // setData({
        //     id: row.id,
        //     semaine_nom: row.semaine_nom || '',
        //     date_debut: row.date_debut ? new Date(row.date_debut) : null,
        //     departement_id: departementId, // CRUCIAL pour filtrer filteredSousDepts
        //     sous_departement_id: row.sous_departement_id || null, // Débloque activeLabs
        //     affectations: formattedAffectations
        // });

        // const formatted = {};
        // (row.items || []).forEach(item => {
        //     const labId = item.laboratoire_id;
        //     if (!formatted[labId]) formatted[labId] = [];

        //     // CORRECTION ICI : 
        //     // Vérifiez si row.items[x].membres existe et contient des données
        //     //const membresIds = (item.membres || []).map(m => Number(m.id));
        //     const membresIds = item.membre_id ? [Number(item.membre_id)] : [];

        //     formatted[labId].push({
        //         role_id: Number(item.role_tache_id),
        //         membres: membresIds // Assurez-vous que ce n'est pas un tableau vide
        //     });
        // });

        const formatted = {};

        (row.items || []).forEach(item => {
            const labId = item.laboratoire_id;
            if (!formatted[labId]) formatted[labId] = [];

            // On prépare l'option pour le Select : ID + NOM
            const membreOption = item.membre ? {
                value: Number(item.membre_id),
                label: item.membre.nom // C'est cette clé qui permettra l'affichage
            } : null;

            formatted[labId].push({
                role_id: Number(item.role_tache_id),
                // ON NE STOCKE QUE L'ID ICI
                membres: item.membre_id ? [Number(item.membre_id)] : []
            });

            // CORRECT : On affiche le dernier élément ajouté au tableau
            const lastIndex = formatted[labId].length - 1;
            console.log(`Membre ajouté :`, formatted[labId][lastIndex].membres);
        });

        setData({
            id: row.id,
            // Correction ici : force une chaîne vide si row.semaine_nom est null en base
            semaine_nom: row.semaine_nom || '',
            date_debut: row.date_debut ? new Date(row.date_debut) : null,
            departement_id: parentDept?.departement_id || null,
            sous_departement_id: row.sous_departement_id || null,
            affectations: formatted
        });

        setVisible(true);
    };

    // Nouveau bouton "Ajouter" pour réinitialiser le formulaire
    const openNew = () => {
        reset();
        setVisible(true);
    };

    const submit = () => {
        // 1. Validation manuelle simple avant l'envoi
        if (!data.sous_departement_id || !data.date_debut) {
            toast.current.show({
                severity: 'error',
                summary: 'Erreur',
                detail: 'Veuillez remplir les champs obligatoires (Libellé, Sous-Département et Date).'
            });
            return;
        }

        // 2. Vérification s'il y a au moins une affectation
        const hasAffectations = Object.values(data.affectations).some(labRoles =>
            labRoles.some(role => role.membres && role.membres.length > 0)
        );

        if (!hasAffectations) {
            toast.current.show({
                severity: 'warn',
                summary: 'Attention',
                detail: 'Vous devez affecter au moins un membre dans un laboratoire.'
            });
            return;
        }

        // 3. Envoi si tout est OK
        const action = data.id ? put : post;
        const url = data.id ? `/designations/${data.id}` : '/designations';

        action(url, {
            onSuccess: () => {
                setVisible(false);
                reset();
                toast.current.show({ severity: 'success', summary: 'Succès', detail: 'Enregistré avec succès' });
            }
        });
    };

    return (
        <Layout>
            <Toast ref={toast} />
            <div className="card">
                {/* LE BOUTON EST ICI */}
                <div className="flex justify-content-between mb-4">
                    <h3>Désignations de la Semaine</h3>
                    <Button
                        label="Nouvelle Désignation"
                        icon="pi pi-plus"
                        severity="success"
                        onClick={openNew} // <--- Appelle la fonction de réinitialisation
                    />
                </div>
                <DataTable value={designations} responsiveLayout="stack" breakpoint="960px" paginator rows={10}>
                    {/* 1. Colonne Département (via la relation sous-département) */}
                    <Column
                        header="Département"
                        body={(row) => row.sous_departement?.departement?.nom || 'N/A'}
                        sortable
                    />

                    {/* 2. Colonne Semaine */}
                    <Column field="semaine_nom" header="Semaine" sortable />

                    {/* 3. Colonne Sous-Département */}
                    <Column field="sous_departement.nom" header="Sous-Département" sortable />

                    {/* 4. Colonne Date (Formatée) */}
                    <Column
                        header="Date"
                        body={(row) => row.date_debut ? new Date(row.date_debut).toLocaleDateString() : ''}
                        sortable
                    />

                    {/* 5. Colonne Labs (Badges) */}
                    <Column
                        header="Laboratoires"
                        body={(row) => {
                            const labs = [...new Set(row.items?.map(i => i.laboratoire?.nom))].filter(Boolean);
                            return (
                                <div className="flex flex-wrap gap-2">
                                    {labs.map((name, i) => (
                                        <Badge key={i} value={name} severity="info" className="px-2" />
                                    ))}
                                </div>
                            );
                        }}
                    />

                    {/* 6. Actions */}
                    {/* Colonne Actions */}
                    <Column
                        header="Actions"
                        style={{ width: '5rem' }}
                        body={(row) => (
                            <Button
                                icon="pi pi-pencil"
                                className="p-button-text p-button-rounded"
                                onClick={() => editDesignation(row)}
                            />
                        )}
                    />
                </DataTable>
            </div>

            <Dialog header="Planification Hebdomadaire" visible={visible} style={{ width: '80vw' }} onHide={() => setVisible(false)}>
                <div className="grid p-fluid">
                    {/* Ligne 1: Libellé et Dates */}
                    <div className="col-12 md:col-6">
                        <label className="font-bold">Libellé Semaine</label>
                        <InputText value={data.semaine_nom} onChange={e => setData('semaine_nom', e.target.value)} placeholder="Ex: Semaine du 15 Mai" />
                    </div>
                    <div className="col-12 md:col-6">
                        <label className="font-bold">Date de début</label>
                        <Calendar value={data.date_debut} onChange={e => setData('date_debut', e.value)} dateFormat="yy-mm-dd" showIcon />
                    </div>

                    {/* Ligne 2: Cascades */}
                    <div className="col-12 md:col-6">
                        <label className="font-bold">Département</label>
                        <Dropdown
                            value={data.departement_id}
                            options={departements}
                            optionLabel="nom" optionValue="id"
                            onChange={e => setData(d => ({ ...d, departement_id: e.value, sous_departement_id: null, affectations: {} }))}
                            placeholder="Sélectionner..."
                        />
                    </div>
                    <div className="col-12 md:col-6">
                        <label className="font-bold">Sous-Département</label>
                        <Dropdown
                            value={data.sous_departement_id}
                            options={filteredSousDepts}
                            disabled={!data.departement_id}
                            optionLabel="nom" optionValue="id"
                            onChange={e => setData(d => ({ ...d, sous_departement_id: e.value, affectations: {} }))}
                            placeholder="Choisir groupe"
                        />
                    </div>
                </div>

                {/* ONGLETS DES LABOS ET REQUIS */}
                {activeLabs.length > 0 && (
                    <TabView className="mt-4">
                        {activeLabs.map(lab => (
                            <TabPanel key={lab.id} header={lab.nom}>
                                {(lab.lab_requis || []).map(requis => (
                                    <div key={requis.id} className="mb-3">
                                        <label className="block mb-1 font-semibold">
                                            {requis.role_tache?.libelle}
                                            {/* {requis.est_obligatoire === 1 && <span className="text-red-500">*</span>} */}
                                        </label>
                                        <MultiSelect
                                            // On passe directement le tableau d'IDs qui est dans le state
                                            value={
                                                data.affectations[lab.id]
                                                    ?.find(r => Number(r.role_id) === Number(requis.role_tache_id))
                                                    ?.membres || []
                                            }
                                            options={membres} // Cette liste doit contenir {id, nom}
                                            optionLabel="nom"
                                            optionValue="id"
                                            onChange={e => onMemberChange(lab.id, requis.role_tache_id, e.value)}
                                            display="chip"
                                            filter
                                            className="w-full"
                                        />
                                    </div>
                                ))}
                            </TabPanel>
                        ))}
                    </TabView>
                )}

                <div className="flex justify-content-end mt-4">
                    <Button label="Enregistrer la planification" icon="pi pi-check" onClick={submit} loading={processing} />
                </div>
            </Dialog>

        </Layout>
    );
};

export default DesignationsPage;