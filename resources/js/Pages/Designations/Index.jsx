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

const DesignationsPage = ({ designations, departements,sousDepartements, laboratoires, membres, rolesTache }) => {
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

    const onMemberChange = (labId, roleId, selectedIds) => {
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

        const formattedAffectations = {};
        // Sécurisation contre le "undefined" avec || []
        (row.items || []).forEach(item => {
            if (!formattedAffectations[item.laboratoire_id]) {
                formattedAffectations[item.laboratoire_id] = [];
            }
            formattedAffectations[item.laboratoire_id].push({
                role_id: item.role_tache_id,
                membres: (item.membres || []).map(m => m.id)
            });
        });

        setData({
            id: row.id,
            date_debut: row.date_debut ? new Date(row.date_debut) : null,
            semaine_nom: row.semaine_nom || '',
            sous_departement_id: row.sous_departement_id || null,
            affectations: formattedAffectations
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
                <DataTable value={designations}>
                    <Column field="semaine_nom" header="Semaine" />
                    <Column field="sous_departement.nom" header="Sous-Département" />
                    <Column header="Actions" body={(row) => (
                        <Button icon="pi pi-pencil" text onClick={() => editDesignation(row)} />
                    )} />
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
                                            value={data.affectations[lab.id]?.find(r => r.role_id === requis.role_tache_id)?.membres || []}
                                            options={membres}
                                            optionLabel="nom" optionValue="id"
                                            onChange={e => onMemberChange(lab.id, requis.role_tache_id, e.value)}
                                            display="chip" filter className="w-full"
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
            {/* <Dialog visible={visible} onHide={() => setVisible(false)} header="Planification Multi-Laboratoires" style={{ width: '70vw' }}>
                <div className="grid p-fluid">
                    <div className="col-12 md:col-4">
                        <label className="font-bold">Date de début</label>
                        <Calendar
                            value={data.date_debut}
                            onChange={(e) => setData('date_debut', e.value)}
                            dateFormat="yy-mm-dd"
                            className={errors.date_debut ? 'p-invalid' : ''} // Bordure rouge si erreur
                        />
                        {errors.date_debut && <small className="p-error">{errors.date_debut}</small>}
                    </div>
                    <div className="col-12 md:col-6">
                        <label
                            className="font-bold">Nom de la Semaine
                        </label>
                        <InputText
                            value={data.semaine_nom}
                            onChange={e => setData('semaine_nom', e.target.value)}
                        />
                    </div>
                    <div className="col-12 md:col-6">
                        <label className="font-bold">Sous-Département</label>
                        <Dropdown
                            value={data.sous_departement_id}
                            options={sousDepartements}
                            optionLabel="nom"
                            optionValue="id"
                            onChange={e => setData('sous_departement_id', e.value)}
                            className={errors.sous_departement_id ? 'p-invalid' : ''}
                        />
                        {errors.sous_departement_id && <small className="p-error">{errors.sous_departement_id}</small>}
                    </div>
                </div>

                {activeLabs.length > 0 && (
                    <div className="mt-4">
                        <TabView>
                            {activeLabs.map(lab => {
                                // Vérifier si ce labo a des affectations
                                const hasMembersInLab = data.affectations[lab.id]?.some(r => r.membres?.length > 0);

                                return (
                                    <TabPanel
                                        key={lab.id}
                                        header={
                                            <span>
                                                {lab.nom}
                                                {!hasMembersInLab && <i className="pi pi-exclamation-circle ml-2 text-orange-500" title="Aucun membre affecté"></i>}
                                            </span>
                                        }
                                    >
                                        {rolesTache.map(role => (
                                            <div key={role.id} className="field mb-4">
                                                <label className="block mb-2">{role.libelle}</label>
                                                <MultiSelect
                                                    value={data.affectations[lab.id]?.find(r => r.role_id === role.id)?.membres || []}
                                                    options={membres}
                                                    optionLabel="nom_complet"
                                                    optionValue="id"
                                                    onChange={e => onMemberChange(lab.id, role.id, e.value)}
                                                    display="chip"
                                                    filter
                                                    placeholder={`Affecter des ${role.libelle}`}
                                                />
                                            </div>
                                        ))}
                                    </TabPanel>
                                );
                            })}
                        </TabView>
                    </div>
                )}

                <div className="flex justify-content-end mt-4">
                    {/* <Button label="Enregistrer" icon="pi pi-check" onClick={() => data.id ? put(route('designations.update', data.id)) : post(route('designations.store'))} loading={processing} /> */}
            {/* <Button
                        label="Enregistrer"
                        icon="pi pi-check"
                        onClick={() => data.id
                            ? put(`/designations/${data.id}`)  // URL manuelle pour UPDATE
                            : post('/designations')            // URL manuelle pour STORE
                        }
                        loading={processing}
                    /> */}
            {/* <Button
                        label="Enregistrer"
                        icon="pi pi-check"
                        onClick={submit} // Appelle la fonction de validation avant l'envoi
                        loading={processing}
                    />
                </div> */}
            {/* </Dialog> */}
        </Layout>
    );
};
// const DesignationsPage = ({ designations, departements, sousDepartements, laboratoires, membres, rolesTache }) => {
//     const [visible, setVisible] = useState(false);
//     const toast = useRef(null);
//     const { flash } = usePage().props;

//     // Filtrage dynamique
//     const [filteredSousDepts, setFilteredSousDepts] = useState([]);
//     const [activeLabs, setActiveLabs] = useState([]);

//     // Ajout de l'ID dans le formulaire pour savoir si on édite
//     const { data, setData, post, put, processing, reset } = useForm({
//         id: null,
//         semaine_nom: '',
//         departement_id: null,
//         sous_departement_id: null,
//         affectations: {}
//     });

//     // --- LOGIQUE D'ÉDITION ---
//     const editDesignation = (designation) => {

//         // Si row est undefined, on sort tout de suite
//         if (!designation) return;

//         // 1. On prépare les affectations pour le format MultiSelect local
//         const formattedAffectations = {};

//         (designation.items || []).forEach(item => {
//             if (!formattedAffectations[item.laboratoire_id]) {
//                 formattedAffectations[item.laboratoire_id] = [];
//             }
//             formattedAffectations[item.laboratoire_id].push({
//                 role_id: item.role_tache_id,
//                 membres: (item.membres || []).map(m => m.id) // On extrait les IDs des membres
//             });
//         });

//         // 2. On remplit le formulaire avec les données existantes
//         setData({
//             id: row.id,
//             semaine_nom: row.semaine_nom || '',
//             departement_id: row.sous_departement?.departement_id || null,
//             sous_departement_id: row.sous_departement_id || null,
//             affectations: formattedAffectations
//         });

//         setVisible(true);
//     };

//     // --- LOGIQUE DE SOUMISSION ---
//     const submit = () => {
//         if (data.id) {
//             // Si on a un ID, on appelle la route UPDATE (PUT)
//             put(route('designations.update', data.id), {
//                 onSuccess: () => { setVisible(false); reset(); },
//                 preserveScroll: true
//             });
//         } else {
//             // Sinon, création classique (POST)
//             post(route('designations.store'), {
//                 onSuccess: () => { setVisible(false); reset(); },
//                 preserveScroll: true
//             });
//         }
//     };

//     // Nouveau bouton "Ajouter" pour réinitialiser le formulaire
//     const openNew = () => {
//         reset();
//         setVisible(true);
//     };

//     // Helper pour mettre à jour les membres dans l'état local
//     const onMemberChange = (labId, roleId, selectedIds) => {
//         const currentAffectations = { ...data.affectations };

//         if (!currentAffectations[labId]) {
//             currentAffectations[labId] = [];
//         }

//         const roleIndex = currentAffectations[labId].findIndex(r => r.role_id === roleId);

//         if (roleIndex > -1) {
//             currentAffectations[labId][roleIndex].membres = selectedIds;
//         } else {
//             currentAffectations[labId].push({
//                 role_id: roleId,
//                 membres: selectedIds
//             });
//         }

//         setData('affectations', currentAffectations);
//     };

//     return (
//         <Layout>
//             <Toast ref={toast} />
//             <div className="card">
//                 <div className="flex justify-content-between mb-4">
//                     <h3>Désignations de la Semaine</h3>
//                     <Button
//                         label="Nouvelle Désignation"
//                         icon="pi pi-plus"
//                         severity="success"
//                         onClick={openNew} // <--- Appel de la fonction pour réinitialiser et ouvrir
//                     />
//                 </div>

//                 <DataTable value={designations} responsiveLayout="scroll">
//                     <Column field="libelle" header="Libellé" />
//                     <Column field="departement.nom" header="Département" />
//                     {/* C'EST ICI QU'ON APPELLE editDesignation */}
//                     <Column
//                         header="Actions"
//                         body={(rowData) => {
//                             // Si rowData n'a pas d'ID, on n'affiche rien (ligne fantôme)
//                             if (!rowData?.id) return null;

//                             return (
//                                 <div className="flex gap-2">
//                                     <Button
//                                         icon="pi pi-pencil"
//                                         className="p-button-rounded p-button-text p-button-info"
//                                         onClick={() => editDesignation(rowData)}
//                                         label='modifer la semaine'
//                                     />
//                                     <Button
//                                         icon="pi pi-trash"
//                                         className="p-button-rounded p-button-text p-button-danger"
//                                         onClick={() => confirmDelete(rowData)}
//                                         label='supprimer la semaine'
//                                     />
//                                 </div>
//                             );
//                         }}
//                     />
//                 </DataTable>
//             </div>

//             <Dialog
//                 visible={visible}
//                 style={{ width: '70vw' }}
//                 header={data.id ? "Modifier la Désignation" : "Nouvelle Désignation"}
//                 modal
//                 onHide={() => setVisible(false)}
//                 footer={(
//                     <div className="mt-4">
//                         <Button label="Annuler" icon="pi pi-times" text onClick={() => setVisible(false)} />
//                         <Button label={data.id ? "Mettre à jour" : "Valider"} icon="pi pi-check" onClick={submit} loading={processing} />
//                     </div>
//                 )}
//             >
//                 <div className="grid p-fluid">
//                     <div className="col-12 md:col-4">
//                         <label className="font-bold">Libellé</label>
//                         <InputText value={data.semaine_nom} onChange={(e) => setData('libelle', e.target.value)} placeholder="Ex: Semaine du 15 Mai" />
//                     </div>
//                     <div className="col-12 md:col-4">
//                         <label className="font-bold">Département</label>
//                         <Dropdown value={data.departement_id} options={departements} optionLabel="nom" optionValue="id" onChange={(e) => setData('departement_id', e.value)} />
//                     </div>
//                     <div className="col-12 md:col-4">
//                         <label className="font-bold">Sous-Département</label>
//                         <Dropdown value={data.sous_departement_id} options={filteredSousDepts} optionLabel="nom" optionValue="id" onChange={(e) => setData('sous_departement_id', e.value)} />
//                     </div>
//                 </div>

//                 {activeLabs.length > 0 && (
//                     <div className="mt-4">
//                         <h5>Configuration par Laboratoire</h5>
//                         <TabView>
//                             {activeLabs.map((lab) => (
//                                 <TabPanel key={lab.id} header={lab.nom}>
//                                     {rolesTache.map((role) => (
//                                         <div key={role.id} className="field mb-4">
//                                             <label className="font-bold block mb-2">{role.libelle}</label>
//                                             <MultiSelect
//                                                 value={data.affectations[lab.id]?.find(r => r.role_id === role.id)?.membres || []}
//                                                 options={membres}
//                                                 optionLabel="nom_complet" // Assurez-vous d'avoir cet accessor dans votre modèle Membre
//                                                 optionValue="id"
//                                                 onChange={(e) => onMemberChange(lab.id, role.id, e.value)}
//                                                 placeholder="Choisir les membres"
//                                                 display="chip"
//                                                 filter
//                                                 className="w-full"
//                                             />
//                                         </div>
//                                     ))}
//                                 </TabPanel>
//                             ))}
//                         </TabView>
//                     </div>
//                 )}
//             </Dialog>
//         </Layout>
//     );
// };

export default DesignationsPage;