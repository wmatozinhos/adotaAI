
<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';

// Obter categorias
$conn = getConnection();
$stmt = $conn->prepare("SELECT * FROM categorias ORDER BY nome");
$stmt->execute();
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Filtros
$categoria = isset($_GET['categoria']) ? intval($_GET['categoria']) : 0;
$query = "SELECT p.*, c.nome as categoria_nome, 
         (SELECT arquivo FROM imagens_pets WHERE pet_id = p.id AND imagem_principal = 1 LIMIT 1) as imagem_principal 
         FROM pets p 
         JOIN categorias c ON p.categoria_id = c.id 
         WHERE p.status = 'Disponível'";

if ($categoria > 0) {
    $query .= " AND p.categoria_id = :categoria_id";
}

$query .= " ORDER BY p.data_cadastro DESC";
$stmt = $conn->prepare($query);

if ($categoria > 0) {
    $stmt->bindParam(':categoria_id', $categoria, PDO::PARAM_INT);
}

$stmt->execute();
$pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdotaAi - Encontre seu novo amigo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Cabeçalho -->
    <?php include 'includes/header.php'; ?>

    <!-- Banner principal -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4">Encontre seu novo melhor amigo!</h1>
                    <p class="lead">O AdotaAi conecta pets que precisam de um lar com pessoas que têm amor para dar.</p>
                    <div class="hero-buttons">
                        <a href="#pets" class="btn btn-primary btn-lg">Ver Pets Disponíveis</a>
                        <a href="registro.php" class="btn btn-outline-primary btn-lg">Cadastre-se</a>
                        <a href="login.php" class="btn btn-primary btn-lg">Entrar</a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img src="assets/images/hero-pets.png" alt="Pets para adoção" class="img-fluid">
                </div>
            </div>
        </div>
    </section>

    <!-- Categorias -->
    <section class="categories-section py-5">
        <div class="container">
            <h2 class="text-center mb-4">Categorias</h2>
            <div class="row">
                <?php foreach ($categorias as $cat): ?>
                <div class="col-6 col-md-4 col-lg-2 mb-3">
                    <a href="index.php?categoria=<?= $cat['id'] ?>" class="category-card">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-paw fa-2x mb-2"></i>
                                <h5><?= htmlspecialchars($cat['nome']) ?></h5>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Lista de pets -->
    <section id="pets" class="pets-section py-5 bg-light">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Pets disponíveis para adoção</h2>
                <?php if ($categoria > 0): ?>
                <a href="index.php" class="btn btn-outline-secondary">Limpar filtros</a>
                <?php endif; ?>
            </div>

            <div class="row">
                <?php if (count($pets) > 0): ?>
                    <?php foreach ($pets as $pet): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="pet-img-container">
                                <?php if ($pet['imagem_principal']): ?>
                                <img src="<?= UPLOAD_URL . $pet['imagem_principal'] ?>" class="card-img-top" alt="<?= htmlspecialchars($pet['nome']) ?>">
                                <?php else: ?>
                                <img src="assets/images/no-image.png" class="card-img-top" alt="Sem imagem">
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($pet['nome']) ?></h5>
                                <p class="card-text">
                                    <span class="badge bg-primary"><?= htmlspecialchars($pet['categoria_nome']) ?></span>
                                    <span class="badge bg-info"><?= htmlspecialchars($pet['idade']) ?></span>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($pet['sexo']) ?></span>
                                </p>
                                <p class="card-text pet-description"><?= nl2br(htmlspecialchars(substr($pet['descricao'], 0, 100))) ?>...</p>
                            </div>
                            <div class="card-footer text-center">
                                <a href="pet-detalhes.php?id=<?= $pet['id'] ?>" class="btn btn-primary">Ver detalhes</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <div class="col-12 text-center py-5">
                    <h3>Nenhum pet encontrado</h3>
                    <p>Não encontramos pets disponíveis com os filtros atuais.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Como funciona -->
    <section class="how-it-works py-5">
        <div class="container">
            <h2 class="text-center mb-5">Como funciona</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <div class="step-icon mb-3">
                                <i class="fas fa-search fa-3x"></i>
                            </div>
                            <h3>Encontre</h3>
                            <p>Explore nosso catálogo de pets disponíveis para adoção</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <div class="step-icon mb-3">
                                <i class="fas fa-heart fa-3x"></i>
                            </div>
                            <h3>Conheça</h3>
                            <p>Demonstre interesse e agende uma visita para conhecer o pet</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <div class="step-icon mb-3">
                                <i class="fas fa-home fa-3x"></i>
                            </div>
                            <h3>Adote</h3>
                            <p>Complete o processo de adoção e dê um novo lar ao seu amigo</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

<!-- Rodapé -->
<footer class="text-center mt-4">
    <p>© 2025 - Wellington Matozinhos. Todos os direitos reservados.</p>
</footer>