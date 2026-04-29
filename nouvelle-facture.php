<?php
// modules/facturation/nouvelle-facture.php – Interface de caisse
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../includes/fonctions-auth.php';
require_once __DIR__ . '/../../includes/fonctions-produits.php';
require_once __DIR__ . '/../../includes/fonctions-factures.php';

verifier_connecte();
verifier_role(ROLE_CAISSIER);

$utilisateur = utilisateur_connecte();
$erreur = '';
$facture_id = '';

// Validation et enregistrement de la facture
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'valider') {
    $articles_json = $_POST['articles_json'] ?? '[]';
    $articles = json_decode($articles_json, true) ?? [];

    if (empty($articles)) {
        $erreur = "Aucun article dans la facture.";
    } else {
        // Re-vérifier chaque article
        $articles_valides = [];
        foreach ($articles as $a) {
            $p = trouver_produit($a['code_barre']);
            if (!$p) { $erreur = "Produit introuvable : " . htmlspecialchars($a['code_barre']); break; }
            if ($p['quantite_stock'] < (int)$a['quantite']) {
                $erreur = "Stock insuffisant pour « {$p['nom']} » (disponible : {$p['quantite_stock']}).";
                break;
            }
            $articles_valides[] = [
                'code_barre'       => $p['code_barre'],
                'nom'              => $p['nom'],
                'prix_unitaire_ht' => $p['prix_unitaire_ht'],
                'quantite'         => (int)$a['quantite'],
                'sous_total_ht'    => $p['prix_unitaire_ht'] * (int)$a['quantite'],
            ];
        }

        if (empty($erreur)) {
            $resultat = creer_facture($articles_valides, $utilisateur['identifiant']);
            if (is_string($resultat) && strpos($resultat, 'FAC-') === 0) {
                header('Location: afficher-facture.php?id=' . urlencode($resultat) . '&nouveau=1');
                exit;
            } else {
                $erreur = $resultat;
            }
        }
    }
}

$page_titre = 'Nouvelle Facture';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h1>📄 Nouvelle Facture</h1>
    <p>Scannez les codes-barres des articles pour les ajouter à la facture.</p>
</div>

<?php if ($erreur): ?><div class="alert alert-danger">⚠ <?= htmlspecialchars($erreur) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 380px;gap:1.5rem">

<!-- Colonne gauche : scanner + liste articles -->
<div>
    <!-- Scanner -->
    <div class="card">
        <div class="card-title">📷 Scanner un article</div>
        <div style="display:flex;gap:1.5rem;flex-wrap:wrap;align-items:flex-start">
            <div>
                <div id="scanner-container" class="scanner-box" style="display:none;max-width:320px"></div>
                <div class="scanner-status" id="scanner-status">Caméra inactive.</div>
            </div>
            <div style="flex:1;min-width:220px">
                <div class="form-group mb-2">
                    <label for="code-input">Code-barres</label>
                    <input type="text" id="code-input" placeholder="Scannez ou saisissez…" autocomplete="off" autofocus>
                </div>
                <div class="d-flex gap-1">
                    <button id="btn-cam" class="btn btn-secondary" onclick="toggleCam()">📷 Caméra</button>
                    <button class="btn btn-primary" onclick="ajouterParCode()">➕ Ajouter</button>
                </div>
                <div id="produit-info" style="margin-top:1rem;display:none" class="alert alert-info"></div>
                <div id="qte-wrap" style="margin-top:.75rem;display:none">
                    <div class="form-group mb-1">
                        <label for="qte-input">Quantité</label>
                        <input type="number" id="qte-input" min="1" value="1">
                    </div>
                    <button class="btn btn-success" onclick="confirmerAjout()">✓ Confirmer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste articles -->
    <div class="card" id="card-articles" style="padding:0">
        <div style="padding:1rem 1rem .5rem;font-family:var(--font-mono);font-size:.75rem;text-transform:uppercase;letter-spacing:1px;color:var(--accent)">
            Articles de la facture
        </div>
        <div class="table-wrap">
            <table id="table-articles">
                <thead>
                    <tr>
                        <th>Désignation</th>
                        <th>Prix HT unit.</th>
                        <th>Qté</th>
                        <th>Sous-total HT</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="tbody-articles">
                    <tr id="row-vide"><td colspan="5" style="text-align:center;color:var(--muted);padding:1.5rem">
                        Aucun article – commencez par scanner un produit.
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Colonne droite : récapitulatif -->
<div>
    <div class="card" style="position:sticky;top:80px">
        <div class="card-title">🧾 Récapitulatif</div>

        <div class="total-row">
            <span>Total HT</span>
            <span id="recap-ht" class="text-mono">0 CDF</span>
        </div>
        <div class="total-row">
            <span>TVA (<?= (TAUX_TVA * 100) ?>%)</span>
            <span id="recap-tva" class="text-mono">0 CDF</span>
        </div>
        <div class="total-row grand-total" style="margin-top:.5rem;padding:.75rem 0;border-top:2px solid var(--accent)">
            <span>Net à payer</span>
            <span id="recap-ttc" class="text-mono">0 CDF</span>
        </div>

        <form method="POST" action="" id="form-facture" style="margin-top:1.5rem">
            <input type="hidden" name="action" value="valider">
            <input type="hidden" name="articles_json" id="articles-json">
            <button type="button" onclick="validerFacture()"
                    id="btn-valider" class="btn btn-primary" style="width:100%;justify-content:center" disabled>
                💾 Valider la Facture
            </button>
        </form>

        <button onclick="viderFacture()" class="btn btn-secondary" style="width:100%;justify-content:center;margin-top:.75rem">
            🗑 Vider
        </button>

        <div style="margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--border);font-size:.8rem;color:var(--muted);font-family:var(--font-mono)">
            Caissier : <?= htmlspecialchars($utilisateur['nom_complet']) ?><br>
            <?= date('d/m/Y H:i') ?>
        </div>
    </div>
