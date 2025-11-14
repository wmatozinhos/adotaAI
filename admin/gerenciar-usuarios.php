<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Verificar se o usuário está logado e é administrador
if (!isLoggedIn() || !isAdmin()) {
    redirectTo('index.php');
}

$conn = getConnection();
$message = '';
$error = '';

// Configuração da paginação
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filtros
$filtroNome = isset($_GET['nome']) ? trim($_GET['nome']) : '';
$filtroEmail = isset($_GET['email']) ? trim($_GET['email']) : '';
$filtroRole = isset($_GET['role']) ? trim($_GET['role']) : '';
$filtroStatus = isset($_GET['status']) ? trim($_GET['status']) : '';

// Construir a consulta SQL com filtros
$whereConditions = [];
$params = [];

if (!empty($filtroNome)) {
    $whereConditions[] = "u.nome LIKE ?";
    $params[] = "%$filtroNome%";
}

if (!empty($filtroEmail)) {
    $whereConditions[] = "u.email LIKE ?";
    $params[] = "%$filtroEmail%";
}

if (!empty($filtroRole)) {
    $whereConditions[] = "u.role = ?";
    $params[] = $filtroRole;
}

if (!empty($filtroStatus)) {
    $whereConditions[] = "u.status = ?";
    $params[] = $filtroStatus;
}

$whereClause = empty($whereConditions) ? "" : "WHERE " . implode(" AND ", $whereConditions);

// Contar total de registros para paginação
$countSql = "SELECT COUNT(*) FROM usuarios u $whereClause";
$countStmt = $conn->prepare($countSql);
for ($i = 0; $i < count($params); $i++) {
    $countStmt->bindValue($i + 1, $params[$i]);
}
$countStmt->execute();
$totalRegistros = $countStmt->fetchColumn();
$totalPaginas = ceil($totalRegistros / $limit);

// Buscar registros
$sql = "
    SELECT u.* 
    FROM usuarios u
    $whereClause
    ORDER BY u.nome
    LIMIT :limit OFFSET :offset
";

$stmt = $conn->prepare($sql);
for ($i = 0; $i < count($params); $i++) {
    $stmt->bindValue($i + 1, $params[$i]);
}
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Processar ação de bloquear/desbloquear usuário
if (isset($_POST['toggle_status']) && isset($_POST['usuario_id'])) {
    $usuarioId = (int)$_POST['usuario_id'];
    $novoStatus = $_POST['novo_status'] === 'ativo' ? 'ativo' : 'inativo';
    $motivo = trim($_POST['motivo'] ?? '');
    
    try {
        // Verificar se o usuário existe
        $checkStmt = $conn->prepare("SELECT id, nome, email, status FROM usuarios WHERE id = ?");
        $checkStmt->execute([$usuarioId]);
        $usuario = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario) {
            throw new Exception('Usuário não encontrado.');
        }
        
        // Não permitir que um admin bloqueie a si mesmo
        if ($usuarioId == $_SESSION['user_id']) {
            throw new Exception('Você não pode alterar seu próprio status.');
        }
        
        // Atualizar status
        $updateStmt = $conn->prepare("UPDATE usuarios SET status = ?, data_atualizacao = NOW() WHERE id = ?");
        $updateStmt->execute([$novoStatus, $usuarioId]);
        
        // Log da atividade
        $acao = $novoStatus === 'ativo' ? 'Ativação de Usuário' : 'Bloqueio de Usuário';
        $descricao = $novoStatus === 'ativo' 
            ? "Usuário {$usuario['nome']} (ID: $usuarioId) foi ativado"
            : "Usuário {$usuario['nome']} (ID: $usuarioId) foi bloqueado. Motivo: $motivo";
        
        logActivity($acao, $descricao, $_SESSION['user_id']);
        
        // Enviar e-mail de notificação ao usuário
        $assunto = $novoStatus === 'ativo' 
            ? "AdotaAi - Sua conta foi ativada" 
            : "AdotaAi - Sua conta foi temporariamente suspensa";
        
        $conteudo = $novoStatus === 'ativo'
            ? "<h2>Olá, {$usuario['nome']}!</h2>
               <p>Informamos que sua conta na plataforma AdotaAi foi reativada e você já pode acessar normalmente.</p>
               <p>Caso tenha dúvidas, entre em contato com nossa equipe de suporte.</p>
               <p>Atenciosamente,<br>Equipe AdotaAi</p>"
            : "<h2>Olá, {$usuario['nome']}!</h2>
               <p>Informamos que sua conta na plataforma AdotaAi foi temporariamente suspensa.</p>
               <p><strong>Motivo:</strong> $motivo</p>
               <p>Para mais informações ou para contestar esta decisão, por favor, responda a este e-mail.</p>
               <p>Atenciosamente,<br>Equipe AdotaAi</p>";
        
        enviarEmail($usuario['email'], $assunto, $conteudo);
        
        $message = $novoStatus === 'ativo' 
            ? "Usuário \"{$usuario['nome']}\" foi ativado com sucesso!" 
            : "Usuário \"{$usuario['nome']}\" foi bloqueado com sucesso!";
        
        // Redirecionar para atualizar a lista
        header("Location: gerenciar-usuarios.php?message=" . urlencode($message));
        exit;
        
    } catch (Exception $e) {
        $error = 'Erro ao atualizar status: ' . $e->getMessage();
    }
}

