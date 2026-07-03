<?php

return [
    
    /*
    |--------------------------------------------------------------------------
    | Mappage des Canevas PDF par Code de Sous-Département
    |--------------------------------------------------------------------------
    */
    'pdf_views' => [
        'LAB1' => 'pdf.canevas_laboratoire', // Plus besoin de l'ID 68 !
        'LAB2' => 'pdf.canevas_radiologie',
        'LAB3' => 'pdf.canevas_urgences',
        
        'default' => 'pdf.rapport', 
    ],
];