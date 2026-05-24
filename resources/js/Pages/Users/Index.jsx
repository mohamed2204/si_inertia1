import React, { useState, useEffect, useRef } from "react";
import { DataTable } from "primereact/datatable";
import { Column } from "primereact/column";
import { InputText } from "primereact/inputtext";
import { Button } from "primereact/button";
import { Dropdown } from "primereact/dropdown";
import { Dialog } from "primereact/dialog";
import { MultiSelect } from "primereact/multiselect";
import { Toast } from "primereact/toast";
import { Tag } from "primereact/tag";
import axios from "axios";
import Layout from "@/Layouts/layout";
import { IconField } from "primereact/iconfield";
import { InputIcon } from "primereact/inputicon";

export default function Index({ all_groups, all_sous_depts = [], can_create }) {
    const toast = useRef(null);

    // États pour le stockage des données de la DataTable
    const [tableData, setTableData] = useState({ data: [], total: 0 });
    const [loading, setLoading] = useState(true);

    // Paramètres d'appel API synchronisés avec le useEffect (Ajout de sous_departement_id)
    const [params, setParams] = useState({
        page: 1,
        search: "",
        group_id: "",
        sous_departement_id: "", // Nouveau filtre
        per_page: 10,
        sort_by: "name",
        sort_dir: "asc",
    });

    // États pour la gestion du formulaire d'édition/création (Dialog)
    const [userDialog, setUserDialog] = useState(false);
    const [deleteDialog, setDeleteDialog] = useState(false);
    const [selectedUser, setSelectedUser] = useState(null);

    // Nouveau champ initialisé dans la structure du formulaire
    const emptyUser = {
        name: "",
        email: "",
        password: "",
        sous_departement_id: null,
        group_ids: [],
    };
    const [formData, setFormData] = useState(emptyUser);
    const [submitted, setSubmitted] = useState(false);

    // Effet déclenché à chaque changement de filtre, tri ou pagination
    useEffect(() => {
        loadUsers();
    }, [
        params.page,
        params.search,
        params.group_id,
        params.sous_departement_id,
        params.sort_by,
        params.sort_dir,
        params.per_page,
    ]);

    const loadUsers = async () => {
        setLoading(true);
        try {
            const response = await axios.get("/admin/users", { params });
            setTableData({
                data: response.data.data,
                total: response.data.total,
            });
        } catch (error) {
            toast.current.show({
                severity: "error",
                summary: "Erreur",
                detail: "Impossible de charger les utilisateurs",
            });
        } finally {
            setLoading(false);
        }
    };

    // Actions formulaires
    const openNew = () => {
        setFormData(emptyUser);
        setSubmitted(false);
        setSelectedUser(null);
        setUserDialog(true);
    };

    const editUser = (user) => {
        setFormData({
            name: user.name,
            email: user.email,
            password: "",
            sous_departement_id: user.sous_departement_id || null, // Hydratation du champ structurel
            group_ids: user.groups ? user.groups.map((g) => g.id) : [],
        });
        setSelectedUser(user);
        setSubmitted(false);
        setUserDialog(true);
    };

    const confirmDeleteUser = (user) => {
        setSelectedUser(user);
        setDeleteDialog(true);
    };

    const handleInputChange = (e, name) => {
        const val = e.target.value;
        setFormData((prev) => ({ ...prev, [name]: val }));
    };

    const saveUser = async () => {
        setSubmitted(true);

        if (
            !formData.name ||
            !formData.email ||
            (!selectedUser && !formData.password)
        ) {
            return;
        }

        try {
            if (selectedUser) {
                await axios.put(`/admin/users/${selectedUser.id}`, formData);
                toast.current.show({
                    severity: "success",
                    summary: "Succès",
                    detail: "Utilisateur mis à jour",
                });
            } else {
                await axios.post("/admin/users", formData);
                toast.current.show({
                    severity: "success",
                    summary: "Succès",
                    detail: "Utilisateur créé",
                });
            }
            setUserDialog(false);
            loadUsers();
        } catch (error) {
            const msg =
                error.response?.data?.message || "Une erreur est survenue.";
            toast.current.show({
                severity: "error",
                summary: "Échec",
                detail: msg,
            });
        }
    };

    const deleteUser = async () => {
        try {
            await axios.delete(`/admin/users/${selectedUser.id}`);
            setDeleteDialog(false);
            toast.current.show({
                severity: "success",
                summary: "Succès",
                detail: "Utilisateur supprimé",
            });
            loadUsers();
        } catch (error) {
            toast.current.show({
                severity: "error",
                summary: "Erreur",
                detail: "Action impossible",
            });
        }
    };

    // Rendu des badges de groupes
    const groupsBodyTemplate = (rowData) => {
        return rowData.groups && rowData.groups.length > 0 ? (
            <div className="flex flex-wrap gap-1">
                {rowData.groups.map((g) => (
                    <Tag key={g.id} value={g.name} severity="info" />
                ))}
            </div>
        ) : (
            <span className="text-gray-400 italic text-sm">Aucun groupe</span>
        );
    };

    // Nouveau template pour afficher la structure d'appartenance principale
    const deptBodyTemplate = (rowData) => {
        return rowData.sous_departement ? (
            <span className="font-medium text-gray-700">
                {rowData.sous_departement.nom}
            </span>
        ) : (
            <span className="text-gray-400 italic text-sm">Non affecté</span>
        );
    };

    const actionBodyTemplate = (rowData) => {
        return (
            <div className="flex gap-2">
                {rowData.can_edit && (
                    <Button
                        icon="pi pi-pencil"
                        rounded
                        severity="warning"
                        size="small"
                        onClick={() => editUser(rowData)}
                    />
                )}
                {rowData.can_delete && (
                    <Button
                        icon="pi pi-trash"
                        rounded
                        severity="danger"
                        size="small"
                        onClick={() => confirmDeleteUser(rowData)}
                    />
                )}
            </div>
        );
    };

    // En-tête de la table intégrant la recherche multicritères
    const renderHeader = () => {
        return (
            // Conteneur principal alignant les filtres à gauche et le bouton à droite verticalement centrés
            <div
                style={{
                    display: "flex",
                    justifyContent: "space-between",
                    alignItems: "flex-end",
                    flexWrap: "wrap",
                    gap: "1rem",
                    width: "100%",
                }}
            >
                {/* Zone des filtres organisée avec la grille de Sakai */}
                <div
                    className="grid formgrid p-fluid"
                    style={{ margin: 0, flex: 1, gap: "0.5rem" }}
                >
                    {/* 1. Recherche globale */}
                    <div className="col-12 md:col-3">
                        <label className="block mb-1 text-xs font-bold text-600">
                            RECHERCHE
                        </label>
                        <IconField iconPosition="left">
                            <InputIcon className="pi pi-search" />
                            <InputText
                                value={params.search}
                                onChange={(e) =>
                                    setParams((prev) => ({
                                        ...prev,
                                        search: e.target.value,
                                        page: 1,
                                    }))
                                }
                                placeholder="Nom, email..."
                                className="w-full"
                            />
                        </IconField>
                        {/* <span className="p-input-icon-left">
                            <i className="pi pi-search" />
                            <InputText
                                value={params.search}
                                onChange={(e) =>
                                    setParams((prev) => ({
                                        ...prev,
                                        search: e.target.value,
                                        page: 1,
                                    }))
                                }
                                placeholder="Nom, email..."
                                className="w-full"
                            />
                        </span> */}
                    </div>

                    {/* 2. Filtre Sous-Département */}
                    <div className="col-12 md:col-4 lg:col-3">
                        <label className="block mb-1 text-xs font-bold text-600">
                            SOUS-DÉPARTEMENT
                        </label>
                        <Dropdown
                            value={params.sous_departement_id}
                            options={all_sous_depts}
                            optionLabel="nom"
                            optionValue="id"
                            onChange={(e) =>
                                setParams((prev) => ({
                                    ...prev,
                                    sous_departement_id: e.value || "",
                                    page: 1,
                                }))
                            }
                            placeholder="Sélectionner..."
                            showClear
                            className="w-full"
                        />
                    </div>

                    {/* 3. Filtre Groupe */}
                    <div className="col-12 md:col-3">
                        <label className="block mb-1 text-xs font-bold text-600">
                            GROUPE / RÔLE
                        </label>
                        <Dropdown
                            value={params.group_id}
                            options={all_groups}
                            optionLabel="name"
                            optionValue="id"
                            onChange={(e) =>
                                setParams((prev) => ({
                                    ...prev,
                                    group_id: e.value || "",
                                    page: 1,
                                }))
                            }
                            placeholder="Sélectionner..."
                            showClear
                            className="w-full"
                        />
                    </div>
                </div>

                {/* Zone du bouton (Poussé à droite et aligné avec le bas des inputs) */}
                {can_create && (
                    <div style={{ marginBottom: "2px" }}>
                        <Button
                            label="Nouvel Utilisateur"
                            icon="pi pi-plus"
                            className="p-button-success p-button-sm"
                            onClick={openNew}
                        />
                    </div>
                )}
            </div>
        );
    };

    const dialogFooter = (
        <div className="flex justify-end gap-2">
            <Button
                label="Annuler"
                icon="pi pi-times"
                outlined
                onClick={() => setUserDialog(false)}
            />
            <Button label="Enregistrer" icon="pi pi-check" onClick={saveUser} />
        </div>
    );

    const deleteDialogFooter = (
        <div className="flex justify-end gap-2">
            <Button
                label="Non"
                icon="pi pi-times"
                outlined
                onClick={() => setDeleteDialog(false)}
            />
            <Button
                label="Oui, Supprimer"
                icon="pi pi-check"
                severity="danger"
                onClick={deleteUser}
            />
        </div>
    );

    return (
        <Layout>
            <div className="card p-4 shadow-1 surface-card border-round-sm">
                <Toast ref={toast} />
                <h2 className="text-xl font-bold mb-4 text-slate-800">
                    Gestion des Comptes Utilisateurs
                </h2>

                <DataTable
                    value={tableData.data}
                    lazy
                    paginator
                    rows={params.per_page}
                    first={(params.page - 1) * params.per_page}
                    totalRecords={tableData.total}
                    onPage={(e) =>
                        setParams((prev) => ({
                            ...prev,
                            page: e.page + 1,
                            per_page: e.rows,
                        }))
                    }
                    onSort={(e) =>
                        setParams((prev) => ({
                            ...prev,
                            sort_by: e.sortField,
                            sort_dir: e.sortOrder === 1 ? "asc" : "desc",
                        }))
                    }
                    sortField={params.sort_by}
                    sortOrder={params.sort_dir === "asc" ? 1 : -1}
                    loading={loading}
                    dataKey="id"
                    header={renderHeader()}
                    emptyMessage="Aucun utilisateur trouvé."
                    responsiveLayout="scroll"
                    className="p-datatable-sm"
                >
                    <Column
                        field="name"
                        header="Nom Complet"
                        sortable
                        className="font-semibold"
                    />
                    <Column field="email" header="Adresse Email" sortable />
                    {/* Nouvelle colonne insérée pour afficher le lab d'appartenance */}
                    <Column header="Sous-Département" body={deptBodyTemplate} />
                    <Column
                        header="Groupes / Rôles Assignés"
                        body={groupsBodyTemplate}
                    />
                    <Column
                        header="Actions"
                        body={actionBodyTemplate}
                        style={{ width: "100px" }}
                    />
                </DataTable>

                {/* Dialog de Création / Édition */}
                <Dialog
                    visible={userDialog}
                    style={{ width: "480px" }}
                    header={
                        selectedUser
                            ? "Modifier l'utilisateur"
                            : "Créer un utilisateur"
                    }
                    modal
                    className="p-fluid"
                    footer={dialogFooter}
                    onHide={() => setUserDialog(false)}
                >
                    <div className="field mb-3">
                        <label htmlFor="name" className="font-bold block mb-1">
                            Nom Complet
                        </label>
                        <InputText
                            id="name"
                            value={formData.name}
                            onChange={(e) => handleInputChange(e, "name")}
                            required
                            className={
                                submitted && !formData.name ? "p-invalid" : ""
                            }
                        />
                        {submitted && !formData.name && (
                            <small className="p-error block mt-1">
                                Le nom est requis.
                            </small>
                        )}
                    </div>

                    <div className="field mb-3">
                        <label htmlFor="email" className="font-bold block mb-1">
                            Adresse Email
                        </label>
                        <InputText
                            id="email"
                            value={formData.email}
                            onChange={(e) => handleInputChange(e, "email")}
                            required
                            className={
                                submitted && !formData.email ? "p-invalid" : ""
                            }
                        />
                        {submitted && !formData.email && (
                            <small className="p-error block mt-1">
                                L'adresse email est requise.
                            </small>
                        )}
                    </div>

                    <div className="field mb-3">
                        <label
                            htmlFor="password"
                            className="font-bold block mb-1"
                        >
                            Mot de passe{" "}
                            {selectedUser && (
                                <span className="text-sm font-normal text-slate-400">
                                    (Laisser vide si inchangé)
                                </span>
                            )}
                        </label>
                        <InputText
                            id="password"
                            type="password"
                            value={formData.password}
                            onChange={(e) => handleInputChange(e, "password")}
                            required={!selectedUser}
                            className={
                                submitted && !selectedUser && !formData.password
                                    ? "p-invalid"
                                    : ""
                            }
                        />
                        {submitted && !selectedUser && !formData.password && (
                            <small className="p-error block mt-1">
                                Le mot de passe est requis pour un nouveau
                                compte.
                            </small>
                        )}
                    </div>

                    {/* NOUVEAU : Sélecteur de l'appartenance structurelle principale */}
                    <div className="field mb-3">
                        <label
                            htmlFor="sous_departement_id"
                            className="font-bold block mb-1"
                        >
                            Sous-Département principal d'affectation
                        </label>
                        <Dropdown
                            id="sous_departement_id"
                            value={formData.sous_departement_id}
                            options={all_sous_depts}
                            optionLabel="nom"
                            optionValue="id"
                            placeholder="Sélectionner le sous-département d'origine"
                            showClear
                            onChange={(e) =>
                                setFormData((prev) => ({
                                    ...prev,
                                    sous_departement_id: e.value,
                                }))
                            }
                        />
                    </div>

                    <div className="field mb-3">
                        <label className="font-bold block mb-1">
                            Groupes d'Accès (Matrice)
                        </label>
                        <MultiSelect
                            value={formData.group_ids}
                            options={all_groups}
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Sélectionner un ou plusieurs groupes"
                            maxSelectedLabels={3}
                            onChange={(e) =>
                                setFormData((prev) => ({
                                    ...prev,
                                    group_ids: e.value,
                                }))
                            }
                        />
                    </div>
                </Dialog>

                {/* Dialog de Confirmation de suppression */}
                <Dialog
                    visible={deleteDialog}
                    style={{ width: "450px" }}
                    header="Confirmer la suppression"
                    modal
                    footer={deleteDialogFooter}
                    onHide={() => setDeleteDialog(false)}
                >
                    <div className="flex align-items-center gap-3">
                        <i className="pi pi-exclamation-triangle text-red-500 text-3xl" />
                        {selectedUser && (
                            <span>
                                Êtes-vous sûr de vouloir supprimer
                                définitivement le compte de{" "}
                                <b>{selectedUser.name}</b> ?
                            </span>
                        )}
                    </div>
                </Dialog>
            </div>
        </Layout>
    );
}