// Processar ação de alterar papel do usuário
if (isset($_POST['change_role']) && isset($_POST['usuario_id'])) {
    $usuarioId = (int)$_POST['usuario_id'];
    $novoRole = $_POST['novo_role'] === 'admin' ? 'admin' : 'user';
    
    try {
        // Verificar se o usuário existe
        $checkStmt = $conn->prepare("SELECT id, nome, email, role FROM usuarios WHERE id = ?");
        $checkStmt->execute([$usuarioId]);
        $usuario = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario) {
            throw new Exception('Usuário não encontrado.');
        }
        
        // Não permitir que um admin remova seus próprios privilégios
        if ($usuarioId == $_SESSION['user_id'] && $novoRole != 'admin') {
            throw new Exception('Você não pode remover seus próprios privilégios de administrador.');
        }
        
        // Atualizar role
        $updateStmt = $conn->prepare("UPDATE usuarios SET role = ?, data_atualizacao = NOW() WHERE id = ?");
        $updateStmt->execute([$novoRole, $usuarioId]);
        
        // Log da atividade
        $acao = $novoRole === 'admin' ? 'Promoção de Usuário' : 'Rebaixamento de Usuário';
        $descricao = $novoRole === 'admin' 
            ? "Usuário {$usuario['nome']} (ID: $usuarioId) foi promovido a administrador"
            : "Usuário {$usuario['nome']} (ID: $usuarioId) teve privilégios de administrador removidos";
        
        logActivity($acao, $descricao, $_SESSION['user_id']);
        
        // Enviar e-mail de notificação ao usuário
        $assunto = $novoRole === 'admin' 
            ? "AdotaAi - Você agora é um administrador" 
            : "AdotaAi - Alteração no nível de acesso";
        
        $conteudo = $novoRole === 'admin'
            ? "<h2>Olá, {$usuario['nome']}!</h2>
               <p>Informamos que você foi promovido a <strong>administrador</strong> da plataforma AdotaAi.</p>
               <p>Com isso, você terá acesso a recursos avançados de gerenciamento do sistema.</p>
               <p>Para acessar o painel administrativo, faça login e navegue até a área de administração.</p>
               <p>Atenciosamente,<br>Equipe AdotaAi</p>"
            : "<h2>Olá, {$usuario['nome']}!</h2>
               <p>Informamos que houve uma alteração no seu nível de acesso na plataforma AdotaAi.</p>
               <p>Seu acesso agora está configurado como usuário regular.</p>
               <p>Para quaisquer dúvidas, entre em contato com nossa equipe de suporte.</p>
               <p>Atenciosamente,<br>Equipe AdotaAi</p>";
        
        enviarEmail($usuario['email'], $assunto, $conteudo);
        
        $message = $novoRole === 'admin' 
            ? "Usuário \"{$usuario['nome']}\" foi promovido a administrador!" 
            : "Usuário \"{$usuario['nome']}\" agora é um usuário regular!";
        
        // Redirecionar para atualizar a lista
        header("Location: gerenciar-usuarios.php?message=" . urlencode($message));
        exit;
        
    } catch (Exception $e) {
        $error = 'Erro ao alterar papel: ' . $e->getMessage();
    }
}

