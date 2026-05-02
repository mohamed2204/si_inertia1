import React from 'react';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { GripVertical, Trash2 } from 'lucide-react';
import { Dropdown } from 'primereact/dropdown';
import { Button } from 'primereact/button';
import { InputNumber } from 'primereact/inputnumber';
import { InputText } from 'primereact/inputtext';

export function SortableItem({ id, item, availableRequis, updateItem, removeItem, sectionTypes }) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        zIndex: isDragging ? 10 : 1,
        opacity: isDragging ? 0.6 : 1,
    };

    return (
        <div ref={setNodeRef} style={style} className="flex items-center gap-3 p-3 bg-white border rounded-lg shadow-sm mb-2 group">
            {/* Poignée de drag & drop */}
            <div {...attributes} {...listeners} className="cursor-grab active:cursor-grabbing p-1 hover:bg-gray-100 rounded">
                <GripVertical size={20} className="text-gray-400" />
            </div>

            {/* Champs du formulaire regroupés */}
            <div className="flex items-center gap-3 flex-1">
                {/* Sélection du rôle/tâche */}
                <div className="flex-1">
                    <Dropdown
                        value={item.role_tache_id}
                        options={availableRequis}
                        onChange={(e) => updateItem(id, 'role_tache_id', e.value)}
                        optionLabel="nom"
                        optionValue="id"
                        placeholder="Choisir un rôle / requis"
                        filter
                        className="w-full"
                    />
                </div>

                {/* Input pour le nombre requis */}
                <div className="w-28">
                    <InputNumber
                        value={item.nombre_requis || 1}
                        onValueChange={(e) => updateItem(id, 'nombre_requis', e.value)}
                        min={1}
                        placeholder="Qté"
                        className="w-full"
                    />
                </div>

                {/* Input pour la section */}
                {/* Dropdown pour la section au lieu de InputText */}
                <div className="w-48">
                    <Dropdown
                        value={item.section || ''}
                        options={sectionTypes}
                        onChange={(e) => updateItem(id, 'section', e.value)}
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Choisir Section"
                        className="w-full"
                    />
                </div>
                {/* <div className="w-40">
                    <InputText
                        value={item.section || ''}
                        options={sectionTypes} // Reçu en props depuis RequisConfig -> RequisRepeater
                        onChange={(e) => updateItem(id, 'section', e.target.value)}
                        placeholder="Section"
                        className="w-full"
                    />
                </div> */}
            </div>

            {/* Bouton supprimer */}
            <Button
                icon={<Trash2 size={18} />}
                className="p-button-danger p-button-text p-button-rounded"
                onClick={() => removeItem(id)}
                tooltip="Supprimer"
            />
        </div>
    );
}

export default SortableItem;