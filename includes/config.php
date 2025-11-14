<?php
// Configurações de conexão com o banco de dados
define('DB_HOST', '127.0.0.1:3306');  // Alterar para o host da Hostinger
define('DB_USER', 'u794393669_wmatozinhos');  // Usuário do banco na Hostinger
define('DB_PASS', 'Darkpallis12345');  // Senha do banco na Hostinger
define('DB_NAME', 'u794393669_adotaai');  // Nome do banco de dados na Hostinger');

// Configurações do site
define('SITE_NAME', 'AdotaAi');
define('UPLOAD_DIR', $_SERVER['DOCUMENT_ROOT'] . '/uploads/pets/');
define('UPLOAD_URL', '/uploads/pets/');

// Configurações de email (para notificações)
define('EMAIL_FROM', 'noreply@adotaai.com');
define('ADMIN_EMAIL', 'admin@adotaai.com');

// Configurações de sessão
session_start();

// Funções globais
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

function redirectTo($location) {
    header("Location: $location");
    exit;
}