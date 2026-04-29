<?php
// rapports/rapport-journalier.php – Rapport des ventes du jour
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../includes/fonctions-auth.php';
require_once __DIR__ . '/../includes/fonctions-produits.php';
require_once __DIR__ . '/../includes/fonctions-factures.php';

verifier_connecte();
verifier_role(ROLE_MANAGER);

$date = $_GET['date'] ?? date('Y-m-d');
// Valider la date
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = date('Y-m-d');

$factures = factures_du_jour($date);

$total_ht  = array_sum(array_column($factures, 'total_ht'));
$total_tva = array_sum(array_column($factures, 'tva'));
$total_ttc = array_sum(array_column($factures, 'total_ttc'));

// Ventes par produit
$ventes_produits = [];
foreach ($factures as $f) {
    foreach ($f['articles'] as $a) {
        $cb = $a['code_barre'];
        if (!isset($ventes_produits[$cb])) {
            $ventes_produits[$cb] = ['nom' => $a['nom'], 'quantite' => 0, 'total_ht' => 0];
        }
        $ventes_produits[$cb]['quantite']  += $a['quantite'];
        $ventes_produits[$cb]['total_ht']  += $a['sous_total_ht'];
    }
}
uasort($ventes_produits, fn($a, $b) => $b['total_ht'] - $a['total_ht']);

$page_titre = 'Rapport journalier – ' . $date;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header d-flex justify-between align-center" style="flex-wrap:wrap;gap:1rem">
    <div>
        <h1>📊 Rapport Journalier</h1>
        <p>Données du <?= date('d/m/Y', strtotime($date)) ?></p>
    </div>
    <div class="d-flex gap-1 align-center">
        <form method="GET" action="" class="d-flex gap-1 align-center">
            <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" style="width:auto">
            <button type="submit" class="btn btn-secondary btn-sm">Filtrer</button>
        </form>
        <a href="rapport-mensuel.php" class="btn btn-secondary">📅 Mensuel</a>
        <button onclick="window.print()" class="btn btn-secondary">🖨</button>
    </div>
</div>

<!-- Statistiques -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Factures</div>
        <div class="stat-value"><?= count($factures) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">CA HT</div>
        <div class="stat-value" style="font-size:1.2rem"><?= formater_prix($total_ht) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">TVA collectée</div>
        <div class="stat-value" style="font-size:1.2rem"><?= formater_prix($total_tva) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">CA TTC</div>
        <div class="stat-value" style="font-size:1.2rem;color:var(--success)"><?= formater_prix($total_ttc) ?></div>
    </div>
</div>

<?php if (empty($factures)): ?>
<div class="alert alert-info">ℹ Aucune facture enregistrée pour le <?= htmlspecialchars($date) ?>.</div>
<?php else: ?>

<!-- Top produits -->
<?php if (!empty($ventes_produits)): ?>
<div class="card">
    <div class="card-title">🏆 Ventes par produit</div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Produit</th><th>Qté vendue</th><th>CA HT</th></tr>
            </thead>
            <tbody>
                <?php foreach ($ventes_produits as $cb => $v): ?>
                <tr>
                    <td><?= htmlspecialchars($v['nom']) ?></td>
                    <td class="text-mono"><?= $v['quantite'] ?></td>
                    <td class="text-mono"><strong><?= formater_prix($v['total_ht']) ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Liste factures -->
<div class="card" style="padding:0">
    <div style="padding:1rem 1rem .5rem;font-family:var(--font-mono);font-size:.75rem;text-transform:uppercase;letter-spacing:1px;color:var(--accent)">
        Détail des factures
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>N° Facture</th><th>Heure</th><th>Caissier</th><th>Articles</th><th>Total TTC</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($factures as $f): ?>
                <tr>
                    <td class="text-mono text-accent"><?= htmlspecialchars($f['id_facture']) ?></td>
                    <td class="text-mono"><?= htmlspecialchars($f['heure']) ?></td>
                    <td><?= htmlspecialchars($f['caissier']) ?></td>
                    <td><?= count($f['articles']) ?></td>
                    <td class="text-mono"><strong><?= formater_prix($f['total_ttc']) ?></strong></td>
                    <td><a href="../modules/facturation/afficher-facture.php?id=<?= urlencode($f['id_facture']) ?>" class="btn btn-sm btn-secondary">Voir</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
