
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
$categorias = [];
$statusOptions = ['disponivel' => 'Disponível', 'adotado' => 'Adotado', 'pendente' => 'Pendente', 'inativo' => 'Inativo'];
$sexoOptions = ['M' => 'Macho', 'F' => 'Fêmea'];

// Configuração da paginação
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Buscar categorias
try {
    $catStmt = $conn->query("SELECT id, nome FROM categorias ORDER BY nome");
    while ($row = $catStmt->fetch(PDO::FETCH_ASSOC)) {
        $categorias[$row['id']] = $row['nome'];
    }
} catch (PDOException $e) {
    $error = 'Erro ao carregar categorias: ' . $e->getMessage();
}

// Filtros
$filtroNome = isset($_GET['nome']) ? trim($_GET['nome']) : '';
$filtroCategoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
$filtroStatus = isset($_GET['status']) ? trim($_GET['status']) : '';
$filtroUsuario = isset($_GET['usuario']) ? (int)$_GET['usuario'] : 0;

// Construir a consulta SQL com filtros
$whereConditions = [];
$params = [];

if (!empty($filtroNome)) {
    $whereConditions[] = "p.nome LIKE ?";
    $params[] = "%$filtroNome%";
}

if ($filtroCategoria > 0) {
    $whereConditions[] = "p.categoria_id = ?";
    $params[] = $filtroCategoria;
}

if (!empty($filtroStatus)) {
    $whereConditions[] = "p.status = ?";
    $params[] = $filtroStatus;
}

if ($filtroUsuario > 0) {
    $whereConditions[] = "p.usuario_id = ?";
    $params[] = $filtroUsuario;
}

$whereClause = empty($whereConditions) ? "" : "WHERE " . implode(" AND ", $whereConditions);

// Contar total de registros para paginação
$countSql = "SELECT COUNT(*) FROM pets p $whereClause";
$countStmt = $conn->prepare($countSql);
for ($i = 0; $i < count($params); $i++) {
    $countStmt->bindValue($i + 1, $params[$i]);
}
$countStmt->execute();
$totalRegistros = $countStmt->fetchColumn();
$totalPaginas = ceil($totalRegistros / $limit);

// Buscar registros
$sql = "
    SELECT p.*, c.nome as categoria_nome, u.nome as dono_nome 
    FROM pets p
    LEFT JOIN categorias c ON p.categoria_id = c.id
    LEFT JOIN usuarios u ON p.usuario_id = u.id
    $whereClause
    ORDER BY p.data_cadastro DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $conn->prepare($sql);
for ($i = 0; $i < count($params); $i++) {
    $stmt->bindValue($i + 1, $params[$i]);
}
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$pets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar imagens principais dos pets
$imagens = [];
if (!empty($pets)) {
    $petIds = array_column($pets, 'id');
    $placeholders = implode(',', array_fill(0, count($petIds), '?'));
    
    $imgSql = "SELECT pet_id, arquivo FROM imagens_pets WHERE pet_id IN ($placeholders) AND imagem_principal = 1";
    $imgStmt = $conn->prepare($imgSql);
    
    foreach ($petIds as $index => $id) {
        $imgStmt->bindValue($index + 1, $id, PDO::PARAM_INT);
    }
    
    $imgStmt->execute();
    $imagensResult = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($imagensResult as $imagem) {
        $imagens[$imagem['pet_id']] = $imagem['arquivo'];
    }
}

