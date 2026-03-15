<?php
require_once 'config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$text = $data['text'] ?? '';

if (empty($text)) {
    echo json_encode(['error' => 'No text provided']);
    exit();
}

// Check if we have cached simplified version
$text_hash = hash('sha256', $text);
$sql = "SELECT simplified_text, keywords FROM simplified_texts WHERE original_text_hash = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$text_hash]);
$cached = $stmt->fetch();

if ($cached) {
    echo json_encode([
        'simplified' => $cached['simplified_text'],
        'keywords' => $cached['keywords'],
        'cached' => true
    ]);
    exit();
}

// Simple NLP-based simplification
function simplifyText($text) {
    // Split into sentences
    $sentences = preg_split('/(?<=[.!?])\s+/', $text);
    $simplified = [];
    $keywords = [];
    
    foreach ($sentences as $sentence) {
        // Remove complex connecting words
        $sentence = preg_replace('/\b(however|therefore|consequently|furthermore|nevertheless)\b/i', '', $sentence);
        
        // Break long sentences
        if (str_word_count($sentence) > 20) {
            $clauses = preg_split('/\b(and|but|or|because|since|although)\b/i', $sentence);
            foreach ($clauses as $clause) {
                if (trim($clause)) {
                    $simplified[] = trim($clause) . '.';
                }
            }
        } else {
            $simplified[] = $sentence;
        }
        
        // Extract potential keywords (long words)
        $words = str_word_count($sentence, 1);
        foreach ($words as $word) {
            if (strlen($word) > 6 && !in_array($word, $keywords)) {
                $keywords[] = $word;
            }
        }
    }
    
    return [
        'simplified' => implode("\n\n", $simplified),
        'keywords' => implode(', ', array_slice($keywords, 0, 15))
    ];
}

$result = simplifyText($text);

// Cache the result
$sql = "INSERT INTO simplified_texts (original_text_hash, original_text, simplified_text, keywords) 
        VALUES (?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$text_hash, $text, $result['simplified'], $result['keywords']]);

echo json_encode([
    'simplified' => $result['simplified'],
    'keywords' => $result['keywords'],
    'cached' => false
]);
?>