<?php
// modules/facturation/afficher-facture.php – Affichage d'une facture
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../includes/fonctions-auth.php';
require_once __DIR__ . '/../../includes/fonctions-produits.php';
require_once __DIR__ . '/../../includes/fonctions-factures.php';

verifier_connecte();
verifier_role(ROLE_CAISSIER);

$id = trim($_GET['id'] ?? '');
$nouveau = isset($_GET['nouveau']) && $_GET['nouveau'] == '1';

if (empty($id)) {
    header('Location: nouvelle-facture.php');
    exit;
}

$facture = trouver_facture($id);
if (!$facture) {
    $_SESSION['erreur_acces'] = "Facture introuvable : $id";
    header('Location: ../../index.php');
    exit;
}

$page_titre = 'Facture ' . $facture['id_facture'];
require_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($nouveau): ?>
<div class="alert alert-success">✓ Facture enregistrée avec succès !</div>
<?php endif; ?>

<div class="d-flex justify-between align-center mb-2" style="flex-wrap:wrap;gap:1rem">
    <div class="page-header" style="margin-bottom:0">
        <h1>🧾 Facture</h1>
    </div>
    <div class="d-flex gap-1">
        <button onclick="window.print()" class="btn btn-secondary">🖨 Imprimer</button>
        <a href="nouvelle-facture.php" class="btn btn-primary">📄 Nouvelle Facture</a>
    </div>
</div>

<div class="card" id="zone-impression">
    <!-- En-tête facture -->
    <div class="facture-header">
        <div>
            <div style="font-size:1.8rem;font-weight:800;letter-spacing:-1px;margin-bottom:.25rem">
                <?= APP_NAME ?>
            </div>
            <div style="color:var(--muted);font-size:.85rem">Système de caisse informatisé</div>
        </div>
        <div style="text-align:right">
            <div class="facture-id"><?= htmlspecialchars($facture['id_facture']) ?></div>
            <div class="text-mono" style="font-size:.85rem;color:var(--muted);margin-top:.25rem">
                <?= htmlspecialchars($facture['date']) ?> à <?= htmlspecialchars($facture['heure']) ?>
            </div>
            <div style="font-size:.85rem;margin-top:.25rem">
                Caissier : <strong><?= htmlspecialchars($facture['caissier']) ?></strong>
            </div>
        </div>
    </div>

    <!-- Tableau articles -->
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Désignation</th>
                    <th>Prix unit. HT</th>
                    <th>Qté</th>
                    <th>Sous-total HT</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($facture['articles'] as $a): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($a['nom']) ?></strong>
                        <div style="font-size:.75rem;color:var(--muted);font-family:var(--font-mono)"><?= htmlspecialchars($a['code_barre']) ?></div>
                    </td>
                    <td class="text-mono"><?= formater_prix($a['prix_unitaire_ht']) ?></td>
                    <td class="text-mono"><?= (int)$a['quantite'] ?></td>
                    <td class="text-mono"><strong><?= formater_prix($a['sous_total_ht']) ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Totaux -->
    <div class="facture-totaux" style="margin-top:1rem">
        <div class="total-row">
            <span>Total HT</span>
            <span class="text-mono"><?= formater_prix($facture['total_ht']) ?></span>
        </div>
        <div class="total-row">
            <span>TVA (<?= (int)(TAUX_TVA * 100) ?>%)</span>
            <span class="text-mono"><?= formater_prix($facture['tva']) ?></span>
        </div>
        <div class="total-row grand-total">
            <span>Net à payer</span>
            <span><?= formater_prix($facture['total_ttc']) ?></span>
        </div>
    </div>

    <div style="margin-top:1.5rem;border-top:1px solid var(--border);padding-top:1rem;font-size:.8rem;color:var(--muted);text-align:center;font-family:var(--font-mono)">
        Merci de votre visite &bull; <?= APP_NAME ?> &bull; Document généré le <?= date('d/m/Y H:i:s') ?>
    </div>
</div>

<style>
@media print {
    .navbar, .site-footer, .d-flex.justify-between, .btn { display: none !important; }
    body { background: #fff; color: #000; }
    .card { border: 1px solid #ccc; }
    :root { --text:#000; --muted:#666; --accent:#000; --surface:#f9f9f9; --surface2:#eee; --border:#ccc; }
}
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
