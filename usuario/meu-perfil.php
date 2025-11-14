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

// Buscar informações do usuário
try {
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Erro ao buscar dados do perfil: ' . $e->getMessage();
}

// Processar formulário de atualização
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['atualizar_perfil'])) {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $estado = trim($_POST['estado'] ?? '');
    $cep = trim($_POST['cep'] ?? '');
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    // Validação básica
    if (empty($nome) || empty($email)) {
        $error = 'Nome e e-mail são campos obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, informe um e-mail válido.';
    } else {
        try {
            // Verificar se o email já está em uso por outro usuário
            $checkEmailStmt = $conn->prepare("SELECT id FROM usuarios WHERE email = :email AND id != :user_id");
            $checkEmailStmt->bindParam(':email', $email);
            $checkEmailStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $checkEmailStmt->execute();
            
            if ($checkEmailStmt->rowCount() > 0) {
                $error = 'Este e-mail já está sendo utilizado por outra conta.';
            } else {
                // Iniciar atualização dos dados
                $conn->beginTransaction();
                
                // Se o usuário está tentando mudar a senha
                if (!empty($senha_atual) && !empty($nova_senha)) {
                    if (empty($confirmar_senha)) {
                        $error = 'Por favor, confirme sua nova senha.';
                    } elseif ($nova_senha !== $confirmar_senha) {
                        $error = 'A nova senha e a confirmação não coincidem.';
                    } elseif (strlen($nova_senha) < 6) {
                        $error = 'A nova senha deve ter pelo menos 6 caracteres.';
                    } else {
                        // Verificar senha atual
                        if (!password_verify($senha_atual, $usuario['senha'])) {
                            $error = 'A senha atual informada está incorreta.';
                        } else {
                            // Senha atual correta, atualizar com a nova senha
                            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                            
                            $updateStmt = $conn->prepare("
                                UPDATE usuarios SET 
                                nome = :nome,
                                email = :email,
                                senha = :senha,
                                telefone = :telefone,
                                endereco = :endereco,
                                cidade = :cidade,
                                estado = :estado,
                                cep = :cep
                                WHERE id = :user_id
                            ");
                            $updateStmt->bindParam(':senha', $senha_hash);
                        }
                    }
                } else {
                    // Atualização sem mudança de senha
                    $updateStmt = $conn->prepare("
                        UPDATE usuarios SET 
                        nome = :nome,
                        email = :email,
                        telefone = :telefone,
                        endereco = :endereco,
                        cidade = :cidade,
                        estado = :estado,
                        cep = :cep
                        WHERE id = :user_id
                    ");
                }
                
                if (!isset($error)) {
                    $updateStmt->bindParam(':nome', $nome);
                    $updateStmt->bindParam(':email', $email);
                    $updateStmt->bindParam(':telefone', $telefone);
                    $updateStmt->bindParam(':endereco', $endereco);
                    $updateStmt->bindParam(':cidade', $cidade);
                    $updateStmt->bindParam(':estado', $estado);
                    $updateStmt->bindParam(':cep', $cep);
                    $updateStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                    $updateStmt->execute();
                    
                    $conn->commit();
                    
                    // Atualizar sessão com o novo nome
                    $_SESSION['user_name'] = $nome;
                    
                    $message = 'Perfil atualizado com sucesso!';
                    
                    // Recarregar dados do usuário após atualização
                    $stmt->execute();
                    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            }
        } catch (PDOException $e) {
            $conn->rollBack();
            $error = 'Erro ao atualizar perfil: ' . $e->getMessage();
        }
    }
}

// Processar upload de foto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['atualizar_foto'])) {
    if (!empty($_FILES['foto_perfil']['tmp_name'])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        
        if (!in_array($_FILES['foto_perfil']['type'], $allowed_types)) {
            $error = 'Apenas imagens JPEG, PNG e JPG são permitidas.';
        } elseif ($_FILES['foto_perfil']['size'] > 2097152) { // 2 MB
            $error = 'O arquivo deve ter no máximo 2MB.';
        } else {
            try {
                $uploadDir = UPLOAD_DIR;
                
                // Criar diretório se não existir
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $extension = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
                $filename = 'user_' . $user_id . '_' . uniqid() . '.' . $extension;
                $destination = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $destination)) {
                    // Remover foto antiga se existir
                    if (!empty($usuario['foto_perfil'])) {
                        $oldFile = $uploadDir . $usuario['foto_perfil'];
                        if (file_exists($oldFile)) {
                            unlink($oldFile);
                        }
                    }
                    
                    // Atualizar referência no banco
                    $updatePhotoStmt = $conn->prepare("UPDATE usuarios SET foto_perfil = :foto WHERE id = :user_id");
                    $updatePhotoStmt->bindParam(':foto', $filename);
                    $updatePhotoStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                    $updatePhotoStmt->execute();
                    
                    $message = 'Foto de perfil atualizada com sucesso!';
                    
                    // Recarregar dados do usuário após atualização
                    $stmt->execute();
                    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error = 'Falha ao fazer upload da imagem.';
                }
            } catch (Exception $e) {
                $error = 'Erro ao processar a imagem: ' . $e->getMessage();
            }
        }
    } else {
        $error = 'Por favor, selecione uma imagem para upload.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - AdotaAi</title>
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
                <?php if ($message): ?>
                <div class="alert alert-success"><?= $message ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <div class="row">
                    <!-- Foto de perfil -->
                    <div class="col-md-4 mb-4">
                        <div class="card shadow">
                            <div class="card-header bg-primary text-white">
                                <h5 class="m-0">Foto de Perfil</h5>
                            </div>
                            <div class="card-body text-center">
                                <div class="profile-photo mb-3">
                                    <?php if (!empty($usuario['foto_perfil'])): ?>
                                        <img src="<?= UPLOAD_URL . htmlspecialchars($usuario['foto_perfil']) ?>" alt="Foto de Perfil" class="img-fluid rounded-circle">
                                    <?php else: ?>
                                        <i class="fas fa-user-circle fa-6x text-secondary"></i>
                                    <?php endif; ?>
                                </div>
                                
                                <form method="post" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="foto_perfil" class="form-label">Atualizar foto</label>
                                        <input type="file" class="form-control" id="foto_perfil" name="foto_perfil" accept="image/jpeg, image/png, image/jpg">
                                        <div class="form-text">Tamanho máximo: 2MB</div>
                                    </div>
                                    <button type="submit" name="atualizar_foto" class="btn btn-primary">Enviar foto</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dados do perfil -->
                    <div class="col-md-8">
                        <div class="card shadow">
                            <div class="card-header bg-primary text-white">
                                <h5 class="m-0">Meus Dados</h5>
                            </div>
                            <div class="card-body">
                                <form method="post">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="nome" class="form-label">Nome completo *</label>
                                            <input type="text" class="form-control" id="nome" name="nome" required 
                                                value="<?= htmlspecialchars($usuario['nome'] ?? '') ?>">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">Email *</label>
                                            <input type="email" class="form-control" id="email" name="email" required
                                                value="<?= htmlspecialchars($usuario['email'] ?? '') ?>">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="telefone" class="form-label">Telefone</label>
                                            <input type="text" class="form-control" id="telefone" name="telefone"
                                                value="<?= htmlspecialchars($usuario['telefone'] ?? '') ?>">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="cep" class="form-label">CEP</label>
                                            <input type="text" class="form-control" id="cep" name="cep" 
                                                value="<?= htmlspecialchars($usuario['cep'] ?? '') ?>">
                                        </div>
                                        
                                        <div class="col-md-12 mb-3">
                                            <label for="endereco" class="form-label">Endereço</label>
                                            <input type="text" class="form-control" id="endereco" name="endereco"
                                                value="<?= htmlspecialchars($usuario['endereco'] ?? '') ?>">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="cidade" class="form-label">Cidade</label>
                                            <input type="text" class="form-control" id="cidade" name="cidade"
                                                value="<?= htmlspecialchars($usuario['cidade'] ?? '') ?>">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="estado" class="form-label">Estado</label>
                                            <input type="text" class="form-control" id="estado" name="estado"
                                                value="<?= htmlspecialchars($usuario['estado'] ?? '') ?>">
                                        </div>
                                        
                                        <hr class="my-4">
                                        <h5>Alterar Senha</h5>
                                        <div class="col-md-12 mb-3 form-text">
                                            Preencha apenas se deseja alterar sua senha atual.
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label for="senha_atual" class="form-label">Senha Atual</label>
                                            <input type="password" class="form-control" id="senha_atual" name="senha_atual">
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label for="nova_senha" class="form-label">Nova Senha</label>
                                            <input type="password" class="form-control" id="nova_senha" name="nova_senha">
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                                            <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha">
                                        </div>
                                        
                                        <div class="col-12 mt-3">
                                            <button type="submit" name="atualizar_perfil" class="btn btn-primary">
                                                Atualizar Perfil
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rodapé -->
    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Função para mostrar preview da foto selecionada
        document.getElementById('foto_perfil').addEventListener('change', function() {
            const previewContainer = document.querySelector('.profile-photo');
            const file = this.files[0];
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewContainer.innerHTML = `<img src="${e.target.result}" alt="Foto de Perfil" class="img-fluid rounded-circle">`;
                }
                reader.readAsDataURL(file);
            }
        });
        
        // Função para buscar endereço pelo CEP
        document.getElementById('cep').addEventListener('blur', function() {
            const cep = this.value.replace(/\D/g, '');
            
            if (cep.length === 8) {
                fetch(`https://viacep.com.br/ws/${cep}/json/`)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.erro) {
                            document.getElementById('endereco').value = `${data.logradouro}, ${data.bairro}`;
                            document.getElementById('cidade').value = data.localidade;
                            document.getElementById('estado').value = data.uf;
                        }
                    })
                    .catch(error => console.error('Erro ao consultar CEP:', error));
            }
        });
    </script>
</body>
</html>