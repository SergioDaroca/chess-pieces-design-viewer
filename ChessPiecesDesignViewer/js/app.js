// ============================================
// app.js - Observers and DOM setup
// ============================================

// Global state
let boardState = {};
let orientation = 'white';
let currentSet = null;
let currentSetName = null;

// ============================================
// Board Core Functions
// ============================================

function getEmptyBoard() {
    const files = ['a','b','c','d','e','f','g','h'];
    const board = {};
    for (let i = 0; i < 8; i++) {
        for (let j = 0; j < 8; j++) {
            board[files[j] + (8 - i)] = '';
        }
    }
    return board;
}

function setStartingPosition() {
    const files = ['a','b','c','d','e','f','g','h'];
    const board = getEmptyBoard();

    for (const file of files) {
        board[file + '2'] = 'P';
        board[file + '7'] = 'p';
    }

    const pieces = ['R','N','B','Q','K','B','N','R'];
    for (let i = 0; i < files.length; i++) {
        board[files[i] + '1'] = pieces[i];
        board[files[i] + '8'] = pieces[i].toLowerCase();
    }

    return board;
}

function renderBoard() {
    const boardEl = document.getElementById('chessboard');
    if (!boardEl) return;

    boardEl.innerHTML = '';
    const files = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
    const ranks = ['8', '7', '6', '5', '4', '3', '2', '1'];
    const squares = [];

    for (const rank of ranks) {
        for (const file of files) {
            const square = file + rank;
            const piece = boardState[square] || '';
            const isLight = (files.indexOf(file) + ranks.indexOf(rank)) % 2 === 0;
            squares.push({ piece, isLight });
        }
    }

    if (orientation === 'black') squares.reverse();

    for (const { piece, isLight } of squares) {
        const div = document.createElement('div');
        div.className = `square ${isLight ? 'light' : 'dark'}`;

        if (piece && currentSet && currentSet.pieces) {
            const pieceCode = piece === piece.toUpperCase() ? 'w' + piece.toUpperCase() : 'b' + piece.toUpperCase();
            const pieceUrl = currentSet.pieces[pieceCode];

            if (pieceUrl) {
                const img = document.createElement('div');
                img.className = 'piece-img';
                img.style.backgroundImage = `url('${pieceUrl}')`;
                div.appendChild(img);
            } else {
                const unicode = {
                    'K':'♔','Q':'♕','R':'♖','B':'♗','N':'♘','P':'♙',
                    'k':'♚','q':'♛','r':'♜','b':'♝','n':'♞','p':'♟'
                };
                div.textContent = unicode[piece] || piece;
                div.style.fontSize = 'clamp(20px, 8vw, 48px)';
            }
        } else if (piece) {
            const unicode = {
                'K':'♔','Q':'♕','R':'♖','B':'♗','N':'♘','P':'♙',
                'k':'♚','q':'♛','r':'♜','b':'♝','n':'♞','p':'♟'
            };
            div.textContent = unicode[piece] || piece;
            div.style.fontSize = 'clamp(20px, 8vw, 48px)';
        }

        boardEl.appendChild(div);
    }
}

// ============================================
// Observers - Respond to notifications
// ============================================

// Flip board request
NotificationCenter.observe('flipBoardRequested', () => {
    orientation = orientation === 'white' ? 'black' : 'white';
    renderBoard();
    NotificationCenter.postNotification('boardDidFlip', { orientation });
});

// Load piece set request
NotificationCenter.observe('loadPieceSetRequested', async (notification) => {
    const { setName } = notification.userInfo;

    try {
        window.history.pushState({}, '', '/ChessViewer/' + encodeURIComponent(setName));

        const response = await fetch(`/ChessViewer/api.php?action=getPieceSet&name=${encodeURIComponent(setName)}`);
        if (response.ok) {
            currentSet = await response.json();
            currentSetName = setName;
            document.getElementById('set-name').textContent = setName;
            renderBoard();

            NotificationCenter.postNotification('pieceSetDidLoad', {
                setName,
                pieces: currentSet.pieces
            });
        }
    } catch (error) {
        NotificationCenter.postNotification('pieceSetLoadDidFail', { setName, error });
    }
});

// Refresh list request
NotificationCenter.observe('refreshPieceSetListRequested', async () => {
    NotificationCenter.postNotification('pieceSetListWillRefresh');
    try {
        const response = await fetch('/ChessViewer/api.php?action=refresh');
        const data = await response.json();
        NotificationCenter.postNotification('pieceSetListDidRefresh', { sets: data.sets });
    } catch (error) {
        NotificationCenter.postNotification('pieceSetListRefreshDidFail', { error });
    }
});

