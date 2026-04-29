<?php
// modules/facturation/calcul.php – Endpoint AJAX : calcul des totaux d'une facture
// Reçoit en POST un tableau JSON d'articles, retourne les totaux calculés.

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../includes/fonctions-auth.php';
require_once __DIR__ . '/../../includes/fonctions-produits.php';
require_once __DIR__ . '/../../includes/fonctions-factures.php';

verifier_connecte();
verifier_role(ROLE_CAISSIER);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erreur' => 'Méthode non autorisée.']);
    exit;
}

$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (!is_array($data) || !isset($data['articles']) || !is_array($data['articles'])) {
    http_response_code(400);
    echo json_encode(['erreur' => 'Données invalides.']);
    exit;
}

$articles = $data['articles'];
$erreurs  = [];
$articles_valides = [];

foreach ($articles as $i => $a) {
    $code = trim($a['code_barre'] ?? '');
    $qte  = (int)($a['quantite'] ?? 0);

    if (empty($code)) {
        $erreurs[] = "Article #" . ($i + 1) . " : code-barres manquant.";
        continue;
    }
    if ($qte <= 0) {
        $erreurs[] = "Article « $code » : quantité invalide ($qte).";
        continue;
    }

    $produit = trouver_produit($code);
    if (!$produit) {
        $erreurs[] = "Produit introuvable : $code.";
        continue;
    }
    if ($produit['quantite_stock'] < $qte) {
        $erreurs[] = "Stock insuffisant pour « {$produit['nom']} » (disponible : {$produit['quantite_stock']}, demandé : $qte).";
        continue;
    }

    $articles_valides[] = [
        'code_barre'       => $produit['code_barre'],
        'nom'              => $produit['nom'],
        'prix_unitaire_ht' => $produit['prix_unitaire_ht'],
        'quantite'         => $qte,
        'sous_total_ht'    => $produit['prix_unitaire_ht'] * $qte,
    ];
}

if (!empty($erreurs)) {
    echo json_encode(['erreur' => implode(' | ', $erreurs)]);
    exit;
}

$totaux = calculer_totaux($articles_valides);

echo json_encode([
    'articles'  => $articles_valides,
    'total_ht'  => $totaux['total_ht'],
    'tva'       => $totaux['tva'],
    'total_ttc' => $totaux['total_ttc'],
    'taux_tva'  => TAUX_TVA,
    'devise'    => DEVISE,
]);
