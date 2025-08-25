-- Dados de exemplo para o Vombs Importados ERP
-- Execute este arquivo após a instalação para ter dados de teste

USE vombs_erp;

-- Inserir produtos de exemplo
INSERT INTO produtos (nome, quantidade, preco_custo, preco_venda, status, observacao) VALUES
('Fone Bluetooth JBL', 5, 45.00, 89.90, 'aprovado', 'Produto com boa aceitação'),
('Cabo USB Type-C', 12, 8.50, 19.90, 'aprovado', 'Venda rápida'),
('Carregador Wireless', 3, 35.00, 69.90, 'aprovado', 'Produto premium'),
('Mouse Gamer RGB', 0, 25.00, 49.90, 'aprovado', 'Estoque baixo - fazer pedido'),
('Teclado Mecânico', 2, 120.00, 249.90, 'aprovado', 'Produto de alto valor'),
('Smartwatch Xiaomi', 1, 180.00, 399.90, 'aprovado', 'Última unidade'),
('Câmera Action Cam', 8, 95.00, 199.90, 'analise', 'Testando mercado'),
('Drone Mini', 15, 150.00, 299.90, 'analise', 'Produto novo - observar demanda'),
('Fone Gaming', 0, 80.00, 159.90, 'nao_vender', 'Produto descontinuado - não repor'),
('Webcam HD', 6, 65.00, 129.90, 'aprovado', 'Boa procura para home office');

-- Inserir pedidos de exemplo
INSERT INTO pedidos (nome_produto, preco, local_compra, imposto, quantidade, status_pedido, status_entrega) VALUES
('Mouse Gamer RGB', 25.00, 'Aliexpress', 12.50, 10, 'pedido_feito', 'aguardando'),
('Fone Gaming', 80.00, 'Mercado Livre', 0.00, 5, 'falta_fazer', 'aguardando'),
('Cabo USB Type-C', 8.50, 'Aliexpress', 4.25, 50, 'pedido_feito', 'entregue'),
('Smartwatch Xiaomi', 180.00, 'Shopee', 0.00, 3, 'pedido_feito', 'aguardando'),
('Teclado Mecânico', 120.00, 'Aliexpress', 60.00, 8, 'falta_fazer', 'aguardando');

-- Inserir vendas de exemplo
INSERT INTO vendas (produto_id, nome_cliente, local_venda, preco_vendido, taxa_cartao, taxa_marketplace, lucro) VALUES
(1, 'João Silva', 'Mercado Livre', 89.90, 4.49, 14.38, 26.03),
(2, 'Maria Santos', 'Venda Direta', 19.90, 0.00, 0.00, 11.40),
(3, 'Pedro Costa', 'Shopee', 69.90, 0.00, 8.39, 26.51),
(4, 'Ana Oliveira', 'Mercado Livre', 49.90, 2.49, 7.98, 14.43),
(5, 'Carlos Lima', 'Venda Direta', 249.90, 12.49, 0.00, 117.41),
(1, 'Fernanda Rocha', 'Shopee', 89.90, 0.00, 10.79, 34.11),
(2, 'Roberto Alves', 'Mercado Livre', 19.90, 0.99, 3.18, 7.23),
(3, 'Lucia Ferreira', 'Venda Direta', 69.90, 0.00, 0.00, 34.90);

-- Atualizar estoque baseado nas vendas (diminuir quantidades)
UPDATE produtos SET quantidade = quantidade - 1 WHERE id IN (1, 2, 3, 4, 5);
UPDATE produtos SET quantidade = quantidade - 1 WHERE id IN (1, 2, 3);

-- Atualizar estoque baseado nos pedidos entregues (aumentar quantidades)
UPDATE produtos SET quantidade = quantidade + 50 WHERE nome = 'Cabo USB Type-C';