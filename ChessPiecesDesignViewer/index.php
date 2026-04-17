<?php
// index.php - Loads the view with initial data

session_start();

// Parse route for clean URLs
$route = $_GET['route'] ?? '';
$pieceSet = null;

if (preg_match('/^([a-zA-Z0-9_-]+)$/', $route, $matches)) {
    $pieceSet = $matches[1];
    $_SESSION['current_piece_set'] = $pieceSet;
} elseif (!empty($_SESSION['current_piece_set'])) {
    $pieceSet = $_SESSION['current_piece_set'];
}

// Include the library to get piece set data
require_once __DIR__ . '/lib/pieceSetList.php';

// Get current piece set data (pass the clean name without asterisk)
$currentSet = null;
if ($pieceSet) {
    // Try to get the set info
    $currentSet = getPieceSetInfo($pieceSet);
    
    // If not found, maybe it has an asterisk in session? Clean it
    if (!$currentSet) {
        $cleanName = rtrim($pieceSet, '*');
        $currentSet = getPieceSetInfo($cleanName);
    }
}

$allSets = getPieceSets();

// Also pass the requested set name to the view for JavaScript
$requestedSet = $pieceSet ? rtrim($pieceSet, '*') : null;

// Load the view
include __DIR__ . '/views/viewer.php';