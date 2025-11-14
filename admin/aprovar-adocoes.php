
<?php
// Iniciar sessão e verificar se o usuário está logado e é administrador
session_start();
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

// Processar ação de aprovar adoção
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['interesse_id'])) {
    $interesse_id = intval($_POST['interesse_id']);

    try {
        $conn->beginTransaction();

        // Atualizar status do interesse para aprovado
        $updateInteresse = $conn->prepare("
            UPDATE interesses
            SET status = 'aprovado', data_atualizacao = NOW()
            WHERE id = :interesse_id
        ");
        $updateInteresse->bindParam(':interesse_id', $interesse_id, PDO::PARAM_INT);
        $updateInteresse->execute();

        // Atualizar status do pet para adotado
        $updatePet = $conn->prepare("
            UPDATE pets
            SET status = 'Adotado'
            WHERE id = (SELECT pet_id FROM interesses WHERE id = :interesse_id)
        ");
        $updatePet->bindParam(':interesse_id', $interesse_id, PDO::PARAM_INT);
        $updatePet->execute();

        // Rejeitar outros interesses no mesmo pet
        $rejectOthers = $conn->prepare("
            UPDATE interesses
            SET status = 'rejeitado', data_atualizacao = NOW()
            WHERE pet_id = (SELECT pet_id FROM interesses WHERE id = :interesse_id)
            AND id != :interesse_id
        ");
        $rejectOthers->bindParam(':interesse_id', $interesse_id, PDO::PARAM_INT);
        $rejectOthers->execute();

        $conn->commit();
        $message = "Adoção aprovada com sucesso!";
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Erro ao aprovar adoção: " . $e->getMessage();
    }
}

// Buscar solicitações pendentes
$stmt = $conn->prepare("
    SELECT i.id, p.nome AS nome_pet, u.nome AS nome_adotante
    FROM interesses i
    JOIN pets p ON i.pet_id = p.id
    JOIN usuarios u ON i.usuario_id = u.id
    WHERE i.status = 'pendente'
");
$stmt->execute();
$solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aprovar Adoções</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-md-11 col-sm-12">
                <!-- Menu de navegação centralizado -->
                <div class="d-flex flex-wrap justify-content-center mb-4">
                    <a href="../index.php" class="btn btn-primary btn-sm me-1 mb-1">Home</a>
                    <a href="gerenciar-pets.php" class="btn btn-primary btn-sm me-1 mb-1">Gerenciar Pets</a>
                    <a href="gerenciar-usuarios.php" class="btn btn-primary btn-sm me-1 mb-1">Gerenciar Usuários</a>
                    <a href="dashboard.php" class="btn btn-primary btn-sm me-1 mb-1">Dashboard</a>
                    <a href="cadastrar-pet.php" class="btn btn-primary btn-sm me-1 mb-1">Cadastrar Pet</a>
                    <a href="aprovar-adocoes.php" class="btn btn-primary btn-sm me-1 mb-1">Aprovar Adoções</a>
                    <a href="logout.php" class="btn btn-danger btn-sm mb-1">Sair</a>
                </div>

                <!-- Título centralizado -->
                <h1 class="mb-4 text-center">Aprovar Solicitações de Adoção</h1>

                <!-- Mensagens de alerta -->
                <?php if ($message): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- Tabela de solicitações em um card -->
                <div class="card shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nome do Pet</th>
                                        <th>Adotante</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($solicitacoes) > 0): ?>
                                        <?php foreach ($solicitacoes as $solicitacao): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($solicitacao['nome_pet']) ?></td>
                                                <td><?= htmlspecialchars($solicitacao['nome_adotante']) ?></td>
                                                <td class="text-center">
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="interesse_id" value="<?= $solicitacao['id'] ?>">
                                                        <button type="submit" class="btn btn-success btn-sm">Aprovar</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center">Nenhuma solicitação pendente.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>