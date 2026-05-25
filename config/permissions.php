<?php

return [

    'actions' => [
        'view_any',
        'view',
        'create',
        'update',
        'delete',
    ],

    'custom_actions' => [
        'promotion' => ['gerer_phases','gerer_specialites', 'export', 'edit'],
        'matiere' => ['validate', 'assign'],
        'programme' => ['publish'],
        'note' => ['generate', 'validate', 'view_stats', 'initialize_list'],
    ],

];
