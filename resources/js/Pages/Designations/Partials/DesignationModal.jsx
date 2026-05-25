import React from 'react';
import { Dialog } from 'primereact/dialog';
import { Dropdown } from 'primereact/dropdown';
import { Button } from 'primereact/button';
import { Accordion, AccordionTab } from 'primereact/accordion';

const DesignationModal = ({ visible, onHide, data, setData, membres, departements, sousDepartements, processing, post }) => {
    
    // Header personnalisé pour coller à l'image
    const modalHeader = (
        <div className="flex flex-column gap-1">
            <span className="text-xs font-semibold text-500 uppercase tracking-wider">Designation & Scheduling</span>
            <span className="text-2xl font-bold text-900">LABORATORY STAFF ASSIGNMENT</span>
            <div className="border-bottom-2 border-primary w-2rem mt-1"></div>
        </div>
    );

    const modalFooter = (
        <div className="flex justify-content-end gap-2 p-3 border-top-1 surface-border">
            <Button label="Save Schedule" icon="pi pi-check" onClick={() => post('/designations/store')} loading={processing} className="p-button-primary px-4" />
            <Button label="Export to PDF" icon="pi pi-file-pdf" className="p-button-success p-button-outlined" />
            <Button label="Import Template" icon="pi pi-upload" className="p-button-secondary p-button-text" />
        </div>
    );

    return (
        <Dialog 
            visible={visible} 
            onHide={onHide} 
            header={modalHeader} 
            footer={modalFooter}
            style={{ width: '90vw' }} 
            maximized={false}
            className="p-fluid designation-modal"
            modal
        >
            {/* --- SECTION FILTRES (LIGNE UNIQUE) --- */}
            <div className="grid mt-2 mb-4">
                <div className="col-12 md:col-3">
                    <label className="text-xs font-bold mb-1 block">Department:</label>
                    <Dropdown value={data.selectedDept} options={departements} onChange={(e) => setData('selectedDept', e.value)} placeholder="Biology" className="surface-0" />
                </div>
                <div className="col-12 md:col-3">
                    <label className="text-xs font-bold mb-1 block">Lab:</label>
                    <Dropdown value={data.selectedLab} options={sousDepartements} placeholder="Genetics Lab A" className="surface-0" />
                </div>
                <div className="col-12 md:col-3">
                    <label className="text-xs font-bold mb-1 block">Week:</label>
                    <Dropdown placeholder="Oct 26 - Nov 1, 2024" className="surface-0" />
                </div>
                <div className="col-12 md:col-3">
                    <label className="text-xs font-bold mb-1 block">Nature:</label>
                    <Dropdown placeholder="Regular" className="surface-0" />
                </div>
            </div>

            {/* --- GRILLE DES JOURS --- */}
            <div className="grid">
                {data.selectedLabConfig?.map((conf) => (
                    <div key={conf.jour} className="col-12 md:col-6 lg:col-4 p-2">
                        <div className="border-1 surface-border border-round-xl p-0 bg-white shadow-1 h-full">
                            {/* Jour Header */}
                            <div className="p-3 border-bottom-1 surface-border">
                                <span className="text-lg font-bold text-900 uppercase">{conf.jour_label}</span>
                            </div>
                            
                            {/* Liste des postes (venant de votre table laboratoire_config_requis) */}
                            <div className="p-3">
                                <div className="grid">
                                    {conf.details.map((requis) => (
                                        <div key={requis.id} className="col-6 mb-3">
                                            <label className="text-xs font-bold text-500 uppercase block mb-1">
                                                {requis.libelle}
                                            </label>
                                            <Dropdown 
                                                value={data.designations?.[conf.jour]?.[requis.id]}
                                                options={membres} 
                                                optionLabel="nom" 
                                                placeholder="N/A"
                                                className="surface-100 border-none"
                                                onChange={(e) => handleMemberChange(conf.jour, requis.id, e.value)}
                                            />
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>
                ))}
                
                {/* CARTE REMPLAÇANTS */}
                <div className="col-12 md:col-6 lg:col-4 p-2">
                    <div className="border-1 surface-border border-round-xl p-0 bg-blue-50 shadow-1 h-full">
                        <div className="p-3 border-bottom-1 border-blue-100">
                            <span className="text-lg font-bold text-blue-900 uppercase">Remplaçants</span>
                        </div>
                        <div className="p-3">
                             <label className="text-xs font-bold text-blue-400 uppercase block mb-1">Liste de réserve</label>
                             <Dropdown placeholder="N/A" options={membres} optionLabel="nom" className="border-none" />
                        </div>
                    </div>
                </div>
            </div>
        </Dialog>
    );
};