</div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>
<script src="<?= chemin_base('assets/js/scanner.js') ?>"></script>
<script>
const TVA = <?= TAUX_TVA ?>;
const DEVISE = '<?= DEVISE ?>';
let articles = [];
let produitCourant = null;
let camActive = false;

function formaterPrix(n) {
    return n.toLocaleString('fr-FR', {maximumFractionDigits:0}) + ' ' + DEVISE;
}

function toggleCam() {
    if (!camActive) {
        document.getElementById('scanner-container').style.display = 'block';
        initScanner('scanner-container', function(code) {
            document.getElementById('code-input').value = code;
            stopScanner(); camActive = false;
            document.getElementById('btn-cam').textContent = '📷 Caméra';
            document.getElementById('scanner-container').style.display = 'none';
            rechercherProduit(code);
        }, 'scanner-status');
        camActive = true;
        document.getElementById('btn-cam').textContent = '⏹ Arrêter';
    } else {
        stopScanner(); camActive = false;
        document.getElementById('scanner-container').style.display = 'none';
        document.getElementById('btn-cam').textContent = '📷 Caméra';
    }
}

async function rechercherProduit(code) {
    const infoEl = document.getElementById('produit-info');
    const qteEl  = document.getElementById('qte-wrap');
    infoEl.style.display = 'none';
    qteEl.style.display  = 'none';
    produitCourant = null;

    if (!code) return;

    infoEl.style.display = 'block';
    infoEl.className = 'alert alert-info';
    infoEl.textContent = 'Recherche en cours…';

    const resp = await fetch('<?= chemin_module('produits', 'lire.php') ?>?code=' + encodeURIComponent(code));
    const data = await resp.json();

    if (data.erreur) {
        infoEl.className = 'alert alert-danger';
        infoEl.textContent = '⚠ ' + data.erreur;
        if (data.inconnu && <?= a_role(ROLE_MANAGER) ? 'true' : 'false' ?>) {
            infoEl.innerHTML += ' <a href="<?= chemin_module('produits', 'enregistrer.php') ?>?code=' + encodeURIComponent(code) + '" style="color:var(--accent)">Enregistrer ce produit →</a>';
        }
    } else {
        const p = data.produit;
        produitCourant = p;
        infoEl.className = 'alert alert-success';
        infoEl.textContent = '✓ ' + p.nom + ' — ' + formaterPrix(p.prix_unitaire_ht) + ' HT (stock : ' + p.quantite_stock + ')';
        qteEl.style.display = 'block';
        document.getElementById('qte-input').max = p.quantite_stock;
        document.getElementById('qte-input').value = 1;
        document.getElementById('qte-input').focus();
    }
}