// Rename piece set request
NotificationCenter.observe('renamePieceSetRequested', async (notification) => {
    const { setName } = notification.userInfo;
    NotificationCenter.postNotification('pieceSetWillRename', { setName });

    try {
        const response = await fetch(`/ChessViewer/api.php?action=rename&set=${encodeURIComponent(setName)}`);
        const data = await response.json();

        if (data.success) {
            NotificationCenter.postNotification('pieceSetDidRename', {
                setName,
                renamedCount: data.summary?.renamed_count || 0
            });
        } else {
            NotificationCenter.postNotification('pieceSetRenameDidFail', { setName, error: data.error });
        }
    } catch (error) {
        NotificationCenter.postNotification('pieceSetRenameDidFail', { setName, error });
    }
});

// Update UI after list refresh
NotificationCenter.observe('pieceSetListDidRefresh', (notification) => {
    const { sets } = notification.userInfo;
    const setsList = document.getElementById('sets-list');

    setsList.innerHTML = sets.map(set => {
        const isBroken = set.name.includes('*');
        const cleanName = set.cleanName || set.name.replace('*', '');
        return `
            <div class="set-item ${cleanName === currentSetName ? 'active' : ''} ${isBroken ? 'broken' : ''}"
                 data-set="${escapeHtml(cleanName)}">
                ${set.preview ? `<div class="set-preview" style="background-image: url('${set.preview}')"></div>` : '<div class="set-preview"></div>'}
                <span class="set-item-name">${escapeHtml(cleanName)}</span>
                ${isBroken ? `<button class="fix-set-btn" data-set="${escapeHtml(cleanName)}" title="Fix piece naming">🔧</button>` : ''}
            </div>
        `;
    }).join('');

    attachDOMEventHandlers();
});

// After rename, refresh the list
NotificationCenter.observe('pieceSetDidRename', async (notification) => {
    const { setName, renamedCount } = notification.userInfo;
    console.log(`Renamed ${renamedCount} files in ${setName}`);
    NotificationCenter.postNotification('refreshPieceSetListRequested');

    // Reload the set if it was current
    if (currentSetName === setName) {
        NotificationCenter.postNotification('loadPieceSetRequested', { setName });
    }
});

// ============================================
// DOM Event Handlers
// ============================================

function attachDOMEventHandlers() {
    // Flip button
    const flipBtn = document.getElementById('flip-btn');
    if (flipBtn) {
        flipBtn.removeEventListener('click', flipHandler);
        flipBtn.addEventListener('click', flipHandler);
    }

    // Refresh button
    const refreshBtn = document.getElementById('refresh-sets-btn');
    if (refreshBtn) {
        refreshBtn.removeEventListener('click', refreshHandler);
        refreshBtn.addEventListener('click', refreshHandler);
    }

    // Set items (click to load)
    document.querySelectorAll('.set-item').forEach(item => {
        item.removeEventListener('click', setItemHandler);
        item.addEventListener('click', setItemHandler);
    });

    // Fix buttons
    document.querySelectorAll('.fix-set-btn').forEach(btn => {
        btn.removeEventListener('click', fixButtonHandler);
        btn.addEventListener('click', fixButtonHandler);
    });
}

// Handler functions that post notifications
function flipHandler() {
    NotificationCenter.postNotification('flipBoardRequested');
}

function refreshHandler() {
    NotificationCenter.postNotification('refreshPieceSetListRequested');
}

function setItemHandler(e) {
    if (e.target.classList.contains('fix-set-btn')) return;
    const setName = this.dataset.set;
    if (setName && setName !== currentSetName) {
        NotificationCenter.postNotification('loadPieceSetRequested', { setName });
    }
}

function fixButtonHandler(e) {
    e.stopPropagation();
    const setName = this.dataset.set;
    NotificationCenter.postNotification('renamePieceSetRequested', { setName });
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// ============================================
// Initialization
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    // Initialize board
    boardState = setStartingPosition();
    renderBoard();

    // Get initial data from PHP
    const currentSetData = window.initialCurrentSet || null;
    const allSetsData = window.initialAllSets || [];

    if (currentSetData && currentSetData.pieces && Object.keys(currentSetData.pieces).length > 0) {
        currentSet = currentSetData;
        currentSetName = currentSetData.name;
        document.getElementById('set-name').textContent = currentSetData.name;
        renderBoard();
        attachDOMEventHandlers();
    } else if (allSetsData.length > 0) {
        const firstSet = allSetsData[0].cleanName || allSetsData[0].name.replace('*', '');
        NotificationCenter.postNotification('loadPieceSetRequested', { setName: firstSet });
        attachDOMEventHandlers();
    } else {
        attachDOMEventHandlers();
    }
});

// Handle browser back/forward
window.addEventListener('popstate', () => {
    const path = window.location.pathname;
    const match = path.match(/\/ChessViewer\/([^\/]+)$/);
    if (match && match[1]) {
        NotificationCenter.postNotification('loadPieceSetRequested', { setName: match[1] });
    }
});
