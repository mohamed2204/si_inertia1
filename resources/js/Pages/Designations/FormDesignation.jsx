import React, { useEffect, useState } from "react";
import Layout from "@/Layouts/layout";
import { Head, useForm } from "@inertiajs/react";
import { Dropdown } from "primereact/dropdown";
import { Calendar } from "primereact/calendar";
import { Button } from "primereact/button";
import { InputText } from "primereact/inputtext";
import axios from "axios";
import Swal from "sweetalert2";
import { AutoComplete } from "primereact/autocomplete";

// 1. AJOUT de la prop 'designation' envoyée lors de l'édition
const FormDesignation = ({ departements = [], designation = null }) => {
    // Mode détective : si un objet designation existe, on est en mode édition
    const isEditMode = !!designation;

    const [sousDepts, setSousDepts] = useState([]);
    const [labs, setLabs] = useState([]);
    const [currentLabConfig, setCurrentLabConfig] = useState(null);
    const [membres, setMembres] = useState([]);
    const [loading, setLoading] = useState(false);

    // 2. Initialisation dynamique de useForm
    const { data, setData, post, put, processing, errors } = useForm({
        semaine_nom: designation?.semaine_nom || "",
        // Si édition, on transforme la chaîne SQL en objet Date valide pour PrimeReact
        date_debut: designation?.date_debut
            ? new Date(designation.date_debut)
            : null,
        departement_id: designation?.sous_departement?.departement_id || null,
        sous_departement_id: designation?.sous_departement_id || null,
        selected_lab_id: designation?.items?.[0]?.laboratoire_id || null, // On pré-sélectionne le premier labo trouvé
        all_designations: designation?.formatted_items || {}, // Les items structurés {labId: {jour: {reqId: membre}}}
    });

    // 3. Hydratation automatique des cascades en mode Édition au chargement initial
    useEffect(() => {
        if (isEditMode && designation) {
            // Charger les sous-départements du département existant
            const deptId = designation?.sous_departement?.departement_id;
            if (deptId) {
                axios
                    .get(`/api/departments/${deptId}/sous-departments`)
                    .then((res) => setSousDepts(res.data))
                    .catch((err) => console.error(err));
            }
            // Charger les labos du sous-département existant
            if (designation.sous_departement_id) {
                axios
                    .get(
                        `/api/sous-departements/${designation.sous_departement_id}/labs`,
                    )
                    .then((res) => setLabs(res.data))
                    .catch((err) => console.error(err));
            }
        }
    }, [designation]);

    // Charger les Sous-Départements au changement manuel
    useEffect(() => {
        if (data.departement_id && !isEditMode) {
            axios
                .get(`/api/departments/${data.departement_id}/sous-departments`)
                .then((res) => setSousDepts(res.data))
                .catch((err) => console.error("Erreur sous-depts", err));
        }
    }, [data.departement_id]);

    // Charger les Laboratoires au changement manuel
    useEffect(() => {
        if (data.sous_departement_id && !isEditMode) {
            axios
                .get(`/api/sous-departements/${data.sous_departement_id}/labs`)
                .then((res) => setLabs(res.data))
                .catch((err) => console.error("Erreur labs", err));
        }
    }, [data.sous_departement_id]);

    // Auto-génération du nom de la semaine (uniquement en mode création)
    useEffect(() => {
        if (data.date_debut && !data.semaine_nom && !isEditMode) {
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

    // Charger la config du Labo sélectionné
    useEffect(() => {
        if (data.selected_lab_id) {
            setLoading(true);
            axios
                .get(`/api/labs/${data.selected_lab_id}/config`)
                .then((res) => {
                    setCurrentLabConfig(res.data);
                    setLoading(false);
                })
                .catch((err) => {
                    console.error("Erreur config labo", err);
                    setLoading(false);
                });
        } else {
            setCurrentLabConfig(null);
        }
    }, [data.selected_lab_id]);

    const searchMembres = (event) => {
        if (!data.selected_lab_id) return;
        axios
            .get(`/api/labs/${data.selected_lab_id}/membres`, {
                params: { query: event.query },
            })
            .then((res) => {
                const dataReceived = Array.isArray(res.data)
                    ? res.data
                    : res.data.data || [];
                setMembres(dataReceived);
            })
            .catch((err) => {
                console.error("Erreur recherche membres", err);
                setMembres([]);
            });
    };

    const handleMemberChange = (labId, jour, requisId, membreId) => {
        const newDesignations = { ...data.all_designations };
        if (!newDesignations[labId]) newDesignations[labId] = {};
        if (!newDesignations[labId][jour]) newDesignations[labId][jour] = {};

        newDesignations[labId][jour][requisId] = membreId;
        setData("all_designations", newDesignations);
    };

    // 4. Soumission dynamique (POST ou PUT)
    const submit = (e) => {
        e.preventDefault();
        const payload = { ...data };
        const userTimeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;

        const options = {
            ...payload,
            browser_timezone: userTimeZone,
        };

        const config = {
            onError: (errs) => {
                const firstError = Object.values(errs)[0];
                Swal.fire({
                    icon: "error",
                    title: "Erreur",
                    text: firstError || "Vérifiez le formulaire",
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                });
            },
            onSuccess: () => {
                Swal.fire({
                    icon: "success",
                    title: isEditMode
                        ? "Planification mise à jour !"
                        : "Enregistré !",
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                });
            },
        };

        // Redirection conditionnelle de l'action Inertia
        if (isEditMode) {
            // Utilise la route de mise à jour pour l'édition
            put(
                route("designations.api.update", designation.id),
                options,
                config,
            );
        } else {
            // Utilise la route de stockage par défaut
            post(route("designations.api.store"), options, config);
        }
    };

    return (
        <Layout>
            {/* Titre de l'onglet dynamique */}
            <Head
                title={
                    isEditMode
                        ? "Modifier la Désignation"
                        : "Nouvelle Désignation"
                }
            />

            <div className="p-4 card shadow-2 border-round-xl surface-card border-1 surface-border">
                {/* En-tête dynamique */}
                <h2 className="pb-3 mb-4 text-xl font-bold border-bottom-1 surface-border text-color">
                    <i
                        className={`mr-2 ${isEditMode ? "text-orange-500 pi pi-pencil" : "text-blue-500 pi pi-calendar-plus"}`}
                    ></i>
                    {isEditMode
                        ? `Modifier la Planification : ${designation.semaine_nom}`
                        : "Nouvelle Planification Hebdomadaire"}
                </h2>

                {/* Barre de filtres / Métadonnées de la planification */}
                <div className="grid p-3 mb-5 surface-section border-round-xl border-1 surface-border">
                    <div className="col-12 md:col-6 lg:col-2">
                        <label className="block mb-1 text-xs font-bold text-color-secondary">
                            DATE DEBUT
                        </label>
                        <Calendar
                            value={data.date_debut}
                            onChange={(e) => setData("date_debut", e.value)}
                            showIcon
                            dateFormat="dd/mm/yy"
                            className={`w-full ${errors.date_debut ? "p-invalid" : ""}`}
                            disabled={isEditMode}
                        />
                        {errors.date_debut && (
                            <small className="block mt-1 p-error text-xs font-semibold">
                                {errors.date_debut}
                            </small>
                        )}
                    </div>
                    <div className="col-12 md:col-6 lg:col-2">
                        <label className="block mb-1 text-xs font-bold text-color-secondary">
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
                        <label className="block mb-1 text-xs font-bold text-color-secondary">
                            DÉPARTEMENT
                        </label>
                        <Dropdown
                            value={data.departement_id}
                            options={departements}
                            optionLabel="nom"
                            optionValue="id"
                            placeholder="Sélectionner..."
                            disabled={isEditMode}
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
                        <label className="block mb-1 text-xs font-bold text-color-secondary">
                            SOUS-DÉPARTEMENT
                        </label>
                        <Dropdown
                            value={data.sous_departement_id}
                            options={sousDepts}
                            optionLabel="nom"
                            optionValue="id"
                            placeholder="Choisir..."
                            disabled={isEditMode || !data.departement_id}
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
                        <label className="block mb-1 text-xs font-bold text-primary">
                            LABORATOIRE CIBLE
                        </label>
                        <Dropdown
                            value={data.selected_lab_id}
                            options={labs}
                            optionLabel="nom"
                            optionValue="id"
                            placeholder="Choisir le labo"
                            className="w-full shadow-1"
                            disabled={!data.sous_departement_id}
                            onChange={(e) =>
                                setData("selected_lab_id", e.value)
                            }
                        />
                    </div>
                </div>

                {/* GRILLE DYNAMIQUE */}
                {loading ? (
                    <div className="p-8 text-center">
                        <i className="pi pi-spin pi-spinner text-4xl text-primary"></i>
                        <p className="mt-2 text-color-secondary">
                            Chargement de la grille...
                        </p>
                    </div>
                ) : currentLabConfig ? (
                    <div className="fadein animation-duration-400">
                        <div className="flex gap-2 mb-4 align-items-center">
                            <span className="flex p-2 text-white bg-primary border-round-md align-items-center justify-content-center">
                                <i className="text-xl pi pi-building"></i>
                            </span>
                            <h3 className="m-0 font-semibold tracking-tight uppercase text-color">
                                {currentLabConfig.nom}
                            </h3>
                        </div>

                        <div className="grid">
                            {currentLabConfig.config_jours?.map((conf) => (
                                <div
                                    key={conf.id || conf.jour}
                                    className="p-2 col-12 md:col-6 xl:col-4"
                                >
                                    <div className="h-full overflow-hidden border-1 surface-card surface-border border-round-xl shadow-1">
                                        <div
                                            className="p-3 text-center"
                                            style={{
                                                backgroundColor:
                                                    conf.type_config === "fixe"
                                                        ? "rgba(149, 156, 224, 0.35)"
                                                        : "rgba(255, 255, 255, 0.06)",
                                                color: "var(--text-color)",
                                                borderBottom:
                                                    "1px solid var(--surface-border)",
                                            }}
                                        >
                                            <span className="text-sm font-bold tracking-wider uppercase">
                                                {conf.jour_label ||
                                                    `Jour ${conf.jour}`}
                                            </span>
                                        </div>

                                        <div
                                            className="p-3"
                                            style={{
                                                backgroundColor:
                                                    conf.type_config === "fixe"
                                                        ? "rgba(157, 190, 238, 0.1)"
                                                        : "transparent",
                                            }}
                                        >
                                            <div className="flex gap-3 flex-column">
                                                {conf.requis?.map((req) => (
                                                    <div
                                                        key={req.id}
                                                        className="grid p-0 m-0 align-items-center"
                                                    >
                                                        <div className="py-0 col-3">
                                                            <label
                                                                className="block text-xs font-semibold uppercase truncate text-color-secondary text-left"
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
                                                        <div className="py-0 col-9">
                                                            <Dropdown
                                                                value={
                                                                    data
                                                                        .all_designations[
                                                                        currentLabConfig
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
                                                                        currentLabConfig.id,
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

                        <div className="flex pt-4 mt-6 justify-content-end border-top-1 surface-border">
                            <Button
                                label={
                                    isEditMode
                                        ? "Mettre à jour la planification"
                                        : "Enregistrer la planification"
                                }
                                icon="pi pi-check"
                                className={`px-5 py-3 font-bold border-round-xl shadow-3 ${isEditMode ? "p-button-warning" : "p-button-success"}`}
                                loading={processing}
                                onClick={submit}
                            />
                        </div>
                    </div>
                ) : (
                    <div className="flex p-8 mt-4 text-center border-2 border-dashed surface-border border-round-xl surface-section flex-column align-items-center">
                        <i className="mb-3 text-4xl pi pi-info-circle text-color-secondary opacity-60"></i>
                        <p className="text-lg text-color-secondary">
                            Sélectionnez un laboratoire pour afficher la grille.
                        </p>
                    </div>
                )}
            </div>
        </Layout>
        // <Layout>
        //     {/* Titre de l'onglet dynamique */}
        //     <Head title={isEditMode ? "Modifier la Désignation" : "Nouvelle Désignation"} />

        //     <div className="p-4 card shadow-2 border-round-xl">
        //         {/* En-tête dynamique */}
        //         <h2 className="pb-3 mb-4 text-xl font-bold border-bottom-1 surface-border text-800">
        //             <i className={`mr-2 ${isEditMode ? 'text-orange-500 pi pi-pencil' : 'text-blue-500 pi pi-calendar-plus'}`}></i>
        //             {isEditMode ? `Modifier la Planification : ${designation.semaine_nom}` : "Nouvelle Planification Hebdomadaire"}
        //         </h2>

        //         <div className="grid p-3 mb-5 bg-bluegray-50 border-round-xl border-1 border-100">
        //             <div className="col-12 md:col-6 lg:col-2">
        //                 <label className="block mb-1 text-xs font-bold text-600">DATE DEBUT</label>
        //                 <Calendar
        //                     value={data.date_debut}
        //                     onChange={(e) => setData("date_debut", e.value)}
        //                     showIcon
        //                     dateFormat="dd/mm/yy"
        //                     className={`w-full ${errors.date_debut ? 'p-invalid' : ''}`}
        //                     disabled={isEditMode} // Optionnel : Bloquer la modification de la date racine si index unique sensible
        //                 />
        //                 {errors.date_debut && (
        //                     <small className="block mt-1 p-error text-xs font-semibold">{errors.date_debut}</small>
        //                 )}
        //             </div>
        //             <div className="col-12 md:col-6 lg:col-2">
        //                 <label className="block mb-1 text-xs font-bold text-600">NOM SEMAINE</label>
        //                 <InputText value={data.semaine_nom} onChange={(e) => setData("semaine_nom", e.target.value)} className="w-full" />
        //             </div>

        //             <div className="col-12 md:col-4 lg:col-2">
        //                 <label className="block mb-1 text-xs font-bold text-600">DÉPARTEMENT</label>
        //                 <Dropdown
        //                     value={data.departement_id}
        //                     options={departements}
        //                     optionLabel="nom"
        //                     optionValue="id"
        //                     placeholder="Sélectionner..."
        //                     disabled={isEditMode} // Désactivé en édition pour préserver la cohérence des liaisons
        //                     onChange={(e) => setData(d => ({ ...d, departement_id: e.value, sous_departement_id: null, selected_lab_id: null }))}
        //                     className="w-full"
        //                 />
        //             </div>

        //             <div className="col-12 md:col-4 lg:col-3">
        //                 <label className="block mb-1 text-xs font-bold text-600">SOUS-DÉPARTEMENT</label>
        //                 <Dropdown
        //                     value={data.sous_departement_id}
        //                     options={sousDepts}
        //                     optionLabel="nom"
        //                     optionValue="id"
        //                     placeholder="Choisir..."
        //                     disabled={isEditMode || !data.departement_id}
        //                     onChange={(e) => setData(d => ({ ...d, sous_departement_id: e.value, selected_lab_id: null }))}
        //                     className="w-full"
        //                 />
        //             </div>

        //             <div className="col-12 md:col-4 lg:col-3">
        //                 <label className="block mb-1 text-xs font-bold text-blue-700">LABORATOIRE CIBLE</label>
        //                 <Dropdown
        //                     value={data.selected_lab_id}
        //                     options={labs}
        //                     optionLabel="nom"
        //                     optionValue="id"
        //                     placeholder="Choisir le labo"
        //                     className="w-full border-blue-200 shadow-1"
        //                     disabled={!data.sous_departement_id}
        //                     onChange={(e) => setData("selected_lab_id", e.value)}
        //                 />
        //             </div>
        //         </div>

        //         {loading ? (
        //             <div className="p-8 text-center"><i className="pi pi-spin pi-spinner text-4xl text-blue-500"></i><p>Chargement de la grille...</p></div>
        //         ) : currentLabConfig ? (
        //             <div className="fadein animation-duration-400">
        //                 <div className="flex gap-2 mb-4 align-items-center">
        //                     <span className="flex p-2 text-white bg-blue-500 border-round-md align-items-center justify-content-center">
        //                         <i className="text-xl pi pi-building"></i>
        //                     </span>
        //                     <h3 className="m-0 tracking-tight uppercase text-700">{currentLabConfig.nom}</h3>
        //                 </div>

        //                 <div className="grid">
        //                     {currentLabConfig.config_jours?.map((conf) => (
        //                         <div key={conf.id} className="p-2 col-12 md:col-6 xl:col-4">
        //                             <div className="h-full overflow-hidden bg-white border-1 border-200 border-round-xl shadow-1">
        //                                 <div className="p-3 text-center" style={{ backgroundColor: conf.type_config === 'fixe' ? "#959ce0d5" : "#d1d5dbd5" }}>
        //                                     <span className="text-sm font-bold tracking-wider uppercase text-800">{conf.jour_label}</span>
        //                                 </div>

        //                                 <div className="p-3" style={{ backgroundColor: conf.type_config === 'fixe' ? "#9dbeeed5" : "transparent" }}>
        //                                     <div className="flex gap-3 flex-column">
        //                                         {conf.requis?.map((req) => (
        //                                             <div key={req.id} className="grid p-0 m-0 align-items-center">
        //                                                 <div className="py-0 col-3">
        //                                                     <label className="block text-xs font-semibold uppercase truncate text-500">{req.role_tache?.libelle || req.libelle} :</label>
        //                                                 </div>
        //                                                 <div className="py-0 col-9">
        //                                                     <AutoComplete
        //                                                         value={
        //                                                             (Array.isArray(membres) ? membres : []).find(
        //                                                                 m => m.id === data.all_designations[currentLabConfig.id]?.[conf.jour]?.[req.id]
        //                                                             ) || data.all_designations[currentLabConfig.id]?.[conf.jour]?.[req.id] || ''
        //                                                         }
        //                                                         suggestions={Array.isArray(membres) ? membres : []}
        //                                                         completeMethod={searchMembres}
        //                                                         field="nom"
        //                                                         dropdown
        //                                                         placeholder="Tapez un nom..."
        //                                                         className="w-full text-xs"
        //                                                         inputClassName="w-full p-inputtext-sm border-round-md"
        //                                                         style={{ height: "34px" }}
        //                                                         onChange={(e) => {
        //                                                             const selectedId = e.value?.id ? e.value.id : e.value;
        //                                                             handleMemberChange(currentLabConfig.id, conf.jour, req.id, selectedId);
        //                                                         }}
        //                                                     />
        //                                                 </div>
        //                                             </div>
        //                                         ))}
        //                                     </div>
        //                                 </div>
        //                             </div>
        //                         </div>
        //                     ))}
        //                 </div>

        //                 <div className="flex pt-4 mt-6 justify-content-end border-top-1">
        //                     {/* Libellé du bouton dynamique */}
        //                     <Button
        //                         label={isEditMode ? "Mettre à jour la planification" : "Enregistrer la planification"}
        //                         icon="pi pi-check"
        //                         className={`px-5 py-3 font-bold border-round-xl shadow-3 ${isEditMode ? 'p-button-warning' : 'p-button-success'}`}
        //                         loading={processing}
        //                         onClick={submit}
        //                     />
        //                 </div>
        //             </div>
        //         ) : (
        //             <div className="flex p-8 mt-4 text-center border-2 border-dashed border-200 border-round-xl bg-gray-50 flex-column align-items-center">
        //                 <i className="mb-3 text-4xl pi pi-info-circle text-200"></i>
        //                 <p className="text-lg text-500">Sélectionnez un laboratoire pour afficher la grille.</p>
        //             </div>
        //         )}
        //     </div>
        // </Layout>
    );
};

export default FormDesignation;
