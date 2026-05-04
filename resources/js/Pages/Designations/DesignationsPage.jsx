import React, { useState, useRef } from 'react';
import Layout from '@/Layouts/layout';
import { useForm } from '@inertiajs/react';
import { TabView, TabPanel } from 'primereact/tabview';
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

    const { data, setData, post, put, processing, reset } = useForm({
        id: null,
        semaine_nom: '',
        departement_id: null,
        sous_departement_id: null,
        date_debut: null,
        // Structure isolée par Labo
        labs_data: {} // { labId: [ {role_id: X, slot: Y, membre_id: Z} ] }
    });

    const filteredSousDepts = sousDepartements.filter(sd => sd.departement_id === data.departement_id);
    const activeLabs = laboratoires.filter(lab => lab.sous_departement_id === data.sous_departement_id);

    const handleGridChange = (labId, roleId, slot, membreId) => {
        const currentLabsData = { ...data.labs_data };
        if (!currentLabsData[labId]) currentLabsData[labId] = [];

        const index = currentLabsData[labId].findIndex(a => a.role_id === roleId && a.slot === slot);

        if (index > -1) {
            if (membreId) currentLabsData[labId][index].membre_id = membreId;
            else currentLabsData[labId].splice(index, 1); // Supprimer si vide
        } else if (membreId) {
            currentLabsData[labId].push({ role_id: roleId, slot: slot, membre_id: membreId });
        }

        setData('labs_data', currentLabsData);
    };

    const submit = () => {
        const action = data.id ? put : post;
        const url = data.id ? `/designations/${data.id}` : '/designations';

        action(url, {
            onSuccess: () => {
                setVisible(false);
                reset();
                toast.current.show({ severity: 'success', summary: 'Succès', detail: 'Planning enregistré' });
            }
        });
    };

    return (
        <Layout>
            <Toast ref={toast} />
            <div className="card">
                <div className="flex justify-content-between mb-4">
                    <h3 className="m-0">Gestion des Désignations</h3>
                    <Button label="Nouvelle Planification" icon="pi pi-plus" severity="success" onClick={() => { reset(); setVisible(true); }} />
                </div>

                <DataTable value={designations} paginator rows={10} responsiveLayout="stack">
                    <Column header="Département" body={(row) => row.sous_departement?.departement?.nom} sortable />
                    <Column field="semaine_nom" header="Semaine" sortable />
                    <Column field="sous_departement.nom" header="Sous-Département" sortable />
                    <Column header="Date" body={(row) => new Date(row.date_debut).toLocaleDateString()} sortable />
                    <Column header="Actions" body={(row) => (
                        <Button icon="pi pi-pencil" className="p-button-text" onClick={() => { /* Logique Edit adaptée au labs_data */ }} />
                    )} />
                </DataTable>
            </div>

            <Dialog header="Planification de la Semaine" visible={visible} style={{ width: '85vw' }} onHide={() => setVisible(false)} maximizable>
                <div className="grid p-fluid">
                    <div className="col-12 md:col-6">
                        <label className="font-bold">Libellé</label>
                        <InputText value={data.semaine_nom} onChange={e => setData('semaine_nom', e.target.value)} />
                    </div>
                    <div className="col-12 md:col-6">
                        <label className="font-bold">Date de début</label>
                        <Calendar value={data.date_debut} onChange={e => setData('date_debut', e.value)} showIcon dateFormat="dd/mm/yy" />
                    </div>
                    <div className="col-12 md:col-6 mt-2">
                        <label className="font-bold">Département</label>
                        <Dropdown value={data.departement_id} options={departements} optionLabel="nom" optionValue="id" 
                            onChange={e => setData(d => ({ ...d, departement_id: e.value, sous_departement_id: null, labs_data: {} }))} />
                    </div>
                    <div className="col-12 md:col-6 mt-2">
                        <label className="font-bold">Sous-Département</label>
                        <Dropdown value={data.sous_departement_id} options={filteredSousDepts} optionLabel="nom" optionValue="id" disabled={!data.departement_id}
                            onChange={e => setData(d => ({ ...d, sous_departement_id: e.value, labs_data: {} }))} />
                    </div>
                </div>

                {activeLabs.length > 0 && (
                    <TabView className="mt-4 shadow-2">
                        {activeLabs.map(lab => (
                            <TabPanel key={lab.id} header={lab.nom} leftIcon="pi pi-building mr-2">
                                <div className="p-3 bg-white">
                                    <h4 className="text-primary mb-4 border-bottom-1 pb-2">Configuration des postes : {lab.nom}</h4>
                                    <GrilleDesignation 
                                        lab={lab} 
                                        membres={membres} 
                                        affectations={data.labs_data[lab.id] || []}
                                        onCellChange={(roleId, slot, membreId) => handleGridChange(lab.id, roleId, slot, membreId)}
                                    />
                                </div>
                            </TabPanel>
                        ))}
                    </TabView>
                )}

                <div className="flex justify-content-end mt-4">
                    <Button label="Valider toutes les désignations" icon="pi pi-save" className="p-button-lg" onClick={submit} loading={processing} />
                </div>
            </Dialog>
        </Layout>
    );
};

export default DesignationsPage;