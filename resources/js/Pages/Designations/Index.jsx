import React, { useState, useEffect, useRef } from 'react';
import Layout from '@/Layouts/layout';
import { useForm, router} from '@inertiajs/react';
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


const Designations = ({
    designations = { data: [] },
    departements = [],
    sousDepartements = [],
    laboratoires = [],
    membres = []
}) => {

    const [visible, setVisible] = useState(false);
    const toast = useRef(null);

    const { data, setData, post, put, processing, reset, errors } = useForm({
        id: null,
        semaine_nom: '',
        departement_id: null,
        sous_departement_id: null,
        date_debut: null,
        decision_id: null, // Ajout du lien parent (optionnel au départ)
        affectations: {} // Structure: { labId: [ {role_id: X, membres: [ids]} ] }
    });

    // --- FILTRAGE DYNAMIQUE ---
    const filteredSousDepts = sousDepartements.filter(sd => sd.departement_id === data.departement_id);
    const activeLabs = laboratoires.filter(lab => lab.sous_departement_id === data.sous_departement_id);

    const onMemberChange = (labId, roleId, selectedIds) => {
        const current = { ...data.affectations };
        if (!current[labId]) current[labId] = [];

        const roleIndex = current[labId].findIndex(r => Number(r.role_id) === Number(roleId));
        if (roleIndex > -1) {
            current[labId][roleIndex].membres = selectedIds;
        } else {
            current[labId].push({ role_id: roleId, membres: selectedIds });
        }
        setData('affectations', current);
    };

    const editDesignation = (row) => {
        if (!row) return;

        // 1. Trouver le département parent
        const parentDept = sousDepartements.find(sd => sd.id === row.sous_departement_id);

        // 2. Formater les affectations de la DB vers le state MultiSelect
        const formatted = {};
        (row.items || []).forEach(item => {
            const labId = item.laboratoire_id;
            if (!formatted[labId]) formatted[labId] = [];

            // On cherche si le rôle existe déjà pour ce labo dans notre objet de formatage
            const existingRole = formatted[labId].find(r => r.role_id === Number(item.role_tache_id));

            if (existingRole) {
                if (item.membre_id) existingRole.membres.push(Number(item.membre_id));
            } else {
                formatted[labId].push({
                    role_id: Number(item.role_tache_id),
                    membres: item.membre_id ? [Number(item.membre_id)] : []
                });
            }
        });

        setData({
            id: row.id,
            semaine_nom: row.semaine_nom || '',
            date_debut: row.date_debut ? new Date(row.date_debut) : null,
            departement_id: parentDept?.departement_id || null,
            sous_departement_id: row.sous_departement_id || null,
            decision_id: row.decision_id || null,
            affectations: formatted
        });

        setVisible(true);
    };

    const openNew = () => {
        // reset();
        // setVisible(true);
        //router.get(route('designations.create'));
        router.get('/designations/create');

    };

    const submit = () => {
        if (!data.sous_departement_id || !data.date_debut) {
            toast.current.show({ severity: 'error', summary: 'Champs requis', detail: 'Libellé, Sous-Département et Date sont obligatoires.' });
            return;
        }

        const action = data.id ? put : post;
        const url = data.id ? `/designations/${data.id}` : '/designations';

        action(url, {
            onSuccess: () => {
                setVisible(false);
                reset();
                toast.current.show({ severity: 'success', summary: 'Succès', detail: 'La planification a été enregistrée.' });
            }
        });
    };

    return (
        <Layout>
            <Toast ref={toast} />
            <div className="card shadow-2 border-round-xl p-3">
                <div className="flex justify-content-between align-items-center mb-4">
                    <h2 className="m-0 text-xl font-bold text-700">Gestion des Désignations</h2>
                    <Button label="Nouvelle Planification" icon="pi pi-plus" severity="success" onClick={openNew} />
                </div>

                <DataTable
                    value={designations.data} paginator rows={10} className="p-datatable-sm" sortField="date_debut" sortOrder={-1}>
                    <Column header="Département" body={(row) => row.sous_departement?.departement?.nom} sortable />
                    <Column field="semaine_nom" header="Semaine" sortable />
                    <Column field="sous_departement.nom" header="Sous-Département" sortable />
                    <Column header="Date" body={(row) => row.date_debut ? new Date(row.date_debut).toLocaleDateString() : ''} sortable />
                    <Column header="Laboratoires" body={(row) => {
                        const labs = [...new Set(row.items?.map(i => i.laboratoire?.nom))].filter(Boolean);
                        return (
                            <div className="flex flex-wrap gap-1">
                                {labs.map((name, i) => <Badge key={i} value={name} severity="info" />)}
                            </div>
                        );
                    }} />
                    <Column header="Décision Parent" body={(row) => row.decision_id ? <Badge value="Fédéré" severity="success" /> : <Badge value="En attente" severity="warning" />} />
                    <Column header="Actions" style={{ width: '5rem' }} body={(row) => (
                        <Button icon="pi pi-pencil" className="p-button-text p-button-rounded p-button-warning" onClick={() => editDesignation(row)} />
                    )} />
                </DataTable>
            </div>

            <Dialog header={data.id ? "Modifier la Planification" : "Nouvelle Planification Hebdomadaire"} visible={visible} style={{ width: '85vw' }} onHide={() => setVisible(false)} modal>
                <div className="grid p-fluid mt-2">
                    <div className="col-12 md:col-6 field">
                        <label className="font-bold">Libellé Semaine</label>
                        <InputText value={data.semaine_nom} onChange={e => setData('semaine_nom', e.target.value)} placeholder="Ex: S22 - Biologie" />
                    </div>
                    <div className="col-12 md:col-6 field">
                        <label className="font-bold">Date de début (Lundi)</label>
                        <Calendar value={data.date_debut} onChange={e => setData('date_debut', e.value)} dateFormat="yy-mm-dd" showIcon placeholder="Sélectionnez la date" />
                    </div>
                    <div className="col-12 md:col-6 field">
                        <label className="font-bold">Département</label>
                        <Dropdown value={data.departement_id} options={departements} optionLabel="nom" optionValue="id"
                            onChange={e => setData(d => ({ ...d, departement_id: e.value, sous_departement_id: null, affectations: {} }))}
                            placeholder="Choisir département" filter />
                    </div>
                    <div className="col-12 md:col-6 field">
                        <label className="font-bold">Sous-Département / Groupe</label>
                        <Dropdown value={data.sous_departement_id} options={filteredSousDepts} optionLabel="nom" optionValue="id"
                            disabled={!data.departement_id} onChange={e => setData(d => ({ ...d, sous_departement_id: e.value, affectations: {} }))}
                            placeholder="Choisir sous-département" filter />
                    </div>
                </div>

                {activeLabs.length > 0 ? (
                    <TabView className="mt-4 shadow-1">
                        {activeLabs.map(lab => (
                            <TabPanel key={lab.id} header={lab.nom} leftIcon="pi pi-building mr-2">
                                <div className="grid">
                                    {(lab.lab_requis || []).map(requis => (
                                        <div key={requis.id} className="col-12 md:col-6 lg:col-4 mb-3">
                                            <label className="block mb-2 font-semibold text-600 uppercase text-xs">
                                                {requis.role_tache?.libelle}
                                            </label>
                                            <MultiSelect
                                                value={data.affectations[lab.id]?.find(r => Number(r.role_id) === Number(requis.role_tache_id))?.membres || []}
                                                options={membres}
                                                optionLabel="nom"
                                                optionValue="id"
                                                onChange={e => onMemberChange(lab.id, requis.role_tache_id, e.value)}
                                                display="chip"
                                                filter
                                                placeholder="Assigner membres..."
                                                className="w-full border-primary"
                                            />
                                        </div>
                                    ))}
                                </div>
                            </TabPanel>
                        ))}
                    </TabView>
                ) : (
                    <div className="text-center p-5 surface-100 border-round-xl mt-4 border-2 border-dashed border-300">
                        <p className="text-500 italic">Veuillez sélectionner un sous-département pour afficher les laboratoires disponibles.</p>
                    </div>
                )}

                <div className="flex justify-content-end mt-5 gap-2 border-top-1 surface-border pt-3">
                    <Button label="Annuler" icon="pi pi-times" className="p-button-text" onClick={() => setVisible(false)} />
                    <Button label="Enregistrer la planification" icon="pi pi-check" onClick={submit} loading={processing} severity="success" />
                </div>
            </Dialog>
        </Layout>
    );
};

export default Designations;