import React, { useState, useEffect } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import { Dropdown } from 'primereact/dropdown';
import { Button } from 'primereact/button';
import { Card } from 'primereact/card';
import { Message } from 'primereact/message';
import RequisRepeater from './RequisRepeater';
import Layout from '@/Layouts/layout'; // Ajustez selon votre projet
import { route } from 'ziggy-js';

const RequisConfig = ({ structure, allRequisOptions }) => {
    // structure : Departement -> sous_departements -> laboratoires


    console.log('structure', structure);

    // États pour les sélections
    const [selectedDept, setSelectedDept] = useState(null);
    const [selectedSousDept, setSelectedSousDept] = useState(null);

    // Listes filtrées pour les dropdowns
    const [availableSousDepts, setAvailableSousDepts] = useState([]);
    const [availableLabs, setAvailableLabs] = useState([]);

    const { data, setData, post, processing, recentlySuccessful } = useForm({
        laboratoire_id: null,
        requis_list: []
    });

    // Cascade 1 : Quand le Département change
    useEffect(() => {
        if (selectedDept) {
            const dept = structure.find(d => d.id === selectedDept);
            setAvailableSousDepts(dept ? dept.sous_departements : []);
            setSelectedSousDept(null);
            setAvailableLabs([]);
            setData('laboratoire_id', null);
        }
    }, [selectedDept, structure]);

    // Cascade 2 : Quand le Sous-Département change
    useEffect(() => {
        if (selectedSousDept) {
            const sdep = availableSousDepts.find(s => s.id === selectedSousDept);
            setAvailableLabs(sdep ? sdep.laboratoires : []);
            setData('laboratoire_id', null);
        }
    }, [selectedSousDept, availableSousDepts]);

    // Chargement des données du laboratoire sélectionné
    useEffect(() => {
        if (data.laboratoire_id) {
            const lab = availableLabs.find(l => l.id === data.laboratoire_id);
            if (lab && lab.lab_requis) {
                setData('requis_list', lab.lab_requis.map(r => ({
                    id: `db-${r.id}`,
                    role_tache_id: r.role_tache_id,
                    nombre_requis: r.nombre_requis,
                    section: r.section
                })));
            }
        }
    }, [data.laboratoire_id]);

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

    const handleSubmit = (e) => {
        e.preventDefault();

        if (!data.laboratoire_id) {
            // Optionnel : ajouter une notification d'erreur ici
            return;
        }

        /**
         * Utilisation de la route définie dans votre contrôleur
         * @cite 10: post(route('laboratoires.requis.sync', data.laboratoire_id));
         */
        post(route('laboratoires.requis.sync', data.laboratoire_id), {
            onSuccess: () => {
                // Logique additionnelle après succès si nécessaire
            },
        });
    };
    return (
        <Layout>
            <div className="p-6 max-w-4xl mx-auto">
                <Card title="Configuration des Requis par Laboratoire" className="mb-4">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        {/* Sélection Département */}
                        <div className="flex flex-col gap-2">
                            <label className="font-bold">Département</label>
                            <Dropdown
                                value={selectedDept}
                                options={structure || []} // Sécurité si structure est null
                                onChange={(e) => setSelectedDept(e.value)}
                                optionLabel={structure?.[0]?.name !== undefined ? "name" : "nom"} // Auto-détection
                                optionValue="id"
                                placeholder="Choisir un département"
                                filter
                            />
                        </div>

                        {/* Sélection Sous-Département */}
                        <div className="flex flex-col gap-2">
                            <label className="font-bold">Sous-Département</label>
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
                            <Dropdown
                                value={data.laboratoire_id}
                                options={availableLabs}
                                onChange={(e) => setData('laboratoire_id', e.value)}
                                optionLabel="nom"
                                optionValue="id"
                                placeholder="Choisir un labo"
                                disabled={!selectedSousDept}
                                filter
                            />
                        </div>
                    </div>

                    {data.laboratoire_id ? (
                        <form onSubmit={handleSubmit}>
                            <RequisRepeater
                                data={data}
                                setData={setData}
                                allRequisOptions={allRequisOptions}
                            />

                            <pre className="text-xs bg-gray-100 p-2">
                                {JSON.stringify(data.requis_list, null, 2)}
                            </pre>

                            <div className="mt-6 flex items-center gap-4">
                                <Button
                                    label="Enregistrer l'ordre et les requis"
                                    icon="pi pi-save"
                                    loading={processing}
                                    type="submit"
                                    className="p-button-primary"
                                />
                                {recentlySuccessful && (
                                    <Message severity="success" text="Enregistré avec succès" />
                                )}
                            </div>
                        </form>
                    ) : (
                        <div className="text-center p-8 bg-gray-50 rounded-lg border-2 border-dashed">
                            <i className="pi pi-info-circle text-2xl mb-2 text-blue-500"></i>
                            <p>Veuillez sélectionner un laboratoire pour configurer ses requis.</p>
                        </div>
                    )}
                </Card>
            </div>
        </Layout>
    );
};

export default RequisConfig;