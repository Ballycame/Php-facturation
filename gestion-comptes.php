<?php
// modules/admin/gestion-comptes.php – Gestion des comptes utilisateurs
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../includes/fonctions-auth.php';

verifier_connecte();
verifier_role(ROLE_SUPERADMIN);

$erreur = '';
$succes = '';

// Récupérer les messages flash de session
if (!empty($_SESSION['succes_admin'])) {
    $succes = $_SESSION['succes_admin'];
    unset($_SESSION['succes_admin']);
}

$utilisateurs = lister_utilisateurs(true); // avec inactifs

$role_labels = [
    ROLE_SUPERADMIN => 'Super Admin',
    ROLE_MANAGER    => 'Manager',
    ROLE_CAISSIER   => 'Caissier',
];

$page_titre = 'Gestion des Comptes';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header d-flex justify-between align-center" style="flex-wrap:wrap;gap:1rem">
    <div>
        <h1>👥 Gestion des Comptes</h1>
        <p><?= count(array_filter($utilisateurs, fn($u) => $u['actif'])) ?> compte(s) actif(s)</p>
    </div>
    <a href="ajouter-compte.php" class="btn btn-primary">➕ Ajouter un compte</a>
</div>

<?php if ($erreur): ?><div class="alert alert-danger">⚠ <?= htmlspecialchars($erreur) ?></div><?php endif; ?>
<?php if ($succes): ?><div class="alert alert-success">✓ <?= htmlspecialchars($succes) ?></div><?php endif; ?>

<div class="card" style="padding:0">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Identifiant</th>
                    <th>Nom complet</th>
                    <th>Rôle</th>
                    <th>Créé le</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($utilisateurs as $u): ?>
                <tr style="<?= !$u['actif'] ? 'opacity:.5' : '' ?>">
                    <td class="text-mono"><?= htmlspecialchars($u['identifiant']) ?></td>
                    <td><?= htmlspecialchars($u['nom_complet']) ?></td>
                    <td>
                        <span class="user-badge role-<?= $u['role'] ?>">
                            <?= htmlspecialchars($role_labels[$u['role']] ?? $u['role']) ?>
                        </span>
                    </td>
                    <td class="text-mono" style="font-size:.85rem"><?= htmlspecialchars($u['date_creation']) ?></td>
                    <td>
                        <?php if ($u['actif']): ?>
                        <span style="color:var(--success);font-family:var(--font-mono);font-size:.8rem">● Actif</span>
                        <?php else: ?>
                        <span style="color:var(--muted);font-family:var(--font-mono);font-size:.8rem">○ Inactif</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($u['actif'] && $u['role'] !== ROLE_SUPERADMIN && $u['identifiant'] !== utilisateur_connecte()['identifiant']): ?>
                        <a href="supprimer-compte.php?id=<?= urlencode($u['identifiant']) ?>"
                           class="btn btn-sm btn-danger">🗑 Désactiver</a>
                        <?php else: ?>
                        <span style="color:var(--muted);font-size:.8rem">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