// Processar ação de excluir usuário
if (isset($_POST['excluir_usuario']) && isset($_POST['usuario_id'])) {
    $usuarioId = (int)$_POST['usuario_id'];
    
    try {
        $conn->beginTransaction();
        
        // Verificar se o usuário existe
        $checkStmt = $conn->prepare("SELECT id, nome FROM usuarios WHERE id = ?");
        $checkStmt->execute([$usuarioId]);
        $usuario = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario) {
            throw new Exception('Usuário não encontrado.');
        }
        
        // Não permitir que um admin exclua a si mesmo
        if ($usuarioId == $_SESSION['user_id']) {
            throw new Exception('Você não pode excluir sua própria conta.');
        }
        
        // Verificar se o usuário possui pets registrados
        $petStmt = $conn->prepare("SELECT COUNT(*) FROM pets WHERE usuario_id = ?");
        $petStmt->execute([$usuarioId]);
        if ($petStmt->fetchColumn() > 0) {
            throw new Exception('Este usuário possui pets cadastrados e não pode ser excluído.');
        }
        
        // Verificar se o usuário adotou algum pet
        $adocaoStmt = $conn->prepare("SELECT COUNT(*) FROM adocoes WHERE usuario_id = ? AND status = 'concluido'");
        $adocaoStmt->execute([$usuarioId]);
        if ($adocaoStmt->fetchColumn() > 0) {
            throw new Exception('Este usuário tem adoções registradas e não pode ser excluído.');
        }
        
        // Excluir interesses do usuário
        $deleteInteressesStmt = $conn->prepare("DELETE FROM interesses WHERE usuario_id = ?");
        $deleteInteressesStmt->execute([$usuarioId]);
        
        // Excluir notificações do usuário
        $deleteNotificacoesStmt = $conn->prepare("DELETE FROM notificacoes WHERE usuario_id = ?");
        $deleteNotificacoesStmt->execute([$usuarioId]);
        
        // Excluir tokens de recuperação
        $deleteTokensStmt = $conn->prepare("DELETE FROM tokens_recuperacao WHERE usuario_id = ?");
        $deleteTokensStmt->execute([$usuarioId]);
        
        // Excluir logs (opcional, pode querer manter para auditoria)
        // $deleteLogsStmt = $conn->prepare("DELETE FROM logs_atividades WHERE usuario_id = ?");
        // $deleteLogsStmt->execute([$usuarioId]);
        
        // Excluir usuário
        $deleteUserStmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
        $deleteUserStmt->execute([$usuarioId]);
        
        // Log da atividade
        logActivity('Exclusão de Usuário', "Usuário {$usuario['nome']} (ID: $usuarioId) foi excluído do sistema");
        
        $conn->commit();
        $message = "Usuário \"{$usuario['nome']}\" excluído com sucesso!";
        
        // Redirecionar para atualizar a lista
        header("Location: gerenciar-usuarios.php?message=" . urlencode($message));
        exit;
        
    } catch (Exception $e) {
        $conn->rollBack();
        $error = 'Erro ao excluir usuário: ' . $e->getMessage();
    }
}

// Mensagem de redirect
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

// Pegar total de pets e adoções de cada usuário
$petsCount = [];
$adocoesCount = [];

