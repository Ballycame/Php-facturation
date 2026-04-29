<?php
// ============================================================
// includes/fonctions-factures.php – Gestion des factures
// ============================================================

require_once __DIR__ . '/../config/config.php';

/**
 * Charge toutes les factures.
 */
function charger_factures(): array {
    if (!file_exists(FICHIER_FACTURES)) return [];
    $contenu = file_get_contents(FICHIER_FACTURES);
    return json_decode($contenu, true) ?? [];
}

/**
 * Sauvegarde la liste des factures.
 */
function sauvegarder_factures(array $factures): bool {
    return file_put_contents(
        FICHIER_FACTURES,
        json_encode($factures, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    ) !== false;
}

/**
 * Génère un identifiant de facture unique.
 * Format : FAC-AAAAMMJJ-NNN
 */
function generer_id_facture(): string {
    $factures = charger_factures();
    $date = date('Ymd');
    $prefix = FACTURE_PREFIXE . '-' . $date . '-';
    $max = 0;
    foreach ($factures as $f) {
        if (strpos($f['id_facture'], $prefix) === 0) {
            $num = (int)substr($f['id_facture'], strlen($prefix));
            if ($num > $max) $max = $num;
        }
    }
    return $prefix . str_pad($max + 1, 3, '0', STR_PAD_LEFT);
}

/**
 * Calcule les totaux d'une liste d'articles.
 * Retourne [total_ht, tva, total_ttc].
 */
function calculer_totaux(array $articles): array {
    $total_ht = 0;
    foreach ($articles as $a) {
        $total_ht += $a['sous_total_ht'];
    }
    $tva       = round($total_ht * TAUX_TVA);
    $total_ttc = $total_ht + $tva;
    return [
        'total_ht'  => $total_ht,
        'tva'       => $tva,
        'total_ttc' => $total_ttc,
    ];
}

/**
 * Sauvegarde une nouvelle facture.
 * $articles : tableau de [code_barre, nom, prix_unitaire_ht, quantite, sous_total_ht]
 * Retourne l'identifiant de la facture créée ou un message d'erreur.
 */
function creer_facture(array $articles, string $caissier): string|bool {
    if (empty($articles)) return "Aucun article dans la facture.";

    require_once __DIR__ . '/fonctions-produits.php';

    // Vérification des stocks et décrément
    foreach ($articles as $a) {
        $resultat = decrementer_stock($a['code_barre'], $a['quantite']);
        if ($resultat !== true) return $resultat;
    }

    $totaux = calculer_totaux($articles);
    $id = generer_id_facture();

    $facture = [
        'id_facture' => $id,
        'date'       => date('Y-m-d'),
        'heure'      => date('H:i:s'),
        'caissier'   => $caissier,
        'articles'   => $articles,
        'total_ht'   => $totaux['total_ht'],
        'tva'        => $totaux['tva'],
        'total_ttc'  => $totaux['total_ttc'],
    ];

    $factures = charger_factures();
    $factures[] = $facture;

    return sauvegarder_factures($factures) ? $id : "Erreur lors de l'enregistrement.";
}

/**
 * Recherche une facture par son identifiant.
 */
function trouver_facture(string $id_facture): ?array {
    foreach (charger_factures() as $f) {
        if ($f['id_facture'] === $id_facture) return $f;
    }
    return null;
}

/**
 * Retourne les factures d'une date donnée (format Y-m-d).
 */
function factures_du_jour(string $date = ''): array {
    if (empty($date)) $date = date('Y-m-d');
    return array_values(array_filter(charger_factures(), fn($f) => $f['date'] === $date));
}

/**
 * Retourne les factures d'un mois donné.
 * $mois : format Y-m
 */
function factures_du_mois(string $mois = ''): array {
    if (empty($mois)) $mois = date('Y-m');
    return array_values(array_filter(charger_factures(), fn($f) => substr($f['date'], 0, 7) === $mois));
}
