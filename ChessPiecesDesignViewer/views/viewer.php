<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Chess Piece Viewer</title>
    <link rel="stylesheet" href="/ChessViewer/css/style.css?v=<?= time() ?>">
</head>
<body>
    <div class="app">
        <div class="viewer-container">
            <!-- Chess Board -->
            <div class="board-area">
                <div class="chessboard" id="chessboard"></div>
            </div>

            <!-- Controls -->
            <div class="controls-area">
                <div class="set-info">
                    <span class="set-name" id="set-name">
                        <?= htmlspecialchars($currentSet['name'] ?? 'Select a set') ?>
                    </span>
                    <button id="flip-btn" class="flip-btn" title="Flip board">⟳ Flip</button>
                </div>

                <div class="sets-list-container">
                    <div class="sets-header">
                        <span>Piece Sets</span>
                        <button id="refresh-sets-btn" class="refresh-btn" title="Refresh list">↻ Refresh</button>
                    </div>
                    <div class="sets-list" id="sets-list">
                        <?php foreach ($allSets as $set):
                            $isBroken = strpos($set['name'], '*') !== false;
                            $cleanName = $set['cleanName'] ?? rtrim($set['name'], '*');
                        ?>
                        <div class="set-item <?= ($currentSet['name'] ?? '') === $cleanName ? 'active' : '' ?> <?= $isBroken ? 'broken' : '' ?>"
                             data-set="<?= htmlspecialchars($cleanName) ?>">
                            <?php if ($set['preview']): ?>
                            <div class="set-preview" style="background-image: url('<?= $set['preview'] ?>')"></div>
                            <?php else: ?>
                            <div class="set-preview"></div>
                            <?php endif; ?>
                            <span class="set-item-name"><?= htmlspecialchars($cleanName) ?></span>
                            <?php if ($isBroken): ?>
                            <button class="fix-set-btn" data-set="<?= htmlspecialchars($cleanName) ?>" title="Fix piece naming">🔧</button>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="/ChessViewer/js/NotificationCenter.js"></script>
    <script>
        // Pass PHP data to JavaScript before app.js loads
        window.initialCurrentSet = <?= json_encode($currentSet) ?>;
        window.initialAllSets = <?= json_encode($allSets) ?>;
        window.requestedSet = <?= json_encode($requestedSet) ?>;
    </script>
    <script src="/ChessViewer/js/app.js?v=<?= time() ?>"></script>
</body>
</html>
