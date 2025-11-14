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
$pet_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$editing = ($pet_id > 0);

// Carregar categorias para o select
$catStmt = $conn->prepare("SELECT * FROM categorias ORDER BY nome");
$catStmt->execute();
$categorias = $catStmt->fetchAll(PDO::FETCH_ASSOC);

// Se estiver editando, carregar dados do pet
if ($editing) {
    $petStmt = $conn->prepare("
        SELECT * FROM pets WHERE id = :pet_id AND usuario_id = :user_id
    ");
    $petStmt->bindParam(':pet_id', $pet_id);
    $petStmt->bindParam(':user_id', $user_id);
    $petStmt->execute();
    
    if ($petStmt->rowCount() == 0) {
        redirectTo('meus-pets.php');
    }
    
    $pet = $petStmt->fetch(PDO::FETCH_ASSOC);
    
    // Carregar imagens do pet
    $imgStmt = $conn->prepare("SELECT * FROM imagens_pets WHERE pet_id = :pet_id");
    $imgStmt->bindParam(':pet_id', $pet_id);
    $imgStmt->execute();
    $imagens = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Dados do pet
    $nome = trim($_POST['nome'] ?? '');
    $categoria_id = intval($_POST['categoria_id'] ?? 0);
    $idade = trim($_POST['idade'] ?? '');
    $porte = trim($_POST['porte'] ?? '');
    $sexo = trim($_POST['sexo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $status_saude = trim($_POST['status_saude'] ?? '');
    $vacinacao = trim($_POST['vacinacao'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $estado = trim($_POST['estado'] ?? '');
    
    // Validação básica
    if (empty($nome) || empty($categoria_id) || empty($sexo)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $conn->beginTransaction();
            
            if ($editing) {
                // Atualizar pet existente
                $stmt = $conn->prepare("
                    UPDATE pets SET 
                    nome = :nome, 
                    categoria_id = :categoria_id,
                    idade = :idade,
                    porte = :porte,
                    sexo = :sexo,
                    descricao = :descricao,
                    status_saude = :status_saude,
                    vacinacao = :vacinacao,
                    endereco = :endereco,
                    cidade = :cidade,
                    estado = :estado
                    WHERE id = :pet_id AND usuario_id = :user_id
                ");
                $stmt->bindParam(':pet_id', $pet_id);
            } else {
                // Inserir novo pet
                $stmt = $conn->prepare("
                    INSERT INTO pets (
                        nome, categoria_id, idade, porte, sexo, descricao, 
                        status_saude, vacinacao, endereco, cidade, estado, 
                        usuario_id, status
                    ) VALUES (
                        :nome, :categoria_id, :idade, :porte, :sexo, :descricao,
                        :status_saude, :vacinacao, :endereco, :cidade, :estado,
                        :user_id, 'Disponível'
                    )
                ");
            }
            
            // Bind parameters
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':categoria_id', $categoria_id, PDO::PARAM_INT);
            $stmt->bindParam(':idade', $idade);
            $stmt->bindParam(':porte', $porte);
            $stmt->bindParam(':sexo', $sexo);
            $stmt->bindParam(':descricao', $descricao);
            $stmt->bindParam(':status_saude', $status_saude);
            $stmt->bindParam(':vacinacao', $vacinacao);
            $stmt->bindParam(':endereco', $endereco);
            $stmt->bindParam(':cidade', $cidade);
            $stmt->bindParam(':estado', $estado);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            
            $stmt->execute();
            
            // Se for novo pet, pegar o ID gerado
            if (!$editing) {
                $pet_id = $conn->lastInsertId();
            }
            
            // Processar imagens
            if (!empty($_FILES['imagens']['name'][0])) {
                $uploadDir = UPLOAD_DIR;
                
                // Criar diretório se não existir
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                foreach ($_FILES['imagens']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['imagens']['error'][$key] == 0) {
                        $filename = uniqid() . '_' . $_FILES['imagens']['name'][$key];
                        $destination = $uploadDir . $filename;
                        
                        if (move_uploaded_file($tmp_name, $destination)) {
                            // Verificar se é a imagem principal
                            $principal = isset($_POST['imagem_principal']) && $_POST['imagem_principal'] == $key;
                            
                            // Inserir referência no banco
                            $imgStmt = $conn->prepare("
                                INSERT INTO imagens_pets (pet_id, arquivo, imagem_principal)
                                VALUES (:pet_id, :arquivo, :principal)
                            ");
                            
                            $imgStmt->bindParam(':pet_id', $pet_id, PDO::PARAM_INT);
                            $imgStmt->bindParam(':arquivo', $filename);
                            $imgStmt->bindParam(':principal', $principal, PDO::PARAM_BOOL);
                            $imgStmt->execute();
                        }
                    }
                }
            }
            
            // Se nenhuma imagem foi marcada como principal, marca a primeira
            $checkPrincipal = $conn->prepare("
                SELECT COUNT(*) FROM imagens_pets 
                WHERE pet_id = :pet_id AND imagem_principal = 1
            ");
            $checkPrincipal->bindParam(':pet_id', $pet_id);
            $checkPrincipal->execute();
            
            if ($checkPrincipal->fetchColumn() == 0) {
                $setPrincipal = $conn->prepare("
                    UPDATE imagens_pets 
                    SET imagem_principal = 1 
                    WHERE pet_id = :pet_id 
                    ORDER BY id ASC 
                    LIMIT 1
                ");
                $setPrincipal->bindParam(':pet_id', $pet_id);
                $setPrincipal->execute();
            }
            
            $conn->commit();
            
            $message = $editing ? 'Pet atualizado com sucesso!' : 'Pet cadastrado com sucesso!';
            
            if (!$editing) {
                // Redirecionar para evitar duplicação no recarregamento
                redirectTo('meus-pets.php?success=1');
            }
        } catch (PDOException $e) {
            $conn->rollBack();
            $error = 'Erro ao salvar os dados: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $editing ? 'Editar' : 'Cadastrar' ?> Pet - AdotaAi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css" rel="stylesheet">
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
                    <div class="card-header bg-primary text-white">
                        <h5 class="m-0"><?= $editing ? 'Editar' : 'Cadastrar novo' ?> Pet</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                        <div class="alert alert-success"><?= $message ?></div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <form method="post" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nome" class="form-label">Nome do Pet *</label>
                                    <input type="text" class="form-control" id="nome" name="nome" required 
                                        value="<?= $editing ? htmlspecialchars($pet['nome']) : '' ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="categoria_id" class="form-label">Categoria *</label>
                                    <select class="form-select" id="categoria_id" name="categoria_id" required>
                                        <option value="">Selecione...</option>
                                        <?php foreach ($categorias as $categoria): ?>
                                            <option value="<?= $categoria['id'] ?>" <?= $editing && $pet['categoria_id'] == $categoria['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($categoria['nome']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="idade" class="form-label">Idade</label>
                                    <input type="text" class="form-control" id="idade" name="idade" 
                                        value="<?= $editing ? htmlspecialchars($pet['idade']) : '' ?>" 
                                        placeholder="Ex: 2 anos, 6 meses">
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="porte" class="form-label">Porte</label>
                                    <select class="form-select" id="porte" name="porte">
                                        <option value="">Selecione...</option>
                                        <option value="Pequeno" <?= $editing && $pet['porte'] == 'Pequeno' ? 'selected' : '' ?>>Pequeno</option>
                                        <option value="Médio" <?= $editing && $pet['porte'] == 'Médio' ? 'selected' : '' ?>>Médio</option>
                                        <option value="Grande" <?= $editing && $pet['porte'] == 'Grande' ? 'selected' : '' ?>>Grande</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="sexo" class="form-label">Sexo *</label>
                                    <select class="form-select" id="sexo" name="sexo" required>
                                        <option value="">Selecione...</option>
                                        <option value="Macho" <?= $editing && $pet['sexo'] == 'Macho' ? 'selected' : '' ?>>Macho</option>
                                        <option value="Fêmea" <?= $editing && $pet['sexo'] == 'Fêmea' ? 'selected' : '' ?>>Fêmea</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label for="descricao" class="form-label">Descrição</label>
                                    <textarea class="form-control" id="descricao" name="descricao" rows="4"><?= $editing ? htmlspecialchars($pet['descricao']) : '' ?></textarea>
                                    <div class="form-text">Descreva a personalidade, comportamento e características do pet.</div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="status_saude" class="form-label">Status de Saúde</label>
                                    <textarea class="form-control" id="status_saude" name="status_saude" rows="3"><?= $editing ? htmlspecialchars($pet['status_saude']) : '' ?></textarea>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="vacinacao" class="form-label">Vacinação</label>
                                    <textarea class="form-control" id="vacinacao" name="vacinacao" rows="3"><?= $editing ? htmlspecialchars($pet['vacinacao']) : '' ?></textarea>
                                </div>
                                
                                <hr class="my-4">
                                <h5>Localização do Pet</h5>
                                
                                <div class="col-md-12 mb-3">
                                    <label for="endereco" class="form-label">Endereço</label>
                                    <input type="text" class="form-control" id="endereco" name="endereco" 
                                        value="<?= $editing ? htmlspecialchars($pet['endereco']) : '' ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="cidade" class="form-label">Cidade</label>
                                    <input type="text" class="form-control" id="cidade" name="cidade" 
                                        value="<?= $editing ? htmlspecialchars($pet['cidade']) : '' ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="estado" class="form-label">Estado</label>
                                    <input type="text" class="form-control" id="estado" name="estado" 
                                        value="<?= $editing ? htmlspecialchars($pet['estado']) : '' ?>">
                                </div>
                                
                                <hr class="my-4">
                                <h5>Imagens do Pet</h5>
                                <div class="col-12 mb-4">
                                    <div class="dropzone-container">
                                        <p>Arraste as imagens aqui ou clique para selecionar</p>
                                        <input type="file" class="form-control" id="imagens" name="imagens[]" multiple 
                                               accept="image/jpeg, image/png, image/jpg">
                                    </div>
                                    <div class="form-text">Envie pelo menos uma foto do pet. A primeira será a imagem principal.</div>
                                </div>
                                
                                <?php if ($editing && count($imagens) > 0): ?>
                                <div class="col-12 mb-4">
                                    <label class="form-label">Imagens atuais</label>
                                    <div class="row">
                                        <?php foreach ($imagens as $img): ?>
                                        <div class="col-md-3 mb-3">
                                            <div class="card">
                                                <img src="<?= UPLOAD_URL . htmlspecialchars($img['arquivo']) ?>" 
                                                     class="card-img-top" alt="Imagem do pet">
                                                <div class="card-body text-center">
                                                    <?php if ($img['imagem_principal']): ?>
                                                    <span class="badge bg-success mb-2">Imagem principal</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="col-12 mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <?= $editing ? 'Atualizar' : 'Cadastrar' ?> Pet
                                    </button>
                                    <a href="meus-pets.php" class="btn btn-secondary">Cancelar</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rodapé -->
    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js"></script>
    <script>
        // Função para preview das imagens antes do upload
        document.getElementById('imagens').addEventListener('change', function() {
            var container = document.querySelector('.dropzone-container');
            container.innerHTML = ''; // Limpar container
            
            if (this.files) {
                for (var i = 0; i < this.files.length; i++) {
                    let reader = new FileReader();
                    reader.onload = function(e) {
                        let img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'img-thumbnail preview-img';
                        container.appendChild(img);
                    }
                    reader.readAsDataURL(this.files[i]);
                }
            }
        });
    </script>
</body>
</html>