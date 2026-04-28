<?php
// Vos ajouts ici (ils ne remplaceront pas le reste, ils s'ajouteront)
//$config['identities_level'] = 3;
//$config['disabled_actions'] = array('settings.identities', 'settings.responses');
//$config['dont_override'] = array('server_settings', 'skin');

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
