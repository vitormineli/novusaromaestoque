<?php
// Script de instalação automática do Vombs ERP
session_start();

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($step == 1) {
        // Verificar requisitos
        $requirements = [
            'php' => version_compare(PHP_VERSION, '7.4.0', '>='),
            'pdo_mysql' => extension_loaded('pdo_mysql'),
            'pdo' => extension_loaded('pdo'),
            'config_writable' => is_writable('config/'),
            'database_writable' => is_writable('database.sql')
        ];
        
        if (array_search(false, $requirements) === false) {
            header('Location: install.php?step=2');
            exit;
        } else {
            $error = 'Alguns requisitos não foram atendidos. Verifique a configuração do PHP.';
        }
    } elseif ($step == 2) {
        // Configurar banco de dados
        $host = $_POST['host'];
        $username = $_POST['username'];
        $password = $_POST['password'];
        $database = $_POST['database'];
        
        try {
            // Testar conexão
            $pdo = new PDO("mysql:host=$host", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Criar banco de dados
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database`");
            $pdo->exec("USE `$database`");
            
            // Executar script SQL
            $sql = file_get_contents('database.sql');
            $pdo->exec($sql);
            
            // Salvar configuração
            $config = "<?php
// Configuração da conexão com o banco de dados
\$host = '$host';
\$dbname = '$database';
\$username = '$username';
\$password = '$password';

try {
    \$pdo = new PDO(\"mysql:host=\$host;dbname=\$dbname;charset=utf8\", \$username, \$password);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException \$e) {
    die(\"Erro na conexão com o banco de dados: \" . \$e->getMessage());
}
?>";
            
            file_put_contents('config/database.php', $config);
            
            $success = 'Banco de dados configurado com sucesso!';
            header('Location: install.php?step=3');
            exit;
            
        } catch (Exception $e) {
            $error = 'Erro ao configurar banco de dados: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - Vombs Importados ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">
                            <i class="fas fa-store"></i> Vombs Importados - Instalação
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- Progress Bar -->
                        <div class="progress mb-4">
                            <div class="progress-bar" role="progressbar" style="width: <?= ($step / 3) * 100 ?>%"></div>
                        </div>
                        
                        <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?= $success ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($step == 1): ?>
                        <!-- Passo 1: Verificação de Requisitos -->
                        <h4><i class="fas fa-check-circle"></i> Verificação de Requisitos</h4>
                        <div class="table-responsive">
                            <table class="table">
                                <tbody>
                                    <tr>
                                        <td>PHP 7.4+</td>
                                        <td>
                                            <?php if (version_compare(PHP_VERSION, '7.4.0', '>=')): ?>
                                                <span class="badge bg-success">OK</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Falhou</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Extensão PDO MySQL</td>
                                        <td>
                                            <?php if (extension_loaded('pdo_mysql')): ?>
                                                <span class="badge bg-success">OK</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Falhou</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Pasta config/ gravável</td>
                                        <td>
                                            <?php if (is_writable('config/')): ?>
                                                <span class="badge bg-success">OK</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Falhou</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <form method="POST">
                            <button type="submit" class="btn btn-primary">Continuar</button>
                        </form>
                        
                        <?php elseif ($step == 2): ?>
                        <!-- Passo 2: Configuração do Banco -->
                        <h4><i class="fas fa-database"></i> Configuração do Banco de Dados</h4>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Host do MySQL</label>
                                <input type="text" class="form-control" name="host" value="localhost" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Usuário</label>
                                <input type="text" class="form-control" name="username" value="root" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Senha</label>
                                <input type="password" class="form-control" name="password" value="">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nome do Banco</label>
                                <input type="text" class="form-control" name="database" value="vombs_erp" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Configurar Banco</button>
                        </form>
                        
                        <?php elseif ($step == 3): ?>
                        <!-- Passo 3: Instalação Concluída -->
                        <div class="text-center">
                            <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                            <h4 class="mt-3">Instalação Concluída!</h4>
                            <p class="text-muted">O sistema Vombs Importados ERP foi instalado com sucesso.</p>
                            
                            <div class="alert alert-info">
                                <h6>Próximos Passos:</h6>
                                <ol class="text-start">
                                    <li>Remova o arquivo <code>install.php</code> por segurança</li>
                                    <li>Acesse o sistema em: <a href="index.php">index.php</a></li>
                                    <li>Comece adicionando produtos no estoque</li>
                                </ol>
                            </div>
                            
                            <a href="index.php" class="btn btn-success btn-lg">
                                <i class="fas fa-rocket"></i> Acessar Sistema
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <small class="text-muted">
                        Passo <?= $step ?> de 3 - Vombs Importados ERP
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>