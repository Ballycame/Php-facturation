<?php
// ============================================================
// config/config.php – Paramètres globaux de l'application
// ============================================================

define('APP_NAME', 'SuperMarché – Système de Facturation');
define('APP_VERSION', '1.0.0');

// Taux de TVA (18%)
define('TAUX_TVA', 0.18);

// Devise
define('DEVISE', 'CDF');

// Chemins des fichiers de données
define('DATA_DIR', __DIR__ . '/../data/');
define('FICHIER_PRODUITS',    DATA_DIR . 'produits.json');
define('FICHIER_FACTURES',    DATA_DIR . 'factures.json');
define('FICHIER_UTILISATEURS', DATA_DIR . 'utilisateurs.json');

// Chemins des includes
define('INCLUDES_DIR', __DIR__ . '/../includes/');

// Durée de session (secondes) – 2 heures
define('SESSION_DUREE', 7200);

// Préfixe des identifiants de factures
define('FACTURE_PREFIXE', 'FAC');

// Rôles disponibles
define('ROLE_SUPERADMIN', 'superadmin');
define('ROLE_MANAGER',    'manager');
define('ROLE_CAISSIER',   'caissier');

// Hiérarchie des rôles (plus le chiffre est élevé, plus le rôle a de permissions)
define('ROLES_HIERARCHIE', [
    ROLE_CAISSIER   => 1,
    ROLE_MANAGER    => 2,
    ROLE_SUPERADMIN => 3,
]);
