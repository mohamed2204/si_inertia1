import React, { useEffect } from 'react';
import Layout from '@/Layouts/layout';
import { Head, useForm } from '@inertiajs/react';
import { Dropdown } from 'primereact/dropdown';
import { Calendar } from 'primereact/calendar';
import { Button } from 'primereact/button';
import { InputText } from 'primereact/inputtext';
import { Message } from 'primereact/message';

const DesignationsPage = ({ departements, sousDepartements, laboratoires, membres }) => {
    
    const { data, setData, post, processing, errors } = useForm({
        semaine_nom: '',
        date_debut: null,
        departement_id: null,
        sous_departement_id: null,
        selected_lab_id: null,
        // Structure : { labId: { jour: { requisId: membreId } } }
        // On stocke par labId pour ne pas perdre la saisie en changeant de labo
        all_designations: {} 
    });

    // Optionnel : Générer automatiquement le nom de la semaine quand la date change
    useEffect(() => {
        if (data.date_debut && !data.semaine_nom) {
            const date = new Date(data.date_debut);
            const weekNumber = Math.ceil((((date - new Date(date.getFullYear(), 0, 1)) / 8.64e7) + 1) / 7);
            setData('semaine_nom', `Semaine ${weekNumber} - ${date.getFullYear()}`);
        }
    }, [data.date_debut]);

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
        // On envoie tout le paquet au serveur
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

                {/* 1. SECTION CONTEXTE */}
                <div className="grid mb-4 bg-gray-50 p-3 border-round-lg">
                    <div className="col-12 md:col-3">
                        <label className="font-bold block mb-1">Date du Lundi</label>
                        <Calendar 
                            value={data.date_debut} 
                            onChange={e => setData('date_debut', e.value)} 
                            showIcon 
                            className="w-full"
                            placeholder="Choisir le lundi"
                        />
                        {errors.date_debut && <small className="p-error">{errors.date_debut}</small>}
                    </div>

                    <div className="col-12 md:col-3">
                        <label className="font-bold block mb-1">Nom/Réf Semaine</label>
                        <InputText 
                            value={data.semaine_nom} 
                            onChange={e => setData('semaine_nom', e.target.value)} 
                            placeholder="ex: Semaine 22 - Mai"
                        />
                    </div>
                    
                    <div className="col-12 md:col-3">
                        <label className="font-bold block mb-1">Sous-Département</label>
                        <Dropdown 
                            value={data.sous_departement_id} 
                            options={sousDepartements} 
                            optionLabel="nom" optionValue="id"
                            placeholder="Sélectionner..."
                            onChange={e => setData('sous_departement_id', e.value)} 
                        />
                    </div>

                    <div className="col-12 md:col-3">
                        <label className="font-bold block mb-1 text-primary">LABORATOIRE À TRAVAILLER</label>
                        <Dropdown 
                            value={data.selected_lab_id} 
                            options={laboratoires.filter(l => l.sous_departement_id === data.sous_departement_id)} 
                            optionLabel="nom" optionValue="id"
                            placeholder={data.sous_departement_id ? "Choisir le labo" : "Choisir sous-dept d'abord"}
                            className="p-inputtext-lg shadow-2 border-primary"
                            disabled={!data.sous_departement_id}
                            onChange={e => setData('selected_lab_id', e.value)} 
                        />
                    </div>
                </div>

                {/* 2. ZONE DE TRAVAIL DYNAMIQUE */}
                {currentLab ? (
                    <div className="fadein animation-duration-300">
                        <div className="flex align-items-center justify-content-between mb-4">
                            <div className="flex align-items-center gap-3">
                                <i className="pi pi-building text-3xl text-primary"></i>
                                <h3 className="m-0 uppercase">{currentLab.nom}</h3>
                            </div>
                            {errors.all_designations && <Message severity="error" text="Certains postes sont requis" />}
                        </div>

                        <div className="grid">
                            {currentLab.config_jours?.map((conf) => (
                                <div key={conf.jour} className="col-12 md:col-6 lg:col-4 p-2">
                                    <div className="surface-card border-1 surface-border border-round-xl shadow-1 h-full">
                                        <div className="p-3 surface-100 font-bold border-bottom-1 surface-border border-top-round-xl uppercase text-xs text-600">
                                            {conf.jour_label || `Jour ${conf.jour}`}
                                        </div>
                                        <div className="p-3">
                                            {conf.requis?.map((req) => (
                                                <div key={req.id} className="mb-3">
                                                    <label className="text-xs font-bold text-500 uppercase block mb-1">
                                                        {req.libelle}
                                                    </label>
                                                    <Dropdown
                                                        value={data.all_designations[currentLab.id]?.[conf.jour]?.[req.id] || null}
                                                        options={membres}
                                                        optionLabel="nom" optionValue="id"
                                                        placeholder="Sélectionner membre"
                                                        className="w-full p-inputtext-sm"
                                                        filter
                                                        onChange={(e) => handleMemberChange(currentLab.id, conf.jour, req.id, e.value)}
                                                    />
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                        
                        <div className="flex justify-content-end mt-6 pt-4 border-top-1 surface-border">
                             <Button 
                                label="Enregistrer la planification" 
                                icon="pi pi-save" 
                                className="p-button-lg shadow-4" 
                                loading={processing}
                                onClick={submit} 
                             />
                        </div>
                    </div>
                ) : (
                    <div className="text-center p-8 border-2 border-dashed border-300 border-round-xl mt-4 bg-gray-50">
                        <i className="pi pi-arrow-up text-4xl text-300 mb-3"></i>
                        <p className="text-600 font-italic text-xl">
                            Sélectionnez un laboratoire dans la liste bleue pour configurer les postes.
                        </p>
                    </div>
                )}
            </div>
        </Layout>
    );
};

export default DesignationsPage;