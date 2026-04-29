<?php
// modules/admin/supprimer-compte.php – Confirmation de suppression d'un compte
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../includes/fonctions-auth.php';

verifier_connecte();
verifier_role(ROLE_SUPERADMIN);

$erreur = '';
$succes = '';

$identifiant = trim($_GET['id'] ?? $_POST['identifiant'] ?? '');

if (empty($identifiant)) {
    header('Location: gestion-comptes.php');
    exit;
}

// Récupérer l'utilisateur
$tous = lister_utilisateurs(true);
$cible = null;
foreach ($tous as $u) {
    if ($u['identifiant'] === $identifiant) { $cible = $u; break; }
}

if (!$cible) {
    $_SESSION['erreur_acces'] = "Utilisateur introuvable.";
    header('Location: gestion-comptes.php');
    exit;
}

// Vérifications de sécurité
if ($cible['role'] === ROLE_SUPERADMIN) {
    $_SESSION['erreur_acces'] = "Impossible de supprimer un Super Administrateur.";
    header('Location: gestion-comptes.php');
    exit;
}
if ($cible['identifiant'] === utilisateur_connecte()['identifiant']) {
    $_SESSION['erreur_acces'] = "Vous ne pouvez pas supprimer votre propre compte.";
    header('Location: gestion-comptes.php');
    exit;
}

// Traitement de la suppression confirmée
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirme'] ?? '') === '1') {
    $r = supprimer_utilisateur($identifiant);
    if ($r === true) {
        $_SESSION['succes_admin'] = "Compte « $identifiant » désactivé avec succès.";
        header('Location: gestion-comptes.php');
        exit;
    } else {
        $erreur = $r;
    }
}

$role_labels = [
    ROLE_SUPERADMIN => 'Super Administrateur',
    ROLE_MANAGER    => 'Manager',
    ROLE_CAISSIER   => 'Caissier',
];

$page_titre = 'Supprimer un compte';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h1>🗑 Désactiver un compte</h1>
    <p><a href="gestion-comptes.php" style="color:var(--accent)">← Retour à la gestion des comptes</a></p>
</div>

<?php if ($erreur): ?>
<div class="alert alert-danger">⚠ <?= htmlspecialchars($erreur) ?></div>
<?php endif; ?>

<div class="card" style="max-width:480px">
    <div class="alert alert-warning" style="margin-bottom:1.5rem">
        ⚠ Vous êtes sur le point de <strong>désactiver</strong> le compte suivant.
        L'utilisateur ne pourra plus se connecter, mais son historique sera conservé.
    </div>

    <table style="width:100%;margin-bottom:1.5rem;font-size:.9rem">
        <tr>
            <td style="color:var(--muted);padding:.4rem 0;font-family:var(--font-mono);font-size:.8rem;text-transform:uppercase">Identifiant</td>
            <td class="text-mono text-accent"><strong><?= htmlspecialchars($cible['identifiant']) ?></strong></td>
        </tr>
        <tr>
            <td style="color:var(--muted);padding:.4rem 0;font-family:var(--font-mono);font-size:.8rem;text-transform:uppercase">Nom complet</td>
            <td><?= htmlspecialchars($cible['nom_complet']) ?></td>
        </tr>
        <tr>
            <td style="color:var(--muted);padding:.4rem 0;font-family:var(--font-mono);font-size:.8rem;text-transform:uppercase">Rôle</td>
            <td><span class="user-badge role-<?= $cible['role'] ?>"><?= htmlspecialchars($role_labels[$cible['role']] ?? $cible['role']) ?></span></td>
        </tr>
        <tr>
            <td style="color:var(--muted);padding:.4rem 0;font-family:var(--font-mono);font-size:.8rem;text-transform:uppercase">Créé le</td>
            <td class="text-mono"><?= htmlspecialchars($cible['date_creation']) ?></td>
        </tr>
        <tr>
            <td style="color:var(--muted);padding:.4rem 0;font-family:var(--font-mono);font-size:.8rem;text-transform:uppercase">Statut actuel</td>
            <td><?= $cible['actif'] ? '<span style="color:var(--success)">● Actif</span>' : '<span style="color:var(--muted)">○ Déjà inactif</span>' ?></td>
        </tr>
    </table>

    <form method="POST" action="">
        <input type="hidden" name="identifiant" value="<?= htmlspecialchars($identifiant) ?>">
        <input type="hidden" name="confirme" value="1">
        <div class="d-flex gap-1">
            <button type="submit" class="btn btn-danger">🗑 Confirmer la désactivation</button>
            <a href="gestion-comptes.php" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
