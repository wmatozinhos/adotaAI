
<?php

require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Verificar se o usuário está logado e é administrador
if (!isLoggedIn() || !isAdmin()) {
    redirectTo('index.php');
}

$conn = getConnection();

// Estatísticas gerais
try {
    // Total de pets cadastrados
    $totalPetsStmt = $conn->query("SELECT COUNT(*) FROM pets");
    $totalPets = $totalPetsStmt->fetchColumn();
    
    // Pets por status
    $statusStmt = $conn->query("
        SELECT status, COUNT(*) AS total 
        FROM pets 
        GROUP BY status
    ");
    $petsPorStatus = [];
    while ($row = $statusStmt->fetch(PDO::FETCH_ASSOC)) {
        $petsPorStatus[$row['status']] = $row['total'];
    }
    
    // Total de usuários
    $totalUsuariosStmt = $conn->query("SELECT COUNT(*) FROM usuarios");
    $totalUsuarios = $totalUsuariosStmt->fetchColumn();
    
    // Usuários cadastrados nos últimos 30 dias
    $novosCadastrosStmt = $conn->query("
        SELECT COUNT(*) FROM usuarios 
        WHERE data_cadastro >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");
    $novosCadastros = $novosCadastrosStmt->fetchColumn();
    
    // Total de adoções concluídas
    $adocoesConcluidasStmt = $conn->query("
        SELECT COUNT(*) FROM adocoes 
        WHERE status = 'concluido'
    ");
    $adocoesConcluidas = $adocoesConcluidasStmt->fetchColumn();
    
    // Adoções nos últimos 30 dias
    $adocoesRecentesStmt = $conn->query("
        SELECT COUNT(*) FROM adocoes 
        WHERE data_adocao >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
        AND status = 'concluido'
    ");
    $adocoesRecentes = $adocoesRecentesStmt->fetchColumn();
    
    // Solicitações pendentes
    $solicitacoesPendentesStmt = $conn->query("
        SELECT COUNT(*) FROM interesses 
        WHERE status = 'pendente'
    ");
    $solicitacoesPendentes = $solicitacoesPendentesStmt->fetchColumn();
    
    // Atividade recente - últimos registros (logs)
    $atividadesStmt = $conn->query("
        SELECT l.*, u.nome as nome_usuario
        FROM logs_atividades l
        LEFT JOIN usuarios u ON l.usuario_id = u.id
        ORDER BY l.data_hora DESC
        LIMIT 10
    ");
    $atividades = $atividadesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Pets recentemente adicionados
    $petsRecentesStmt = $conn->query("
        SELECT p.*, u.nome as nome_usuario, c.nome as categoria_nome
        FROM pets p
        JOIN usuarios u ON p.usuario_id = u.id
        JOIN categorias c ON p.categoria_id = c.id
        ORDER BY p.data_cadastro DESC
        LIMIT 5
    ");
    $petsRecentes = $petsRecentesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar imagens dos pets recentes
    $petIds = array_column($petsRecentes, 'id');
    $imagensPets = [];
    
    if (!empty($petIds)) {
        $placeholders = implode(',', array_fill(0, count($petIds), '?'));
        $imgSql = "
            SELECT pet_id, arquivo 
            FROM imagens_pets 
            WHERE pet_id IN ($placeholders) AND imagem_principal = 1
        ";
        $imgStmt = $conn->prepare($imgSql);
        
        foreach ($petIds as $index => $id) {
            $imgStmt->bindValue($index + 1, $id, PDO::PARAM_INT);
        }
        
        $imgStmt->execute();
        $imagensResult = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($imagensResult as $img) {
            $imagensPets[$img['pet_id']] = $img['arquivo'];
        }
    }
    
    // Adoções recentes
    $adocoesRecentesDetalhesStmt = $conn->query("
        SELECT a.*, 
            p.nome as nome_pet, 
            ua.nome as nome_adotante,
            ud.nome as nome_doador
        FROM adocoes a
        JOIN pets p ON a.pet_id = p.id
        JOIN usuarios ua ON a.usuario_id = ua.id
        JOIN usuarios ud ON p.usuario_id = ud.id
        WHERE a.status = 'concluido'
        ORDER BY a.data_adocao DESC
        LIMIT 5
    ");
    $adocoesRecentesDetalhes = $adocoesRecentesDetalhesStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Erro ao carregar estatísticas: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - AdotaAi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Cabeçalho -->
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <div class="row">
            <!-- Menu lateral -->
            <?php include 'admin/sidebar.php'; ?>
            <div>
            
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="h4">Dashboard</h2>
            </div>        
                    <div>
                        <span class="text-muted">Hoje: <?= date('d/m/Y') ?></span>
                    </div>
        <div>            
            <div>
                    <div class="col-lg-9">
        <a href="../index.php" class="btn btn-primary btn-sm me-1">Home</a>
        <a href="gerenciar-pets.php" class="btn btn-primary btn-sm me-1">Gerenciar Pets</a>
        <a href="gerenciar-usuarios.php" class="btn btn-primary btn-sm me-1">Gerenciar Usuários</a>
        <a href="dashboard.php" class="btn btn-primary btn-sm me-1">Dashboard</a>
        <a href="cadastrar-pet.php" class="btn btn-primary btn-sm me-1">Cadastrar Pet</a>
        <a href="aprovar-adocoes.php" class="btn btn-primary btn-sm me-1">Aprovar Adoções</a>
        <a href="logout.php" class="btn btn-danger btn-sm">Sair</a>
                    </div>
            </div>
        </div>   
                
                <!-- Cards informativos -->
                <div class="row mb-4">
                    <!-- Total de Pets -->
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card text-white bg-primary h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Total de Pets</h6>
                                        <h2 class="mb-0"><?= number_format($totalPets) ?></h2>
                                    </div>
                                    <div>
                                        <i class="fas fa-paw fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="admin-pets.php" class="text-white stretched-link text-decoration-none">Ver detalhes</a>
                                <div class="text-white">
                                    <i class="fas fa-angle-right"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total de Usuários -->
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card text-white bg-success h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Usuários</h6>
                                        <h2 class="mb-0"><?= number_format($totalUsuarios) ?></h2>
                                    </div>
                                    <div>
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="admin-usuarios.php" class="text-white stretched-link text-decoration-none">Ver detalhes</a>
                                <div class="text-white">
                                    <i class="fas fa-angle-right"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Adoções Concluídas -->
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card text-white bg-info h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Adoções</h6>
                                        <h2 class="mb-0"><?= number_format($adocoesConcluidas) ?></h2>
                                    </div>
                                    <div>
                                        <i class="fas fa-home fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="admin-adocoes.php" class="text-white stretched-link text-decoration-none">Ver detalhes</a>
                                <div class="text-white">
                                    <i class="fas fa-angle-right"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Solicitações Pendentes -->
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card text-white bg-warning h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Pendentes</h6>
                                        <h2 class="mb-0"><?= number_format($solicitacoesPendentes) ?></h2>
                                    </div>
                                    <div>
                                        <i class="fas fa-clock fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="aprovar-adocoes.php" class="text-white stretched-link text-decoration-none">Ver pendências</a>
                                <div class="text-white">
                                    <i class="fas fa-angle-right"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <!-- Gráfico de status dos pets -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-header bg-light">
                                <h5 class="m-0 font-weight-bold">Status dos Pets</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="petStatusChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pets Recentes -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h5 class="m-0 font-weight-bold">Pets Recentes</h5>
                                <a href="admin-pets.php" class="btn btn-sm btn-primary">Ver Todos</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <?php foreach ($petsRecentes as $pet): ?>
                                    <a href="pet.php?id=<?= $pet['id'] ?>" class="list-group-item list-group-item-action">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0" style="width: 50px; height: 50px">
                                                <?php if (isset($imagensPets[$pet['id']])): ?>
                                                    <img src="<?= UPLOAD_URL . htmlspecialchars($imagensPets[$pet['id']]) ?>" 
                                                        class="img-fluid rounded" 
                                                        alt="<?= htmlspecialchars($pet['nome']) ?>"
                                                        style="width: 50px; height: 50px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px">
                                                        <i class="fas fa-paw text-secondary"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ms-3">
                                                <h6 class="mb-0"><?= htmlspecialchars($pet['nome']) ?></h6>
                                                <div class="small text-muted">
                                                    <?= htmlspecialchars($pet['categoria_nome']) ?> • 
                                                    <span class="badge <?= $pet['status'] == 'disponivel' ? 'bg-success' : ($pet['status'] == 'adotado' ? 'bg-info' : 'bg-secondary') ?>">
                                                        <?= ucfirst($pet['status']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="ms-auto text-end">
                                                <small class="text-muted"><?= date('d/m/Y', strtotime($pet['data_cadastro'])) ?></small>
                                            </div>
                                        </div>
                                    </a>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($petsRecentes)): ?>
                                    <div class="list-group-item py-4 text-center text-muted">
                                        Nenhum pet cadastrado recentemente.
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Últimas Adoções -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h5 class="m-0 font-weight-bold">Adoções Recentes</h5>
                                <a href="admin-adocoes.php" class="btn btn-sm btn-primary">Ver Todas</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <?php foreach ($adocoesRecentesDetalhes as $adocao): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex align-items-center">
                                            <div class="ms-3">
                                                <h6 class="mb-1">Pet: <?= htmlspecialchars($adocao['nome_pet']) ?></h6>
                                                <small class="text-muted">
                                                    <i class="fas fa-user me-1"></i> Adotante: <?= htmlspecialchars($adocao['nome_adotante']) ?><br>
                                                    <i class="fas fa-user-plus me-1"></i> Doador: <?= htmlspecialchars($adocao['nome_doador']) ?>
                                                </small>
                                            </div>
                                            <div class="ms-auto">
                                                <span class="badge bg-success">Concluída</span><br>
                                                <small class="text-muted"><?= date('d/m/Y', strtotime($adocao['data_adocao'])) ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($adocoesRecentesDetalhes)): ?>
                                    <div class="list-group-item py-4 text-center text-muted">
                                        Nenhuma adoção concluída recentemente.
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Atividades Recentes -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h5 class="m-0 font-weight-bold">Atividades Recentes</h5>
                                <a href="logs-atividades.php" class="btn btn-sm btn-primary">Ver Todos</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                            <?php foreach ($atividades as $atividade): ?>
                                <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?= htmlspecialchars($atividade['acao']) ?></h6>
                                <small class="text-muted"><?= date('d/m H:i', strtotime($atividade['data_hora'])) ?></small>
                                </div>
                                <p class="mb-1 small"><?= htmlspecialchars($atividade['descricao'] ?? 'Sem descrição') ?></p>
                                <small class="text-muted">
                                <i class="fas fa-user me-1"></i><?= htmlspecialchars($atividade['nome_usuario']) ?>
                                </small>
                            </div>
                            <?php endforeach; ?>
                                    <?php if (empty($atividades)): ?>
                                    <div class="list-group-item py-4 text-center text-muted">
                                        Nenhuma atividade recente.
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Inicialização do gráfico de status de pets
        document.addEventListener('DOMContentLoaded', function() {
            const statusChart = document.getElementById('petStatusChart').getContext('2d');
            
            // Dados para o gráfico de status
            const statusData = {
                labels: [
                    'Disponíveis', 
                    'Adotados', 
                    'Em Processo'
                ],
                datasets: [{
                    data: [
                        <?= isset($petsPorStatus['disponivel']) ? $petsPorStatus['disponivel'] : 0 ?>,
                        <?= isset($petsPorStatus['adotado']) ? $petsPorStatus['adotado'] : 0 ?>,
                        <?= isset($petsPorStatus['em_processo']) ? $petsPorStatus['em_processo'] : 0 ?>
                    ],
                    backgroundColor: [
                        '#28a745',
                        '#17a2b8',
                        '#6c757d'
                    ]
                }]
            };
            
            new Chart(statusChart, {
                type: 'doughnut',
                data: statusData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>