// Deletar pet
if (isset($_POST['excluir_pet']) && isset($_POST['pet_id'])) {
    $petId = (int)$_POST['pet_id'];
    
    try {
        $conn->beginTransaction();
        
        // Verificar se o pet existe
        $checkStmt = $conn->prepare("SELECT id, nome FROM pets WHERE id = ?");
        $checkStmt->execute([$petId]);
        $pet = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$pet) {
            throw new Exception('Pet não encontrado.');
        }
        
        // Verificar se há adoções concluídas para este pet
        $adocaoStmt = $conn->prepare("
            SELECT COUNT(*) FROM adocoes 
            WHERE pet_id = ? AND status = 'concluido'
        ");
        $adocaoStmt->execute([$petId]);
        if ($adocaoStmt->fetchColumn() > 0) {
            throw new Exception('Não é possível excluir este pet pois ele já foi adotado.');
        }
        
        // Excluir interesses relacionados
        $deleteInteressesStmt = $conn->prepare("DELETE FROM interesses WHERE pet_id = ?");
        $deleteInteressesStmt->execute([$petId]);
        
        // Buscar e excluir imagens físicas
        $imgStmt = $conn->prepare("SELECT arquivo FROM imagens_pets WHERE pet_id = ?");
        $imgStmt->execute([$petId]);
        while ($img = $imgStmt->fetch(PDO::FETCH_ASSOC)) {
            $caminhoImagem = UPLOAD_DIR . $img['arquivo'];
            if (file_exists($caminhoImagem)) {
                unlink($caminhoImagem);
            }
        }
        
        // Excluir registros de imagens
        $deleteImgsStmt = $conn->prepare("DELETE FROM imagens_pets WHERE pet_id = ?");
        $deleteImgsStmt->execute([$petId]);
        
        // Excluir pet
        $deletePetStmt = $conn->prepare("DELETE FROM pets WHERE id = ?");
        $deletePetStmt->execute([$petId]);
        
        // Log da atividade
        logActivity('Exclusão de Pet', "Pet {$pet['nome']} (ID: $petId) foi excluído do sistema");
        
        $conn->commit();
        $message = "Pet \"{$pet['nome']}\" excluído com sucesso!";
        
        // Redirecionar para atualizar a lista
        header("Location: gerenciar-pets.php?message=" . urlencode($message));
        exit;
        
    } catch (Exception $e) {
        $conn->rollBack();
        $error = 'Erro ao excluir pet: ' . $e->getMessage();
    }
}

// Mensagem de redirect
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

