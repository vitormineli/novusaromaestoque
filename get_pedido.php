<?php
require_once 'config.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
    $stmt->execute([$id]);
    $pedido = $stmt->fetch();
    
    if ($pedido) {
        header('Content-Type: application/json');
        echo json_encode($pedido);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Pedido não encontrado']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'ID não fornecido']);
}
?>