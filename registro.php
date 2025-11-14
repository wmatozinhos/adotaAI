<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';

// Redirecionar se já estiver logado
if (isLoggedIn()) {
    if (isAdmin()) {
        redirectTo('admin/dashboard.php');
    } else {
        redirectTo('usuario/meu-perfil.php');
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Capturar dados do formulário
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    $telefone = trim($_POST['telefone'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $estado = trim($_POST['estado'] ?? '');
    $cep = trim($_POST['cep'] ?? '');
    
    // Validar campos
    if (empty($nome) || empty($email) || empty($senha) || empty($confirmar_senha)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } elseif (strlen($senha) < 6) {
        $error = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif ($senha !== $confirmar_senha) {
        $error = 'As senhas não coincidem.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, informe um e-mail válido.';
    } else {
        try {
            $conn = getConnection();
            
            // Verificar se o email já está em uso
            $checkStmt = $conn->prepare("SELECT id FROM usuarios WHERE email = :email");
            $checkStmt->bindParam(':email', $email);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                $error = 'Este e-mail já está sendo utilizado por outra conta.';
            } else {
                // Hash da senha
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                
                // Inserir novo usuário
                $stmt = $conn->prepare("
                    INSERT INTO usuarios (nome, email, senha, telefone, endereco, cidade, estado, cep)
                    VALUES (:nome, :email, :senha, :telefone, :endereco, :cidade, :estado, :cep)
                ");
                
                $stmt->bindParam(':nome', $nome);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':senha', $senha_hash);
                $stmt->bindParam(':telefone', $telefone);
                $stmt->bindParam(':endereco', $endereco);
                $stmt->bindParam(':cidade', $cidade);
                $stmt->bindParam(':estado', $estado);
                $stmt->bindParam(':cep', $cep);
                
                $stmt->execute();
                
                $success = 'Cadastro realizado com sucesso! Você já pode fazer login.';
                
                // Opcional: Fazer login automático após o registro
                // $_SESSION['user_id'] = $conn->lastInsertId();
                // $_SESSION['user_name'] = $nome;
                // $_SESSION['is_admin'] = false;
                // redirectTo('usuario/meu-perfil.php');
            }
        } catch (PDOException $e) {
            $error = 'Erro ao realizar cadastro. Por favor, tente novamente mais tarde.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - AdotaAi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Cabeçalho -->