<?php
// auth/logout.php – Déconnexion
require_once __DIR__ . '/../config/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
session_unset();
session_destroy();
header('Location: login.php?deconnecte=1');
exit;
