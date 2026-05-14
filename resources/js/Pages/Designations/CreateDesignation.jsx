import React, { useEffect, useState } from "react"; // Ajout de useState
import Layout from "@/Layouts/layout";
import { Head, useForm } from "@inertiajs/react";
import { Dropdown } from "primereact/dropdown";
import { Calendar } from "primereact/calendar";
import { Button } from "primereact/button";
import { InputText } from "primereact/inputtext";
import axios from "axios"; // Import axios
import Swal from 'sweetalert2';

const CreateDesignation = ({ departements = [], membres = [] }) => {
    // États pour les données chargées dynamiquement via Axios
    const [sousDepts, setSousDepts] = useState([]);
    const [labs, setLabs] = useState([]);
    const [currentLabConfig, setCurrentLabConfig] = useState(null);
    const [loading, setLoading] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        semaine_nom: "",
        date_debut: null,
        departement_id: null,
        sous_departement_id: null,
        selected_lab_id: null,
        all_designations: {},
    });

    // 1. Charger les Sous-Départements quand le Département change
    useEffect(() => {
        if (data.departement_id) {
            axios.get(`/api/departments/${data.departement_id}/sous-departments`)
                .then(res => setSousDepts(res.data))
                .catch(err => console.error("Erreur sous-depts", err));
        } else {
            setSousDepts([]);
        }
    }, [data.departement_id]);

    // 2. Charger les Laboratoires quand le Sous-Département change
    useEffect(() => {
        if (data.sous_departement_id) {
            axios.get(`/api/sous-departements/${data.sous_departement_id}/labs`)
                .then(res => setLabs(res.data))
                .catch(err => console.error("Erreur labs", err));
        } else {
            setLabs([]);
        }
    }, [data.sous_departement_id]);

    // 3. Charger la configuration complète du Labo (jours + requis) quand le Labo est sélectionné
    useEffect(() => {
        if (data.selected_lab_id) {
            setLoading(true);
            axios.get(`/api/labs/${data.selected_lab_id}/config`)
                .then(res => {
                    setCurrentLabConfig(res.data);
                    setLoading(false);
                })
                .catch(err => {
                    console.error("Erreur config labo", err);
                    setLoading(false);
                });
        } else {
            setCurrentLabConfig(null);
        }
    }, [data.selected_lab_id]);

    // Auto-génération du nom de la semaine
    useEffect(() => {
        if (data.date_debut && !data.semaine_nom) {
            const date = new Date(data.date_debut);
            const janFirst = new Date(date.getFullYear(), 0, 1);
            const weekNumber = Math.ceil(
                ((date - janFirst) / 8.64e7 + janFirst.getDay() + 1) / 7,
            );
            setData("semaine_nom", `Semaine ${weekNumber} - ${date.getFullYear()}`);
        }
    }, [data.date_debut]);

    const handleMemberChange = (labId, jour, requisId, membreId) => {
        const newDesignations = { ...data.all_designations };
        if (!newDesignations[labId]) newDesignations[labId] = {};
        if (!newDesignations[labId][jour]) newDesignations[labId][jour] = {};

        newDesignations[labId][jour][requisId] = membreId;
        setData("all_designations", newDesignations);
    };

    const submit = (e) => {
        e.preventDefault();
        post(route("designations.store"), {
            onError: (errs) => {
                const firstError = Object.values(errs)[0];
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: firstError || "Vérifiez le formulaire",
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
            },
            onSuccess: () => {
                Swal.fire({ icon: 'success', title: 'Enregistré !', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            }
        });
    };

    return (
        <Layout>
            <Head title="Nouvelle Désignation" />

            <div className="p-4 card shadow-2 border-round-xl">
                <h2 className="pb-3 mb-4 text-xl font-bold border-bottom-1 surface-border text-800">
                    <i className="mr-2 text-blue-500 pi pi-calendar-plus"></i>
                    Nouvelle Planification Hebdomadaire
                </h2>

                <div className="grid p-3 mb-5 bg-bluegray-50 border-round-xl border-1 border-100">
                    {/* Date et Nom Semaine */}
                    <div className="col-12 md:col-6 lg:col-2">
                        <label className="block mb-1 text-xs font-bold text-600">DATE DEBUT</label>
                        <Calendar value={data.date_debut} onChange={(e) => setData("date_debut", e.value)} showIcon className="w-full" dateFormat="dd/mm/yy" />
                    </div>
                    <div className="col-12 md:col-6 lg:col-2">
                        <label className="block mb-1 text-xs font-bold text-600">NOM SEMAINE</label>
                        <InputText value={data.semaine_nom} onChange={(e) => setData("semaine_nom", e.target.value)} className="w-full" />
                    </div>

                    {/* SELECTS DÉPENDANTS AXIOS */}
                    <div className="col-12 md:col-4 lg:col-2">
                        <label className="block mb-1 text-xs font-bold text-600">DÉPARTEMENT</label>
                        <Dropdown
                            value={data.departement_id}
                            options={departements}
                            optionLabel="nom"
                            optionValue="id"
                            placeholder="Sélectionner..."
                            onChange={(e) => setData(d => ({ ...d, departement_id: e.value, sous_departement_id: null, selected_lab_id: null }))}
                            className="w-full"
                        />
                    </div>

                    <div className="col-12 md:col-4 lg:col-3">
                        <label className="block mb-1 text-xs font-bold text-600">SOUS-DÉPARTEMENT</label>
                        <Dropdown
                            value={data.sous_departement_id}
                            options={sousDepts} // Utilise l'état local axios
                            optionLabel="nom"
                            optionValue="id"
                            placeholder="Choisir..."
                            disabled={!data.departement_id}
                            onChange={(e) => setData(d => ({ ...d, sous_departement_id: e.value, selected_lab_id: null }))}
                            className="w-full"
                        />
                    </div>

                    <div className="col-12 md:col-4 lg:col-3">
                        <label className="block mb-1 text-xs font-bold text-blue-700">LABORATOIRE CIBLE</label>
                        <Dropdown
                            value={data.selected_lab_id}
                            options={labs} // Utilise l'état local axios
                            optionLabel="nom"
                            optionValue="id"
                            placeholder="Choisir le labo"
                            className="w-full border-blue-200 shadow-1"
                            disabled={!data.sous_departement_id}
                            onChange={(e) => setData("selected_lab_id", e.value)}
                        />
                    </div>
                </div>

                {/* GRILLE DYNAMIQUE */}
                {loading ? (
                    <div className="p-8 text-center"><i className="pi pi-spin pi-spinner text-4xl text-blue-500"></i><p>Chargement de la grille...</p></div>
                ) : currentLabConfig ? (
                    <div className="fadein animation-duration-400">
                        <div className="flex gap-2 mb-4 align-items-center">
                            <span className="flex p-2 text-white bg-blue-500 border-round-md align-items-center justify-content-center">
                                <i className="text-xl pi pi-building"></i>
                            </span>
                            <h3 className="m-0 tracking-tight uppercase text-700">{currentLabConfig.nom}</h3>
                        </div>

                        <div className="grid">
                            {currentLabConfig.config_jours?.map((conf) => (
                                <div key={conf.id} className="p-2 col-12 md:col-6 xl:col-4">
                                    <div className="h-full overflow-hidden bg-white border-1 border-200 border-round-xl shadow-1">
                                        <div className="p-3 text-center" style={{ backgroundColor: conf.type_config === 'fixe' ? "#959ce0d5" : "#d1d5dbd5" }}>
                                            <span className="text-sm font-bold tracking-wider uppercase text-800">{conf.jour_label}</span>
                                        </div>

                                        <div className="p-3" style={{ backgroundColor: conf.type_config === 'fixe' ? "#9dbeeed5" : "transparent" }}>
                                            <div className="flex gap-3 flex-column">
                                                {conf.requis?.map((req) => (
                                                    <div key={req.id} className="grid p-0 m-0 align-items-center">
                                                        <div className="py-0 col-3">
                                                            <label className="block text-xs font-semibold uppercase truncate text-500">{req.role_tache?.libelle || req.libelle} :</label>
                                                        </div>
                                                        <div className="py-0 col-9">
                                                            <Dropdown
                                                                value={data.all_designations[currentLabConfig.id]?.[conf.jour]?.[req.id] || null}
                                                                options={membres}
                                                                optionLabel="nom"
                                                                optionValue="id"
                                                                placeholder="..."
                                                                className="w-full text-xs p-inputtext-sm border-round-md"
                                                                style={{ height: "34px" }}
                                                                filter
                                                                onChange={(e) => handleMemberChange(currentLabConfig.id, conf.jour, req.id, e.value)}
                                                            />
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>

                        <div className="flex pt-4 mt-6 justify-content-end border-top-1">
                            <Button label="Enregistrer la planification" icon="pi pi-check" className="px-5 py-3 font-bold p-button-success border-round-xl shadow-3" loading={processing} onClick={submit} />
                        </div>
                    </div>
                ) : (
                    <div className="flex p-8 mt-4 text-center border-2 border-dashed border-200 border-round-xl bg-gray-50 flex-column align-items-center">
                        <i className="mb-3 text-4xl pi pi-info-circle text-200"></i>
                        <p className="text-lg text-500">Sélectionnez un laboratoire pour afficher la grille.</p>
                    </div>
                )}
            </div>
        </Layout>
    );
};

export default CreateDesignation;