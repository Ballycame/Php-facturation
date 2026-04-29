<?php
// modules/admin/ajouter-compte.php – Création d'un nouveau compte
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../includes/fonctions-auth.php';

verifier_connecte();
verifier_role(ROLE_SUPERADMIN);

$erreur = '';
$succes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifiant   = trim($_POST['identifiant']   ?? '');
    $mot_de_passe  = trim($_POST['mot_de_passe']  ?? '');
    $mdp2          = trim($_POST['mot_de_passe2'] ?? '');
    $role          = trim($_POST['role']          ?? '');
    $nom_complet   = trim($_POST['nom_complet']   ?? '');

    if (empty($identifiant) || empty($mot_de_passe) || empty($role) || empty($nom_complet)) {
        $erreur = "Tous les champs sont obligatoires.";
    } elseif ($mot_de_passe !== $mdp2) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($mot_de_passe) < 6) {
        $erreur = "Le mot de passe doit contenir au moins 6 caractères.";
    } elseif (!preg_match('/^[a-z0-9._-]+$/', $identifiant)) {
        $erreur = "Identifiant invalide (lettres minuscules, chiffres, . _ - uniquement).";
    } else {
        $r = creer_utilisateur($identifiant, $mot_de_passe, $role, $nom_complet);
        if ($r === true) {
            $succes = "Compte « $identifiant » créé avec succès.";
        } else {
            $erreur = $r;
        }
    }
}

$page_titre = 'Ajouter un compte';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h1>➕ Ajouter un compte</h1>
    <p><a href="gestion-comptes.php" style="color:var(--accent)">← Retour à la gestion des comptes</a></p>
</div>

<?php if ($erreur): ?><div class="alert alert-danger">⚠ <?= htmlspecialchars($erreur) ?></div><?php endif; ?>
<?php if ($succes): ?><div class="alert alert-success">✓ <?= htmlspecialchars($succes) ?></div><?php endif; ?>

<div class="card" style="max-width:520px">
    <form method="POST" action="">
        <div class="form-grid">
            <div class="form-group form-full">
                <label for="nom_complet">Nom complet *</label>
                <input type="text" id="nom_complet" name="nom_complet"
                       value="<?= htmlspecialchars($_POST['nom_complet'] ?? '') ?>"
                       placeholder="ex: Jean Mbeki">
            </div>
            <div class="form-group form-full">
                <label for="identifiant">Identifiant * <span style="color:var(--muted);font-weight:400">(minuscules, chiffres, . _ -)</span></label>
                <input type="text" id="identifiant" name="identifiant"
                       value="<?= htmlspecialchars($_POST['identifiant'] ?? '') ?>"
                       placeholder="ex: jean.mbeki">
            </div>
            <div class="form-group">
                <label for="mot_de_passe">Mot de passe *</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" autocomplete="new-password">
            </div>
            <div class="form-group">
                <label for="mot_de_passe2">Confirmer le mot de passe *</label>
                <input type="password" id="mot_de_passe2" name="mot_de_passe2" autocomplete="new-password">
            </div>
            <div class="form-group form-full">
                <label for="role">Rôle *</label>
                <select id="role" name="role">
                    <option value="">— Choisir un rôle —</option>
                    <option value="<?= ROLE_CAISSIER ?>"   <?= ($_POST['role'] ?? '') === ROLE_CAISSIER   ? 'selected' : '' ?>>Caissier</option>
                    <option value="<?= ROLE_MANAGER ?>"    <?= ($_POST['role'] ?? '') === ROLE_MANAGER    ? 'selected' : '' ?>>Manager</option>
                    <option value="<?= ROLE_SUPERADMIN ?>" <?= ($_POST['role'] ?? '') === ROLE_SUPERADMIN ? 'selected' : '' ?>>Super Administrateur</option>
                </select>
            </div>
        </div>
        <div class="mt-2 d-flex gap-1">
            <button type="submit" class="btn btn-primary">💾 Créer le compte</button>
            <a href="gestion-comptes.php" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
