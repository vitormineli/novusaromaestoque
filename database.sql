-- ERP Vombs Importados - Estrutura do Banco de Dados
-- Execute este arquivo no phpMyAdmin do XAMPP

CREATE DATABASE IF NOT EXISTS vombs_erp;
USE vombs_erp;

-- Tabela de Produtos/Estoque
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

-- Tabela de Pedidos
CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT,
    nome_produto VARCHAR(255) NOT NULL,
    preco DECIMAL(10,2) NOT NULL,
    quantidade INT NOT NULL,
    local_compra VARCHAR(100) NOT NULL,
    imposto DECIMAL(10,2) DEFAULT 0.00,
    status_pedido ENUM('falta_fazer', 'pedido_feito') DEFAULT 'falta_fazer',
    status_entrega ENUM('aguardando', 'entregue') DEFAULT 'aguardando',
    data_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_entrega TIMESTAMP NULL,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE SET NULL
);

-- Tabela de Precificação
CREATE TABLE precificacao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT,
    preco_custo DECIMAL(10,2) NOT NULL,
    taxa_mercado DECIMAL(5,2) DEFAULT 0.00,
    taxa_cartao DECIMAL(5,2) DEFAULT 0.00,
    custo_embalagem DECIMAL(10,2) DEFAULT 0.00,
    preco_ideal DECIMAL(10,2) DEFAULT 0.00,
    data_calculo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
);

-- Tabela de Vendas/Clientes
CREATE TABLE vendas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT,
    nome_cliente VARCHAR(255) NOT NULL,
    local_venda VARCHAR(100) NOT NULL,
    preco_vendido DECIMAL(10,2) NOT NULL,
    taxa_cartao DECIMAL(5,2) DEFAULT 0.00,
    taxa_mercado DECIMAL(5,2) DEFAULT 0.00,
    lucro DECIMAL(10,2) DEFAULT 0.00,
    data_venda TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE SET NULL
);

-- Inserir alguns produtos de exemplo
INSERT INTO produtos (nome, quantidade, preco_custo, preco_venda, status) VALUES
('Smartphone Xiaomi', 5, 800.00, 1200.00, 'aprovado'),
('Fone Bluetooth', 2, 50.00, 89.90, 'aprovado'),
('Cabo USB-C', 1, 15.00, 29.90, 'aprovado'),
('Capa de Celular', 0, 20.00, 39.90, 'analise'),
('Carregador Wireless', 3, 80.00, 129.90, 'aprovado');