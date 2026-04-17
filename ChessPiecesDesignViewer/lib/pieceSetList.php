<?php
// lib/pieceSetList.php - Shared piece set functions

function getPieceSets() {
    $piecesDir = __DIR__ . '/../pieces';
    if (!is_dir($piecesDir)) return [];

    $sets = array_filter(glob($piecesDir . '/*'), 'is_dir');
    $result = [];
    foreach ($sets as $setPath) {
        $name = basename($setPath);
        $preview = null;
        
        // Check if any valid piece exists
        $hasValidPieces = false;
        foreach (['svg', 'png', 'webp'] as $ext) {
            if (file_exists("$setPath/wN.$ext")) {
                $preview = "pieces/$name/wN.$ext";
                $hasValidPieces = true;
                break;
            }
        }
        
        // If no preview found, check other pieces
        if (!$hasValidPieces) {
            $pieceCodes = ['wK','wQ','wR','wB','wP','bK','bQ','bR','bB','bN','bP'];
            foreach ($pieceCodes as $code) {
                foreach (['svg', 'png', 'webp'] as $ext) {
                    if (file_exists("$setPath/$code.$ext")) {
                        $hasValidPieces = true;
                        break 2;
                    }
                }
            }
        }
        
        $displayName = $hasValidPieces ? $name : $name . '*';
        
        $result[] = [
            'name' => $displayName, 
            'preview' => $preview,
            'isBroken' => !$hasValidPieces,
            'cleanName' => $name
        ];
    }
    return $result;
}

function getPieceSetInfo($setName) {
    $cleanName = rtrim($setName, '*');
    $setPath = __DIR__ . "/../pieces/$cleanName";
    if (!is_dir($setPath)) return null;

    $pieces = [];
    $pieceCodes = ['wK','wQ','wR','wB','wN','wP','bK','bQ','bR','bB','bN','bP'];
    foreach ($pieceCodes as $code) {
        foreach (['svg', 'png', 'webp'] as $ext) {
            if (file_exists("$setPath/$code.$ext")) {
                $pieces[$code] = "pieces/$cleanName/$code.$ext";
                break;
            }
        }
    }
    return ['name' => $cleanName, 'pieces' => $pieces];
}