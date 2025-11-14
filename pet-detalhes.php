<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';

// Verificar se o id do pet foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirectTo('index.php');
}

$pet_id = intval($_GET['id']);
$conn = getConnection();

// Buscar informações do pet
$stmt = $conn->prepare("
    SELECT p.*, c.nome as categoria_nome, u.nome as dono_nome
    FROM pets p
    JOIN categorias c ON p.categoria_id = c.id
    JOIN usuarios u ON p.usuario_id = u.id
    WHERE p.id = :pet_id
");
$stmt->bindParam(':pet_id', $pet_id, PDO::PARAM_INT);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    // Pet não encontrado
    redirectTo('index.php');
}

$pet = $stmt->fetch(PDO::FETCH_ASSOC);

// Verificar se o usuário já solicitou esse pet
$alreadyRequested = false;
if (isLoggedIn()) {
    $checkStmt = $conn->prepare("
        SELECT * FROM adocoes 
        WHERE pet_id = :pet_id AND usuario_id = :user_id AND status IN ('Pendente', 'Aprovado')
    ");
    $checkStmt->bindParam(':pet_id', $pet_id, PDO::PARAM_INT);
    $checkStmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $checkStmt->execute();
    $alreadyRequested = ($checkStmt->rowCount() > 0);
}

// Buscar imagens do pet
$imgStmt = $conn->prepare("SELECT * FROM imagens_pets WHERE pet_id = :pet_id ORDER BY imagem_principal DESC");
$imgStmt->bindParam(':pet_id', $pet_id, PDO::PARAM_INT);
$imgStmt->execute();
$imagens = $imgStmt->fetchAll(PDO::FETCH_ASSOC);

// Processar solicitação de adoção
$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['solicitar_adocao']) && isLoggedIn()) {
    if (!$alreadyRequested && $pet['status'] == 'Disponível') {
        try {
            $mensagem_adocao = isset($_POST['mensagem']) ? $_POST['mensagem'] : '';
            
            $adocaoStmt = $conn->prepare("
                INSERT INTO adocoes (pet_id, usuario_id, mensagem, status)
                VALUES (:pet_id, :user_id, :mensagem, 'Pendente')
            ");
            
            $adocaoStmt->bindParam(':pet_id', $pet_id, PDO::PARAM_INT);
            $adocaoStmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $adocaoStmt->bindParam(':mensagem', $mensagem_adocao, PDO::PARAM_STR);
            $adocaoStmt->execute();
            
            $mensagem = '<div class="alert alert-success">Solicitação de adoção enviada com sucesso! Em breve entraremos em contato.</div>';
            $alreadyRequested = true;
        } catch (PDOException $e) {
            $mensagem = '<div class="alert alert-danger">Erro ao solicitar adoção. Por favor, tente novamente mais tarde.</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pet['nome']) ?> - AdotaAi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Cabeçalho -->
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <?= $mensagem ?>

        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Início</a></li>
                <li class="breadcrumb-item"><a href="index.php?categoria=<?= $pet['categoria_id'] ?>"><?= htmlspecialchars($pet['categoria_nome']) ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($pet['nome']) ?></li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-lg-7 mb-4">
                <?php if (count($imagens) > 0): ?>
                <div class="swiper pet-gallery">
                    <div class="swiper-wrapper">
                        <?php foreach ($imagens as $img): ?>
                        <div class="swiper-slide">
                            <img src="<?= UPLOAD_URL . htmlspecialchars($img['arquivo']) ?>" 
                                class="img-fluid rounded" 
                                alt="Foto de <?= htmlspecialchars($pet['nome']) ?>">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="swiper-pagination"></div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                </div>
                <?php else: ?>
                <img src="assets/images/no-image.png" class="img-fluid rounded" alt="Sem imagem disponível">
                <?php endif; ?>
            </div>

            <div class="col-lg-5">
                <div class="pet-details">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="mb-0"><?= htmlspecialchars($pet['nome']) ?></h1>
                        <span class="badge bg-success fs-6"><?= htmlspecialchars($pet['status']) ?></span>
                    </div>
                    
                    <div class="pet-tags mb-4">
                        <span class="badge bg-primary"><?= htmlspecialchars($pet['categoria_nome']) ?></span>
                        <span class="badge bg-info"><?= htmlspecialchars($pet['idade']) ?></span>
                        <span class="badge bg-secondary"><?= htmlspecialchars($pet['sexo']) ?></span>
                        <span class="badge bg-warning text-dark"><?= htmlspecialchars($pet['porte']) ?></span>
                    </div>

                    <div class="pet-description mb-4">
                        <h5>Descrição</h5>
                        <p><?= nl2br(htmlspecialchars($pet['descricao'])) ?></p>
                    </div>

                    <div class="pet-info mb-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <h5>Status de Saúde</h5>
                                <p><?= nl2br(htmlspecialchars($pet['status_saude'])) ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <h5>Vacinação</h5>
                                <p><?= nl2br(htmlspecialchars($pet['vacinacao'])) ?></p>
                            </div>
                            <div class="col-12 mb-3">
                                <h5>Localização</h5>
                                <p><?= htmlspecialchars($pet['cidade']) ?>, <?= htmlspecialchars($pet['estado']) ?></p>
                            </div>
                        </div>
                    </div>

                    <?php if ($pet['status'] == 'Disponível'): ?>
                        <?php if (isLoggedIn()): ?>
                            <?php if ($alreadyRequested): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> Você já solicitou a adoção deste pet. Por favor, aguarde o contato do responsável.
                                </div>
                            <?php else: ?>
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <h5 class="card-title">Quero adotar!</h5>
                                        <form method="post" action="">
                                            <div class="mb-3">
                                                <label for="mensagem" class="form-label">Mensagem para o doador</label>
                                                <textarea class="form-control" id="mensagem" name="mensagem" rows="3" placeholder="Conte um pouco sobre você e por que deseja adotar este pet"></textarea>
                                            </div>
                                            <button type="submit" name="solicitar_adocao" class="btn btn-primary w-100">Solicitar adoção</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-circle"></i> Para solicitar a adoção, é necessário <a href="login.php">fazer login</a> ou <a href="registro.php">cadastrar-se</a>.
                            </div>
                        <?php endif; ?>
                    <?php elseif ($pet['status'] == 'Adotado'): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-heart"></i> Este pet já foi adotado e ganhou um novo lar!
                        </div>
                    <?php else: ?>
                        <div class="alert alert-secondary">
                            <i class="fas fa-info-circle"></i> Este pet não está disponível para adoção no momento.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Seção de pets similares -->
    <section class="similar-pets py-5 bg-light">
        <div class="container">
            <h2 class="mb-4">Pets similares</h2>
            <div class="row">
                <?php
                // Buscar pets similares (mesma categoria, exceto o atual)
                $similarStmt = $conn->prepare("
                    SELECT p.*, 
                           (SELECT arquivo FROM imagens_pets WHERE pet_id = p.id AND imagem_principal = 1 LIMIT 1) as imagem_principal 
                    FROM pets p 
                    WHERE p.categoria_id = :categoria_id AND p.id != :pet_id AND p.status = 'Disponível'
                    ORDER BY RAND()
                    LIMIT 3
                ");
                $similarStmt->bindParam(':categoria_id', $pet['categoria_id'], PDO::PARAM_INT);
                $similarStmt->bindParam(':pet_id', $pet['id'], PDO::PARAM_INT);
                $similarStmt->execute();
                $similarPets = $similarStmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($similarPets as $similarPet):
                ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="pet-img-container">
                            <?php if ($similarPet['imagem_principal']): ?>
                            <img src="<?= UPLOAD_URL . $similarPet['imagem_principal'] ?>" class="card-img-top" alt="<?= htmlspecialchars($similarPet['nome']) ?>">
                            <?php else: ?>
                            <img src="assets/images/no-image.png" class="card-img-top" alt="Sem imagem">
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($similarPet['nome']) ?></h5>
                            <p class="card-text">
                                <span class="badge bg-info"><?= htmlspecialchars($similarPet['idade']) ?></span>
                                <span class="badge bg-secondary"><?= htmlspecialchars($similarPet['sexo']) ?></span>
                            </p>
                            <p class="card-text pet-description"><?= nl2br(htmlspecialchars(substr($similarPet['descricao'], 0, 100))) ?>...</p>
                        </div>
                        <div class="card-footer text-center">
                            <a href="pet-detalhes.php?id=<?= $similarPet['id'] ?>" class="btn btn-primary">Ver detalhes</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (count($similarPets) == 0): ?>
                <div class="col-12 text-center py-3">
                    <p>Não encontramos outros pets similares no momento.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Rodapé -->
    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const swiper = new Swiper('.pet-gallery', {
                loop: true,
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true,
                },
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
            });
        });
    </script>
</body>
</html>