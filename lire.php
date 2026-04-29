<?php
// modules/produits/lire.php – Point de terminaison AJAX pour lire un produit par code-barres
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../includes/fonctions-auth.php';
require_once __DIR__ . '/../../includes/fonctions-produits.php';

verifier_connecte();

header('Content-Type: application/json; charset=utf-8');

$code = trim($_GET['code'] ?? '');
if (empty($code)) {
    echo json_encode(['erreur' => 'Code-barres manquant.']);
    exit;
}

$produit = trouver_produit($code);
if (!$produit) {
    echo json_encode(['erreur' => 'Produit inconnu. Veuillez demander au Manager de l\'enregistrer.', 'inconnu' => true]);
    exit;
}

echo json_encode(['produit' => $produit]);