function ajouterParCode() {
    const code = document.getElementById('code-input').value.trim();
    if (!code) return;
    rechercherProduit(code);
}

document.getElementById('code-input').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); ajouterParCode(); }
});
document.getElementById('qte-input')?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); confirmerAjout(); }
});

function confirmerAjout() {
    if (!produitCourant) return;
    const qte = parseInt(document.getElementById('qte-input').value);
    if (isNaN(qte) || qte < 1) { alert('Quantité invalide.'); return; }
    if (qte > produitCourant.quantite_stock) {
        alert('⚠ Stock insuffisant ! Disponible : ' + produitCourant.quantite_stock);
        return;
    }

    // Chercher si l'article existe déjà
    const idx = articles.findIndex(a => a.code_barre === produitCourant.code_barre);
    if (idx >= 0) {
        const total = articles[idx].quantite + qte;
        if (total > produitCourant.quantite_stock) {
            alert('⚠ Total cumulé dépasserait le stock disponible (' + produitCourant.quantite_stock + ').');
            return;
        }
        articles[idx].quantite = total;
        articles[idx].sous_total_ht = articles[idx].prix_unitaire_ht * total;
    } else {
        articles.push({
            code_barre:      produitCourant.code_barre,
            nom:             produitCourant.nom,
            prix_unitaire_ht: produitCourant.prix_unitaire_ht,
            quantite:        qte,
            sous_total_ht:   produitCourant.prix_unitaire_ht * qte,
        });
    }

    rafraichirTable();
    document.getElementById('code-input').value = '';
    document.getElementById('produit-info').style.display = 'none';
    document.getElementById('qte-wrap').style.display = 'none';
    produitCourant = null;
    document.getElementById('code-input').focus();
}

function supprimerArticle(i) {
    articles.splice(i, 1);
    rafraichirTable();
}

function rafraichirTable() {
    const tbody = document.getElementById('tbody-articles');
    const vide  = document.getElementById('row-vide');

    tbody.innerHTML = '';
    if (articles.length === 0) {
        tbody.innerHTML = '<tr id="row-vide"><td colspan="5" style="text-align:center;color:var(--muted);padding:1.5rem">Aucun article – commencez par scanner un produit.</td></tr>';
        document.getElementById('btn-valider').disabled = true;
        document.getElementById('recap-ht').textContent  = '0 ' + DEVISE;
        document.getElementById('recap-tva').textContent = '0 ' + DEVISE;
        document.getElementById('recap-ttc').textContent = '0 ' + DEVISE;
        return;
    }

    articles.forEach((a, i) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><strong>${a.nom}</strong></td>
            <td class="text-mono">${formaterPrix(a.prix_unitaire_ht)}</td>
            <td class="text-mono">${a.quantite}</td>
            <td class="text-mono"><strong>${formaterPrix(a.sous_total_ht)}</strong></td>
            <td><button class="btn btn-sm btn-danger" onclick="supprimerArticle(${i})">✕</button></td>
        `;
        tbody.appendChild(tr);
    });

    const ht  = articles.reduce((s, a) => s + a.sous_total_ht, 0);
    const tva = Math.round(ht * TVA);
    const ttc = ht + tva;

    document.getElementById('recap-ht').textContent  = formaterPrix(ht);
    document.getElementById('recap-tva').textContent = formaterPrix(tva);
    document.getElementById('recap-ttc').textContent = formaterPrix(ttc);
    document.getElementById('btn-valider').disabled = false;
}

function validerFacture() {
    if (articles.length === 0) return;
    document.getElementById('articles-json').value = JSON.stringify(articles);
    document.getElementById('form-facture').submit();
}

function viderFacture() {
    if (articles.length === 0) return;
    if (!confirm('Vider la facture en cours ?')) return;
    articles = [];
    rafraichirTable();
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
