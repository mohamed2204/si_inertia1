import React, { useState, useEffect, useCallback } from "react";
import Layout from "@/Layouts/layout";
import { Head, Link } from "@inertiajs/react";
import api from "@/Services/api";
import { debounce } from "lodash";
import Pagination from "../Components/Pagination"; // Conservé si nécessaire
import Swal from "sweetalert2";
import { Button } from "primereact/button";
import { DataTable } from "primereact/datatable";
import { Column } from "primereact/column";
import { Tag } from "primereact/tag";
import { InputText } from "primereact/inputtext";
import { Dropdown } from "primereact/dropdown";
import { Paginator } from "primereact/paginator";
import { IconField } from "primereact/iconfield";
import { InputIcon } from "primereact/inputicon";

export default function Index({ initialDepartments, filters, can_create }) {
    const [tableData, setTableData] = useState({
        data: [],
        links: [],
        total: 0,
    });

    const [loading, setLoading] = useState(true);
    const [options, setOptions] = useState({
        departements: initialDepartments || [],
        sousDepartements: [],
        statusList: [
            { id: "en_attente", label: "En attente" },
            { id: "valide", label: "Validé" },
            { id: "rejete", label: "Rejeté" },
        ],
    });

    // Utilisation stricte de 'departement_id' partout
    const [params, setParams] = useState({
        page: filters?.page || 1,
        search: filters?.search || "",
        departement_id: filters?.departement_id || filters?.department_id || "",
        sous_departement_id: filters?.sous_departement_id || "",
        statut: filters?.statut || "",
        per_page: 10,
        sort_by: "created_at",
        sort_dir: "desc",
    });

    const dateBodyTemplate = (rowData) => {
        return rowData.date_debut
            ? `Du ${new Date(rowData.date_debut).toLocaleDateString()}`
            : "Date non définie";
    };

    const statusBodyTemplate = (rowData) => {
        return (
            <Tag
                value={rowData.statut?.toUpperCase()}
                severity={getStatusSeverity(rowData.statut)}
            />
        );
    };

    const getStatusSeverity = (status) => {
        switch (status) {
            case "valide":
                return "success";
            case "en_attente":
                return "warning";
            case "rejete":
                return "danger";
            default:
                return "secondary";
        }
    };

    const actionBodyTemplate = (rowData) => {
        return (
            <div className="flex gap-2 justify-content-center">
                {rowData.can_edit && (
                    <Link href={`/designations/${rowData.id}/edit`}>
                        <Button
                            icon="pi pi-pencil"
                            className="p-button-rounded p-button-warning p-button-sm"
                            title="Modifier"
                        />
                    </Link>
                )}

                {rowData.can_delete && (
                    <Button
                        icon="pi pi-trash"
                        className="p-button-rounded p-button-danger p-button-sm"
                        onClick={() => handleDelete(rowData.id)}
                        title="Supprimer"
                    />
                )}

                {!rowData.can_edit && !rowData.can_delete && (
                    <i
                        className="pi pi-lock text-400"
                        title="Lecture seule"
                    ></i>
                )}
            </div>
        );
    };

    const loadDesignations = async () => {
        setLoading(true);
        try {
            const response = await api.getDesignationsIndex(params);
            console.log("Réponse API des désignations :", response.data);

            const resData = response.data?.results
                ? response.data.results
                : response.data;

            setTableData({
                data: resData.data || [],
                links: resData.links || [],
                total: resData.total || 0,
            });
        } catch (error) {
            console.error("Erreur lors du chargement des désignations", error);
        } finally {
            setLoading(false);
        }
    };

    // Synchronisation sur 'departement_id'
    useEffect(() => {
        loadDesignations();
    }, [
        params.page,
        params.departement_id,
        params.sous_departement_id,
        params.statut,
        params.search,
        params.sort_by,
        params.sort_dir,
    ]);

    const debouncedSearch = useCallback(
        debounce((value) => {
            setParams((prev) => ({ ...prev, search: value, page: 1 }));
        }, 500),
        [],
    );

    // Correction appliquée ici : mise à jour de la bonne clé 'departement_id'
    const handleDeptChange = async (deptId) => {
        setParams((prev) => ({
            ...prev,
            departement_id: deptId,
            sous_departement_id: "",
            page: 1,
        }));

        if (deptId) {
            const data = await api.getSousDepts(deptId);
            setOptions((prev) => ({ ...prev, sousDepartements: data }));
        } else {
            setOptions((prev) => ({ ...prev, sousDepartements: [] }));
        }
    };

    const handleDelete = (id) => {
        Swal.fire({
            title: "Êtes-vous sûr ?",
            text: "Cette action est irréversible !",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Oui, supprimer !",
            cancelButtonText: "Annuler",
            reverseButtons: true,
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    await api.deleteDesignation(id);
                    loadDesignations();
                    Swal.fire(
                        "Supprimé !",
                        "La désignation a été supprimée.",
                        "success",
                    );
                } catch (error) {
                    Swal.fire(
                        "Erreur",
                        "Impossible de supprimer cette donnée.",
                        "error",
                    );
                }
            }
        });
    };

    const handleSort = (e) => {
        setParams((prev) => ({
            ...prev,
            sort_by: e.sortField,
            sort_dir: e.sortOrder === 1 ? "asc" : "desc",
        }));
    };

    return (
        <Layout>
            <div className="p-6 bg-gray-50 min-h-screen">
                <Head title="Liste des Désignations" />

                <div className="max-w-7xl mx-auto">
                    <h1 className="text-2xl font-bold mb-6">
                        Gestion des Désignations
                    </h1>

                    {/* BARRE DE FILTRES CORRIGÉE ET ALIGNÉE */}
                    <div className="flex flex-col sm:flex-row gap-3 mb-6 surface-card p-4 rounded-lg shadow-sm items-center justify-between w-full">
                        {/* Recherche */}
                        <div className="w-full sm:w-1/4 inline-flex items-center">
                            <IconField className="w-full">
                                <InputIcon className="pi pi-search" />
                                <InputText
                                    type="text"
                                    placeholder="Rechercher une semaine..."
                                    className="w-full p-inputtext-sm"
                                    onChange={(e) =>
                                        debouncedSearch(e.target.value)
                                    }
                                />
                            </IconField>
                        </div>

                        {/* Select Départements */}
                        <div className="w-full sm:w-1/4">
                            <Dropdown
                                value={params.departement_id}
                                options={options.departements}
                                optionLabel="nom"
                                optionValue="id"
                                placeholder="Tous les Départements"
                                showClear
                                className="w-full p-inputtext-sm"
                                onChange={(e) => handleDeptChange(e.value)}
                            />
                        </div>

                        {/* Select Sous-Départements */}
                        <div className="w-full sm:w-1/4">
                            <Dropdown
                                value={params.sous_departement_id}
                                options={options.sousDepartements || []}
                                optionLabel="nom"
                                optionValue="id"
                                placeholder="Tous les Sous-Départs"
                                disabled={!params.departement_id}
                                showClear
                                className="w-full p-inputtext-sm"
                                onChange={(e) =>
                                    setParams((prev) => ({
                                        ...prev,
                                        sous_departement_id: e.value,
                                        page: 1,
                                    }))
                                }
                            />
                        </div>

                        {/* Select Statuts */}
                        <div className="w-full sm:w-1/4">
                            <Dropdown
                                value={params.statut}
                                options={options.statusList}
                                optionLabel="label"
                                optionValue="id"
                                placeholder="Tous les Statuts"
                                showClear
                                className="w-full p-inputtext-sm"
                                onChange={(e) =>
                                    setParams((prev) => ({
                                        ...prev,
                                        statut: e.value,
                                        page: 1,
                                    }))
                                }
                            />
                        </div>
                    </div>

                    {/* Barre d'outils au-dessus du tableau */}

                    {/* Barre d'outils au-dessus du tableau */}
                    {/* Barre d'outils au-dessus du tableau */}
                    <div className="w-full flex justify-between items-center flex-wrap gap-3 mb-4 block">
                        {/* Titre à gauche */}
                        <h5 className="m-0 whitespace-nowrap text-color font-semibold text-xl inline-block">
                            Liste des Désignations
                        </h5>

                        {/* Zone du bouton poussée à droite */}
                        {can_create && (
                            <Link
                                href="/designations/create"
                                className="ml-auto inline-block"
                            >
                                <Button
                                    label="Nouvelle Désignation"
                                    icon="pi pi-plus"
                                    className="p-button-sm p-button-success"
                                />
                            </Link>
                        )}
                    </div>

                    {/* TABLEAU */}
                    <div className="card shadow-2 border-round-xl overflow-hidden surface-card">
                        <DataTable
                            value={tableData.data}
                            loading={loading}
                            dataKey="id"
                            className="p-datatable-sm"
                            stripedRows
                            responsiveLayout="stack"
                            breakpoint="960px"
                            emptyMessage="Aucune désignation trouvée."
                            lazy
                            sortField={params.sort_by}
                            sortOrder={params.sort_dir === "asc" ? 1 : -1}
                            onSort={handleSort}
                        >
                            <Column
                                field="semaine_nom"
                                header="Semaine"
                                sortable
                            />
                            <Column
                                header="Département / Labo"
                                field="emplacement_formate"
                                sortable
                                body={(rowData) =>
                                    rowData.emplacement_formate || "N/A"
                                }
                            />
                            <Column
                                field="date_debut"
                                header="Date de début"
                                body={dateBodyTemplate}
                                sortable
                            />
                            <Column
                                header="Statut"
                                field="statut"
                                body={statusBodyTemplate}
                                sortable
                            />
                            <Column
                                header="Créateur"
                                body={(rowData) =>
                                    rowData.createur?.name || "Inconnu"
                                }
                            />
                            <Column
                                body={actionBodyTemplate}
                                header="Actions"
                                headerStyle={{
                                    width: "12rem",
                                    textAlign: "center",
                                }}
                                bodyStyle={{
                                    textAlign: "center",
                                    overflow: "visible",
                                }}
                            />
                        </DataTable>

                        {/* CONFIGURATION DE LA PAGINATION ADAPTÉE */}
                        <div className="p-1 surface-ground border-t-1 surface-border flex items-center justify-between">
                            <Paginator
                                first={
                                    (params.page - 1) * (params.per_page || 10)
                                }
                                rows={params.per_page || 10}
                                totalRecords={tableData.total || 0}
                                template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                                currentPageReportTemplate="Total: {totalRecords} désignations"
                                onPageChange={(e) => {
                                    const nextPage = e.page + 1;
                                    setParams((prev) => ({
                                        ...prev,
                                        page: nextPage,
                                    }));
                                }}
                                className="w-full bg-transparent border-none p-0 text-sm flex justify-between items-center"
                                style={{
                                    height: "auto",
                                    padding: "0.25rem 0.5rem",
                                }}
                            />
                        </div>
                    </div>
                </div>
            </div>
        </Layout>
    );
}
