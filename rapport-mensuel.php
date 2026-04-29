<?php
// rapports/rapport-mensuel.php – Rapport mensuel
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../includes/fonctions-auth.php';
require_once __DIR__ . '/../includes/fonctions-produits.php';
require_once __DIR__ . '/../includes/fonctions-factures.php';

verifier_connecte();
verifier_role(ROLE_MANAGER);

$mois = $_GET['mois'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $mois)) $mois = date('Y-m');

$factures = factures_du_mois($mois);

$total_ht  = array_sum(array_column($factures, 'total_ht'));
$total_tva = array_sum(array_column($factures, 'tva'));
$total_ttc = array_sum(array_column($factures, 'total_ttc'));

// Ventes par jour
$par_jour = [];
foreach ($factures as $f) {
    $j = $f['date'];
    if (!isset($par_jour[$j])) $par_jour[$j] = ['nb' => 0, 'ca' => 0];
    $par_jour[$j]['nb']++;
    $par_jour[$j]['ca'] += $f['total_ttc'];
}
ksort($par_jour);

$mois_label = date('F Y', strtotime($mois . '-01'));

$page_titre = 'Rapport mensuel – ' . $mois_label;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header d-flex justify-between align-center" style="flex-wrap:wrap;gap:1rem">
    <div>
        <h1>📅 Rapport Mensuel</h1>
        <p><?= ucfirst($mois_label) ?></p>
    </div>
    <div class="d-flex gap-1 align-center">
        <form method="GET" action="" class="d-flex gap-1 align-center">
            <input type="month" name="mois" value="<?= htmlspecialchars($mois) ?>" style="width:auto">
            <button type="submit" class="btn btn-secondary btn-sm">Filtrer</button>
        </form>
        <a href="rapport-journalier.php" class="btn btn-secondary">📊 Journalier</a>
        <button onclick="window.print()" class="btn btn-secondary">🖨</button>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Jours d'activité</div>
        <div class="stat-value"><?= count($par_jour) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Factures</div>
        <div class="stat-value"><?= count($factures) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">CA HT</div>
        <div class="stat-value" style="font-size:1.1rem"><?= formater_prix($total_ht) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">CA TTC</div>
        <div class="stat-value" style="font-size:1.1rem;color:var(--success)"><?= formater_prix($total_ttc) ?></div>
    </div>
</div>

<?php if (empty($factures)): ?>
<div class="alert alert-info">ℹ Aucune facture pour <?= htmlspecialchars($mois_label) ?>.</div>
<?php else: ?>
<div class="card" style="padding:0">
    <div style="padding:1rem 1rem .5rem;font-family:var(--font-mono);font-size:.75rem;text-transform:uppercase;letter-spacing:1px;color:var(--accent)">
        Activité par jour
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Date</th><th>Factures</th><th>CA TTC</th><th>Détail</th></tr></thead>
            <tbody>
                <?php foreach ($par_jour as $date => $info): ?>
                <tr>
                    <td class="text-mono"><?= date('d/m/Y', strtotime($date)) ?></td>
                    <td><?= $info['nb'] ?></td>
                    <td class="text-mono"><strong><?= formater_prix($info['ca']) ?></strong></td>
                    <td><a href="rapport-journalier.php?date=<?= urlencode($date) ?>" class="btn btn-sm btn-secondary">Voir</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:var(--surface2);font-weight:700">
                    <td class="text-mono">TOTAL</td>
                    <td><?= count($factures) ?></td>
                    <td class="text-mono text-accent"><?= formater_prix($total_ttc) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
