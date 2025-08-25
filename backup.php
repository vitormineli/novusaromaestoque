<?php
// Script de backup automático do banco de dados
require_once 'config/database.php';

// Configurações
$backup_dir = 'backups/';
$filename = 'vombs_erp_backup_' . date('Y-m-d_H-i-s') . '.sql';

// Criar pasta de backup se não existir
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Função para fazer backup
function backupDatabase($pdo, $backup_dir, $filename) {
    try {
        // Obter todas as tabelas
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        $backup_content = "-- Backup do Vombs Importados ERP\n";
        $backup_content .= "-- Data: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($tables as $table) {
            // Estrutura da tabela
            $create_table = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
            $backup_content .= "\n-- Estrutura da tabela `$table`\n";
            $backup_content .= "DROP TABLE IF EXISTS `$table`;\n";
            $backup_content .= $create_table['Create Table'] . ";\n\n";
            
            // Dados da tabela
            $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll();
            if (!empty($rows)) {
                $backup_content .= "-- Dados da tabela `$table`\n";
                foreach ($rows as $row) {
                    $values = array_map(function($value) {
                        if ($value === null) return 'NULL';
                        return "'" . addslashes($value) . "'";
                    }, $row);
                    $backup_content .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
                }
                $backup_content .= "\n";
            }
        }
        
        // Salvar arquivo
        file_put_contents($backup_dir . $filename, $backup_content);
        return true;
        
    } catch (Exception $e) {
        return false;
    }
}

// Executar backup
if (backupDatabase($pdo, $backup_dir, $filename)) {
    echo "Backup realizado com sucesso: $filename";
} else {
    echo "Erro ao realizar backup";
}
?>