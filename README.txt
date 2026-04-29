===========================================================
  SuperCaisse – Système de Facturation avec Codes-Barres
  Université Protestante au Congo – L2 FASI 2025-2026
===========================================================

INSTRUCTIONS DE DEPLOIEMENT LOCAL
===================================

PRÉREQUIS
----------
- PHP >= 8.0 (avec extensions : json, session)
- Serveur web : Apache, Nginx OU PHP built-in server
- Navigateur moderne avec accès caméra (HTTPS ou localhost)

DÉPLOIEMENT RAPIDE (PHP built-in server)
------------------------------------------
1. Placer le dossier "facturation/" dans votre répertoire web
2. Ouvrir un terminal dans le dossier parent de "facturation/"
3. Exécuter :
     php -S localhost:8080 -t facturation/
4. Ouvrir http://localhost:8080 dans votre navigateur

DÉPLOIEMENT AVEC WAMP/XAMPP/MAMP
-----------------------------------
1. Copier le dossier "facturation/" dans :
   - WAMP/XAMPP : C:\wamp64\www\ ou C:\xampp\htdocs\
   - MAMP       : /Applications/MAMP/htdocs/
2. Démarrer le serveur Apache
3. Ouvrir http://localhost/facturation/

DÉPLOIEMENT AVEC LARAGON
--------------------------
1. Copier le dossier "facturation/" dans C:\laragon\www\
2. Ouvrir http://facturation.test/ (ou http://localhost/facturation/)

PERMISSIONS FICHIERS (Linux/Mac)
----------------------------------
chmod 664 data/produits.json data/factures.json data/utilisateurs.json
chmod 755 data/

COMPTES DE DÉMONSTRATION
--------------------------
  Identifiant    | Mot de passe | Rôle
  ---------------+--------------+------------------
  superadmin     | password     | Super Administrateur
  manager.demo   | password     | Manager
  caissier.demo  | password     | Caissier

NOTE : Le mot de passe "password" correspond au hash bcrypt dans
data/utilisateurs.json. Pour changer un mot de passe, modifier le
hash avec password_hash() en PHP.

STRUCTURE DES DONNÉES
----------------------
Toutes les données sont stockées dans data/ au format JSON :
- produits.json    : catalogue des produits
- factures.json    : historique des factures
- utilisateurs.json: comptes utilisateurs (mots de passe hachés)

FONCTIONNALITÉS
----------------
✓ Lecture codes-barres via caméra (QuaggaJS)
✓ Saisie manuelle de codes-barres
✓ Gestion du catalogue produits (CRUD)
✓ Facturation avec calcul TVA (18%)
✓ Gestion du stock (décrémentation automatique)
✓ Système d'authentification RBAC (3 rôles)
✓ Rapports journaliers et mensuels
✓ Impression des factures

SCANNER CODES-BARRES
---------------------
- Nécessite une connexion HTTPS ou localhost (contrainte navigateur)
- Bibliothèque : QuaggaJS v0.12.1 (CDN cloudflare)
- Formats supportés : EAN-13, EAN-8, Code128, Code39, UPC-A, UPC-E
- Fallback : saisie manuelle disponible sur toutes les pages

CONTACT / SOUMISSION
---------------------
Rapport PDF à envoyer à : programmationwebphp@gmail.com
Code source sur GitHub : https://github.com/[votre-repo]
===========================================================
