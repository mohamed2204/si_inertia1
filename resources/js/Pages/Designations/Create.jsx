import React, { useEffect } from 'react';
import Layout from '@/Layouts/layout';
import { Head, useForm } from '@inertiajs/react';
import { Dropdown } from 'primereact/dropdown';
import { Calendar } from 'primereact/calendar';
import { Button } from 'primereact/button';
import { InputText } from 'primereact/inputtext';
import { Message } from 'primereact/message';

const CreateDesignation = ({ departements = [], sousDepartements = [], laboratoires = [], membres = [] }) => {

    const { data, setData, post, processing, errors } = useForm({
        semaine_nom: '',
        date_debut: null,
        departement_id: null,
        sous_departement_id: null,
        selected_lab_id: null,
        all_designations: {}
    });

    // Auto-génération du nom de la semaine
    useEffect(() => {
        if (data.date_debut && !data.semaine_nom) {
            const date = new Date(data.date_debut);
            const janFirst = new Date(date.getFullYear(), 0, 1);
            const weekNumber = Math.ceil((((date - janFirst) / 8.64e7) + janFirst.getDay() + 1) / 7);
            setData('semaine_nom', `Semaine ${weekNumber} - ${date.getFullYear()}`);
        }
    }, [data.date_debut]);

    const filteredSousDepts = sousDepartements.filter(sd => sd.departement_id === data.departement_id);
    const filteredLabs = laboratoires.filter(l => l.sous_departement_id === data.sous_departement_id);
    const currentLab = laboratoires.find(l => l.id === data.selected_lab_id);

    const handleMemberChange = (labId, jour, requisId, membreId) => {
        const newDesignations = { ...data.all_designations };
        if (!newDesignations[labId]) newDesignations[labId] = {};
        if (!newDesignations[labId][jour]) newDesignations[labId][jour] = {};

        newDesignations[labId][jour][requisId] = membreId;
        setData('all_designations', newDesignations);
    };

    const submit = (e) => {
        e.preventDefault();
        post(route('designations.store'));
    };

    return (
        <Layout>
            <Head title="Nouvelle Désignation" />

            <div className="card shadow-2 border-round-xl p-4">
                <h2 className="text-xl font-bold mb-4 border-bottom-1 surface-border pb-3 text-800">
                    <i className="pi pi-calendar-plus mr-2 text-blue-500"></i>
                    Nouvelle Planification Hebdomadaire
                </h2>

                {/* 1. SECTION CONTEXTE (Filtres avec style épuré) */}
                <div className="grid mb-5 bg-bluegray-50 p-3 border-round-xl border-1 border-100">
                    <div className="col-12 md:col-6 lg:col-2">
                        <label className="text-xs font-bold block mb-1 text-600">DATE LUNDI</label>
                        <Calendar value={data.date_debut} onChange={e => setData('date_debut', e.value)} showIcon className="w-full" />
                    </div>
                    <div className="col-12 md:col-6 lg:col-2">
                        <label className="text-xs font-bold block mb-1 text-600">NOM SEMAINE</label>
                        <InputText value={data.semaine_nom} onChange={e => setData('semaine_nom', e.target.value)} className="w-full" />
                    </div>
                    <div className="col-12 md:col-4 lg:col-2">
                        <label className="text-xs font-bold block mb-1 text-600">DÉPARTEMENT</label>
                        <Dropdown value={data.departement_id} options={departements} optionLabel="nom" optionValue="id" placeholder="Sélectionner..." onChange={e => setData(d => ({ ...d, departement_id: e.value, sous_departement_id: null, selected_lab_id: null }))} className="w-full" />
                    </div>
                    <div className="col-12 md:col-4 lg:col-3">
                        <label className="text-xs font-bold block mb-1 text-600">SOUS-DÉPARTEMENT</label>
                        <Dropdown value={data.sous_departement_id} options={filteredSousDepts} optionLabel="nom" optionValue="id" placeholder="Choisir..." disabled={!data.departement_id} onChange={e => setData(d => ({ ...d, sous_departement_id: e.value, selected_lab_id: null }))} className="w-full" />
                    </div>
                    <div className="col-12 md:col-4 lg:col-3">
                        <label className="text-xs font-bold block mb-1 text-blue-700">LABORATOIRE CIBLE</label>
                        <Dropdown value={data.selected_lab_id} options={filteredLabs} optionLabel="nom" optionValue="id" placeholder="Choisir le labo" className="w-full shadow-1 border-blue-200" disabled={!data.sous_departement_id} onChange={e => setData('selected_lab_id', e.value)} />
                    </div>
                </div>

                {/* 2. GRILLE DES JOURS STYLE IMAGE */}
                {currentLab ? (
                    <div className="fadein animation-duration-400">
                        <div className="flex align-items-center gap-2 mb-4">
                            <span className="bg-blue-500 text-white border-round-md p-2 flex align-items-center justify-content-center">
                                <i className="pi pi-building text-xl"></i>
                            </span>
                            <h3 className="m-0 text-700 uppercase tracking-tight">{currentLab.nom}</h3>
                        </div>

                        <div className="grid">
                            {currentLab.config_jours?.map((conf) => (
                                <div key={conf.id || conf.jour} className="col-12 md:col-6 xl:col-4 p-2">
                                    {/* CONTENEUR CARTE STYLE FLAT */}
                                    <div className="border-1 border-200 border-round-xl bg-white shadow-1 overflow-hidden h-full">
                                        
                                        {/* HEADER BLEU CIEL */}
                                        <div className="bg-blue-50 p-3 text-center border-bottom-1 border-100">
                                            <span className="font-bold text-700 uppercase tracking-wider text-sm">
                                                {conf.jour_label || `Jour ${conf.jour}`}
                                            </span>
                                        </div>

                                        {/* CORPS DE CARTE DENSE (3 colonnes) */}
                                        <div className="p-3">
                                            <div className="grid row-gap-3">
                                                {conf.requis?.map((req) => (
                                                    <div key={req.id} className="col-4 p-1">
                                                        <label className="text-center block text-xs font-semibold text-500 uppercase mb-1 truncate" title={req.role_tache?.libelle || req.libelle}>
                                                            {req.role_tache?.libelle || req.libelle}
                                                        </label>

                                                        <Dropdown
                                                            value={data.all_designations[currentLab.id]?.[conf.jour]?.[req.id] || null}
                                                            options={membres}
                                                            optionLabel="nom"
                                                            optionValue="id"
                                                            placeholder="..."
                                                            className="w-full p-inputtext-sm text-xs border-round-md"
                                                            style={{ height: '34px' }}
                                                            filter
                                                            onChange={(e) => handleMemberChange(currentLab.id, conf.jour, req.id, e.value)}
                                                        />
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>

                        <div className="flex justify-content-end mt-6 pt-4 border-top-1 border-100">
                            <Button
                                label="Enregistrer la planification"
                                icon="pi pi-check"
                                className="p-button-success px-5 py-3 font-bold border-round-xl shadow-3"
                                loading={processing}
                                onClick={submit}
                            />
                        </div>
                    </div>
                ) : (
                    <div className="text-center p-8 border-2 border-dashed border-200 border-round-xl mt-4 bg-gray-50 flex flex-column align-items-center">
                        <i className="pi pi-info-circle text-4xl text-200 mb-3"></i>
                        <p className="text-500 text-lg">Sélectionnez un laboratoire pour afficher la grille de planification.</p>
                    </div>
                )}
            </div>
        </Layout>
    );
};

export default CreateDesignation;