// Buscar usuários para o filtro
$usuarios = [];
try {
    $userStmt = $conn->query("SELECT id, nome FROM usuarios WHERE role = 'user' ORDER BY nome");
    while ($row = $userStmt->fetch(PDO::FETCH_ASSOC)) {
        $usuarios[$row['id']] = $row['nome'];
    }
} catch (PDOException $e) {
    // Silenciar erro, não é crítico
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Pets - AdotaAi</title>
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
    <a href="../index.php" class="btn btn-primary btn-sm me-1">Home</a>
    <a href="gerenciar-pets.php" class="btn btn-primary btn-sm me-1">Gerenciar Pets</a>
    <a href="gerenciar-usuarios.php" class="btn btn-primary btn-sm me-1">Gerenciar Usuários</a>
    <a href="dashboard.php" class="btn btn-primary btn-sm me-1">Dashboard</a>
    <a href="cadastrar-pet.php" class="btn btn-primary btn-sm me-1">Cadastrar Pet</a>
    <a href="aprovar-adocoes.php" class="btn btn-primary btn-sm me-1">Aprovar Adoções</a>
    <a href="logout.php" class="btn btn-danger btn-sm">Sair</a>
                
            <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="m-0">Gerenciar Pets</h5>
                        <a href="cadastrar-pet.php" class="btn btn-light btn-sm">
                            <i class="fas fa-plus me-1"></i> Novo Pet
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
                                    <div class="col-lg-3 col-md-6">
                                        <label class="form-label">Nome</label>
                                        <input type="text" class="form-control" name="nome" value="<?= htmlspecialchars($filtroNome) ?>">
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <label class="form-label">Categoria</label>
                                        <select class="form-select" name="categoria">
                                            <option value="0">Todas</option>
                                            <?php foreach ($categorias as $id => $nome): ?>
                                                <option value="<?= $id ?>" <?= $filtroCategoria == $id ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($nome) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" name="status">
                                            <option value="">Todos</option>
                                            <?php foreach ($statusOptions as $value => $label): ?>
                                                <option value="<?= $value ?>" <?= $filtroStatus === $value ? 'selected' : '' ?>>
                                                    <?= $label ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <label class="form-label">Dono</label>
                                        <select class="form-select" name="usuario">
                                            <option value="0">Todos</option>
                                            <?php foreach ($usuarios as $id => $nome): ?>
                                                <option value="<?= $id ?>" <?= $filtroUsuario == $id ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($nome) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12 d-flex justify-content-end">
                                        <a href="gerenciar-pets.php" class="btn btn-secondary me-2">Limpar</a>
                                        <button type="submit" class="btn btn-primary">Filtrar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Lista de Pets -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Foto</th>
                                        <th>Nome</th>
                                        <th>Categoria</th>
                                        <th>Status</th>
                                        <th>Dono</th>
                                        <th>Cadastro</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($pets)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-search fa-2x mb-3 text-muted"></i>
                                            <p class="text-muted mb-0">Nenhum pet encontrado com os filtros aplicados.</p>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($pets as $pet): ?>
                                        <tr>
                                            <td><?= $pet['id'] ?></td>
                                            <td>
                                                <?php if (isset($imagens[$pet['id']])): ?>
                                                    <img src="<?= UPLOAD_URL . $imagens[$pet['id']] ?>" 
                                                        alt="<?= htmlspecialchars($pet['nome']) ?>" 
                                                        class="img-thumbnail" 
                                                        style="width: 50px; height: 50px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-light d-flex align-items-center justify-content-center" 
                                                        style="width: 50px; height: 50px;">
                                                        <i class="fas fa-paw text-secondary"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($pet['nome']) ?>
                                                <?php if (!empty($pet['raca'])): ?>
                                                    <small class="d-block text-muted"><?= htmlspecialchars($pet['raca']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($pet['categoria_nome']) ?></td>
                                            <td>
                                                <span class="badge <?= 
                                                    $pet['status'] == 'disponivel' ? 'bg-success' : 
                                                    ($pet['status'] == 'adotado' ? 'bg-info' : 
                                                    ($pet['status'] == 'pendente' ? 'bg-warning' : 'bg-secondary')) 
                                                ?>">
                                                    <?= ucfirst($pet['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($pet['dono_nome']) ?></td>
                                            <td><?= date('d/m/Y', strtotime($pet['data_cadastro'])) ?></td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <a href="pet.php?id=<?= $pet['id'] ?>" class="btn btn-sm btn-outline-primary" title="Ver">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="editar-pet.php?id=<?= $pet['id'] ?>" class="btn btn-sm btn-outline-warning" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#deletePetModal" 
                                                            data-pet-id="<?= $pet['id'] ?>" 
                                                            data-pet-nome="<?= htmlspecialchars($pet['nome']) ?>" 
                                                            title="Excluir">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Paginação -->
                        <?php if ($totalPaginas > 1): ?>
                        <nav aria-label="Navegação de página">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=1<?= !empty($filtroNome) ? '&nome=' . urlencode($filtroNome) : '' ?><?= $filtroCategoria ? '&categoria=' . $filtroCategoria : '' ?><?= !empty($filtroStatus) ? '&status=' . $filtroStatus : '' ?><?= $filtroUsuario ? '&usuario=' . $filtroUsuario : '' ?>">
                                        Primeira
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?><?= !empty($filtroNome) ? '&nome=' . urlencode($filtroNome) : '' ?><?= $filtroCategoria ? '&categoria=' . $filtroCategoria : '' ?><?= !empty($filtroStatus) ? '&status=' . $filtroStatus : '' ?><?= $filtroUsuario ? '&usuario=' . $filtroUsuario : '' ?>">
                                        &laquo;
                                    </a>
                                </li>
                                <?php endif; ?>
                                
                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPaginas, $page + 2);
                                for ($i = $startPage; $i <=     $endPage; $i++):
                                ?>
                                <li class="page-item<?= $i == $page ? ' active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?><?= !empty($filtroNome) ? '&nome=' . urlencode($filtroNome) : '' ?><?= $filtroCategoria ? '&categoria=' . $filtroCategoria : '' ?><?= !empty($filtroStatus) ? '&status=' . $filtroStatus : '' ?><?= $filtroUsuario ? '&usuario=' . $filtroUsuario : '' ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                                <?php if ($page < $totalPaginas): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?><?= !empty($filtroNome) ? '&nome=' . urlencode($filtroNome) : '' ?><?= $filtroCategoria ? '&categoria=' . $filtroCategoria : '' ?><?= !empty($filtroStatus) ? '&status=' . $filtroStatus : '' ?><?= $filtroUsuario ? '&usuario=' . $filtroUsuario : '' ?>">
                                        &raquo;
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $totalPaginas ?><?= !empty($filtroNome) ? '&nome=' . urlencode($filtroNome) : '' ?><?= $filtroCategoria ? '&categoria=' . $filtroCategoria : '' ?><?= !empty($filtroStatus) ? '&status=' . $filtroStatus : '' ?><?= $filtroUsuario ? '&usuario=' . $filtroUsuario : '' ?>">
                                        Última
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
