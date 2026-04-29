<?php
// ============================================================
// auth/login.php – Page de connexion
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/fonctions-auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Si déjà connecté, rediriger
if (isset($_SESSION['utilisateur'])) {
    header('Location: ../index.php');
    exit;
}

$erreur  = '';
$succes  = '';
$expire  = isset($_GET['expire']) && $_GET['expire'] == '1';
$deconnecte = isset($_GET['deconnecte']) && $_GET['deconnecte'] == '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifiant  = trim($_POST['identifiant']  ?? '');
    $mot_de_passe = trim($_POST['mot_de_passe'] ?? '');

    if (empty($identifiant) || empty($mot_de_passe)) {
        $erreur = 'Veuillez remplir tous les champs.';
    } else {
        $utilisateur = authentifier_utilisateur($identifiant, $mot_de_passe);
        if ($utilisateur) {
            $_SESSION['utilisateur']       = $utilisateur;
            $_SESSION['derniere_activite'] = time();
            header('Location: ../index.php');
            exit;
        } else {
            $erreur = 'Identifiant ou mot de passe incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Connexion – <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@400;700;800&display=swap" rel="stylesheet">
<style>
:root {
    --bg: #0d0d0d;
    --surface: #161616;
    --border: #2a2a2a;
    --accent: #f0c040;
    --accent2: #e05c2a;
    --text: #f0ede8;
    --muted: #888;
    --danger: #e05c2a;
    --success: #4caf6e;
    --radius: 4px;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    background: var(--bg);
    color: var(--text);
    font-family: 'Syne', sans-serif;
    min-height: 100vh;
    display: grid;
    place-items: center;
    background-image: repeating-linear-gradient(
        0deg, transparent, transparent 39px, var(--border) 39px, var(--border) 40px
    ), repeating-linear-gradient(
        90deg, transparent, transparent 39px, var(--border) 39px, var(--border) 40px
    );
}
.login-wrap {
    width: 100%;
    max-width: 420px;
    padding: 1rem;
}
.brand {
    text-align: center;
    margin-bottom: 2rem;
}
.brand-icon {
    width: 60px; height: 60px;
    background: var(--accent);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 1rem;
    font-size: 1.8rem;
}
.brand h1 {
    font-size: 1.5rem;
    font-weight: 800;
    letter-spacing: -0.5px;
}
.brand p { color: var(--muted); font-size: .85rem; margin-top: .3rem; }

.card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 2rem;
}
.alert {
    padding: .75rem 1rem;
    border-radius: var(--radius);
    font-size: .9rem;
    margin-bottom: 1.25rem;
    font-family: 'Space Mono', monospace;
}
.alert-danger  { background: rgba(224,92,42,.15); border: 1px solid var(--danger); color: #ff9a75; }
.alert-warning { background: rgba(240,192,64,.1);  border: 1px solid var(--accent); color: var(--accent); }
.alert-success { background: rgba(76,175,110,.1);  border: 1px solid var(--success); color: var(--success); }

.form-group { margin-bottom: 1.2rem; }
label { display: block; font-size: .8rem; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; margin-bottom: .5rem; color: var(--muted); }
input[type=text], input[type=password] {
    width: 100%;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    color: var(--text);
    font-family: 'Space Mono', monospace;
    font-size: .95rem;
    padding: .75rem 1rem;
    outline: none;
    transition: border-color .2s;
}
input:focus { border-color: var(--accent); }

.btn {
    display: block;
    width: 100%;
    padding: .85rem;
    background: var(--accent);
    color: #000;
    font-family: 'Syne', sans-serif;
    font-weight: 800;
    font-size: 1rem;
    letter-spacing: 1px;
    text-transform: uppercase;
    border: none;
    border-radius: var(--radius);
    cursor: pointer;
    transition: opacity .2s;
}
.btn:hover { opacity: .85; }
.demo-hint {
    margin-top: 1.5rem;
    background: rgba(255,255,255,.03);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1rem;
}
.demo-hint p { font-size: .78rem; color: var(--muted); margin-bottom: .5rem; font-family: 'Space Mono', monospace; }
.demo-hint table { width: 100%; font-size: .78rem; font-family: 'Space Mono', monospace; border-collapse: collapse; }
.demo-hint td { padding: .2rem .4rem; }
.demo-hint td:first-child { color: var(--accent); }
</style>
</head>
<body>
<div class="login-wrap">
    <div class="brand">
        <div class="brand-icon">🛒</div>
        <h1><?= APP_NAME ?></h1>
        <p>Système de caisse informatisé</p>
    </div>

    <?php if ($expire): ?>
        <div class="alert alert-warning">⏱ Votre session a expiré. Veuillez vous reconnecter.</div>
    <?php elseif ($deconnecte): ?>
        <div class="alert alert-success">✓ Déconnexion réussie.</div>
    <?php endif; ?>

    <?php if ($erreur): ?>
        <div class="alert alert-danger">⚠ <?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <div class="card">
        <form method="POST" action="">
            <div class="form-group">
                <label for="identifiant">Identifiant</label>
                <input type="text" id="identifiant" name="identifiant"
                       value="<?= htmlspecialchars($_POST['identifiant'] ?? '') ?>"
                       autocomplete="username" autofocus>
            </div>
            <div class="form-group">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" autocomplete="current-password">
            </div>
            <button type="submit" class="btn">Se connecter</button>
        </form>
    </div>

    <div class="demo-hint">
        <p>// Comptes de démonstration (mot de passe : <strong>password</strong>)</p>
        <table>
            <tr><td>superadmin</td><td>Super Administrateur</td></tr>
            <tr><td>manager.demo</td><td>Manager</td></tr>
            <tr><td>caissier.demo</td><td>Caissier</td></tr>
        </table>
    </div>
</div>
</body>
</html>
