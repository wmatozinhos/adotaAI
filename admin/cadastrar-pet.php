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

// Buscar categorias para o formulário
$categorias = [];
try {
    $catStmt = $conn->query("SELECT id, nome FROM categorias ORDER BY nome");
    $categorias = $catStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Erro ao carregar categorias: ' . $e->getMessage();
}

// Processar o formulário de cadastro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $categoriaId = (int)$_POST['categoria'];
    $sexo = $_POST['sexo'];
    $status = $_POST['status'];
    $descricao = trim($_POST['descricao']);
    $usuarioId = $_SESSION['user_id']; // ID do usuário logado

    // Validar campos obrigatórios
    if (empty($nome) || empty($categoriaId) || empty($sexo) || empty($status)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $conn->beginTransaction();

            // Inserir o pet no banco de dados
            $stmt = $conn->prepare("
                INSERT INTO pets (nome, categoria_id, sexo, status, descricao, usuario_id, data_cadastro)
                VALUES (:nome, :categoria_id, :sexo, :status, :descricao, :usuario_id, NOW())
            ");
            $stmt->execute([
                ':nome' => $nome,
                ':categoria_id' => $categoriaId,
                ':sexo' => $sexo,
                ':status' => $status,
                ':descricao' => $descricao,
                ':usuario_id' => $usuarioId,
            ]);

            $petId = $conn->lastInsertId();

            // Processar upload de imagem
            if (!empty($_FILES['imagem']['name'])) {
                $imagem = $_FILES['imagem'];
                $extensao = pathinfo($imagem['name'], PATHINFO_EXTENSION);
                $nomeArquivo = uniqid('pet_') . '.' . $extensao;
                $caminhoDestino = UPLOAD_DIR . $nomeArquivo;

                if (move_uploaded_file($imagem['tmp_name'], $caminhoDestino)) {
                    $stmt = $conn->prepare("
                        INSERT INTO imagens_pets (pet_id, arquivo, imagem_principal)
                        VALUES (:pet_id, :arquivo, 1)
                    ");
                    $stmt->execute([
                        ':pet_id' => $petId,
                        ':arquivo' => $nomeArquivo,
                    ]);
                } else {
                    throw new Exception('Erro ao fazer upload da imagem.');
                }
            }

            $conn->commit();
            $message = 'Pet cadastrado com sucesso!';
        } catch (Exception $e) {
            $conn->rollBack();
            $error = 'Erro ao cadastrar pet: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Pet - AdotaAi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="d-flex flex-wrap justify-content-center mb-3 mt-4">
                    <a href="../index.php" class="btn btn-primary btn-sm me-1 mb-1">Home</a>
                    <a href="gerenciar-pets.php" class="btn btn-primary btn-sm me-1 mb-1">Gerenciar Pets</a>
                    <a href="gerenciar-usuarios.php" class="btn btn-primary btn-sm me-1 mb-1">Gerenciar Usuários</a>
                    <a href="dashboard.php" class="btn btn-primary btn-sm me-1 mb-1">Dashboard</a>
                    <a href="cadastrar-pet.php" class="btn btn-primary btn-sm me-1 mb-1">Cadastrar Pet</a>
                    <a href="aprovar-adocoes.php" class="btn btn-primary btn-sm me-1 mb-1">Aprovar Adoções</a>
                    <a href="logout.php" class="btn btn-danger btn-sm mb-1">Sair</a>
                </div>
                
                <h1 class="mb-4 text-center">Cadastrar Pet</h1>

                <?php if ($message): ?>
                <div class="alert alert-success"><?= $message ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <div class="card shadow">
                    <div class="card-body">
                        <form action="" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome do Pet</label>
                                <input type="text" class="form-control" id="nome" name="nome" required>
                            </div>
                            <div class="mb-3">
                                <label for="categoria" class="form-label">Categoria</label>
                                <select class="form-select" id="categoria" name="categoria" required>
                                    <option value="">Selecione uma categoria</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= $categoria['id'] ?>"><?= htmlspecialchars($categoria['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="sexo" class="form-label">Sexo</label>
                                <select class="form-select" id="sexo" name="sexo" required>
                                    <option value="M">Macho</option>
                                    <option value="F">Fêmea</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="disponivel">Disponível</option>
                                    <option value="adotado">Adotado</option>
                                    <option value="pendente">Pendente</option>
                                    <option value="inativo">Inativo</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="descricao" class="form-label">Descrição</label>
                                <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="imagem" class="form-label">Imagem do Pet</label>
                                <input type="file" class="form-control" id="imagem" name="imagem" accept="image/*">
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">Cadastrar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>