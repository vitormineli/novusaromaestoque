<?php
require_once 'config.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
    $stmt->execute([$id]);
    $produto = $stmt->fetch();
    
    if ($produto) {
        header('Content-Type: application/json');
        echo json_encode($produto);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Produto não encontrado']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'ID não fornecido']);
}
?>