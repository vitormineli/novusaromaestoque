# ERP Vombs Importados

Sistema de gerenciamento empresarial completo para loja online Vombs Importados.

## Funcionalidades

### 📦 Estoque
- Adicionar produtos com nome, quantidade, preços e status
- Status: Aprovado (verde), Análise de Mercado (amarelo), Não Vender (vermelho)
- Alertas automáticos de estoque baixo (quantidade ≤ 1) apenas para produtos aprovados
- Observações para cada produto
- Edição de produtos existentes

### 🛒 Pedidos
- Cadastro de pedidos de reposição
- Local de compra (AliExpress, Shopee, Mercado Livre, etc.)
- Campo para impostos (especialmente AliExpress)
- Status do pedido: Falta Fazer (vermelho) / Pedido Feito (verde)
- Status da entrega: Aguardando (amarelo) / Entregue (verde)
- Atualização automática do estoque quando pedido é entregue

### 🧮 Precificação
- Calculadora de preço ideal
- Considera custo do produto, taxas de marketplace, taxas de cartão e custo de embalagem
- Histórico de cálculos realizados
- Fórmula: Preço Ideal = (Custo Total) / (1 - Taxas Totais)

### 👥 Vendas/Clientes
- Registro de vendas com nome do cliente
- Local da venda (Mercado Livre, Shopee, Instagram, WhatsApp, etc.)
- Cálculo automático de lucro considerando taxas
- Atualização automática do estoque
- Histórico completo de vendas

## Instalação

### Pré-requisitos
- XAMPP instalado
- PHP 7.4 ou superior
- MySQL 5.7 ou superior

### Passos de Instalação

1. **Iniciar o XAMPP**
   - Abra o XAMPP Control Panel
   - Inicie Apache e MySQL

2. **Criar o Banco de Dados**
   - Abra o phpMyAdmin (http://localhost/phpmyadmin)
   - Importe o arquivo `database.sql`
   - Ou execute o SQL manualmente

3. **Configurar o Projeto**
   - Copie todos os arquivos para a pasta `htdocs` do XAMPP
   - Exemplo: `C:\xampp\htdocs\vombs-erp\`

4. **Acessar o Sistema**
   - Abra o navegador
   - Acesse: `http://localhost/vombs-erp/`

## Estrutura de Arquivos

```
vombs-erp/
├── index.php          # Arquivo principal do sistema
├── config.php         # Configuração de banco de dados
├── database.sql       # Estrutura do banco de dados
├── get_produto.php    # API para buscar produto
├── get_pedido.php     # API para buscar pedido
└── README.md          # Este arquivo
```

## Configuração do Banco de Dados

O sistema está configurado para usar:
- **Host**: localhost
- **Database**: vombs_erp
- **Username**: root
- **Password**: (vazio)

Para alterar essas configurações, edite o arquivo `config.php`.

## Uso do Sistema

### Primeiro Acesso
1. Acesse o sistema
2. Vá para a aba "Estoque"
3. Adicione seus primeiros produtos
4. Configure os status conforme necessário

### Fluxo de Trabalho Recomendado
1. **Estoque**: Adicione produtos em "Análise de Mercado"
2. **Precificação**: Calcule o preço ideal para produtos promissores
3. **Pedidos**: Faça pedidos de reposição quando necessário
4. **Vendas**: Registre as vendas realizadas

### Alertas
- Produtos com estoque baixo (≤ 1) aparecem em vermelho
- Alertas são exibidos apenas para produtos "Aprovados"
- Produtos em "Análise de Mercado" não geram alertas

## Recursos Técnicos

- **Frontend**: Bootstrap 5 + Font Awesome
- **Backend**: PHP 7.4+ com PDO
- **Banco**: MySQL
- **Responsivo**: Funciona em desktop e mobile
- **Segurança**: Prepared statements para prevenir SQL injection

## Suporte

Para dúvidas ou sugestões de melhorias, entre em contato.

## Sugestões de Melhorias Futuras

- Dashboard com gráficos de vendas
- Relatórios em PDF
- Integração com APIs de marketplaces
- Sistema de backup automático
- Múltiplos usuários com permissões
- Notificações por email
- App mobile