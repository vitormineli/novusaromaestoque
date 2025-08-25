# Vombs Importados - ERP

Sistema ERP completo para gerenciamento de loja online, desenvolvido em PHP com MySQL.

## 🚀 Funcionalidades

### 📦 Estoque
- Adicionar produtos com nome, quantidade, preços e status
- Status diferenciados por cores:
  - 🟡 **Análise de Mercado** - Produtos em teste
  - 🟢 **Aprovado** - Produtos para venda
  - 🔴 **Não Vender** - Produtos descontinuados
- Alerta automático de estoque baixo (quantidade ≤ 1) apenas para produtos aprovados
- Observações para cada produto

### 🛒 Pedidos
- Cadastro de pedidos com nome do produto, preço e local de compra
- Campo automático de imposto quando Aliexpress é selecionado
- Status do pedido:
  - 🔴 **Falta Fazer** - Pedido pendente
  - 🟢 **Pedido Feito** - Pedido realizado
- Status da entrega:
  - 🟡 **Aguardando Entrega** - Produto em trânsito
  - 🟢 **Entregue** - Produto recebido (atualiza estoque automaticamente)

### 💰 Precificação
- Calculadora de preços com:
  - Preço de custo
  - Taxas de marketplace (Mercado Livre 16%, Shopee 12%)
  - Taxas de cartão (Crédito 4.99%, Débito 2.99%)
  - Custo de embalagem (R$ 5,00)
  - Margem de lucro configurável
- Configurações de taxas editáveis

### 👥 Clientes/Vendas
- Registro de vendas com:
  - Seleção de produto do estoque
  - Nome do cliente
  - Local da venda
  - Preço vendido
  - Taxas de cartão e marketplace
  - Cálculo de lucro
- Histórico completo de vendas
- Atualização automática do estoque

## 📋 Requisitos

- XAMPP (Apache + MySQL + PHP)
- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Navegador web moderno

## 🔧 Instalação

1. **Instale o XAMPP**
   - Baixe e instale o XAMPP em: https://www.apachefriends.org/
   - Inicie o Apache e MySQL no painel de controle

2. **Configure o Projeto**
   - Copie todos os arquivos para a pasta `htdocs` do XAMPP
   - Exemplo: `C:\xampp\htdocs\vombs-erp\`

3. **Configure o Banco de Dados**
   - Abra o phpMyAdmin: http://localhost/phpmyadmin
   - Crie um novo banco de dados chamado `vombs_erp`
   - Importe o arquivo `database.sql` para criar as tabelas

4. **Configure a Conexão**
   - Edite o arquivo `config/database.php` se necessário
   - Credenciais padrão: usuário `root`, senha vazia

5. **Acesse o Sistema**
   - Abra o navegador e acesse: http://localhost/vombs-erp/

## 🎯 Como Usar

### Primeiro Acesso
1. Acesse o sistema
2. Vá para a aba **Estoque**
3. Adicione seus primeiros produtos
4. Configure o status como "Análise de Mercado" para produtos em teste

### Fluxo de Trabalho
1. **Análise**: Adicione produtos com status "Análise de Mercado"
2. **Aprovação**: Mude para "Aprovado" quando decidir vender
3. **Pedidos**: Faça pedidos quando estoque estiver baixo
4. **Vendas**: Registre vendas na aba Clientes
5. **Precificação**: Use a calculadora para definir preços ideais

### Alertas
- Produtos com estoque baixo (≤1) aparecem em vermelho
- Alertas são exibidos apenas para produtos "Aprovados"
- Produtos em "Análise" não geram alertas

## 🔧 Configurações

### Taxas Padrão
- **Mercado Livre**: 16%
- **Shopee**: 12%
- **Cartão Crédito**: 4.99%
- **Cartão Débito**: 2.99%
- **Embalagem**: R$ 5,00

### Personalização
Para alterar as taxas, edite diretamente no banco de dados na tabela `config_precificacao`.

## 📊 Estrutura do Banco

### Tabelas Principais
- `produtos` - Cadastro de produtos e estoque
- `pedidos` - Controle de pedidos de reposição
- `vendas` - Histórico de vendas
- `config_precificacao` - Configurações de taxas

## 🛠️ Manutenção

### Backup
- Faça backup regular do banco de dados
- Exporte via phpMyAdmin ou use mysqldump

### Atualizações
- Mantenha o XAMPP atualizado
- Faça backup antes de atualizações

## 🆘 Suporte

Para dúvidas ou problemas:
1. Verifique se o XAMPP está rodando
2. Confirme as configurações do banco de dados
3. Verifique os logs de erro do Apache

## 📝 Notas

- O sistema é responsivo e funciona em dispositivos móveis
- Todos os dados são salvos automaticamente
- O sistema não possui sistema de login (para uso interno)
- Recomenda-se fazer backup regular dos dados

---

**Desenvolvido para Vombs Importados** 🛍️