import React, { useEffect } from "react";
import Layout from "@/Layouts/layout";
import { Head, useForm } from "@inertiajs/react";
import { Dropdown } from "primereact/dropdown";
import { Calendar } from "primereact/calendar";
import { Button } from "primereact/button";
import { InputText } from "primereact/inputtext";
import { Message } from "primereact/message";

const CreateDesignation = ({
    departements = [],
    sousDepartements = [],
    laboratoires = [],
    membres = [],
}) => {
    const { data, setData, post, processing, errors } = useForm({
        semaine_nom: "",
        date_debut: null,
        departement_id: null,
        sous_departement_id: null,
        selected_lab_id: null,
        all_designations: {},
    });

    // Auto-génération du nom de la semaine
    useEffect(() => {
        if (data.date_debut && !data.semaine_nom) {
            const date = new Date(data.date_debut);
            const janFirst = new Date(date.getFullYear(), 0, 1);
            const weekNumber = Math.ceil(
                ((date - janFirst) / 8.64e7 + janFirst.getDay() + 1) / 7,
            );
            setData(
                "semaine_nom",
                `Semaine ${weekNumber} - ${date.getFullYear()}`,
            );
        }
    }, [data.date_debut]);

    const filteredSousDepts = sousDepartements.filter(
        (sd) => sd.departement_id === data.departement_id,
    );
    const filteredLabs = laboratoires.filter(
        (l) => l.sous_departement_id === data.sous_departement_id,
    );
    const currentLab = laboratoires.find((l) => l.id === data.selected_lab_id);

    const handleMemberChange = (labId, jour, requisId, membreId) => {
        const newDesignations = { ...data.all_designations };
        if (!newDesignations[labId]) newDesignations[labId] = {};
        if (!newDesignations[labId][jour]) newDesignations[labId][jour] = {};

        newDesignations[labId][jour][requisId] = membreId;
        setData("all_designations", newDesignations);
    };

    const submit = (e) => {
        e.preventDefault();
        post(route("designations.store"));
    };

    return (
        <Layout>
            <Head title="Nouvelle Désignation" />

            <div className="p-4 card shadow-2 border-round-xl">
                <h2 className="pb-3 mb-4 text-xl font-bold border-bottom-1 surface-border text-800">
                    <i className="mr-2 text-blue-500 pi pi-calendar-plus"></i>
                    Nouvelle Planification Hebdomadaire
                </h2>

                {/* 1. SECTION CONTEXTE (Filtres avec style épuré) */}
                <div className="grid p-3 mb-5 bg-bluegray-50 border-round-xl border-1 border-100">
                    <div className="col-12 md:col-6 lg:col-2">
                        <label className="block mb-1 text-xs font-bold text-600">
                            DATE DEBUT
                        </label>
                        <Calendar
                            value={data.date_debut}
                            onChange={(e) => setData("date_debut", e.value)}
                            showIcon
                            className="w-full"
                            locale="fr" // Optionnel si configuré globalement
                            dateFormat="dd/mm/yy" // Pour avoir le format 08/05/2026
                        />
                        {/* <Calendar
                            value={data.date_debut}
                            onChange={(e) => setData("date_debut", e.value)}
                            showIcon
                            className="w-full"
                        /> */}
                    </div>
                    <div className="col-12 md:col-6 lg:col-2">
                        <label className="block mb-1 text-xs font-bold text-600">
                            NOM SEMAINE
                        </label>
                        <InputText
                            value={data.semaine_nom}
                            onChange={(e) =>
                                setData("semaine_nom", e.target.value)
                            }
                            className="w-full"
                        />
                    </div>
                    <div className="col-12 md:col-4 lg:col-2">
                        <label className="block mb-1 text-xs font-bold text-600">
                            DÉPARTEMENT
                        </label>
                        <Dropdown
                            value={data.departement_id}
                            options={departements}
                            optionLabel="nom"
                            optionValue="id"
                            placeholder="Sélectionner..."
                            onChange={(e) =>
                                setData((d) => ({
                                    ...d,
                                    departement_id: e.value,
                                    sous_departement_id: null,
                                    selected_lab_id: null,
                                }))
                            }
                            className="w-full"
                        />
                    </div>
                    <div className="col-12 md:col-4 lg:col-3">
                        <label className="block mb-1 text-xs font-bold text-600">
                            SOUS-DÉPARTEMENT
                        </label>
                        <Dropdown
                            value={data.sous_departement_id}
                            options={filteredSousDepts}
                            optionLabel="nom"
                            optionValue="id"
                            placeholder="Choisir..."
                            disabled={!data.departement_id}
                            onChange={(e) =>
                                setData((d) => ({
                                    ...d,
                                    sous_departement_id: e.value,
                                    selected_lab_id: null,
                                }))
                            }
                            className="w-full"
                        />
                    </div>
                    <div className="col-12 md:col-4 lg:col-3">
                        <label className="block mb-1 text-xs font-bold text-blue-700">
                            LABORATOIRE CIBLE
                        </label>
                        <Dropdown
                            value={data.selected_lab_id}
                            options={filteredLabs}
                            optionLabel="nom"
                            optionValue="id"
                            placeholder="Choisir le labo"
                            className="w-full border-blue-200 shadow-1"
                            disabled={!data.sous_departement_id}
                            onChange={(e) =>
                                setData("selected_lab_id", e.value)
                            }
                        />
                    </div>
                </div>

                {/* 2. GRILLE DES JOURS STYLE IMAGE */}
                {currentLab ? (
                    <div className="fadein animation-duration-400">
                        <div className="flex gap-2 mb-4 align-items-center">
                            <span className="flex p-2 text-white bg-blue-500 border-round-md align-items-center justify-content-center">
                                <i className="text-xl pi pi-building"></i>
                            </span>
                            <h3 className="m-0 tracking-tight uppercase text-700">
                                {currentLab.nom}
                            </h3>
                        </div>

                        <div className="grid">
                            {currentLab.config_jours?.map((conf) => (
                                <div
                                    key={conf.id || conf.jour}
                                    className="p-2 col-12 md:col-6 xl:col-4"
                                >
                                    {/* CONTENEUR CARTE STYLE FLAT */}
                                    <div className="h-full overflow-hidden bg-white border-1 border-200 border-round-xl shadow-1">
                                        {/* HEADER BLEU CIEL */}
                                        <div className="p-3 text-center bg-blue-50 border-bottom-1">
                                            <span className="text-sm font-bold tracking-wider uppercase text-700">
                                                {conf.jour_label ||
                                                    `Jour ${conf.jour}`}
                                            </span>
                                        </div>

                                        {/* CORPS DE CARTE DENSE (3 colonnes) */}
                                        <div className="p-3">
                                            {/* On utilise flex-column pour empiler les lignes proprement */}
                                            <div className="flex gap-3 flex-column">
                                                {conf.requis?.map((req) => (
                                                    <div
                                                        key={req.id}
                                                        className="grid p-0 m-0 align-items-center"
                                                    >
                                                        {/* Libellé à gauche : prend 4 colonnes sur 12 */}
                                                        <div className="py-0 col-3">
                                                            <label
                                                                className="block text-xs font-semibold uppercase truncate text-500"
                                                                style={{
                                                                    textAlign:
                                                                        "left",
                                                                }} // Alignement à gauche pour le titre
                                                                title={
                                                                    req
                                                                        .role_tache
                                                                        ?.libelle ||
                                                                    req.libelle
                                                                }
                                                            >
                                                                {req.role_tache
                                                                    ?.libelle ||
                                                                    req.libelle}{" "}
                                                                :
                                                            </label>
                                                        </div>

                                                        {/* Dropdown à droite : prend 8 colonnes sur 12 */}
                                                        <div className="py-0 col-9">
                                                            <Dropdown
                                                                value={
                                                                    data
                                                                        .all_designations[
                                                                        currentLab
                                                                            .id
                                                                    ]?.[
                                                                        conf
                                                                            .jour
                                                                    ]?.[
                                                                        req.id
                                                                    ] || null
                                                                }
                                                                options={
                                                                    membres
                                                                }
                                                                optionLabel="nom"
                                                                optionValue="id"
                                                                placeholder="..."
                                                                className="w-full text-xs p-inputtext-sm border-round-md"
                                                                style={{
                                                                    height: "34px",
                                                                }}
                                                                filter
                                                                onChange={(e) =>
                                                                    handleMemberChange(
                                                                        currentLab.id,
                                                                        conf.jour,
                                                                        req.id,
                                                                        e.value,
                                                                    )
                                                                }
                                                            />
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                        {/* <div className="p-3">
                                            <div className="grid row-gap-3">
                                                {conf.requis?.map((req) => (
                                                    <div
                                                        key={req.id}
                                                        className="p-1 col-4"
                                                    >
                                                        <label
                                                            className="block mb-1 text-xs font-semibold text-center uppercase truncate text-500"
                                                            title={
                                                                req.role_tache
                                                                    ?.libelle ||
                                                                req.libelle
                                                            }
                                                        >
                                                            {req.role_tache
                                                                ?.libelle ||
                                                                req.libelle}
                                                        </label>

                                                        <Dropdown
                                                            value={
                                                                data
                                                                    .all_designations[
                                                                    currentLab
                                                                        .id
                                                                ]?.[
                                                                    conf.jour
                                                                ]?.[req.id] ||
                                                                null
                                                            }
                                                            options={membres}
                                                            optionLabel="nom"
                                                            optionValue="id"
                                                            placeholder="..."
                                                            className="w-full text-xs p-inputtext-sm border-round-md"
                                                            style={{
                                                                height: "34px",
                                                            }}
                                                            filter
                                                            onChange={(e) =>
                                                                handleMemberChange(
                                                                    currentLab.id,
                                                                    conf.jour,
                                                                    req.id,
                                                                    e.value,
                                                                )
                                                            }
                                                        />
                                                    </div>
                                                ))}
                                            </div>
                                        </div> */}
                                    </div>
                                </div>
                            ))}
                        </div>

                        <div className="flex pt-4 mt-6 justify-content-end border-top-1">
                            <Button
                                label="Enregistrer la planification"
                                icon="pi pi-check"
                                className="px-5 py-3 font-bold p-button-success border-round-xl shadow-3"
                                loading={processing}
                                onClick={submit}
                            />
                        </div>
                    </div>
                ) : (
                    <div className="flex p-8 mt-4 text-center border-2 border-dashed border-200 border-round-xl bg-gray-50 flex-column align-items-center">
                        <i className="mb-3 text-4xl pi pi-info-circle text-200"></i>
                        <p className="text-lg text-500">
                            Sélectionnez un laboratoire pour afficher la grille
                            de planification.
                        </p>
                    </div>
                )}
            </div>
        </Layout>
    );
};

export default CreateDesignation;

// import React, { useEffect } from 'react';
// import Layout from '@/Layouts/layout';
// import { Head, useForm } from '@inertiajs/react';
// import { Dropdown } from 'primereact/dropdown';
// import { Calendar } from 'primereact/calendar';
// import { Button } from 'primereact/button';
// import { InputText } from 'primereact/inputtext';
// import { Message } from 'primereact/message';

// const CreateDesignation = ({ departements = [], sousDepartements = [], laboratoires = [], membres = [] }) => {

//     const { data, setData, post, processing, errors } = useForm({
//         semaine_nom: '',
//         date_debut: null,
//         departement_id: null,
//         sous_departement_id: null,
//         selected_lab_id: null,
//         all_designations: {}
//     });

//     // Auto-génération du nom de la semaine
//     useEffect(() => {
//         if (data.date_debut && !data.semaine_nom) {
//             const date = new Date(data.date_debut);
//             const janFirst = new Date(date.getFullYear(), 0, 1);
//             const weekNumber = Math.ceil((((date - janFirst) / 8.64e7) + janFirst.getDay() + 1) / 7);
//             setData('semaine_nom', `Semaine ${weekNumber} - ${date.getFullYear()}`);
//         }
//     }, [data.date_debut]);

//     const filteredSousDepts = sousDepartements.filter(sd => sd.departement_id === data.departement_id);
//     const filteredLabs = laboratoires.filter(l => l.sous_departement_id === data.sous_departement_id);
//     const currentLab = laboratoires.find(l => l.id === data.selected_lab_id);

//     const handleMemberChange = (labId, jour, requisId, membreId) => {
//         const newDesignations = { ...data.all_designations };
//         if (!newDesignations[labId]) newDesignations[labId] = {};
//         if (!newDesignations[labId][jour]) newDesignations[labId][jour] = {};

//         newDesignations[labId][jour][requisId] = membreId;
//         setData('all_designations', newDesignations);
//     };

//     const submit = (e) => {
//         e.preventDefault();
//         post(route('designations.store'));
//     };

//     return (
//         <Layout>
//             <Head title="Nouvelle Désignation" />

//             <div className="p-4 card shadow-2 border-round-xl">
//                 <h2 className="pb-3 mb-4 text-xl font-bold border-bottom-1 surface-border text-800">
//                     <i className="mr-2 text-blue-500 pi pi-calendar-plus"></i>
//                     Nouvelle Planification Hebdomadaire
//                 </h2>

//                 {/* 1. SECTION CONTEXTE (Filtres avec style épuré) */}
//                 <div className="grid p-3 mb-5 bg-bluegray-50 border-round-xl border-1 border-100">
//                     <div className="col-12 md:col-6 lg:col-2">
//                         <label className="block mb-1 text-xs font-bold text-600">DATE LUNDI</label>
//                         <Calendar value={data.date_debut} onChange={e => setData('date_debut', e.value)} showIcon className="w-full" />
//                     </div>
//                     <div className="col-12 md:col-6 lg:col-2">
//                         <label className="block mb-1 text-xs font-bold text-600">NOM SEMAINE</label>
//                         <InputText value={data.semaine_nom} onChange={e => setData('semaine_nom', e.target.value)} className="w-full" />
//                     </div>
//                     <div className="col-12 md:col-4 lg:col-2">
//                         <label className="block mb-1 text-xs font-bold text-600">DÉPARTEMENT</label>
//                         <Dropdown value={data.departement_id} options={departements} optionLabel="nom" optionValue="id" placeholder="Sélectionner..." onChange={e => setData(d => ({ ...d, departement_id: e.value, sous_departement_id: null, selected_lab_id: null }))} className="w-full" />
//                     </div>
//                     <div className="col-12 md:col-4 lg:col-3">
//                         <label className="block mb-1 text-xs font-bold text-600">SOUS-DÉPARTEMENT</label>
//                         <Dropdown value={data.sous_departement_id} options={filteredSousDepts} optionLabel="nom" optionValue="id" placeholder="Choisir..." disabled={!data.departement_id} onChange={e => setData(d => ({ ...d, sous_departement_id: e.value, selected_lab_id: null }))} className="w-full" />
//                     </div>
//                     <div className="col-12 md:col-4 lg:col-3">
//                         <label className="block mb-1 text-xs font-bold text-blue-700">LABORATOIRE CIBLE</label>
//                         <Dropdown value={data.selected_lab_id} options={filteredLabs} optionLabel="nom" optionValue="id" placeholder="Choisir le labo" className="w-full border-blue-200 shadow-1" disabled={!data.sous_departement_id} onChange={e => setData('selected_lab_id', e.value)} />
//                     </div>
//                 </div>

//                 {/* 2. GRILLE DES JOURS STYLE IMAGE */}
//                 {currentLab ? (
//                     <div className="fadein animation-duration-400">
//                         <div className="flex gap-2 mb-4 align-items-center">
//                             <span className="flex p-2 text-white bg-blue-500 border-round-md align-items-center justify-content-center">
//                                 <i className="text-xl pi pi-building"></i>
//                             </span>
//                             <h3 className="m-0 tracking-tight uppercase text-700">{currentLab.nom}</h3>
//                         </div>

//                         <div className="grid">
//                             {currentLab.config_jours?.map((conf) => (
//                                 <div key={conf.id || conf.jour} className="p-2 col-12 md:col-6 xl:col-4">
//                                     {/* CONTENEUR CARTE STYLE FLAT */}
//                                     <div className="h-full overflow-hidden bg-white border-1 border-200 border-round-xl shadow-1">

//                                         {/* HEADER BLEU CIEL */}
//                                         <div className="p-3 text-center bg-blue-50 border-bottom-1 border-100">
//                                             <span className="text-sm font-bold tracking-wider uppercase text-700">
//                                                 {conf.jour_label || `Jour ${conf.jour}`}
//                                             </span>
//                                         </div>

//                                         {/* CORPS DE CARTE DENSE (3 colonnes) */}
//                                         <div className="p-3">
//                                             <div className="grid row-gap-3">
//                                                 {conf.requis?.map((req) => (
//                                                     <div key={req.id} className="p-1 col-4">
//                                                         <label className="block mb-1 text-xs font-semibold text-center uppercase truncate text-500" title={req.role_tache?.libelle || req.libelle}>
//                                                             {req.role_tache?.libelle || req.libelle}
//                                                         </label>

//                                                         <Dropdown
//                                                             value={data.all_designations[currentLab.id]?.[conf.jour]?.[req.id] || null}
//                                                             options={membres}
//                                                             optionLabel="nom"
//                                                             optionValue="id"
//                                                             placeholder="..."
//                                                             className="w-full text-xs p-inputtext-sm border-round-md"
//                                                             style={{ height: '34px' }}
//                                                             filter
//                                                             onChange={(e) => handleMemberChange(currentLab.id, conf.jour, req.id, e.value)}
//                                                         />
//                                                     </div>
//                                                 ))}
//                                             </div>
//                                         </div>
//                                     </div>
//                                 </div>
//                             ))}
//                         </div>

//                         <div className="flex pt-4 mt-6 justify-content-end border-top-1 border-100">
//                             <Button
//                                 label="Enregistrer la planification"
//                                 icon="pi pi-check"
//                                 className="px-5 py-3 font-bold p-button-success border-round-xl shadow-3"
//                                 loading={processing}
//                                 onClick={submit}
//                             />
//                         </div>
//                     </div>
//                 ) : (
//                     <div className="flex p-8 mt-4 text-center border-2 border-dashed border-200 border-round-xl bg-gray-50 flex-column align-items-center">
//                         <i className="mb-3 text-4xl pi pi-info-circle text-200"></i>
//                         <p className="text-lg text-500">Sélectionnez un laboratoire pour afficher la grille de planification.</p>
//                     </div>
//                 )}
//             </div>
//         </Layout>
//     );
// };

// export default CreateDesignation;
