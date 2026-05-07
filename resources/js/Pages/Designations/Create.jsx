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

    // Filtrage dynamique
    const filteredSousDepts = sousDepartements.filter(sd => sd.departement_id === data.departement_id);
    const filteredLabs = laboratoires.filter(l => l.sous_departement_id === data.sous_departement_id);

    // Le labo actuellement sélectionné avec ses données techniques
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

            <div className="card shadow-4 border-round-xl p-4">
                <h2 className="text-2xl font-bold mb-4 border-bottom-1 surface-border pb-2">
                    <i className="pi pi-calendar-plus mr-2 text-primary"></i>
                    Nouvelle Planification Hebdomadaire
                </h2>

                {/* 1. SECTION CONTEXTE (FILTRES) */}
                <div className="grid mb-4 bg-gray-50 p-3 border-round-lg">
                    <div className="col-12 md:col-6 lg:col-2">
                        <label className="font-bold block mb-1">Date Lundi</label>
                        <Calendar
                            value={data.date_debut}
                            onChange={e => setData('date_debut', e.value)}
                            showIcon className="w-full" placeholder="Date"
                        />
                    </div>

                    <div className="col-12 md:col-6 lg:col-2">
                        <label className="font-bold block mb-1">Nom Semaine</label>
                        <InputText
                            value={data.semaine_nom}
                            onChange={e => setData('semaine_nom', e.target.value)}
                            className="w-full"
                        />
                    </div>

                    <div className="col-12 md:col-4 lg:col-2">
                        <label className="font-bold block mb-1">Département</label>
                        <Dropdown
                            value={data.departement_id}
                            options={departements}
                            optionLabel="nom" optionValue="id"
                            placeholder="Sélectionner..."
                            onChange={e => setData(d => ({ ...d, departement_id: e.value, sous_departement_id: null, selected_lab_id: null }))}
                            className="w-full"
                        />
                    </div>

                    <div className="col-12 md:col-4 lg:col-3">
                        <label className="font-bold block mb-1">Sous-Département</label>
                        <Dropdown
                            value={data.sous_departement_id}
                            options={filteredSousDepts}
                            optionLabel="nom" optionValue="id"
                            placeholder="Choisir..."
                            disabled={!data.departement_id}
                            onChange={e => setData(d => ({ ...d, sous_departement_id: e.value, selected_lab_id: null }))}
                            className="w-full"
                        />
                    </div>

                    <div className="col-12 md:col-4 lg:col-3">
                        <label className="font-bold block mb-1 text-primary">LABORATOIRE</label>
                        <Dropdown
                            value={data.selected_lab_id}
                            options={filteredLabs}
                            optionLabel="nom" optionValue="id"
                            placeholder="Choisir le labo"
                            className="w-full shadow-1 border-primary"
                            disabled={!data.sous_departement_id}
                            onChange={e => setData('selected_lab_id', e.value)}
                        />
                    </div>
                </div>

                {/* 2. GRILLE DES JOURS */}
                {currentLab ? (
                    <div className="fadein animation-duration-300">
                        <div className="flex align-items-center justify-content-between mb-4 bg-primary-reverse p-3 border-round-lg">
                            <div className="flex align-items-center gap-3">
                                <i className="pi pi-building text-3xl text-primary"></i>
                                <h3 className="m-0 uppercase">{currentLab.nom}</h3>
                            </div>
                            {errors.all_designations && <Message severity="error" text="Données invalides" />}
                        </div>
                        {/* ZONE DE TRAVAIL : GRILLE DES JOURS */}
                        <div className="grid mt-4">
                            {currentLab.config_jours?.map((conf) => (
                                <div key={conf.id || conf.jour} className="col-12 lg:col-4 p-3">
                                    <div className="surface-card border-1 border-300 border-round-xl shadow-2 h-full overflow-hidden">

                                        {/* Header du Jour - Gris sobre comme l'image */}
                                        <div className="p-3 bg-200 text-center border-bottom-1 border-300">
                                            <span className="font-bold text-900 uppercase tracking-wider">
                                                {conf.jour_label || `Jour ${conf.jour}`}
                                            </span>
                                        </div>

                                        {/* Corps de la carte : Grille interne de 3 colonnes pour les rôles */}
                                        <div className="p-3">
                                            <div className="grid">
                                                {conf.requis?.map((req) => (
                                                    <div key={req.id} className="col-4 p-1"> {/* 3 colonnes (4/12) */}
                                                        <label className="text-center block text-xs font-bold text-600 uppercase mb-1 truncate" title={req.role_tache?.libelle || req.libelle}>
                                                            {req.role_tache?.libelle || req.libelle}
                                                        </label>

                                                        <Dropdown
                                                            value={data.all_designations[currentLab.id]?.[conf.jour]?.[req.id] || null}
                                                            options={membres}
                                                            optionLabel="nom"
                                                            optionValue="id"
                                                            placeholder="N/A"
                                                            className="w-full p-inputtext-sm text-xs bg-100"
                                                            filter
                                                            onChange={(e) => handleMemberChange(currentLab.id, conf.jour, req.id, e.value)}
                                                            // Style spécifique pour ressembler à l'image
                                                            style={{ height: '35px' }}
                                                        />
                                                    </div>
                                                ))}
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            ))}

                            {/* Section Remplaçants / Réserves (Optionnel, calqué sur l'image) */}
                            <div className="col-12 lg:col-4 p-3">
                                <div className="surface-card border-1 border-blue-200 border-round-xl shadow-2 h-full overflow-hidden bg-blue-50">
                                    <div className="p-3 bg-blue-100 text-center border-bottom-1 border-blue-200">
                                        <span className="font-bold text-blue-800 uppercase tracking-wider">Remplaçants / Réserves</span>
                                    </div>
                                    <div className="p-3 grid">
                                        {/* Exemple de liste de réserve */}
                                        <div className="col-6 p-1">
                                            <label className="text-center block text-xs font-bold text-blue-600 uppercase mb-1">Réserve 1</label>
                                            <Dropdown placeholder="N/A" className="w-full p-inputtext-sm text-xs" />
                                        </div>
                                        <div className="col-6 p-1">
                                            <label className="text-center block text-xs font-bold text-blue-600 uppercase mb-1">Réserve 2</label>
                                            <Dropdown placeholder="N/A" className="w-full p-inputtext-sm text-xs" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {/* <div className="grid">
                            {currentLab.config_jours?.length > 0 ? (
                                currentLab.config_jours.map((conf) => (
                                    <div key={conf.id || conf.jour} className="col-12 md:col-6 lg:col-4 p-2">
                                        <div className="surface-card border-1 surface-border border-round-xl shadow-1 h-full">
                                            <div className="p-3 surface-100 font-bold border-bottom-1 surface-border border-top-round-xl uppercase text-xs text-600 flex justify-content-between">
                                                <span>{conf.jour_label || `Jour ${conf.jour}`}</span>
                                                <i className="pi pi-clock"></i>
                                            </div>
                                            <div className="p-3">
                                                {conf.requis?.map((req) => (
                                                    <div key={req.id} className="mb-3">
                                                        <label className="text-xs font-bold text-500 uppercase block mb-1">
                                                            {req.role_tache?.libelle || req.libelle}
                                                        </label>
                                                        <Dropdown
                                                            value={data.all_designations[currentLab.id]?.[conf.jour]?.[req.id] || null}
                                                            options={membres}
                                                            optionLabel="nom" optionValue="id"
                                                            placeholder="Assigner membre"
                                                            className="w-full p-inputtext-sm"
                                                            filter
                                                            onChange={(e) => handleMemberChange(currentLab.id, conf.jour, req.id, e.value)}
                                                        />
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <div className="col-12">
                                    <Message severity="warn" text="Aucune configuration de jours trouvée pour ce laboratoire." className="w-full" />
                                </div>
                            )}
                        </div>
                         */}
                        <div className="flex justify-content-end mt-6 pt-4 border-top-1 surface-border">
                            <Button
                                label="Enregistrer toute la planification"
                                icon="pi pi-save"
                                className="p-button-lg shadow-4 p-button-success"
                                loading={processing}
                                onClick={submit}
                            />
                        </div>
                    </div>
                ) : (
                    <div className="text-center p-8 border-2 border-dashed border-300 border-round-xl mt-4 bg-gray-50">
                        <i className="pi pi-arrow-up text-4xl text-300 mb-3"></i>
                        <p className="text-600 font-italic text-xl">
                            Sélectionnez un département, puis un sous-département et enfin un laboratoire pour voir la grille.
                        </p>
                    </div>
                )}
            </div>
        </Layout>
    );
};

export default CreateDesignation;