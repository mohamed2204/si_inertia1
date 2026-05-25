import React, { useEffect, useMemo } from "react";
import Layout from "@/Layouts/layout";
import { Head, useForm } from "@inertiajs/react";
import { Dropdown } from "primereact/dropdown";
import { Calendar } from "primereact/calendar";
import { Button } from "primereact/button";
import { InputText } from "primereact/inputtext";
import { Message } from "primereact/message";
import { addLocale } from "primereact/api";

// Configuration FR pour le calendrier si nécessaire
addLocale("fr", {
    firstDayOfWeek: 1,
    dayNames: [
        "dimanche",
        "lundi",
        "mardi",
        "mercredi",
        "jeudi",
        "vendredi",
        "samedi",
    ],
    dayNamesShort: ["dim", "lun", "mar", "mer", "jeu", "ven", "sam"],
    dayNamesMin: ["D", "L", "M", "M", "J", "V", "S"],
    monthNames: [
        "janvier",
        "février",
        "mars",
        "avril",
        "mai",
        "juin",
        "juillet",
        "août",
        "septembre",
        "octobre",
        "novembre",
        "décembre",
    ],
    monthNamesShort: [
        "jan",
        "fév",
        "mar",
        "avr",
        "mai",
        "jun",
        "jul",
        "aoû",
        "sep",
        "oct",
        "nov",
        "déc",
    ],
    today: "Aujourd'hui",
    clear: "Effacer",
});

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

    // 1. Calcul des listes filtrées (Optimisé avec useMemo)
    const filteredSousDepts = useMemo(
        () =>
            sousDepartements.filter(
                (sd) => sd.departement_id === data.departement_id,
            ),
        [sousDepartements, data.departement_id],
    );

    const filteredLabs = useMemo(
        () =>
            laboratoires.filter(
                (l) => l.sous_departement_id === data.sous_departement_id,
            ),
        [laboratoires, data.sous_departement_id],
    );

    const currentLab = useMemo(
        () => laboratoires.find((l) => l.id === data.selected_lab_id),
        [laboratoires, data.selected_lab_id],
    );

    // 2. Auto-génération intelligente du nom de la semaine
    useEffect(() => {
        if (data.date_debut) {
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

    const handleMemberChange = (labId, jour, requisId, membreId) => {
        setData("all_designations", {
            ...data.all_designations,
            [labId]: {
                ...data.all_designations[labId],
                [jour]: {
                    ...(data.all_designations[labId]?.[jour] || {}),
                    [requisId]: membreId,
                },
            },
        });
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

                {/* SECTION CONTEXTE */}
                <div className="grid p-3 mb-5 bg-bluegray-50 border-round-xl border-1 border-100">
                    <div className="col-12 md:col-6 lg:col-2">
                        <label className="block mb-1 text-xs font-bold text-600">
                            DATE LUNDI
                        </label>
                        <Calendar
                            value={data.date_debut}
                            onChange={(e) => setData("date_debut", e.value)}
                            showIcon
                            className={`w-full ${errors.date_debut ? "p-invalid" : ""}`}
                            locale="fr"
                            dateFormat="dd/mm/yy"
                        />
                        {errors.date_debut && (
                            <small className="p-error">
                                {errors.date_debut}
                            </small>
                        )}
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
                            filter
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
                            filter
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
                            filter
                        />
                    </div>
                </div>

                {/* GRILLE DES JOURS */}
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
                                    <div className="h-full overflow-hidden bg-white border-1 border-200 border-round-xl shadow-1">
                                        <div className="p-3 text-center bg-blue-50 border-bottom-1 border-100">
                                            <span className="text-sm font-bold tracking-wider uppercase text-700">
                                                {conf.jour_label ||
                                                    `Jour ${conf.jour}`}
                                            </span>
                                        </div>

                                        <div className="p-3">
                                            <div className="flex gap-3 flex-column">
                                                {conf.requis?.map((req) => (
                                                    <div
                                                        key={req.id}
                                                        className="grid p-0 m-0 align-items-center"
                                                    >
                                                        <div className="py-0 col-4">
                                                            <label
                                                                className="block text-xs font-semibold uppercase truncate text-500"
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

                                                        <div className="py-0 col-8">
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
                                    </div>
                                </div>
                            ))}
                        </div>

                        <div className="flex pt-4 mt-6 justify-content-end border-top-1 border-100">
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
