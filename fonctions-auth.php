<?php
// ============================================================
// includes/fonctions-auth.php – Fonctions d'authentification
// ============================================================

require_once __DIR__ . '/../config/config.php';

/**
 * Charge tous les utilisateurs depuis le fichier JSON.
 */
function charger_utilisateurs(): array {
    if (!file_exists(FICHIER_UTILISATEURS)) return [];
    $contenu = file_get_contents(FICHIER_UTILISATEURS);
    return json_decode($contenu, true) ?? [];
}

/**
 * Sauvegarde la liste des utilisateurs dans le fichier JSON.
 */
function sauvegarder_utilisateurs(array $utilisateurs): bool {
    return file_put_contents(
        FICHIER_UTILISATEURS,
        json_encode($utilisateurs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    ) !== false;
}

/**
 * Authentifie un utilisateur par identifiant + mot de passe.
 * Retourne le tableau utilisateur (sans mot_de_passe) ou null.
 */
function authentifier_utilisateur(string $identifiant, string $mot_de_passe): ?array {
    $utilisateurs = charger_utilisateurs();
    foreach ($utilisateurs as $u) {
        if ($u['identifiant'] === $identifiant && ($u['actif'] ?? false)) {
            if (password_verify($mot_de_passe, $u['mot_de_passe'])) {
                // Ne pas stocker le hash en session
                unset($u['mot_de_passe']);
                return $u;
            }
        }
    }
    return null;
}

/**
 * Crée un nouveau compte utilisateur.
 * Retourne true en cas de succès, string (erreur) sinon.
 */
function creer_utilisateur(string $identifiant, string $mot_de_passe, string $role, string $nom_complet): bool|string {
    $utilisateurs = charger_utilisateurs();

    // Vérifier unicité de l'identifiant
    foreach ($utilisateurs as $u) {
        if ($u['identifiant'] === $identifiant) {
            return "L'identifiant « $identifiant » est déjà utilisé.";
        }
    }

    // Validation du rôle
    $roles_valides = [ROLE_CAISSIER, ROLE_MANAGER, ROLE_SUPERADMIN];
    if (!in_array($role, $roles_valides)) {
        return "Rôle invalide.";
    }

    $nouveau = [
        'identifiant'  => $identifiant,
        'mot_de_passe' => password_hash($mot_de_passe, PASSWORD_DEFAULT),
        'role'         => $role,
        'nom_complet'  => $nom_complet,
        'date_creation'=> date('Y-m-d'),
        'actif'        => true,
    ];

    $utilisateurs[] = $nouveau;
    return sauvegarder_utilisateurs($utilisateurs) ? true : "Erreur lors de la sauvegarde.";
}

/**
 * Désactive (suppression logique) un compte utilisateur.
 */
function supprimer_utilisateur(string $identifiant): bool|string {
    $utilisateurs = charger_utilisateurs();
    $trouve = false;
    foreach ($utilisateurs as &$u) {
        if ($u['identifiant'] === $identifiant) {
            if ($u['role'] === ROLE_SUPERADMIN) {
                return "Impossible de supprimer le Super Administrateur.";
            }
            $u['actif'] = false;
            $trouve = true;
            break;
        }
    }
    unset($u);
    if (!$trouve) return "Utilisateur introuvable.";
    return sauvegarder_utilisateurs($utilisateurs) ? true : "Erreur lors de la sauvegarde.";
}

/**
 * Retourne les utilisateurs actifs (sans mot de passe).
 */
function lister_utilisateurs(bool $avec_inactifs = false): array {
    $utilisateurs = charger_utilisateurs();
    $result = [];
    foreach ($utilisateurs as $u) {
        if (!$avec_inactifs && !($u['actif'] ?? false)) continue;
        unset($u['mot_de_passe']);
        $result[] = $u;
    }
    return $result;
}
