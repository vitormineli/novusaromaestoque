<?php
// Configuração de conexão com o banco de dados
$host = 'localhost';
$dbname = 'vombs_erp';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

// Funções auxiliares
function formatarPreco($preco) {
    return 'R$ ' . number_format($preco, 2, ',', '.');
}

function getStatusColor($status) {
    switch($status) {
        case 'aprovado': return 'success';
        case 'analise': return 'warning';
        case 'nao_vender': return 'danger';
        case 'pedido_feito': return 'success';
        case 'falta_fazer': return 'danger';
        case 'entregue': return 'success';
        case 'aguardando': return 'warning';
        default: return 'secondary';
    }
}

function getStatusText($status) {
    switch($status) {
        case 'aprovado': return 'Aprovado';
        case 'analise': return 'Análise de Mercado';
        case 'nao_vender': return 'Não Vender';
        case 'pedido_feito': return 'Pedido Feito';
        case 'falta_fazer': return 'Falta Fazer Pedido';
        case 'entregue': return 'Entregue';
        case 'aguardando': return 'Aguardando Entrega';
        default: return $status;
    }
}
?>