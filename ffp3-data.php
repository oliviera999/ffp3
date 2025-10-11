<?php

/**
 * Page de visualisation legacy pour environnement PRODUCTION
 * 
 * Redirige vers la route moderne /aquaponie qui utilise
 * les tables de production (ffp3Data, ffp3Outputs)
 */

// Déterminer l'URL de base
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$basePath = str_replace('/ffp3-data.php', '', $_SERVER['SCRIPT_NAME']);

// Redirection vers la route moderne de production
$redirectUrl = $protocol . '://' . $host . $basePath . '/aquaponie';

// Transférer les paramètres POST si présents
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pour les POST, on doit faire une redirection avec les données
    // On utilise une session temporaire pour transférer les données
    session_start();
    $_SESSION['post_data_transfer'] = $_POST;
    session_write_close();
}

// Transférer les paramètres GET si présents
if (!empty($_GET)) {
    $redirectUrl .= '?' . http_build_query($_GET);
}

header('Location: ' . $redirectUrl);
exit;
