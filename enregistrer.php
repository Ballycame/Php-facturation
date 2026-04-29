<?php
// modules/produits/enregistrer.php – Enregistrement / mise à jour d'un produit
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../includes/fonctions-auth.php';
require_once __DIR__ . '/../../includes/fonctions-produits.php';

verifier_connecte();
verifier_role(ROLE_MANAGER);

$erreur  = '';
$succes  = '';
$produit_existant = null;
$code_barre_pre   = '';

// Réception du code-barres depuis le scanner (AJAX ou GET)
if (isset($_GET['code'])) {
    $code_barre_pre = trim($_GET['code']);
    $produit_existant = trouver_produit($code_barre_pre);
}

// Traitement du formulaire d'enregistrement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $donnees = [
        'code_barre'       => trim($_POST['code_barre']       ?? ''),
        'nom'              => trim($_POST['nom']              ?? ''),
        'prix_unitaire_ht' => trim($_POST['prix_unitaire_ht'] ?? ''),
        'date_expiration'  => trim($_POST['date_expiration']  ?? ''),
        'quantite_stock'   => trim($_POST['quantite_stock']   ?? ''),
    ];

    $resultat = enregistrer_produit($donnees);
    if ($resultat === true) {
        $succes = "Produit « {$donnees['nom']} » enregistré avec succès.";
        $produit_existant = trouver_produit($donnees['code_barre']);
        $code_barre_pre = $donnees['code_barre'];
    } else {
        $erreur = $resultat;
        $code_barre_pre = $donnees['code_barre'];
    }
}

$page_titre = 'Enregistrer un produit';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h1>📦 Enregistrement Produit</h1>
    <p>Scannez le code-barres ou saisissez-le manuellement pour enregistrer un produit.</p>
</div>

<?php if ($erreur):  ?><div class="alert alert-danger">⚠ <?= htmlspecialchars($erreur) ?></div><?php endif; ?>
<?php if ($succes):  ?><div class="alert alert-success">✓ <?= htmlspecialchars($succes) ?></div><?php endif; ?>

<!-- Scanner -->
<div class="card">
    <div class="card-title">📷 Lecture code-barres</div>
    <div style="display:flex;gap:1.5rem;flex-wrap:wrap;align-items:flex-start">
        <div>
            <div id="scanner-container" class="scanner-box" style="display:none">
                <div class="scanner-overlay">
                    <div class="scanner-frame"></div>
                    <div class="scanner-line"></div>
                </div>
            </div>
            <div class="scanner-status" id="scanner-status">Caméra inactive.</div>
        </div>
        <div style="flex:1;min-width:240px">
            <div class="form-group mb-2">
                <label for="code-barre-input">Code-barres (saisie manuelle)</label>
                <input type="text" id="code-barre-input"
                       placeholder="ex: 3017620422003"
                       value="<?= htmlspecialchars($code_barre_pre) ?>"
                       autocomplete="off">
            </div>
            <div class="d-flex gap-1" style="flex-wrap:wrap">
                <button id="btn-scanner" class="btn btn-secondary" onclick="toggleScanner()">📷 Activer caméra</button>
                <button class="btn btn-primary" onclick="rechercherCode()">🔍 Rechercher</button>
            </div>
        </div>
    </div>
</div>

<!-- Informations produit trouvé -->
<?php if (!empty($code_barre_pre) && $produit_existant && empty($_POST)): ?>
<div class="alert alert-info">
    ℹ Ce code-barres est déjà référencé. Vous pouvez modifier les informations ci-dessous.
</div>
<?php endif; ?>

<!-- Formulaire -->
<?php if (!empty($code_barre_pre) || !empty($_POST)): ?>
<div class="card">
    <div class="card-title">✏ Informations du produit</div>
    <form method="POST" action="">
        <input type="hidden" name="code_barre" value="<?= htmlspecialchars($_POST['code_barre'] ?? $code_barre_pre) ?>">

        <div style="margin-bottom:1rem;padding:.75rem;background:var(--bg);border-radius:4px;font-family:var(--font-mono);font-size:.9rem">
            Code-barres : <strong style="color:var(--accent)"><?= htmlspecialchars($_POST['code_barre'] ?? $code_barre_pre) ?></strong>
        </div>

        <div class="form-grid">
            <div class="form-group form-full">
                <label for="nom">Nom du produit *</label>
                <input type="text" id="nom" name="nom" required
                       value="<?= htmlspecialchars($_POST['nom'] ?? $produit_existant['nom'] ?? '') ?>"
                       placeholder="ex: Huile de palme 1L">
            </div>
            <div class="form-group">
                <label for="prix_unitaire_ht">Prix unitaire HT (<?= DEVISE ?>) *</label>
                <input type="number" id="prix_unitaire_ht" name="prix_unitaire_ht"
                       min="0.01" step="0.01" required
                       value="<?= htmlspecialchars($_POST['prix_unitaire_ht'] ?? $produit_existant['prix_unitaire_ht'] ?? '') ?>"
                       placeholder="ex: 1200">
            </div>
            <div class="form-group">
                <label for="quantite_stock">Quantité en stock *</label>
                <input type="number" id="quantite_stock" name="quantite_stock"
                       min="0" step="1" required
                       value="<?= htmlspecialchars($_POST['quantite_stock'] ?? $produit_existant['quantite_stock'] ?? '') ?>"
                       placeholder="ex: 50">
            </div>
            <div class="form-group form-full">
                <label for="date_expiration">Date d'expiration (AAAA-MM-JJ) *</label>
                <input type="date" id="date_expiration" name="date_expiration" required
                       value="<?= htmlspecialchars($_POST['date_expiration'] ?? $produit_existant['date_expiration'] ?? '') ?>">
            </div>
        </div>

        <div class="mt-2 d-flex gap-1">
            <button type="submit" class="btn btn-primary">
                💾 <?= $produit_existant ? 'Mettre à jour' : 'Enregistrer' ?>
            </button>
            <a href="enregistrer.php" class="btn btn-secondary">↺ Nouveau</a>
            <a href="liste.php" class="btn btn-secondary">📦 Voir le catalogue</a>
        </div>
    </form>
</div>
<?php else: ?>
<div class="card">
    <p class="text-muted text-mono">Scannez ou saisissez un code-barres pour commencer.</p>
</div>
<?php endif; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>
<script src="<?= chemin_base('assets/js/scanner.js') ?>"></script>
<script>
let scannerActif = false;

function toggleScanner() {
    if (!scannerActif) {
        document.getElementById('scanner-container').style.display = 'block';
        initScanner('scanner-container', function(code) {
            document.getElementById('code-barre-input').value = code;
            stopScanner();
            scannerActif = false;
            document.getElementById('btn-scanner').textContent = '📷 Activer caméra';
            document.getElementById('scanner-container').style.display = 'none';
            rechercherCode();
        }, 'scanner-status');
        scannerActif = true;
        document.getElementById('btn-scanner').textContent = '⏹ Arrêter caméra';
    } else {
        stopScanner();
        scannerActif = false;
        document.getElementById('scanner-container').style.display = 'none';
        document.getElementById('btn-scanner').textContent = '📷 Activer caméra';
        document.getElementById('scanner-status').textContent = 'Caméra inactive.';
    }
}

function rechercherCode() {
    const code = document.getElementById('code-barre-input').value.trim();
    if (!code) { alert('Veuillez saisir un code-barres.'); return; }
    window.location.href = 'enregistrer.php?code=' + encodeURIComponent(code);
}

document.getElementById('code-barre-input').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); rechercherCode(); }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