if (!empty($usuarios)) {
    $userIds = array_column($usuarios, 'id');
    $placeholders = implode(',', array_fill(0, count($userIds), '?'));
    
    // Contar pets
    $petsSql = "SELECT usuario_id, COUNT(*) as total FROM pets WHERE usuario_id IN ($placeholders) GROUP BY usuario_id";
    $petsStmt = $conn->prepare($petsSql);
    foreach ($userIds as $index => $id) {
        $petsStmt->bindValue($index + 1, $id, PDO::PARAM_INT);
    }
    $petsStmt->execute();
    while ($row = $petsStmt->fetch(PDO::FETCH_ASSOC)) {
        $petsCount[$row['usuario_id']] = $row['total'];
    }
    
    // Contar adoções
    $adocoesSql = "SELECT usuario_id, COUNT(*) as total FROM adocoes WHERE usuario_id IN ($placeholders) AND status = 'concluido' GROUP BY usuario_id";
    $adocoesStmt = $conn->prepare($adocoesSql);
    foreach ($userIds as $index => $id) {
        $adocoesStmt->bindValue($index + 1, $id, PDO::PARAM_INT);
    }
    $adocoesStmt->execute();
    while ($row = $adocoesStmt->fetch(PDO::FETCH_ASSOC)) {
        $adocoesCount[$row['usuario_id']] = $row['total'];
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - AdotaAi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Cabeçalho -->
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">

                <div class="col-lg-9">
    <a href="../index.php" class="btn btn-primary btn-sm me-1">Home</a>
    <a href="gerenciar-pets.php" class="btn btn-primary btn-sm me-1">Gerenciar Pets</a>
    <a href="gerenciar-usuarios.php" class="btn btn-primary btn-sm me-1">Gerenciar Usuários</a>
    <a href="dashboard.php" class="btn btn-primary btn-sm me-1">Dashboard</a>
    <a href="cadastrar-pet.php" class="btn btn-primary btn-sm me-1">Cadastrar Pet</a>
    <a href="aprovar-adocoes.php" class="btn btn-primary btn-sm me-1">Aprovar Adoções</a>
    <a href="logout.php" class="btn btn-danger btn-sm">Sair</a>
        <div class="row">
            <!-- Menu lateral -->
            <?php include 'admin/sidebar.php'; ?>
            
            <div class="col-lg-9">
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="m-0">Gerenciar Usuários</h5>
                        <a href="cadastrar-usuario.php" class="btn btn-light btn-sm">
                            <i class="fas fa-plus me-1"></i> Novo Usuário
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                        <div class="alert alert-success"><?= $message ?></div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <!-- Filtros -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="m-0"><i class="fas fa-filter me-1"></i> Filtros</h6>
                            </div>
                            <div class="card-body">
                                <form action="" method="get" class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Nome</label>
                                        <input type="text" class="form-control" name="nome" value="<?= htmlspecialchars($filtroNome) ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Email</label>
                                        <input type="text" class="form-control" name="email" value="<?= htmlspecialchars($filtroEmail) ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Papel</label>
                                        <select class="form-select" name="role">
                                            <option value="">Todos</option>
                                            <option value="user" <?= $filtroRole === 'user' ? 'selected' : '' ?>>Usuário</option>
                                            <option value="admin" <?= $filtroRole === 'admin' ? 'selected' : '' ?>>Administrador</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" name="status">
                                            <option value="">Todos</option>
                                            <option value="ativo" <?= $filtroStatus === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                                            <option value="inativo" <?= $filtroStatus === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-primary mt-4">Filtrar</button>
                                        <a href="gerenciar-usuarios.php" class="btn btn-secondary mt-4">Limpar Filtros</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Tabela de usuários -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="m-0"><i class="fas fa-users me-1"></i> Usuários</h6>
                            </div>
                            <div class="card-body"></div>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th scope="col">Nome</th>
                                            <th scope="col">Email</th>
                                            <th scope="col">Papel</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $query = "SELECT u.id, u.nome, u.email, u.role, u.status, u.created_at, u.updated_at
                                                    FROM usuarios u
                                                    WHERE u.status = 'ativo'
                                                    ORDER BY u.nome ASC";
                                        $stmt = $conn->prepare($query);
                                        $stmt->execute();
                                        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        ?>
                                        <?php foreach ($users as $user) : ?>
                                            <tr>
                                                <td><?= $user['nome'] ?></td>
                                                <td><?= $user['email'] ?></td>
                                                <td><?= $user['role'] ?></td>
                                                <td><?= $user['status'] ?></td>
                                                <td>
                                                    <a href="editar-usuario.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-primary">Editar</a>
                                                    <a href="excluir-usuario.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-danger">Excluir</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Fim da tabela de usuários -->
            </div>
        </div>
    </div>
    <!-- Fim do conteúdo -->
    <!-- Início do rodapé -->
    <?php include 'includes/footer.php'; ?>
    <!-- Fim do rodapé -->
</body>
</html>