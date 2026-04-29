<?php
// modules/produits/liste.php – Catalogue des produits
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../includes/fonctions-auth.php';
require_once __DIR__ . '/../../includes/fonctions-produits.php';

verifier_connecte();
verifier_role(ROLE_MANAGER);

$erreur = '';
$succes = '';

// Modification de stock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier_stock') {
    $code   = trim($_POST['code_barre'] ?? '');
    $stock  = trim($_POST['nouveau_stock'] ?? '');
    if (!is_numeric($stock) || (int)$stock < 0) {
        $erreur = "Stock invalide.";
    } else {
        $r = modifier_stock($code, (int)$stock);
        $succes = ($r === true) ? "Stock mis à jour." : '';
        $erreur = ($r !== true) ? $r : '';
    }
}

$produits = charger_produits();
$recherche = trim($_GET['q'] ?? '');
if ($recherche) {
    $produits = array_filter($produits, fn($p) =>
        stripos($p['nom'], $recherche) !== false ||
        stripos($p['code_barre'], $recherche) !== false
    );
}

$page_titre = 'Catalogue Produits';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-between align-center" style="flex-wrap:wrap;gap:1rem">
        <div>
            <h1>📦 Catalogue Produits</h1>
            <p><?= count($produits) ?> produit(s) <?= $recherche ? "correspondant à « " . htmlspecialchars($recherche) . " »" : "au total" ?></p>
        </div>
        <a href="enregistrer.php" class="btn btn-primary">➕ Nouveau produit</a>
    </div>
</div>

<?php if ($erreur): ?><div class="alert alert-danger">⚠ <?= htmlspecialchars($erreur) ?></div><?php endif; ?>
<?php if ($succes): ?><div class="alert alert-success">✓ <?= htmlspecialchars($succes) ?></div><?php endif; ?>

<!-- Recherche -->
<div class="card" style="padding:1rem">
    <form method="GET" action="" class="d-flex gap-1">
        <input type="text" name="q" placeholder="Recherche par nom ou code-barres…" value="<?= htmlspecialchars($recherche) ?>" style="flex:1">
        <button type="submit" class="btn btn-secondary">🔍</button>
        <?php if ($recherche): ?>
        <a href="liste.php" class="btn btn-secondary">✕</a>
        <?php endif; ?>
    </form>
</div>

<!-- Tableau produits -->
<div class="card" style="padding:0">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Code-barres</th>
                    <th>Nom</th>
                    <th>Prix HT</th>
                    <th>Stock</th>
                    <th>Expiration</th>
                    <th>Enregistré le</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($produits)): ?>
                <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:2rem">Aucun produit trouvé.</td></tr>
                <?php endif; ?>
                <?php foreach ($produits as $p): ?>
                <?php
                    $stock = $p['quantite_stock'];
                    $stock_class = $stock === 0 ? 'badge-stock-out' : ($stock < 5 ? 'badge-stock-warn' : 'badge-stock-ok');
                    $stock_icon  = $stock === 0 ? '❌' : ($stock < 5 ? '⚠' : '✓');
                    $exp = $p['date_expiration'];
                    $exp_ts = strtotime($exp);
                    $exp_class = $exp_ts < time() ? 'text-danger' : ($exp_ts < strtotime('+30 days') ? 'text-accent' : '');
                ?>
                <tr>
                    <td class="text-mono" style="font-size:.85rem"><?= htmlspecialchars($p['code_barre']) ?></td>
                    <td><strong><?= htmlspecialchars($p['nom']) ?></strong></td>
                    <td class="text-mono"><?= formater_prix($p['prix_unitaire_ht']) ?></td>
                    <td>
                        <span class="<?= $stock_class ?>"><?= $stock_icon ?> <?= $stock ?></span>
                    </td>
                    <td class="text-mono <?= $exp_class ?>"><?= htmlspecialchars($exp) ?></td>
                    <td class="text-mono" style="font-size:.8rem;color:var(--muted)"><?= htmlspecialchars($p['date_enregistrement']) ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-secondary"
                                onclick="modifierStock('<?= htmlspecialchars($p['code_barre']) ?>', '<?= htmlspecialchars($p['nom']) ?>', <?= $stock ?>)">
                                📦 Stock
                            </button>
                            <a href="enregistrer.php?code=<?= urlencode($p['code_barre']) ?>"
                               class="btn btn-sm btn-secondary">✏</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal modification stock -->
<div id="modal-stock" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:500;place-items:center;display:none">
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:6px;padding:2rem;width:100%;max-width:380px;margin:auto">
        <h3 style="margin-bottom:1rem">Modifier le stock</h3>
        <p id="modal-nom" style="color:var(--muted);font-size:.9rem;margin-bottom:1rem"></p>
        <form method="POST" action="">
            <input type="hidden" name="action" value="modifier_stock">
            <input type="hidden" name="code_barre" id="modal-code">
            <div class="form-group mb-2">
                <label for="modal-stock-val">Nouveau stock</label>
                <input type="number" name="nouveau_stock" id="modal-stock-val" min="0" required>
            </div>
            <div class="d-flex gap-1">
                <button type="submit" class="btn btn-primary">💾 Valider</button>
                <button type="button" class="btn btn-secondary" onclick="fermerModal()">Annuler</button>
            </div>
        </form>
    </div>
</div>

<script>
function modifierStock(code, nom, stockActuel) {
    document.getElementById('modal-code').value = code;
    document.getElementById('modal-nom').textContent = nom + ' (stock actuel : ' + stockActuel + ')';
    document.getElementById('modal-stock-val').value = stockActuel;
    const modal = document.getElementById('modal-stock');
    modal.style.display = 'grid';
}
function fermerModal() {
    document.getElementById('modal-stock').style.display = 'none';
}
document.getElementById('modal-stock').addEventListener('click', function(e) {
    if (e.target === this) fermerModal();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
