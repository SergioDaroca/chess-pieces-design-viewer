<?php
/**
 * renameChessPieces.php - Smart detection with multi-language support
 *
 * Rules:
 * 1. Color: contains 'w' or any 'white' in multiple languages → WHITE, otherwise BLACK
 * 2. Piece: look for piece patterns (with synonyms and multiple languages)
 * 3. Handles subdirectories (white/black) and removes them after renaming
 * 4. 'b' can ONLY mean bishop (since black is the default color)
 */

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    set_time_limit(0);
    error_reporting(E_ALL);

    $root = $_GET['dir'] ?? '';
    if (!$root) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'No directory specified']);
        exit;
    }

    $result = renamePieceSet($root);
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

function renamePieceSet($root) {
    if (!is_dir($root)) {
        return ['success' => false, 'error' => "Directory not found: $root"];
    }

    $ALLOWED_EXT = ['png', 'svg', 'webp'];

    // Color patterns - any of these means WHITE, otherwise BLACK
    $COLOR_PATTERNS = [
        'w', 'white', 'blanco', 'biau', 'weiss', 'branco', 'blanc', 'wh'
    ];

    // Piece patterns with synonyms (order matters: longer patterns first)
    // Piece patterns with synonyms (order matters: longer and more specific patterns first)
    $PIECE_PATTERNS = [
        // Full words (longest first)
        'knight' => 'N', 'caballo' => 'N', 'paard' => 'N', 'cavalier' => 'N', 'springer' => 'N', 'horse' => 'N', 'jumper' => 'N',
        'bishop' => 'B', 'alfil' => 'B', 'loper' => 'B', 'fou' => 'B', 'laufer' => 'B', 'bispo' => 'B',
        'queen' => 'Q', 'reina' => 'Q', 'koningin' => 'Q', 'dame' => 'Q', 'regina' => 'Q',
        'rook' => 'R', 'torre' => 'R', 'toren' => 'R', 'tour' => 'R', 'turm' => 'R', 'castle' => 'R',
        'pawn' => 'P', 'peon' => 'P', 'pion' => 'P', 'pionek' => 'P', 'bauer' => 'P', 'man' => 'P',
        'king' => 'K', 'rey' => 'K', 'koning' => 'K', 'könig' => 'K', 'roi' => 'K', 're' => 'K',

        // Single letters (check non-ambiguous ones first, 'b' last because it's ambiguous)
        'n' => 'N',  // knight - unambiguous
        'p' => 'P',  // pawn - unambiguous
        'k' => 'K',  // king - unambiguous
        'q' => 'Q',  // queen - unambiguous
        'r' => 'R',  // rook - unambiguous
        'b' => 'B',  // bishop - ambiguous, check last
    ];

    $renamed = [];
    $skipped = [];
    $errors = [];
    $filesToProcess = [];
    $subdirsToRemove = [];

    // ============================================
    // Step 1: Scan directory and subdirectories
    // ============================================
    $scanDir = function($dir, $parentColor = null) use (&$filesToProcess, &$subdirsToRemove, $ALLOWED_EXT, $COLOR_PATTERNS) {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $fullPath = $dir . DIRECTORY_SEPARATOR . $item;

            // Check if this is a color subdirectory
            if (is_dir($fullPath)) {
                $lowerItem = strtolower($item);
                $isColorDir = false;
                foreach ($COLOR_PATTERNS as $pattern) {
                    if ($lowerItem === $pattern) {
                        $subdirsToRemove[] = $fullPath;
                        $scanDir($fullPath, $pattern === 'w' ? 'w' : 'w'); // white subdir
                        $isColorDir = true;
                        break;
                    }
                }
                if ($isColorDir) continue;

                // Also check for 'black' subdirectory
                $blackPatterns = ['black', 'negro', 'noir', 'schwarz', 'preto', 'b'];
                foreach ($blackPatterns as $pattern) {
                    if ($lowerItem === $pattern) {
                        $subdirsToRemove[] = $fullPath;
                        $scanDir($fullPath, 'b');
                        $isColorDir = true;
                        break;
                    }
                }
                if ($isColorDir) continue;
            }

            // Process files
            if (is_file($fullPath)) {
                $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                if (!in_array($ext, $ALLOWED_EXT)) {
                    $errors[] = ['file' => $fullPath, 'error' => "Unsupported extension: .$ext"];
                    continue;
                }

                $filesToProcess[] = [
                    'path' => $fullPath,
                    'basename' => $item,
                    'nameNoExt' => pathinfo($item, PATHINFO_FILENAME),
                    'ext' => $ext,
                    'colorFromDir' => $parentColor
                ];
            }
        }
    };

    $scanDir($root);

    // ============================================
    // Step 2: Detect color and piece for each file
    // ============================================
    foreach ($filesToProcess as &$file) {
        $name = $file['nameNoExt'];
        $lowerName = strtolower($name);

        // Determine COLOR (from directory or filename)
        $color = $file['colorFromDir'];
        if (!$color) {
            // Check if filename contains any white pattern
            $isWhite = false;
            foreach ($COLOR_PATTERNS as $pattern) {
                if (strpos($lowerName, $pattern) !== false) {
                    $isWhite = true;
                    break;
                }
            }
            $color = $isWhite ? 'w' : 'b';
        }

        // Determine PIECE
        $piece = null;
        foreach ($PIECE_PATTERNS as $pattern => $code) {
            if (strpos($lowerName, $pattern) !== false) {
                $piece = $code;
                break;
            }
        }

        // Special case: if only 'b' found and no piece, it's bishop
        if (!$piece && strpos($lowerName, 'b') !== false) {
            $clean = preg_replace('/[^a-z]/', '', $lowerName);
            if ($clean === 'b' || strlen($clean) <= 2) {
                $piece = 'B';
            }
        }

        $file['color'] = $color;
        $file['piece'] = $piece;
    }

    // ============================================
    // Step 3: Rename all files (separate function)
    // ============================================
    $result = doRename($filesToProcess, $root);

    // ============================================
    // Step 4: Remove empty color subdirectories
    // ============================================
    foreach ($subdirsToRemove as $subdir) {
        if (is_dir($subdir)) {
            $remaining = array_diff(scandir($subdir), ['.', '..']);
            if (empty($remaining)) {
                @rmdir($subdir);
            }
        }
    }

    return array_merge($result, ['summary' => [
        'renamed_count' => count($result['renamed'] ?? []),
        'skipped_count' => count($result['skipped'] ?? []),
        'errors_count' => count($result['errors'] ?? []),
    ]]);
}

