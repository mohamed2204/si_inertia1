import React from 'react';
import { DndContext, closestCenter, KeyboardSensor, PointerSensor, useSensor, useSensors } from '@dnd-kit/core';
import { arrayMove, SortableContext, sortableKeyboardCoordinates, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { Button } from 'primereact/button';
import { SortableItem } from './SortableItem';

const RequisRepeater = ({ data, setData, allRequisOptions, sectionTypes }) => {

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates })
    );

    // Ajouter une nouvelle ligne (Requis)
    const addRequis = (e) => {

        if (e) e.preventDefault(); // Sécurité supplémentaire

        const newItem = {
            id: `new-${Date.now()}`,
            role_tache_id: null, // Assurez-vous que le nom correspond à votre backend (PostgreSQL)
            nombre_requis: 1,
            section: 'jour', // Valeur par défaut basée sur vos types backend
            ordre: data.requis_list.length
        }
        setData('requis_list', [...data.requis_list, newItem]);
    };

    // Mettre à jour une ligne spécifique
    // const updateItem = (id, field, value) => {
    //     const newList = data.requis_list.map(item => 
    //         item.id === id ? { ...item, [field]: value } : item
    //     );
    //     setData('requis_list', newList);
    // };

    const updateItem = (id, field, value) => {
        setData(prevData => ({
            ...prevData,
            requis_list: prevData.requis_list.map(item =>
                item.id === id ? { ...item, [field]: value } : item
            )
        }));
    };

    // Supprimer une ligne
    const removeItem = (id) => {
        setData('requis_list', data.requis_list.filter(item => item.id !== id));
    };

    // Gérer le changement d'ordre après le Drag & Drop
    const handleDragEnd = (event) => {
        const { active, over } = event;
        if (active.id !== over.id) {
            const oldIndex = data.requis_list.findIndex(i => i.id === active.id);
            const newIndex = data.requis_list.findIndex(i => i.id === over.id);

            const newArray = arrayMove(data.requis_list, oldIndex, newIndex);

            // Mise à jour de la propriété 'ordre' basée sur le nouvel index
            const orderedArray = newArray.map((item, index) => ({
                ...item,
                ordre: index
            }));

            setData('requis_list', orderedArray);
        }
    };

    return (
        <div className="p-4 bg-gray-50 border rounded-xl">
            <h3 className="text-lg font-semibold mb-4 flex items-center gap-2">
                Configuration des Requis
            </h3>

            <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
                <SortableContext items={data.requis_list.map(i => i.id)} strategy={verticalListSortingStrategy}>
                    <div className="min-h-[50px]">
                        {data.requis_list.map((item) => (
                            <SortableItem
                                key={item.id}
                                id={item.id}
                                item={item}
                                sectionTypes={sectionTypes} // <--- Important
                                availableRequis={allRequisOptions}
                                updateItem={updateItem}
                                removeItem={removeItem}
                            />
                        ))}
                    </div>
                </SortableContext>
            </DndContext>

            {/* Message si liste vide */}
            {data.requis_list.length === 0 && (
                <div className="text-center py-6 border-2 border-dashed rounded-lg mb-4 text-gray-500 italic">
                    Aucun requis configuré. Cliquez sur le bouton ci-dessous pour commencer.
                </div>
            )}

            <Button
                type="button" // <--- Crucial pour éviter le rechargement de la page
                label="Ajouter un requis"
                icon="pi pi-plus"
                onClick={addRequis}
                className="p-button-outlined p-button-sm w-full mt-2"
            />
        </div>
    );
};

export default RequisRepeater;