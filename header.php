<?php
// includes/header.php – En-tête commun à toutes les pages
// Doit être inclus APRÈS session.php

require_once __DIR__ . '/../config/config.php';

$utilisateur = utilisateur_connecte();
$role_label  = [
    ROLE_SUPERADMIN => 'Super Admin',
    ROLE_MANAGER    => 'Manager',
    ROLE_CAISSIER   => 'Caissier',
][$utilisateur['role'] ?? ''] ?? '?';

// Récupérer et effacer le message d'erreur d'accès
$erreur_acces = '';
if (!empty($_SESSION['erreur_acces'])) {
    $erreur_acces = $_SESSION['erreur_acces'];
    unset($_SESSION['erreur_acces']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $page_titre ?? APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400&family=Syne:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= chemin_base('assets/css/style.css') ?>">
</head>
<body>

<nav class="navbar">
    <a href="<?= chemin_base('index.php') ?>" class="brand">
        <span class="brand-icon">🛒</span>
        <span class="brand-name">SuperCaisse</span>
    </a>

    <div class="nav-links">
        <?php if (a_role(ROLE_CAISSIER)): ?>
        <a href="<?= chemin_module('facturation', 'nouvelle-facture.php') ?>" class="nav-link">
            📄 Nouvelle Facture
        </a>
        <?php endif; ?>

        <?php if (a_role(ROLE_MANAGER)): ?>
        <a href="<?= chemin_module('produits', 'enregistrer.php') ?>" class="nav-link">
            ➕ Produits
        </a>
        <a href="<?= chemin_module('produits', 'liste.php') ?>" class="nav-link">
            📦 Catalogue
        </a>
        <a href="<?= chemin_base('rapports/rapport-journalier.php') ?>" class="nav-link">
            📊 Rapports
        </a>
        <?php endif; ?>

        <?php if (a_role(ROLE_SUPERADMIN)): ?>
        <a href="<?= chemin_module('admin', 'gestion-comptes.php') ?>" class="nav-link">
            👥 Comptes
        </a>
        <?php endif; ?>
    </div>

    <div class="nav-user">
        <span class="user-badge role-<?= $utilisateur['role'] ?>"><?= $role_label ?></span>
        <span class="user-name"><?= htmlspecialchars($utilisateur['nom_complet']) ?></span>
        <a href="<?= chemin_auth('logout.php') ?>" class="btn-logout">Déconnexion</a>
    </div>
</nav>

<?php if ($erreur_acces): ?>
<div class="container">
    <div class="alert alert-danger">⚠ <?= htmlspecialchars($erreur_acces) ?></div>
</div>
<?php endif; ?>

<main class="container">
