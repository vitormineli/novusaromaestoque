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
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

// Funções auxiliares
function formatarPreco($preco) {
    return 'R$ ' . number_format($preco, 2, ',', '.');
}

function getStatusColor($status) {
    switch($status) {
        case 'aprovado':
            return 'success';
        case 'analise':
            return 'warning';
        case 'nao_vender':
            return 'danger';
        default:
            return 'secondary';
    }
}

function getStatusText($status) {
    switch($status) {
        case 'aprovado':
            return 'Aprovado';
        case 'analise':
            return 'Análise de Mercado';
        case 'nao_vender':
            return 'Não Vender';
        default:
            return $status;
    }
}

function getPedidoColor($status) {
    switch($status) {
        case 'pedido_feito':
            return 'success';
        case 'falta_fazer':
            return 'danger';
        default:
            return 'secondary';
    }
}

function getEntregaColor($status) {
    switch($status) {
        case 'entregue':
            return 'success';
        case 'aguardando':
            return 'warning';
        default:
            return 'secondary';
    }
}
?>