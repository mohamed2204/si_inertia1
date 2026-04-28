<?php
// Verrouillage de la sécurité des identités (Niveau 3 = Maximum)
$config['identities_level'] = 3;

// Masque les sections qui pourraient prêter à confusion en local
// $config['disabled_actions'] = array('settings.identities', 'settings.responses');


$config['disabled_actions'] = array(
    'settings.identities',
    'settings.responses',
    'settings.preferences',
    'mail.import'
);


// Empêche de toucher aux réglages du serveur
$config['dont_override'] = array('server_settings', 'skin');

// Optionnel : Forcer la langue en Français
$config['language'] = 'fr_FR';
?>


