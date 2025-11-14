
<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/db_connect.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        redirectTo('admin/dashboard.php');
    } else {
        redirectTo('usuario/meu-perfil.php');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    if (empty($email) || empty($senha)) {
        $error = 'Preencha todos os campos!';
    } else {
        try {
            $conn = getConnection();
            $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (password_verify($senha, $usuario['senha'])) {
                    // Login bem-sucedido
                    $_SESSION['user_id'] = $usuario['id'];
                    $_SESSION['user_name'] = $usuario['nome'];
                    $_SESSION['is_admin'] = $usuario['is_admin'] == 1;
                    
                    // Atualiza último acesso
                    $upd = $conn->prepare("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = :id");
                    $upd->bindParam(':id', $usuario['id']);
                    $upd->execute();
                    
                    // Redireciona com base no tipo de usuário
                    if ($usuario['is_admin'] == 1) {
                        redirectTo('admin/dashboard.php');
                    } else {
                        redirectTo('usuario/meu-perfil.php');
                    }
                } else {
                    $error = 'Senha incorreta!';
                }
            } else {
                $error = 'Usuário não encontrado!';
            }
        } catch (PDOException $e) {
            $error = 'Erro no sistema. Tente novamente mais tarde.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AdotaAi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Cabeçalho -->
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">Login</h2>
                        
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="login.php">
                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="senha" class="form-label">Senha</label>
                                <input type="password" class="form-control" id="senha" name="senha" required>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="lembrar">
                                <label class="form-check-label" for="lembrar">Lembrar de mim</label>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Entrar</button>
</div>