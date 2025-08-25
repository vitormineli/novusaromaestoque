<?php
session_start();
require_once 'config/database.php';

// Funções auxiliares
function getStatusColor($status) {
    switch($status) {
        case 'aprovado': return 'success';
        case 'analise': return 'warning';
        case 'nao_vender': return 'danger';
        case 'pedido_feito': return 'success';
        case 'falta_fazer': return 'danger';
        case 'aguardando': return 'warning';
        case 'entregue': return 'success';
        default: return 'secondary';
    }
}

function getStatusText($status) {
    switch($status) {
        case 'aprovado': return 'Aprovado';
        case 'analise': return 'Análise de Mercado';
        case 'nao_vender': return 'Não Vender';
        case 'pedido_feito': return 'Pedido Feito';
        case 'falta_fazer': return 'Falta Fazer';
        case 'aguardando': return 'Aguardando Entrega';
        case 'entregue': return 'Entregue';
        default: return $status;
    }
}

// Processamento de formulários
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'add_produto':
                $stmt = $pdo->prepare("INSERT INTO produtos (nome, quantidade, preco_custo, preco_venda, status, observacao) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$_POST['nome'], $_POST['quantidade'], $_POST['preco_custo'], $_POST['preco_venda'], $_POST['status'], $_POST['observacao']]);
                break;
                
            case 'update_produto':
                $stmt = $pdo->prepare("UPDATE produtos SET nome=?, quantidade=?, preco_custo=?, preco_venda=?, status=?, observacao=? WHERE id=?");
                $stmt->execute([$_POST['nome'], $_POST['quantidade'], $_POST['preco_custo'], $_POST['preco_venda'], $_POST['status'], $_POST['observacao'], $_POST['id']]);
                break;
                
            case 'add_pedido':
                $stmt = $pdo->prepare("INSERT INTO pedidos (nome_produto, preco, local_compra, imposto, quantidade, status_pedido) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$_POST['nome_produto'], $_POST['preco'], $_POST['local_compra'], $_POST['imposto'], $_POST['quantidade'], $_POST['status_pedido']]);
                break;
                
            case 'update_pedido':
                $stmt = $pdo->prepare("UPDATE pedidos SET status_pedido=?, status_entrega=? WHERE id=?");
                $stmt->execute([$_POST['status_pedido'], $_POST['status_entrega'], $_POST['id']]);
                
                // Se entregue, atualizar estoque
                if ($_POST['status_entrega'] == 'entregue') {
                    $stmt = $pdo->prepare("UPDATE produtos SET quantidade = quantidade + ? WHERE nome = ?");
                    $stmt->execute([$_POST['quantidade_original'], $_POST['nome_produto']]);
                }
                break;
                
            case 'add_venda':
                $stmt = $pdo->prepare("INSERT INTO vendas (produto_id, nome_cliente, local_venda, preco_vendido, taxa_cartao, taxa_marketplace, lucro) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$_POST['produto_id'], $_POST['nome_cliente'], $_POST['local_venda'], $_POST['preco_vendido'], $_POST['taxa_cartao'], $_POST['taxa_marketplace'], $_POST['lucro']]);
                
                // Diminuir estoque
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
$pedidos = $pdo->query("SELECT * FROM pedidos ORDER BY data_pedido DESC")->fetchAll();
$vendas = $pdo->query("SELECT v.*, p.nome as nome_produto FROM vendas v LEFT JOIN produtos p ON v.produto_id = p.id ORDER BY v.data_venda DESC")->fetchAll();
$config_taxas = $pdo->query("SELECT * FROM config_precificacao WHERE ativo = 1")->fetchAll();

// Produtos com estoque baixo (apenas aprovados)
$estoque_baixo = $pdo->query("SELECT * FROM produtos WHERE quantidade <= 1 AND status = 'aprovado'")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vombs Importados - ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .status-badge {
            font-size: 0.8em;
            padding: 0.3em 0.6em;
        }
        .alert-estoque {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        .nav-tabs .nav-link.active {
            font-weight: bold;
        }
        .table-responsive {
            max-height: 500px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <h1 class="text-center my-4">
                    <i class="fas fa-store"></i> Vombs Importados - ERP
                </h1>
                
                <!-- Alertas de estoque baixo -->
                <?php if (!empty($estoque_baixo)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <h5><i class="fas fa-exclamation-triangle"></i> Alerta de Estoque Baixo!</h5>
                    <ul class="mb-0">
                        <?php foreach($estoque_baixo as $produto): ?>
                        <li><strong><?= htmlspecialchars($produto['nome']) ?></strong> - Quantidade: <?= $produto['quantidade'] ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Navegação por abas -->
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
                            <i class="fas fa-users"></i> Clientes/Vendas
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
                                            <input type="hidden" name="action" value="add_produto">
                                            <div class="mb-3">
                                                <label class="form-label">Nome do Produto</label>
                                                <input type="text" class="form-control" name="nome" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Quantidade</label>
                                                <input type="number" class="form-control" name="quantidade" value="0" min="0" required>
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
                                                        <th>Nome</th>
                                                        <th>Qtd</th>
                                                        <th>Preço Custo</th>
                                                        <th>Preço Venda</th>
                                                        <th>Status</th>
                                                        <th>Ações</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach($produtos as $produto): ?>
                                                    <tr <?= ($produto['quantidade'] <= 1 && $produto['status'] == 'aprovado') ? 'class="table-danger"' : '' ?>>
                                                        <td><?= htmlspecialchars($produto['nome']) ?></td>
                                                        <td><?= $produto['quantidade'] ?></td>
                                                        <td>R$ <?= number_format($produto['preco_custo'], 2, ',', '.') ?></td>
                                                        <td>R$ <?= number_format($produto['preco_venda'], 2, ',', '.') ?></td>
                                                        <td>
                                                            <span class="badge bg-<?= getStatusColor($produto['status']) ?> status-badge">
                                                                <?= getStatusText($produto['status']) ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-primary" onclick="editProduto(<?= $produto['id'] ?>)">
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
                                            <input type="hidden" name="action" value="add_pedido">
                                            <div class="mb-3">
                                                <label class="form-label">Nome do Produto</label>
                                                <input type="text" class="form-control" name="nome_produto" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Preço</label>
                                                <input type="number" class="form-control" name="preco" step="0.01" min="0" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Local de Compra</label>
                                                <select class="form-select" name="local_compra" id="local_compra" required>
                                                    <option value="">Selecione...</option>
                                                    <option value="Aliexpress">Aliexpress</option>
                                                    <option value="Mercado Livre">Mercado Livre</option>
                                                    <option value="Shopee">Shopee</option>
                                                    <option value="Outro">Outro</option>
                                                </select>
                                            </div>
                                            <div class="mb-3" id="imposto_div" style="display:none;">
                                                <label class="form-label">Imposto Pago</label>
                                                <input type="number" class="form-control" name="imposto" step="0.01" min="0" value="0">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Quantidade</label>
                                                <input type="number" class="form-control" name="quantidade" min="1" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Status do Pedido</label>
                                                <select class="form-select" name="status_pedido" required>
                                                    <option value="falta_fazer">Falta Fazer</option>
                                                    <option value="pedido_feito">Pedido Feito</option>
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
                                                        <th>Local</th>
                                                        <th>Qtd</th>
                                                        <th>Status Pedido</th>
                                                        <th>Status Entrega</th>
                                                        <th>Ações</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach($pedidos as $pedido): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($pedido['nome_produto']) ?></td>
                                                        <td>R$ <?= number_format($pedido['preco'], 2, ',', '.') ?></td>
                                                        <td><?= htmlspecialchars($pedido['local_compra']) ?></td>
                                                        <td><?= $pedido['quantidade'] ?></td>
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
                                                            <button class="btn btn-sm btn-outline-primary" onclick="editPedido(<?= $pedido['id'] ?>)">
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
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5><i class="fas fa-calculator"></i> Calculadora de Preços</h5>
                                    </div>
                                    <div class="card-body">
                                        <form id="precificacaoForm">
                                            <div class="mb-3">
                                                <label class="form-label">Preço de Custo</label>
                                                <input type="number" class="form-control" id="preco_custo_calc" step="0.01" min="0" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Local de Venda</label>
                                                <select class="form-select" id="local_venda_calc">
                                                    <option value="">Selecione...</option>
                                                    <option value="Mercado Livre">Mercado Livre</option>
                                                    <option value="Shopee">Shopee</option>
                                                    <option value="Venda Direta">Venda Direta</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Forma de Pagamento</label>
                                                <select class="form-select" id="pagamento_calc">
                                                    <option value="">Selecione...</option>
                                                    <option value="pix">PIX</option>
                                                    <option value="dinheiro">Dinheiro</option>
                                                    <option value="credito">Cartão Crédito</option>
                                                    <option value="debito">Cartão Débito</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Margem de Lucro (%)</label>
                                                <input type="number" class="form-control" id="margem_lucro" value="30" min="0" max="100">
                                            </div>
                                            <button type="button" class="btn btn-primary" onclick="calcularPreco()">Calcular Preço Ideal</button>
                                        </form>
                                        
                                        <div class="mt-4" id="resultado_preco" style="display:none;">
                                            <h6>Resultado do Cálculo:</h6>
                                            <div class="alert alert-info">
                                                <strong>Preço Ideal de Venda: R$ <span id="preco_ideal"></span></strong><br>
                                                <small>Lucro Estimado: R$ <span id="lucro_estimado"></span></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5><i class="fas fa-cog"></i> Configurações de Taxas</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Taxa</th>
                                                        <th>Valor</th>
                                                        <th>Tipo</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach($config_taxas as $taxa): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($taxa['nome_taxa']) ?></td>
                                                        <td><?= $taxa['tipo'] == 'percentual' ? $taxa['valor_taxa'] . '%' : 'R$ ' . number_format($taxa['valor_taxa'], 2, ',', '.') ?></td>
                                                        <td><?= ucfirst($taxa['tipo']) ?></td>
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

                    <!-- ABA CLIENTES/VENDAS -->
                    <div class="tab-pane fade" id="clientes" role="tabpanel">
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5><i class="fas fa-plus"></i> Nova Venda</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="add_venda">
                                            <div class="mb-3">
                                                <label class="form-label">Produto</label>
                                                <select class="form-select" name="produto_id" required>
                                                    <option value="">Selecione...</option>
                                                    <?php foreach($produtos as $produto): ?>
                                                        <?php if($produto['status'] == 'aprovado' && $produto['quantidade'] > 0): ?>
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
                                                <select class="form-select" name="local_venda" id="local_venda" required>
                                                    <option value="">Selecione...</option>
                                                    <option value="Mercado Livre">Mercado Livre</option>
                                                    <option value="Shopee">Shopee</option>
                                                    <option value="Venda Direta">Venda Direta</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Preço Vendido</label>
                                                <input type="number" class="form-control" name="preco_vendido" step="0.01" min="0" required>
                                            </div>
                                            <div class="mb-3" id="taxa_cartao_div" style="display:none;">
                                                <label class="form-label">Taxa do Cartão</label>
                                                <input type="number" class="form-control" name="taxa_cartao" step="0.01" min="0" value="0">
                                            </div>
                                            <div class="mb-3" id="taxa_marketplace_div" style="display:none;">
                                                <label class="form-label">Taxa do Marketplace</label>
                                                <input type="number" class="form-control" name="taxa_marketplace" step="0.01" min="0" value="0">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Lucro</label>
                                                <input type="number" class="form-control" name="lucro" step="0.01" min="0" required>
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
                                                        <th>Data</th>
                                                        <th>Cliente</th>
                                                        <th>Produto</th>
                                                        <th>Local</th>
                                                        <th>Preço</th>
                                                        <th>Lucro</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach($vendas as $venda): ?>
                                                    <tr>
                                                        <td><?= date('d/m/Y H:i', strtotime($venda['data_venda'])) ?></td>
                                                        <td><?= htmlspecialchars($venda['nome_cliente']) ?></td>
                                                        <td><?= htmlspecialchars($venda['nome_produto']) ?></td>
                                                        <td><?= htmlspecialchars($venda['local_venda']) ?></td>
                                                        <td>R$ <?= number_format($venda['preco_vendido'], 2, ',', '.') ?></td>
                                                        <td class="text-success">R$ <?= number_format($venda['lucro'], 2, ',', '.') ?></td>
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
        </div>
    </div>

    <!-- Modal para editar produto -->
    <div class="modal fade" id="editProdutoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Produto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_produto">
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
                            <textarea class="form-control" name="observacao" id="edit_produto_obs" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para editar pedido -->
    <div class="modal fade" id="editPedidoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_pedido">
                        <input type="hidden" name="id" id="edit_pedido_id">
                        <input type="hidden" name="nome_produto" id="edit_pedido_nome">
                        <input type="hidden" name="quantidade_original" id="edit_pedido_qtd">
                        <div class="mb-3">
                            <label class="form-label">Status do Pedido</label>
                            <select class="form-select" name="status_pedido" id="edit_pedido_status" required>
                                <option value="falta_fazer">Falta Fazer</option>
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
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mostrar campo de imposto quando Aliexpress for selecionado
        document.getElementById('local_compra').addEventListener('change', function() {
            const impostoDiv = document.getElementById('imposto_div');
            if (this.value === 'Aliexpress') {
                impostoDiv.style.display = 'block';
            } else {
                impostoDiv.style.display = 'none';
            }
        });

        // Mostrar campos de taxa quando marketplace for selecionado
        document.getElementById('local_venda').addEventListener('change', function() {
            const taxaMarketplaceDiv = document.getElementById('taxa_marketplace_div');
            if (this.value === 'Mercado Livre' || this.value === 'Shopee') {
                taxaMarketplaceDiv.style.display = 'block';
            } else {
                taxaMarketplaceDiv.style.display = 'none';
            }
        });

        // Mostrar campo de taxa do cartão quando cartão for selecionado
        document.getElementById('pagamento_calc').addEventListener('change', function() {
            const taxaCartaoDiv = document.getElementById('taxa_cartao_div');
            if (this.value === 'credito' || this.value === 'debito') {
                taxaCartaoDiv.style.display = 'block';
            } else {
                taxaCartaoDiv.style.display = 'none';
            }
        });

        // Função para calcular preço ideal
        function calcularPreco() {
            const precoCusto = parseFloat(document.getElementById('preco_custo_calc').value);
            const localVenda = document.getElementById('local_venda_calc').value;
            const pagamento = document.getElementById('pagamento_calc').value;
            const margemLucro = parseFloat(document.getElementById('margem_lucro').value);

            if (!precoCusto) {
                alert('Por favor, insira o preço de custo');
                return;
            }

            let custoTotal = precoCusto;
            let taxaMarketplace = 0;
            let taxaCartao = 0;

            // Adicionar taxa do marketplace
            if (localVenda === 'Mercado Livre') {
                taxaMarketplace = precoCusto * 0.16;
            } else if (localVenda === 'Shopee') {
                taxaMarketplace = precoCusto * 0.12;
            }

            // Adicionar taxa do cartão
            if (pagamento === 'credito') {
                taxaCartao = precoCusto * 0.0499;
            } else if (pagamento === 'debito') {
                taxaCartao = precoCusto * 0.0299;
            }

            // Adicionar custo de embalagem
            const custoEmbalagem = 5.00;

            custoTotal += taxaMarketplace + taxaCartao + custoEmbalagem;

            // Calcular preço com margem de lucro
            const precoIdeal = custoTotal * (1 + margemLucro / 100);
            const lucroEstimado = precoIdeal - custoTotal;

            document.getElementById('preco_ideal').textContent = precoIdeal.toFixed(2);
            document.getElementById('lucro_estimado').textContent = lucroEstimado.toFixed(2);
            document.getElementById('resultado_preco').style.display = 'block';
        }

        // Função para editar produto
        function editProduto(id) {
            // Aqui você implementaria a busca dos dados do produto via AJAX
            // Por simplicidade, vou mostrar um alerta
            alert('Funcionalidade de edição será implementada. ID do produto: ' + id);
        }

        // Função para editar pedido
        function editPedido(id) {
            // Aqui você implementaria a busca dos dados do pedido via AJAX
            // Por simplicidade, vou mostrar um alerta
            alert('Funcionalidade de edição será implementada. ID do pedido: ' + id);
        }
    </script>
</body>
</html>