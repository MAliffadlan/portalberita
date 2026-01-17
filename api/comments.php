<?php
/**
 * API Endpoint: Comments
 * GET  ?news_id=X → Ambil semua komentar untuk berita tertentu
 * POST             → Simpan komentar baru (requires login)
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = db();

// Handle GET: Fetch comments
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $newsId = isset($_GET['news_id']) ? (int)$_GET['news_id'] : 0;
    
    if (!$newsId) {
        http_response_code(400);
        echo json_encode(['error' => 'news_id is required']);
        exit;
    }
    
    // Fetch all comments for this news
    $stmt = $pdo->prepare("
        SELECT c.*, u.username 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.news_id = ? 
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$newsId]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Build nested structure
    $nested = buildCommentTree($comments);
    
    // Count total
    $count = count($comments);
    
    echo json_encode([
        'success' => true,
        'count' => $count,
        'comments' => $nested
    ]);
    exit;
}

// Handle POST: Add new comment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check login
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Silakan login terlebih dahulu']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $newsId = isset($data['news_id']) ? (int)$data['news_id'] : 0;
    $parentId = isset($data['parent_id']) && $data['parent_id'] ? (int)$data['parent_id'] : null;
    $content = isset($data['content']) ? trim($data['content']) : '';
    $userId = $_SESSION['user_id'];
    
    // Validate
    if (!$newsId) {
        http_response_code(400);
        echo json_encode(['error' => 'news_id is required']);
        exit;
    }
    
    if (empty($content)) {
        http_response_code(400);
        echo json_encode(['error' => 'Komentar tidak boleh kosong']);
        exit;
    }
    
    if (strlen($content) > 2000) {
        http_response_code(400);
        echo json_encode(['error' => 'Komentar terlalu panjang (max 2000 karakter)']);
        exit;
    }
    
    // Insert comment
    try {
        $stmt = $pdo->prepare("
            INSERT INTO comments (news_id, user_id, parent_id, content) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$newsId, $userId, $parentId, $content]);
        
        $commentId = $pdo->lastInsertId();
        
        // Fetch the newly created comment
        $stmt = $pdo->prepare("
            SELECT c.*, u.username 
            FROM comments c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.id = ?
        ");
        $stmt->execute([$commentId]);
        $newComment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Komentar berhasil ditambahkan',
            'comment' => $newComment
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Gagal menyimpan komentar: ' . $e->getMessage()]);
    }
    exit;
}

// Invalid method
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

/**
 * Build nested comment tree from flat array
 */
function buildCommentTree($comments, $parentId = null) {
    $branch = [];
    
    foreach ($comments as $comment) {
        if ($comment['parent_id'] == $parentId) {
            $children = buildCommentTree($comments, $comment['id']);
            $comment['replies'] = $children;
            $branch[] = $comment;
        }
    }
    
    return $branch;
}
