# categorias.php

```php
<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';

// Verificar se o usuário está logado e é administrador
if (!isLoggedIn() || !isAdmin()) {
    redirectTo('index.php');
}

$conn = getConnection();
$message = '';
$error = '';

// Buscar todas as categorias
try {
    $stmt = $conn->query("SELECT * FROM categorias ORDER BY nome");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Erro ao buscar categorias: ' . $e->getMessage();
    $categorias = [];
}

// Adicionar nova categoria
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['adicionar'])) {
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    
    if (empty($nome)) {
        $error = 'O nome da categoria é obrigatório.';
    } else {
        try {
            // Verificar se já existe uma categoria com o mesmo nome
            $checkStmt = $conn->prepare("SELECT id FROM categorias WHERE nome = :nome");
            $checkStmt->bindParam(':nome', $nome);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                $error = 'Já existe uma categoria com este nome.';
            } else {
                $insertStmt = $conn->prepare("
                    INSERT INTO categorias (nome, descricao, data_cadastro)
                    VALUES (:nome, :descricao, NOW())
                ");
                
                $insertStmt->bindParam(':nome', $nome);
                $insertStmt->bindParam(':descricao', $descricao);
                $insertStmt->execute();
                
                $message = 'Categoria adicionada com sucesso!';
                
                // Recarregar lista de categorias
                $stmt = $conn->query("SELECT * FROM categorias ORDER BY nome");
                $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            $error = 'Erro ao adicionar categoria: ' . $e->getMessage();
        }
    }
}

// Atualizar categoria existente
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['atualizar'])) {
    $id = intval($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    
    if (empty($nome)) {
        $error = 'O nome da categoria é obrigatório.';
    } else {
        try {
            // Verificar se já existe outra categoria com o mesmo nome
            $checkStmt = $conn->prepare("SELECT id FROM categorias WHERE nome = :nome AND id != :id");
            $checkStmt->bindParam(':nome', $nome);
            $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                $error = 'Já existe outra categoria com este nome.';
            } else {
                $updateStmt = $conn->prepare("
                    UPDATE categorias 
                    SET nome = :nome, descricao = :descricao
                    WHERE id = :id
                ");
                
                $updateStmt->bindParam(':nome', $nome);
                $updateStmt->bindParam(':descricao', $descricao);
                $updateStmt->bindParam(':id', $id, PDO::PARAM_INT);
                $updateStmt->execute();
                
                $message = 'Categoria atualizada com sucesso!';
                
                // Recarregar lista de categorias
                $stmt = $conn->query("SELECT * FROM categorias ORDER BY nome");
                $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            $error = 'Erro ao atualizar categoria: ' . $e->getMessage();
        }
    }
}

// Excluir categoria
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['excluir'])) {
    $id = intval($_POST['id'] ?? 0);
    
    try {
        // Verificar se existem pets associados a essa categoria
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM pets WHERE categoria_id = :id");
        $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
        $checkStmt->execute();
        
        if ($checkStmt->fetchColumn() > 0) {
            $error = 'Não é possível excluir esta categoria pois há pets associados a ela.';
        } else {
            $deleteStmt = $conn->prepare("DELETE FROM categorias WHERE id = :id");
            $deleteStmt->bindParam(':id', $id, PDO::PARAM_INT);
            $deleteStmt->execute();
            
            $message = 'Categoria excluída com sucesso!';
            
            // Recarregar lista de categorias
            $stmt = $conn->query("SELECT * FROM categorias ORDER BY nome");
            $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $error = 'Erro ao excluir categoria: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Categorias - AdotaAi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Cabeçalho -->
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <div class="row">
            <!-- Menu lateral -->
            <?php include 'admin/sidebar.php'; ?>
            
            <div class="col-lg-9">
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="m-0">Gerenciar Categorias</h5>
                        <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addCategoriaModal">
                            <i class="fas fa-plus me-1"></i> Nova Categoria
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                        <div class="alert alert-success"><?= $message ?></div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <?php if (empty($categorias)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                                <h4 class="text-muted">Nenhuma categoria cadastrada</h4>
                                <p>Comece adicionando uma nova categoria de pets.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">Nome</th>
                                            <th scope="col">Descrição</th>
                                            <th scope="col">Data de Cadastro</th>
                                            <th scope="col" class="text-center">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categorias as $categoria): ?>
                                        <tr>
                                            <td><?= $categoria['id'] ?></td>
                                            <td><?= htmlspecialchars($categoria['nome']) ?></td>
                                            <td>
                                                <?php if (!empty($categoria['descricao'])): ?>
                                                    <?= htmlspecialchars($categoria['descricao']) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Sem descrição</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= date('d/m/Y H:i', strtotime($categoria['data_cadastro'])) ?>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-primary edit-categoria" 
                                                        data-id="<?= $categoria['id'] ?>"
                                                        data-nome="<?= htmlspecialchars($categoria['nome']) ?>"
                                                        data-descricao="<?= htmlspecialchars($categoria['descricao'] ?? '') ?>"
                                                        data-bs-toggle="modal" data-bs-target="#editCategoriaModal">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-categoria"
                                                        data-id="<?= $categoria['id'] ?>"
                                                        data-nome="<?= htmlspecialchars($categoria['nome']) ?>"
                                                        data-bs-toggle="modal" data-bs-target="#deleteCategoriaModal">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Adicionar Categoria -->
    <div class="modal fade" id="addCategoriaModal" tabindex="-1" aria-labelledby="addCategoriaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addCategoriaModalLabel">Nova Categoria</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome da Categoria *</label>
                            <input type="text" class="form-control" id="nome" name="nome" required>
                        </div>
                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="adicionar" class="btn btn-primary">Adicionar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Categoria -->
    <div class="modal fade" id="editCategoriaModal" tabindex="-1" aria-labelledby="editCategoriaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editCategoriaModalLabel">Editar Categoria</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="edit_id" name="id">
                        <div class="mb-3">
                            <label for="edit_nome" class="form-label">Nome da Categoria *</label>
                            <input type="text" class="form-control" id="edit_nome" name="nome" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="edit_descricao" name="descricao" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="atualizar" class="btn btn-primary">Atualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Excluir Categoria -->
    <div class="modal fade" id="deleteCategoriaModal" tabindex="-1" aria-labelledby="deleteCategoriaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteCategoriaModalLabel">Confirmar Exclusão</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="delete_id" name="id">
                        <p>Tem certeza que deseja excluir a categoria <strong id="delete_nome"></strong>?</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Esta ação não pode ser desfeita. A categoria só poderá ser excluída se não houver pets associados a ela.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="excluir" class="btn btn-danger">Excluir</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Rodapé -->
    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preencher modal de edição com os dados da categoria
        document.querySelectorAll('.edit-categoria').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit_id').value = this.dataset.id;
                document.getElementById('edit_nome').value = this.dataset.nome;
                document.getElementById('edit_descricao').value = this.dataset.descricao;
            });
        });
        
        // Preencher modal de exclusão com os dados da categoria
        document.querySelectorAll('.delete-categoria').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('delete_id').value = this.dataset.id;
                document.getElementById('delete_nome').textContent = this.dataset.nome;
            });
        });
    </script>
</body>
</html>
```