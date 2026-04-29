<?php
// ============================================================
// includes/fonctions-produits.php – CRUD Produits (fichiers)
// ============================================================

require_once __DIR__ . '/../config/config.php';

/**
 * Charge tous les produits depuis le fichier JSON.
 */
function charger_produits(): array {
    if (!file_exists(FICHIER_PRODUITS)) return [];
    $contenu = file_get_contents(FICHIER_PRODUITS);
    return json_decode($contenu, true) ?? [];
}

/**
 * Sauvegarde la liste des produits.
 */
function sauvegarder_produits(array $produits): bool {
    return file_put_contents(
        FICHIER_PRODUITS,
        json_encode(array_values($produits), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    ) !== false;
}

/**
 * Recherche un produit par code-barres.
 * Retourne le produit ou null.
 */
function trouver_produit(string $code_barre): ?array {
    $produits = charger_produits();
    foreach ($produits as $p) {
        if ($p['code_barre'] === $code_barre) return $p;
    }
    return null;
}

/**
 * Enregistre un nouveau produit (ou met à jour si code-barres existant).
 * Retourne true ou message d'erreur.
 */
function enregistrer_produit(array $donnees): bool|string {
    $erreurs = valider_produit($donnees);
    if (!empty($erreurs)) return implode(' | ', $erreurs);

    $produits = charger_produits();

    // Mise à jour si le code-barres existe déjà
    foreach ($produits as &$p) {
        if ($p['code_barre'] === $donnees['code_barre']) {
            $p['nom']              = $donnees['nom'];
            $p['prix_unitaire_ht'] = (float)$donnees['prix_unitaire_ht'];
            $p['date_expiration']  = $donnees['date_expiration'];
            $p['quantite_stock']   = (int)$donnees['quantite_stock'];
            return sauvegarder_produits($produits) ? true : "Erreur d'écriture.";
        }
    }
    unset($p);

    // Nouveau produit
    $produits[] = [
        'code_barre'        => $donnees['code_barre'],
        'nom'               => $donnees['nom'],
        'prix_unitaire_ht'  => (float)$donnees['prix_unitaire_ht'],
        'date_expiration'   => $donnees['date_expiration'],
        'quantite_stock'    => (int)$donnees['quantite_stock'],
        'date_enregistrement' => date('Y-m-d'),
    ];

    return sauvegarder_produits($produits) ? true : "Erreur d'écriture.";
}

/**
 * Décrémente le stock d'un produit après une vente.
 * Retourne true ou message d'erreur.
 */
function decrementer_stock(string $code_barre, int $quantite): bool|string {
    $produits = charger_produits();
    foreach ($produits as &$p) {
        if ($p['code_barre'] === $code_barre) {
            if ($p['quantite_stock'] < $quantite) {
                return "Stock insuffisant (disponible : {$p['quantite_stock']}).";
            }
            $p['quantite_stock'] -= $quantite;
            return sauvegarder_produits($produits) ? true : "Erreur d'écriture.";
        }
    }
    return "Produit introuvable.";
}

/**
 * Met à jour le stock (ajout manuel par Manager).
 */
function modifier_stock(string $code_barre, int $nouveau_stock): bool|string {
    if ($nouveau_stock < 0) return "Le stock ne peut pas être négatif.";
    $produits = charger_produits();
    foreach ($produits as &$p) {
        if ($p['code_barre'] === $code_barre) {
            $p['quantite_stock'] = $nouveau_stock;
            return sauvegarder_produits($produits) ? true : "Erreur d'écriture.";
        }
    }
    return "Produit introuvable.";
}

/**
 * Validation des données d'un produit.
 * Retourne un tableau de messages d'erreur (vide = OK).
 */
function valider_produit(array $d): array {
    $erreurs = [];
    if (empty(trim($d['code_barre'] ?? ''))) $erreurs[] = "Code-barres requis.";
    if (empty(trim($d['nom'] ?? '')))        $erreurs[] = "Nom requis.";
    $prix = $d['prix_unitaire_ht'] ?? '';
    if (!is_numeric($prix) || (float)$prix <= 0) $erreurs[] = "Prix unitaire invalide (doit être > 0).";
    $qte = $d['quantite_stock'] ?? '';
    if (!is_numeric($qte) || (int)$qte < 0) $erreurs[] = "Quantité invalide (doit être ≥ 0).";
    $date = $d['date_expiration'] ?? '';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !checkdate(
        (int)substr($date,5,2), (int)substr($date,8,2), (int)substr($date,0,4)
    )) {
        $erreurs[] = "Date d'expiration invalide (format attendu : AAAA-MM-JJ).";
    }
    return $erreurs;
}

/**
 * Formate un prix en CDF.
 */
function formater_prix(float $montant): string {
    return number_format($montant, 0, ',', ' ') . ' ' . DEVISE;
}
