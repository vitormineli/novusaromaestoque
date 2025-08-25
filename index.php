<?php
require_once 'config.php';

// Processar formulários
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'adicionar_produto':
                $stmt = $pdo->prepare("INSERT INTO produtos (nome, quantidade, preco_custo, preco_venda, status, observacao) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$_POST['nome'], $_POST['quantidade'], $_POST['preco_custo'], $_POST['preco_venda'], $_POST['status'], $_POST['observacao']]);
                break;
                
            case 'atualizar_produto':
                $stmt = $pdo->prepare("UPDATE produtos SET nome = ?, quantidade = ?, preco_custo = ?, preco_venda = ?, status = ?, observacao = ? WHERE id = ?");
                $stmt->execute([$_POST['nome'], $_POST['quantidade'], $_POST['preco_custo'], $_POST['preco_venda'], $_POST['status'], $_POST['observacao'], $_POST['id']]);
                break;
                
            case 'adicionar_pedido':
                $stmt = $pdo->prepare("INSERT INTO pedidos (produto_id, nome_produto, preco, quantidade, local_compra, imposto, status_pedido, status_entrega) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$_POST['produto_id'], $_POST['nome_produto'], $_POST['preco'], $_POST['quantidade'], $_POST['local_compra'], $_POST['imposto'], $_POST['status_pedido'], $_POST['status_entrega']]);
                break;
                
            case 'atualizar_pedido':
                $stmt = $pdo->prepare("UPDATE pedidos SET status_pedido = ?, status_entrega = ? WHERE id = ?");
                $stmt->execute([$_POST['status_pedido'], $_POST['status_entrega'], $_POST['id']]);
                
                // Se o pedido foi entregue, atualizar o estoque
                if ($_POST['status_entrega'] == 'entregue') {
                    $stmt = $pdo->prepare("SELECT produto_id, quantidade FROM pedidos WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $pedido = $stmt->fetch();
                    
                    if ($pedido['produto_id']) {
                        $stmt = $pdo->prepare("UPDATE produtos SET quantidade = quantidade + ? WHERE id = ?");
                        $stmt->execute([$pedido['quantidade'], $pedido['produto_id']]);
                    }
                }
                break;
                
            case 'calcular_preco':
                $preco_custo = $_POST['preco_custo'];
                $taxa_mercado = $_POST['taxa_mercado'];
                $taxa_cartao = $_POST['taxa_cartao'];
                $custo_embalagem = $_POST['custo_embalagem'];
                
                $custo_total = $preco_custo + $custo_embalagem;
                $preco_ideal = $custo_total / (1 - ($taxa_mercado + $taxa_cartao) / 100);
                
                $stmt = $pdo->prepare("INSERT INTO precificacao (produto_id, preco_custo, taxa_mercado, taxa_cartao, custo_embalagem, preco_ideal) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$_POST['produto_id'], $preco_custo, $taxa_mercado, $taxa_cartao, $custo_embalagem, $preco_ideal]);
                break;
                
            case 'adicionar_venda':
                $stmt = $pdo->prepare("INSERT INTO vendas (produto_id, nome_cliente, local_venda, preco_vendido, taxa_cartao, taxa_mercado) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$_POST['produto_id'], $_POST['nome_cliente'], $_POST['local_venda'], $_POST['preco_vendido'], $_POST['taxa_cartao'], $_POST['taxa_mercado']]);
                
                // Calcular e atualizar o lucro
                $venda_id = $pdo->lastInsertId();
                $stmt = $pdo->prepare("SELECT v.*, p.preco_custo FROM vendas v JOIN produtos p ON v.produto_id = p.id WHERE v.id = ?");
                $stmt->execute([$venda_id]);
                $venda = $stmt->fetch();
                
                $lucro = $venda['preco_vendido'] - $venda['preco_custo'] - 
                        ($venda['preco_vendido'] * $venda['taxa_cartao'] / 100) - 
                        ($venda['preco_vendido'] * $venda['taxa_mercado'] / 100);
                
                $stmt = $pdo->prepare("UPDATE vendas SET lucro = ? WHERE id = ?");
                $stmt->execute([$lucro, $venda_id]);
                
                // Diminuir quantidade do estoque
                $stmt = $pdo->prepare("UPDATE produtos SET quantidade = quantidade - 1 WHERE id = ?");
                $stmt->execute([$_POST['produto_id']]);
                break;
        }
        header('Location: index.php');
        exit;
    }
}

// Buscar dados
$produtos = $pdo->query("SELECT * FROM produtos ORDER BY nome")->fetchAll();
$pedidos = $pdo->query("SELECT p.*, pr.nome as nome_produto FROM pedidos p LEFT JOIN produtos pr ON p.produto_id = pr.id ORDER BY p.data_pedido DESC")->fetchAll();
$precificacoes = $pdo->query("SELECT prec.*, pr.nome as nome_produto FROM precificacao prec LEFT JOIN produtos pr ON prec.produto_id = pr.id ORDER BY prec.data_calculo DESC")->fetchAll();
$vendas = $pdo->query("SELECT v.*, pr.nome as nome_produto FROM vendas v LEFT JOIN produtos pr ON v.produto_id = pr.id ORDER BY v.data_venda DESC")->fetchAll();

// Produtos com estoque baixo (apenas aprovados)
$estoque_baixo = $pdo->query("SELECT * FROM produtos WHERE quantidade <= 1 AND status = 'aprovado'")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ERP Vombs Importados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .alert-estoque {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        .status-badge {
            font-size: 0.8em;
            padding: 0.25em 0.6em;
        }
        .table-responsive {
            max-height: 500px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-store"></i> Vombs Importados - ERP</a>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Alertas de Estoque Baixo -->
        <?php if (!empty($estoque_baixo)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h5><i class="fas fa-exclamation-triangle"></i> Alerta de Estoque Baixo!</h5>
            <ul class="mb-0">
                <?php foreach ($estoque_baixo as $produto): ?>
                <li><strong><?= htmlspecialchars($produto['nome']) ?></strong> - Quantidade: <?= $produto['quantidade'] ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Tabs de Navegação -->
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="estoque-tab" data-bs-toggle="tab" data-bs-target="#estoque" type="button" role="tab">
                    <i class="fas fa-boxes"></i> Estoque
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pedidos-tab" data-bs-toggle="tab" data-bs-target="#pedidos" type="button" role="tab">
                    <i class="fas fa-shopping-cart"></i> Pedidos
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="precificacao-tab" data-bs-toggle="tab" data-bs-target="#precificacao" type="button" role="tab">
                    <i class="fas fa-calculator"></i> Precificação
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="clientes-tab" data-bs-toggle="tab" data-bs-target="#clientes" type="button" role="tab">
                    <i class="fas fa-users"></i> Vendas/Clientes
                </button>
            </li>
        </ul>

        <div class="tab-content" id="myTabContent">
            <!-- ABA ESTOQUE -->
            <div class="tab-pane fade show active" id="estoque" role="tabpanel">
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-plus"></i> Adicionar Produto</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="adicionar_produto">
                                    <div class="mb-3">
                                        <label class="form-label">Nome do Produto</label>
                                        <input type="text" class="form-control" name="nome" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Quantidade</label>
                                        <input type="number" class="form-control" name="quantidade" min="0" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Preço de Custo</label>
                                        <input type="number" class="form-control" name="preco_custo" step="0.01" min="0" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Preço de Venda</label>
                                        <input type="number" class="form-control" name="preco_venda" step="0.01" min="0" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" name="status" required>
                                            <option value="analise">Análise de Mercado</option>
                                            <option value="aprovado">Aprovado</option>
                                            <option value="nao_vender">Não Vender</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Observação</label>
                                        <textarea class="form-control" name="observacao" rows="3"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Adicionar Produto</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-list"></i> Lista de Produtos</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Produto</th>
                                                <th>Qtd</th>
                                                <th>Custo</th>
                                                <th>Venda</th>
                                                <th>Status</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($produtos as $produto): ?>
                                            <tr class="<?= ($produto['quantidade'] <= 1 && $produto['status'] == 'aprovado') ? 'table-danger' : '' ?>">
                                                <td><?= htmlspecialchars($produto['nome']) ?></td>
                                                <td><?= $produto['quantidade'] ?></td>
                                                <td><?= formatarPreco($produto['preco_custo']) ?></td>
                                                <td><?= formatarPreco($produto['preco_venda']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= getStatusColor($produto['status']) ?> status-badge">
                                                        <?= getStatusText($produto['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="editarProduto(<?= $produto['id'] ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ABA PEDIDOS -->
            <div class="tab-pane fade" id="pedidos" role="tabpanel">
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-plus"></i> Novo Pedido</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="adicionar_pedido">
                                    <div class="mb-3">
                                        <label class="form-label">Produto</label>
                                        <select class="form-select" name="produto_id">
                                            <option value="">Novo produto</option>
                                            <?php foreach ($produtos as $produto): ?>
                                            <option value="<?= $produto['id'] ?>"><?= htmlspecialchars($produto['nome']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Nome do Produto</label>
                                        <input type="text" class="form-control" name="nome_produto" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Preço</label>
                                        <input type="number" class="form-control" name="preco" step="0.01" min="0" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Quantidade</label>
                                        <input type="number" class="form-control" name="quantidade" min="1" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Local de Compra</label>
                                        <select class="form-select" name="local_compra" required>
                                            <option value="AliExpress">AliExpress</option>
                                            <option value="Shopee">Shopee</option>
                                            <option value="Mercado Livre">Mercado Livre</option>
                                            <option value="Outro">Outro</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Imposto</label>
                                        <input type="number" class="form-control" name="imposto" step="0.01" min="0" value="0">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Status do Pedido</label>
                                        <select class="form-select" name="status_pedido" required>
                                            <option value="falta_fazer">Falta Fazer Pedido</option>
                                            <option value="pedido_feito">Pedido Feito</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Status da Entrega</label>
                                        <select class="form-select" name="status_entrega" required>
                                            <option value="aguardando">Aguardando Entrega</option>
                                            <option value="entregue">Entregue</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Adicionar Pedido</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-list"></i> Lista de Pedidos</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Produto</th>
                                                <th>Preço</th>
                                                <th>Qtd</th>
                                                <th>Local</th>
                                                <th>Status Pedido</th>
                                                <th>Status Entrega</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pedidos as $pedido): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($pedido['nome_produto']) ?></td>
                                                <td><?= formatarPreco($pedido['preco']) ?></td>
                                                <td><?= $pedido['quantidade'] ?></td>
                                                <td><?= htmlspecialchars($pedido['local_compra']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= getStatusColor($pedido['status_pedido']) ?> status-badge">
                                                        <?= getStatusText($pedido['status_pedido']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= getStatusColor($pedido['status_entrega']) ?> status-badge">
                                                        <?= getStatusText($pedido['status_entrega']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="editarPedido(<?= $pedido['id'] ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ABA PRECIFICAÇÃO -->
            <div class="tab-pane fade" id="precificacao" role="tabpanel">
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-calculator"></i> Calcular Preço Ideal</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="calcular_preco">
                                    <div class="mb-3">
                                        <label class="form-label">Produto</label>
                                        <select class="form-select" name="produto_id" required>
                                            <?php foreach ($produtos as $produto): ?>
                                            <option value="<?= $produto['id'] ?>"><?= htmlspecialchars($produto['nome']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Preço de Custo</label>
                                        <input type="number" class="form-control" name="preco_custo" step="0.01" min="0" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Taxa do Marketplace (%)</label>
                                        <input type="number" class="form-control" name="taxa_mercado" step="0.01" min="0" value="0">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Taxa do Cartão (%)</label>
                                        <input type="number" class="form-control" name="taxa_cartao" step="0.01" min="0" value="0">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Custo da Embalagem</label>
                                        <input type="number" class="form-control" name="custo_embalagem" step="0.01" min="0" value="0">
                                    </div>
                                    <button type="submit" class="btn btn-primary">Calcular Preço Ideal</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-list"></i> Histórico de Precificação</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Produto</th>
                                                <th>Custo</th>
                                                <th>Taxa Mercado</th>
                                                <th>Taxa Cartão</th>
                                                <th>Embalagem</th>
                                                <th>Preço Ideal</th>
                                                <th>Data</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($precificacoes as $preco): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($preco['nome_produto']) ?></td>
                                                <td><?= formatarPreco($preco['preco_custo']) ?></td>
                                                <td><?= $preco['taxa_mercado'] ?>%</td>
                                                <td><?= $preco['taxa_cartao'] ?>%</td>
                                                <td><?= formatarPreco($preco['custo_embalagem']) ?></td>
                                                <td><strong><?= formatarPreco($preco['preco_ideal']) ?></strong></td>
                                                <td><?= date('d/m/Y H:i', strtotime($preco['data_calculo'])) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ABA VENDAS/CLIENTES -->
            <div class="tab-pane fade" id="clientes" role="tabpanel">
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-plus"></i> Nova Venda</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="adicionar_venda">
                                    <div class="mb-3">
                                        <label class="form-label">Produto</label>
                                        <select class="form-select" name="produto_id" required>
                                            <?php foreach ($produtos as $produto): ?>
                                            <?php if ($produto['status'] == 'aprovado' && $produto['quantidade'] > 0): ?>
                                            <option value="<?= $produto['id'] ?>"><?= htmlspecialchars($produto['nome']) ?> (Qtd: <?= $produto['quantidade'] ?>)</option>
                                            <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Nome do Cliente</label>
                                        <input type="text" class="form-control" name="nome_cliente" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Local da Venda</label>
                                        <select class="form-select" name="local_venda" required>
                                            <option value="Mercado Livre">Mercado Livre</option>
                                            <option value="Shopee">Shopee</option>
                                            <option value="Instagram">Instagram</option>
                                            <option value="WhatsApp">WhatsApp</option>
                                            <option value="Loja Física">Loja Física</option>
                                            <option value="Outro">Outro</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Preço Vendido</label>
                                        <input type="number" class="form-control" name="preco_vendido" step="0.01" min="0" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Taxa do Cartão (%)</label>
                                        <input type="number" class="form-control" name="taxa_cartao" step="0.01" min="0" value="0">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Taxa do Marketplace (%)</label>
                                        <input type="number" class="form-control" name="taxa_mercado" step="0.01" min="0" value="0">
                                    </div>
                                    <button type="submit" class="btn btn-primary">Registrar Venda</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-list"></i> Histórico de Vendas</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Cliente</th>
                                                <th>Produto</th>
                                                <th>Local</th>
                                                <th>Preço</th>
                                                <th>Taxas</th>
                                                <th>Lucro</th>
                                                <th>Data</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($vendas as $venda): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($venda['nome_cliente']) ?></td>
                                                <td><?= htmlspecialchars($venda['nome_produto']) ?></td>
                                                <td><?= htmlspecialchars($venda['local_venda']) ?></td>
                                                <td><?= formatarPreco($venda['preco_vendido']) ?></td>
                                                <td>
                                                    <?php 
                                                    $total_taxas = ($venda['preco_vendido'] * $venda['taxa_cartao'] / 100) + 
                                                                  ($venda['preco_vendido'] * $venda['taxa_mercado'] / 100);
                                                    echo formatarPreco($total_taxas);
                                                    ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= $venda['lucro'] >= 0 ? 'success' : 'danger' ?>">
                                                        <?= formatarPreco($venda['lucro']) ?>
                                                    </span>
                                                </td>
                                                <td><?= date('d/m/Y H:i', strtotime($venda['data_venda'])) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para editar produto -->
    <div class="modal fade" id="modalEditarProduto" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Produto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formEditarProduto" method="POST">
                        <input type="hidden" name="action" value="atualizar_produto">
                        <input type="hidden" name="id" id="edit_produto_id">
                        <div class="mb-3">
                            <label class="form-label">Nome do Produto</label>
                            <input type="text" class="form-control" name="nome" id="edit_produto_nome" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantidade</label>
                            <input type="number" class="form-control" name="quantidade" id="edit_produto_quantidade" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Preço de Custo</label>
                            <input type="number" class="form-control" name="preco_custo" id="edit_produto_custo" step="0.01" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Preço de Venda</label>
                            <input type="number" class="form-control" name="preco_venda" id="edit_produto_venda" step="0.01" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="edit_produto_status" required>
                                <option value="analise">Análise de Mercado</option>
                                <option value="aprovado">Aprovado</option>
                                <option value="nao_vender">Não Vender</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Observação</label>
                            <textarea class="form-control" name="observacao" id="edit_produto_observacao" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formEditarProduto" class="btn btn-primary">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para editar pedido -->
    <div class="modal fade" id="modalEditarPedido" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formEditarPedido" method="POST">
                        <input type="hidden" name="action" value="atualizar_pedido">
                        <input type="hidden" name="id" id="edit_pedido_id">
                        <div class="mb-3">
                            <label class="form-label">Status do Pedido</label>
                            <select class="form-select" name="status_pedido" id="edit_pedido_status" required>
                                <option value="falta_fazer">Falta Fazer Pedido</option>
                                <option value="pedido_feito">Pedido Feito</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status da Entrega</label>
                            <select class="form-select" name="status_entrega" id="edit_pedido_entrega" required>
                                <option value="aguardando">Aguardando Entrega</option>
                                <option value="entregue">Entregue</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formEditarPedido" class="btn btn-primary">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Função para editar produto
        function editarProduto(id) {
            // Aqui você pode implementar a busca dos dados do produto via AJAX
            // Por simplicidade, vou mostrar como seria a estrutura
            fetch(`get_produto.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit_produto_id').value = data.id;
                    document.getElementById('edit_produto_nome').value = data.nome;
                    document.getElementById('edit_produto_quantidade').value = data.quantidade;
                    document.getElementById('edit_produto_custo').value = data.preco_custo;
                    document.getElementById('edit_produto_venda').value = data.preco_venda;
                    document.getElementById('edit_produto_status').value = data.status;
                    document.getElementById('edit_produto_observacao').value = data.observacao;
                    
                    new bootstrap.Modal(document.getElementById('modalEditarProduto')).show();
                });
        }

        // Função para editar pedido
        function editarPedido(id) {
            // Similar ao produto, buscar dados do pedido
            fetch(`get_pedido.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit_pedido_id').value = data.id;
                    document.getElementById('edit_pedido_status').value = data.status_pedido;
                    document.getElementById('edit_pedido_entrega').value = data.status_entrega;
                    
                    new bootstrap.Modal(document.getElementById('modalEditarPedido')).show();
                });
        }

        // Auto-hide dos alertas após 10 segundos
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 10000);
    </script>
</body>
</html>