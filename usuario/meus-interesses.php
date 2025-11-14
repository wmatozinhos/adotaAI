# meus-interesses.php

```php
<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    redirectTo('../login.php');
}

$conn = getConnection();
$user_id = $_SESSION['user_id'];
$message = '';
$error = '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Processar ação de remover interesse
if (isset($_POST['remover_interesse']) && isset($_POST['interesse_id'])) {
    $interesse_id = intval($_POST['interesse_id']);
    
    try {
        // Verificar se o interesse pertence ao usuário
        $checkStmt = $conn->prepare("
            SELECT id FROM interesses 
            WHERE id = :interesse_id AND usuario_id = :user_id
        ");
        $checkStmt->bindParam(':interesse_id', $interesse_id, PDO::PARAM_INT);
        $checkStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            // Remover o interesse
            $deleteStmt = $conn->prepare("DELETE FROM interesses WHERE id = :interesse_id");
            $deleteStmt->bindParam(':interesse_id', $interesse_id, PDO::PARAM_INT);
            $deleteStmt->execute();
            
            $message = 'Interesse removido com sucesso!';
        } else {
            $error = 'Interesse não encontrado ou não pertence ao seu usuário.';
        }
    } catch (PDOException $e) {
        $error = 'Erro ao remover interesse: ' . $e->getMessage();
    }
}

// Buscar interesses do usuário com paginação
try {
    // Contar total de interesses para paginação
    $countStmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM interesses i
        JOIN pets p ON i.pet_id = p.id
        WHERE i.usuario_id = :user_id
    ");
    $countStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $countStmt->execute();
    $total_records = $countStmt->fetchColumn();
    $total_pages = ceil($total_records / $limit);
    
    // Buscar interesses com dados dos pets
    $stmt = $conn->prepare("
        SELECT i.id, i.data_interesse, i.status,
               p.id AS pet_id, p.nome, p.categoria_id, p.sexo, p.porte, p.idade,
               p.cidade, p.estado, u.nome AS dono_nome
        FROM interesses i
        JOIN pets p ON i.pet_id = p.id
        JOIN usuarios u ON p.usuario_id = u.id
        WHERE i.usuario_id = :user_id
        ORDER BY i.data_interesse DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $interesses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar imagens dos pets
    foreach ($interesses as $key => $interesse) {
        $imgStmt = $conn->prepare("
            SELECT arquivo
            FROM imagens_pets 
            WHERE pet_id = :pet_id AND imagem_principal = 1
            LIMIT 1
        ");
        $imgStmt->bindParam(':pet_id', $interesse['pet_id'], PDO::PARAM_INT);
        $imgStmt->execute();
        $imagem = $imgStmt->fetch(PDO::FETCH_ASSOC);
        
        $interesses[$key]['imagem'] = $imagem ? $imagem['arquivo'] : null;
        
        // Buscar categorias
        $catStmt = $conn->prepare("SELECT nome FROM categorias WHERE id = :categoria_id");
        $catStmt->bindParam(':categoria_id', $interesse['categoria_id'], PDO::PARAM_INT);
        $catStmt->execute();
        $categoria = $catStmt->fetch(PDO::FETCH_ASSOC);
        
        $interesses[$key]['categoria_nome'] = $categoria ? $categoria['nome'] : 'Não categorizado';
    }
} catch (PDOException $e) {
    $error = 'Erro ao buscar interesses: ' . $e->getMessage();
    $interesses = [];
    $total_pages = 0;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Interesses - AdotaAi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Cabeçalho -->
    <?php include '../includes/header.php'; ?>

    <div class="container py-5">
        <div class="row">
            <!-- Menu lateral -->
            <?php include 'sidebar.php'; ?>
            
            <div class="col-lg-9">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="m-0">Meus Interesses</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                        <div class="alert alert-success"><?= $message ?></div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <?php if (empty($interesses)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-heart fa-4x text-muted mb-3"></i>
                                <h4 class="text-muted">Você ainda não demonstrou interesse em nenhum pet</h4>
                                <p>Navegue pelo nosso catálogo de pets para encontrar companheiros que você deseja adotar!</p>
                                <a href="../pets.php" class="btn btn-primary mt-2">
                                    <i class="fas fa-paw me-2"></i>Ver Pets Disponíveis
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($interesses as $interesse): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="row g-0">
                                            <div class="col-md-4">
                                                <a href="../pet.php?id=<?= $interesse['pet_id'] ?>">
                                                    <?php if (!empty($interesse['imagem'])): ?>
                                                        <img src="<?= UPLOAD_URL . htmlspecialchars($interesse['imagem']) ?>" 
                                                             class="img-fluid rounded-start h-100 object-fit-cover" 
                                                             alt="Foto de <?= htmlspecialchars($interesse['nome']) ?>">
                                                    <?php else: ?>
                                                        <div class="rounded-start bg-light h-100 d-flex align-items-center justify-content-center">
                                                            <i class="fas fa-paw fa-3x text-secondary"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </a>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <h5 class="card-title">
                                                            <a href="../pet.php?id=<?= $interesse['pet_id'] ?>" class="text-decoration-none text-dark">
                                                                <?= htmlspecialchars($interesse['nome']) ?>
                                                            </a>
                                                        </h5>
                                                        <span class="badge bg-primary"><?= htmlspecialchars($interesse['categoria_nome']) ?></span>
                                                    </div>
                                                    
                                                    <p class="card-text">
                                                        <small>
                                                            <?php if (!empty($interesse['sexo'])): ?>
                                                                <i class="fas <?= $interesse['sexo'] == 'Macho' ? 'fa-mars' : 'fa-venus' ?> me-1"></i>
                                                                <?= htmlspecialchars($interesse['sexo']) ?> 
                                                            <?php endif; ?>
                                                            
                                                            <?php if (!empty($interesse['porte'])): ?>
                                                                <i class="fas fa-ruler-vertical ms-2 me-1"></i>
                                                                <?= htmlspecialchars($interesse['porte']) ?>
                                                            <?php endif; ?>
                                                            
                                                            <?php if (!empty($interesse['idade'])): ?>
                                                                <i class="fas fa-birthday-cake ms-2 me-1"></i>
                                                                <?= htmlspecialchars($interesse['idade']) ?>
                                                            <?php endif; ?>
                                                        </small>
                                                    </p>
                                                    
                                                    <p class="card-text">
                                                        <small>
                                                            <i class="fas fa-user me-1"></i> Dono: <?= htmlspecialchars($interesse['dono_nome']) ?>
                                                        </small>
                                                        <br>
                                                        <small>
                                                            <i class="fas fa-map-marker-alt me-1"></i>
                                                            <?php if (!empty($interesse['cidade']) && !empty($interesse['estado'])): ?>
                                                                <?= htmlspecialchars($interesse['cidade']) ?>/<?= htmlspecialchars($interesse['estado']) ?>
                                                            <?php else: ?>
                                                                Localização não informada
                                                            <?php endif; ?>
                                                        </small>
                                                    </p>
                                                    
                                                    <div class="card-text">
                                                        <small class="text-muted">
                                                            <i class="far fa-clock me-1"></i>
                                                            Interesse manifestado em: 
                                                            <?= date('d/m/Y H:i', strtotime($interesse['data_interesse'])) ?>
                                                        </small>
                                                    </div>
                                                    
                                                    <div class="mt-3 d-flex justify-content-between">
                                                        <a href="../pet.php?id=<?= $interesse['pet_id'] ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye me-1"></i> Ver Pet
                                                        </a>
                                                        
                                                        <form method="post" onsubmit="return confirm('Tem certeza que deseja remover este interesse?');">
                                                            <input type="hidden" name="interesse_id" value="<?= $interesse['id'] ?>">
                                                            <button type="submit" name="remover_interesse" class="btn btn-sm btn-outline-danger">
                                                                <i class="fas fa-heart-broken me-1"></i> Remover Interesse
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Paginação -->
                            <?php if ($total_pages > 1): ?>
                            <nav aria-label="Navegação de página" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Anterior">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Próximo">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rodapé -->
    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```