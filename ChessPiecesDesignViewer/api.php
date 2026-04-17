<?php
// api.php - All AJAX endpoints with debugging

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate");

// Debug: log the request
error_log("API Request: " . $_SERVER['QUERY_STRING']);

require_once __DIR__ . '/lib/pieceSetList.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'refresh':
        $sets = getPieceSets();
        error_log("Refresh returned " . count($sets) . " sets");
        echo json_encode(['sets' => $sets]);
        break;
        
    case 'getPieceSet':
        $name = $_GET['name'] ?? '';
        if ($name) {
            $setInfo = getPieceSetInfo($name);
            echo json_encode($setInfo);
        } else {
            echo json_encode(['error' => 'No set name provided']);
        }
        break;
        
    case 'rename':
        $setName = $_GET['set'] ?? '';
        if (!$setName) {
            echo json_encode(['success' => false, 'error' => 'No set specified']);
            break;
        }
        
        $setPath = __DIR__ . "/pieces/$setName";
        error_log("Rename requested for: $setPath");
        
        if (!is_dir($setPath)) {
            echo json_encode(['success' => false, 'error' => "Set not found: $setPath"]);
            break;
        }
        
        require_once __DIR__ . '/lib/renameChessPieces.php';
        $result = renamePieceSet($setPath);
        echo json_encode($result);
        break;
        
    default:
        echo json_encode(['error' => 'Unknown action: ' . $action]);
}