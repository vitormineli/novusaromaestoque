-- Criação do banco de dados para Vombs Importados ERP
CREATE DATABASE IF NOT EXISTS vombs_erp;
USE vombs_erp;

-- Tabela de produtos/estoque
CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    quantidade INT DEFAULT 0,
    preco_custo DECIMAL(10,2) DEFAULT 0.00,
    preco_venda DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('aprovado', 'analise', 'nao_vender') DEFAULT 'analise',
    observacao TEXT,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de pedidos
CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_produto VARCHAR(255) NOT NULL,
    preco DECIMAL(10,2) NOT NULL,
    local_compra VARCHAR(100) NOT NULL,
    imposto DECIMAL(10,2) DEFAULT 0.00,
    quantidade INT NOT NULL,
    status_pedido ENUM('pedido_feito', 'falta_fazer') DEFAULT 'falta_fazer',
    status_entrega ENUM('aguardando', 'entregue') DEFAULT 'aguardando',
    data_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de vendas/clientes
CREATE TABLE vendas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT,
    nome_cliente VARCHAR(255) NOT NULL,
    local_venda VARCHAR(100) NOT NULL,
    preco_vendido DECIMAL(10,2) NOT NULL,
    taxa_cartao DECIMAL(10,2) DEFAULT 0.00,
    taxa_marketplace DECIMAL(10,2) DEFAULT 0.00,
    lucro DECIMAL(10,2) DEFAULT 0.00,
    data_venda TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE SET NULL
);

-- Tabela de configurações de precificação
CREATE TABLE config_precificacao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_taxa VARCHAR(100) NOT NULL,
    valor_taxa DECIMAL(5,2) NOT NULL,
    tipo ENUM('percentual', 'fixo') DEFAULT 'percentual',
    ativo BOOLEAN DEFAULT TRUE
);

-- Inserir configurações padrão
INSERT INTO config_precificacao (nome_taxa, valor_taxa, tipo) VALUES
('Taxa Mercado Livre', 16.00, 'percentual'),
('Taxa Shopee', 12.00, 'percentual'),
('Taxa Cartão Crédito', 4.99, 'percentual'),
('Taxa Cartão Débito', 2.99, 'percentual'),
('Custo Embalagem', 5.00, 'fixo');