// ============================================
// Separate rename function
// ============================================
function doRename($filesToProcess, $root) {
    $renamed = [];
    $skipped = [];
    $errors = [];

    foreach ($filesToProcess as $file) {
        // Validate
        if (!$file['color']) {
            $errors[] = ['file' => $file['path'], 'error' => "Could not detect color in: {$file['nameNoExt']}"];
            continue;
        }

        if (!$file['piece']) {
            $errors[] = ['file' => $file['path'], 'error' => "Could not detect piece in: {$file['nameNoExt']}"];
            continue;
        }

        $newName = $file['color'] . $file['piece'] . '.' . $file['ext'];
        $newPath = $root . DIRECTORY_SEPARATOR . $newName;

        // Check if already correct (case-sensitive)
        if (basename($file['path']) === $newName) {
            $skipped[] = ['file' => $file['path'], 'reason' => 'already_correct'];
            continue;
        }

        // Check if target exists
        if (file_exists($newPath)) {
            $errors[] = ['file' => $file['path'], 'error' => "Target exists: $newName"];
            continue;
        }

        // Rename (move to root if was in subdirectory)
        if (@rename($file['path'], $newPath)) {
            $renamed[] = ['from' => $file['path'], 'to' => $newPath];
        } else {
            $errors[] = ['file' => $file['path'], 'error' => "Failed to rename"];
        }
    }

    return [
        'success' => empty($errors),
        'renamed' => $renamed,
        'skipped' => $skipped,
        'errors' => $errors,
    ];
}
