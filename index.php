<?php
// index.php – Tableau de bord principal
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/auth/session.php';
require_once __DIR__ . '/includes/fonctions-auth.php';
require_once __DIR__ . '/includes/fonctions-produits.php';
require_once __DIR__ . '/includes/fonctions-factures.php';

verifier_connecte();

$utilisateur = utilisateur_connecte();
$produits    = charger_produits();
$factures_jour = factures_du_jour();

$total_jour = array_sum(array_column($factures_jour, 'total_ttc'));
$nb_produits = count($produits);
$stock_total = array_sum(array_column($produits, 'quantite_stock'));

// Alertes stock faible (< 5)
$stock_faible = array_filter($produits, fn($p) => $p['quantite_stock'] < 5);

$page_titre = 'Tableau de bord – ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1>Bonjour, <?= htmlspecialchars($utilisateur['nom_complet']) ?> 👋</h1>
    <p><?= date('l j F Y', time()) ?> &bull; Rôle : <strong><?= htmlspecialchars($utilisateur['role']) ?></strong></p>
</div>

<!-- Statistiques du jour -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Factures aujourd'hui</div>
        <div class="stat-value"><?= count($factures_jour) ?></div>
        <div class="stat-sub">transactions</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">CA du jour (TTC)</div>
        <div class="stat-value" style="font-size:1.3rem"><?= formater_prix($total_jour) ?></div>
        <div class="stat-sub">chiffre d'affaires</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Produits catalogue</div>
        <div class="stat-value"><?= $nb_produits ?></div>
        <div class="stat-sub">références</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Unités en stock</div>
        <div class="stat-value"><?= number_format($stock_total, 0, ',', ' ') ?></div>
        <div class="stat-sub">total</div>
    </div>
</div>

<!-- Alertes stock -->
<?php if (!empty($stock_faible) && a_role(ROLE_MANAGER)): ?>
<div class="alert alert-warning">
    ⚠ <strong><?= count($stock_faible) ?> produit(s)</strong> ont un stock inférieur à 5 unités :
    <?= implode(', ', array_map(fn($p) => htmlspecialchars($p['nom']) . ' (' . $p['quantite_stock'] . ')', $stock_faible)) ?>
</div>
<?php endif; ?>

<!-- Actions rapides -->
<div class="card">
    <div class="card-title">Actions rapides</div>
    <div class="d-flex gap-2" style="flex-wrap:wrap">
        <?php if (a_role(ROLE_CAISSIER)): ?>
        <a href="modules/facturation/nouvelle-facture.php" class="btn btn-primary">📄 Nouvelle Facture</a>
        <?php endif; ?>
        <?php if (a_role(ROLE_MANAGER)): ?>
        <a href="modules/produits/enregistrer.php" class="btn btn-secondary">➕ Enregistrer un Produit</a>
        <a href="modules/produits/liste.php" class="btn btn-secondary">📦 Catalogue Produits</a>
        <a href="rapports/rapport-journalier.php" class="btn btn-secondary">📊 Rapport du Jour</a>
        <?php endif; ?>
        <?php if (a_role(ROLE_SUPERADMIN)): ?>
        <a href="modules/admin/gestion-comptes.php" class="btn btn-secondary">👥 Gérer les Comptes</a>
        <?php endif; ?>
    </div>
</div>

<!-- Dernières factures du jour -->
<?php if (!empty($factures_jour)): ?>
<div class="card">
    <div class="card-title">Dernières factures du jour</div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>N° Facture</th>
                    <th>Heure</th>
                    <th>Caissier</th>
                    <th>Articles</th>
                    <th>Total TTC</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_reverse($factures_jour) as $f): ?>
                <tr>
                    <td class="text-mono text-accent"><?= htmlspecialchars($f['id_facture']) ?></td>
                    <td class="text-mono"><?= htmlspecialchars($f['heure']) ?></td>
                    <td><?= htmlspecialchars($f['caissier']) ?></td>
                    <td><?= count($f['articles']) ?></td>
                    <td class="text-mono"><strong><?= formater_prix($f['total_ttc']) ?></strong></td>
                    <td>
                        <a href="modules/facturation/afficher-facture.php?id=<?= urlencode($f['id_facture']) ?>"
                           class="btn btn-sm btn-secondary">Voir</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div class="card">
    <p class="text-muted text-mono">Aucune facture enregistrée aujourd'hui.</p>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
