import React, { useState, useEffect, useRef } from "react";
import { useForm, usePage } from "@inertiajs/react";
import { Dropdown } from "primereact/dropdown";
import { Button } from "primereact/button";
import { Card } from "primereact/card";
import { Message } from "primereact/message";
import RequisRepeater from "./RequisRepeater";
import Layout from "@/Layouts/layout"; // Ajustez selon votre projet
import { route } from "ziggy-js";

const RequisConfig = ({ structure, allRequisOptions, sectionTypes }) => {
    // structure : Departement -> sous_departements -> laboratoires

    console.log("structure", structure);

    // États pour les sélections
    const [selectedDept, setSelectedDept] = useState(null);
    const [selectedSousDept, setSelectedSousDept] = useState(null);

    // Listes filtrées pour les dropdowns
    const [availableSousDepts, setAvailableSousDepts] = useState([]);
    const [availableLabs, setAvailableLabs] = useState([]);

    const { data, setData, post, processing, recentlySuccessful } = useForm({
        laboratoire_id: null,
        requis_list: [],
    });


    const handleLabChange = (labId) => {
        // 1. Mise à jour de l'ID dans le formulaire
        setData("laboratoire_id", labId);

        if (labId) {
            // 2. Chargement des données uniquement sur sélection explicite
            const lab = availableLabs.find((l) => l.id === labId);
            if (lab && lab.lab_requis) {
                setData("requis_list", lab.lab_requis.map((r) => ({
                    id: `db-${r.id}`, // String pour dnd-kit
                    role_tache_id: r.role_tache_id,
                    nombre_requis: r.nombre_requis,
                    section: r.section,
                })));
            } else {
                setData("requis_list", []);
            }
        } else {
            setData("requis_list", []);
        }
    };

    // Cascade 1 : Quand le Département change
    useEffect(() => {
        if (selectedDept) {
            const dept = structure.find((d) => d.id === selectedDept);
            setAvailableSousDepts(dept ? dept.sous_departements : []);
            setSelectedSousDept(null);
            setAvailableLabs([]);
            setData("laboratoire_id", null);
        }
    }, [selectedDept, structure]);

    // Cascade 2 : Quand le Sous-Département change
    // useEffect(() => {
    //     if (selectedSousDept) {
    //         const sdep = availableSousDepts.find(
    //             (s) => s.id === selectedSousDept,
    //         );
    //         setAvailableLabs(sdep ? sdep.laboratoires : []);
    //         setData("laboratoire_id", null);
    //     }
    // }, [selectedSousDept, availableSousDepts]);

    // Cascade pour le Sous-Département
    useEffect(() => {
        if (selectedSousDept) {
            const sdep = availableSousDepts.find((s) => s.id === selectedSousDept);
            setAvailableLabs(sdep ? sdep.laboratoires : []);

            // On reset l'ID du labo, mais on ne touche pas à la requis_list ici
            // pour éviter les flashs visuels ou les resets brutaux
            setData("laboratoire_id", null);
        }
    }, [selectedSousDept]); // Retirez availableSousDepts des dépendances s'il est stable

    // Chargement des données du laboratoire sélectionné
    // useEffect(() => {
    //     if (data.laboratoire_id) {
    //         const lab = availableLabs.find((l) => l.id === data.laboratoire_id);
    //         if (lab && lab.lab_requis) {
    //             setData(
    //                 "requis_list",
    //                 lab.lab_requis.map((r) => ({
    //                     id: `db-${r.id}`,
    //                     role_tache_id: r.role_tache_id,
    //                     nombre_requis: r.nombre_requis,
    //                     section: r.section,
    //                 })),
    //             );
    //         }
    //     }
    // }, [data.laboratoire_id]);

    // 1. Ajoutez une référence pour mémoriser le dernier laboratoire chargé

    // La référence pour bloquer les rechargements intempestifs
    const lastLoadedLabId = useRef(null);

    useEffect(() => {
        console.log("useEffect : data.laboratoire_id", data.laboratoire_id);
        console.log("useEffect : lastLoadedLabId.current", lastLoadedLabId.current);
        console.log("useEffect : availableLabs", availableLabs);
        // 2. On ne charge les données que si l'ID a réellement changé par rapport au dernier chargement
        if (data.laboratoire_id && data.laboratoire_id !== lastLoadedLabId.current) {
            const lab = availableLabs.find((l) => l.id === data.laboratoire_id);

            if (lab && lab.lab_requis) {
                setData("requis_list", lab.lab_requis.map((r) => ({
                    id: `db-${r.id}`,
                    role_tache_id: r.role_tache_id,
                    nombre_requis: r.nombre_requis,
                    section: r.section,
                })));

                // 3. On met à jour la référence pour bloquer le prochain cycle inutile
                lastLoadedLabId.current = data.laboratoire_id;
            } else {
                // Si le labo n'a pas de requis, on vide la liste une seule fois
                setData("requis_list", []);
                lastLoadedLabId.current = data.laboratoire_id;
            }
        }
    }, [data.laboratoire_id, availableLabs]);

    // const RequisConfig = ({ availableLabs, allRequisOptions, sectionTypes }) => {
    //     // Initialisation du formulaire
    //     const { data, setData, post, processing, recentlySuccessful } = useForm({
    //         laboratoire_id: null,
    //         requis_list: []
    //     });


    // useEffect(() => {
    //     // Chargement initial des données seulement si le labo change
    //     if (data.laboratoire_id && data.laboratoire_id !== lastLoadedLabId.current) {
    //         const lab = availableLabs.find((l) => l.id === data.laboratoire_id);

    //         if (lab && lab.lab_requis) {
    //             setData("requis_list", lab.lab_requis.map((r) => ({
    //                 id: `db-${r.id}`, // String unique pour le repeater
    //                 role_tache_id: r.role_tache_id,
    //                 nombre_requis: r.nombre_requis,
    //                 section: r.section,
    //             })));
    //         }
    //         // On mémorise l'ID pour le prochain cycle
    //         lastLoadedLabId.current = data.laboratoire_id;
    //     }
    // }, [data.laboratoire_id, availableLabs]);


    // const handleSubmit = (e) => {
    //     e.preventDefault();
    //     if (!data.laboratoire_id) return;

    //     post(route('laboratoires.requis.sync', data.laboratoire_id), {
    //         preserveScroll: true,
    //         onSuccess: () => {
    //             // Optionnel : Notification de succès
    //         }
    //     });
    // };

    // const handleSubmit = (e) => {
    //     e.preventDefault();

    //     if (!data.laboratoire_id) {
    //         // Optionnel : ajouter une notification d'erreur ici
    //         return;
    //     }

    //     /**
    //      * Utilisation de la route définie dans votre contrôleur
    //      * @cite 10: post(route('laboratoires.requis.sync', data.laboratoire_id));
    //      */
    //     post(route('laboratoires.requis.sync', data.laboratoire_id), {
    //         onSuccess: () => {
    //             // Logique additionnelle après succès si nécessaire
    //         },
    //     });

    // };

    // RequisConfig.jsx

    const handleSubmit = (e) => {
        e.preventDefault();

        if (!data.laboratoire_id) return;

        // Utilisation d'un objet pour nommer explicitement le paramètre attendu {laboratoire}
        // post(
        //     route("laboratoires.requis.sync", {
        //         laboratoire: data.laboratoire_id,
        //     }),
        //     {
        //         preserveScroll: true,
        //         onSuccess: () => {
        //             // Message de succès
        //         },
        //     },
        // );
        post(
            route("laboratoires.requis.sync", {
                laboratoire: data.laboratoire_id,
            }),
            {
                preserveScroll: true,
                // S'exécute si la validation Laravel échoue (ex: 422 Unprocessable Entity)
                onError: (errors) => {
                    console.error("Erreurs de validation ou d'autorisation :", errors);
                    Toast.show({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors de la mise à jour des requis', life: 3000 });
                    // Vous pouvez ici utiliser un toast ou une notification pour l'utilisateur
                },
                // S'exécute si la requête réussit (200 OK ou 302 Redirect sans erreurs)
                onSuccess: () => {
                    // Votre message de succès (ex: avec PrimeReact Toast)
                    Toast.show({ severity: 'success', summary: 'Succès', detail: 'Requis mis à jour avec succès', life: 3000 });
                },
            }
        );
    };
    return (
        <Layout>
            <div className="max-w-4xl p-6 mx-auto">
                <Card
                    title="Configuration des Requis par Laboratoire"
                    className="mb-4"
                >
                    <div className="grid grid-cols-1 gap-4 mb-6 md:grid-cols-3">
                        {/* Sélection Département */}
                        <div className="flex flex-col gap-2">
                            <label className="font-bold">Département</label>
                            <Dropdown
                                value={selectedDept}
                                options={structure || []} // Sécurité si structure est null
                                onChange={(e) => setSelectedDept(e.value)}
                                optionLabel={
                                    structure?.[0]?.name !== undefined
                                        ? "name"
                                        : "nom"
                                } // Auto-détection
                                optionValue="id"
                                placeholder="Choisir un département"
                                filter
                            />
                        </div>

                        {/* Sélection Sous-Département */}
                        <div className="flex flex-col gap-2">
                            <label className="font-bold">
                                Sous-Département
                            </label>
                            <Dropdown
                                value={selectedSousDept}
                                options={availableSousDepts}
                                onChange={(e) => setSelectedSousDept(e.value)}
                                optionLabel="nom"
                                optionValue="id"
                                placeholder="Choisir un sous-département"
                                disabled={!selectedDept}
                                filter
                            />
                        </div>

                        {/* Sélection Laboratoire */}
                        <div className="flex flex-col gap-2">
                            <label className="font-bold">Laboratoire</label>
                            {/* <Dropdown
                                value={data.laboratoire_id}
                                options={availableLabs}
                                onChange={(e) =>
                                    setData("laboratoire_id", e.value)
                                }
                                optionLabel="nom"
                                optionValue="id"
                                placeholder="Choisir un labo"
                                disabled={!selectedSousDept}
                                filter
                            /> */}
                            <Dropdown
                                value={data.laboratoire_id}
                                options={availableLabs}
                                onChange={(e) => handleLabChange(e.value)} // Appel de la fonction manuelle
                                optionLabel="nom"
                                optionValue="id"
                                placeholder="Choisir un laboratoire"
                                disabled={!selectedSousDept}
                                filter
                                className="w-full"
                            />
                        </div>
                    </div>

                    {data.laboratoire_id ? (
                        <form onSubmit={handleSubmit}>
                            <RequisRepeater
                                data={data}
                                setData={setData}
                                allRequisOptions={allRequisOptions}
                                sectionTypes={sectionTypes} // On passe les types ici
                            />

                            {/* <pre className="p-2 text-xs bg-gray-100">
                                {JSON.stringify(data.requis_list, null, 2)}
                            </pre> */}

                            <div className="flex items-center gap-4 mt-6">
                                <Button
                                    label="Enregistrer l'ordre et les requis"
                                    icon="pi pi-save"
                                    loading={processing}
                                    type="submit"
                                    className="p-button-primary"
                                />
                                {recentlySuccessful && (
                                    <Message
                                        severity="success"
                                        text="Enregistré avec succès"
                                    />
                                )}
                            </div>
                        </form>
                    ) : (
                        <div className="p-8 text-center border-2 border-dashed rounded-lg bg-gray-50">
                            <i className="mb-2 text-2xl text-blue-500 pi pi-info-circle"></i>
                            <p>
                                Veuillez sélectionner un laboratoire pour
                                configurer ses requis.
                            </p>
                        </div>
                    )}
                </Card>
            </div>
        </Layout>
    );
};

export default RequisConfig;
