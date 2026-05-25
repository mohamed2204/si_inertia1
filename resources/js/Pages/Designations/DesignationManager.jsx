import React from 'react';
import { useForm } from '@inertiajs/react';
import { Accordion, AccordionTab } from 'primereact/accordion';
import { Dropdown } from 'primereact/dropdown';
import { Button } from 'primereact/button';

const DesignationManager = ({ sousDepartements, membres }) => {
    const { data, setData, post, processing } = useForm({
        designations: {},
        responsables_sd: {}
    });

    const handleMemberChange = (labId, jour, index, value) => {
        const newDesignations = { ...data.designations };
        if (!newDesignations[labId]) newDesignations[labId] = {};
        if (!newDesignations[labId][jour]) newDesignations[labId][jour] = [];
        newDesignations[labId][jour][index] = value;
        setData('designations', newDesignations);
    };

    const handleSDResponsableChange = (sdId, index, value) => {
        const newResp = { ...data.responsables_sd };
        if (!newResp[sdId]) newResp[sdId] = [];
        newResp[sdId][index] = value;
        setData('responsables_sd', newResp);
    };

    return (
        <div className="p-2">
            {sousDepartements.map((sd) => (
                <div key={sd.id} className="mb-4">
                    {/* SECTION RESPONSABLES DU SOUS-DEPARTEMENT */}
                    <div className="surface-card p-4 shadow-1 border-round mb-4 border-1 border-200">
                        <h3 className="mt-0 mb-3 text-900 text-xl">
                            <i className="pi pi-shield mr-2 text-blue-500"></i>
                            {sd.nom} : Responsables Hebdomadaires
                        </h3>
                        <div className="grid">
                            {Array.from({ length: sd.nb_responsables_requis || 2 }).map((_, i) => (
                                <div key={i} className="col-12 md:col-4">
                                    <label className="text-xs font-bold block mb-2 uppercase text-500">Responsable {i + 1}</label>
                                    <Dropdown
                                        value={data.responsables_sd[sd.id]?.[i]}
                                        options={membres}
                                        optionLabel="nom"
                                        optionValue="id"
                                        placeholder="Sélectionner..."
                                        className="w-full"
                                        onChange={(e) => handleSDResponsableChange(sd.id, i, e.value)}
                                        filter
                                    />
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* ACCORDEON DES LABORATOIRES */}
                    <Accordion multiple activeIndex={[0]}>
                        {/* 1. Boucle sur les laboratoires */}
                        {sd.laboratoires.map((lab) => (
                            <AccordionTab key={lab.id} header={lab.nom}>

                                <div className="grid mt-2">
                                    {/* 2. Boucle sur config_jours : C'EST ICI QUE "conf" EST DÉFINI */}
                                    {lab.config_jours.map((conf) => (
                                        <div key={conf.jour} className="col-12 md:col-6 lg:col-4 p-2">
                                            <div className="border-1 surface-border border-round shadow-1 bg-white h-full">

                                                {/* Header de la carte du jour */}
                                                <div className="p-3 surface-100 border-bottom-1 surface-border border-top-round">
                                                    <span className="text-900 font-bold uppercase text-xs tracking-wider">
                                                        {conf.jour_label}
                                                    </span>
                                                </div>

                                                {/* Corps de la carte */}
                                                <div className="p-3">
                                                    <div className="grid">
                                                        {/* 3. Boucle sur les détails du jour */}
                                                        {conf.details.map((detail, idx) => (
                                                            <div key={idx} className="col-12 mb-2">
                                                                <label className="text-xs font-semibold block mb-1 text-500 uppercase">
                                                                    {detail.role}
                                                                </label>

                                                                {Array.from({ length: detail.nb || 1 }).map((_, subIdx) => {
                                                                    const inputKey = `${detail.role}_${subIdx}`;
                                                                    return (
                                                                        <Dropdown
                                                                            key={inputKey}
                                                                            value={data.designations[lab.id]?.[conf.jour]?.[inputKey]}
                                                                            options={membres}
                                                                            optionLabel="nom"
                                                                            optionValue="id"
                                                                            placeholder={`Sélectionner pour ${detail.role}`}
                                                                            className="w-full p-inputtext-sm mb-2"
                                                                            onChange={(e) => handleMemberChange(lab.id, conf.jour, inputKey, e.value)}
                                                                            filter
                                                                        />
                                                                    );
                                                                })}
                                                            </div>
                                                        ))}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                                
                            </AccordionTab>
                        ))}
                    </Accordion>
                </div>
            ))}

            {/* BOUTON DE SAUVEGARDE FIXE OU EN BAS */}
            <div className="flex justify-content-end mt-5 pb-5">
                <Button
                    label="Enregistrer les Désignations"
                    icon="pi pi-check-circle"
                    className="p-button-primary p-button-lg px-6 shadow-4 border-round-xl"
                    onClick={() => post('/designations/store')}
                    loading={processing}
                />
            </div>
        </div>
    );
};

export default DesignationManager;