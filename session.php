<?php
// ============================================================
// auth/session.php – Gestion des sessions et contrôle d'accès
// ============================================================

require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Vérifie que l'utilisateur est connecté.
 * Redirige vers login.php sinon.
 */
function verifier_connecte(): void {
    if (!isset($_SESSION['utilisateur'])) {
        header('Location: ' . chemin_auth('login.php'));
        exit;
    }
    // Vérification de l'expiration de session
    if (isset($_SESSION['derniere_activite']) && (time() - $_SESSION['derniere_activite']) > SESSION_DUREE) {
        session_unset();
        session_destroy();
        header('Location: ' . chemin_auth('login.php') . '?expire=1');
        exit;
    }
    $_SESSION['derniere_activite'] = time();
}

/**
 * Vérifie que l'utilisateur a le rôle minimum requis.
 * @param string $role_minimum Le rôle minimum (caissier|manager|superadmin)
 */
function verifier_role(string $role_minimum): void {
    verifier_connecte();
    $hierarchie = ROLES_HIERARCHIE;
    $role_actuel = $_SESSION['utilisateur']['role'] ?? '';
    $niveau_actuel  = $hierarchie[$role_actuel]  ?? 0;
    $niveau_minimum = $hierarchie[$role_minimum] ?? 999;

    if ($niveau_actuel < $niveau_minimum) {
        $_SESSION['erreur_acces'] = "Accès refusé : votre rôle ne permet pas d'accéder à cette page.";
        header('Location: ' . chemin_base('index.php'));
        exit;
    }
}

/**
 * Retourne l'utilisateur connecté ou null.
 */
function utilisateur_connecte(): ?array {
    return $_SESSION['utilisateur'] ?? null;
}

/**
 * Retourne true si l'utilisateur a au moins le rôle donné.
 */
function a_role(string $role_minimum): bool {
    $hierarchie = ROLES_HIERARCHIE;
    $role_actuel = $_SESSION['utilisateur']['role'] ?? '';
    return ($hierarchie[$role_actuel] ?? 0) >= ($hierarchie[$role_minimum] ?? 999);
}

/**
 * Helpers de chemins relatifs depuis n'importe quel sous-dossier.
 */
function chemin_base(string $fichier = ''): string {
    // Détermine la profondeur du fichier courant par rapport à la racine
    $racine = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    // Cherche "facturation" dans le chemin
    $parts = explode('/', $racine);
    $depth = 0;
    $found = false;
    foreach ($parts as $p) {
        if ($p === 'facturation') { $found = true; continue; }
        if ($found) $depth++;
    }
    $prefix = str_repeat('../', $depth);
    return $prefix . $fichier;
}

function chemin_auth(string $fichier = ''): string {
    return chemin_base('auth/' . $fichier);
}

function chemin_module(string $module, string $fichier = ''): string {
    return chemin_base('modules/' . $module . '/' . $fichier);
}
