
<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

/**
 * Verifica se o usuário está logado no sistema
 * 
 * @return bool - Retorna true se o usuário estiver logado, false caso contrário
 */
// Verifica se o usuário é administrador
if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
    }
}

/**
 * Redireciona para uma página específica
 * 
 * @param string $location - URL para onde redirecionar
 * @return void
 */
if (!function_exists('redirectTo')) {
    function redirectTo($location) {
        header("Location: $location");
        exit;
    }
}

/**
 * Valida um endereço de e-mail
 * 
 * @param string $email - O endereço de e-mail a ser validado
 * @return bool - Retorna true se o e-mail for válido, false caso contrário
 */
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Sanitiza dados de entrada para evitar ataques XSS
 * 
 * @param string $data - Os dados a serem limpos
 * @return string - Os dados limpos
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Formata uma data para o padrão brasileiro
 * 
 * @param string $data - Data no formato Y-m-d
 * @param bool $incluirHora - Se deve incluir hora no formato
 * @return string - Data formatada
 */
function formatarData($data, $incluirHora = false) {
    if (empty($data)) return '';
    
    $timestamp = strtotime($data);
    
    if ($incluirHora) {
        return date('d/m/Y H:i', $timestamp);
    } else {
        return date('d/m/Y', $timestamp);
    }
}

/**
 * Gera um token aleatório para operações que requerem segurança
 * 
 * @param int $length - Comprimento do token
 * @return string - Token gerado
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Verifica se um arquivo é uma imagem válida
 * 
 * @param array $file - Array $_FILES de um arquivo
 * @return bool - Retorna true se for uma imagem válida, false caso contrário
 */
function isValidImage($file) {
    // Verificar se existe erro no upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Verificar o tipo MIME do arquivo
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    if (!in_array($file['type'], $allowedTypes)) {
        return false;
    }
    
    // Verificar a extensão do arquivo
    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension']);
    $allowedExtensions = ['jpg', 'jpeg', 'png'];
    if (!in_array($extension, $allowedExtensions)) {
        return false;
    }
    
    // Verificar o tamanho do arquivo (máximo 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB em bytes
    if ($file['size'] > $maxSize) {
        return false;
    }
    
    return true;
}

/**
 * Faz o upload de uma imagem para o servidor
 * 
 * @param array $file - Array $_FILES de uma imagem
 * @param string $directory - Diretório onde salvar a imagem
 * @param string $newFilename - Nome do arquivo (opcional)
 * @return string|bool - Nome do arquivo salvo ou false em caso de erro
 */
function uploadImage($file, $directory, $newFilename = null) {
    // Verificar se o arquivo é uma imagem válida
    if (!isValidImage($file)) {
        return false;
    }
    
    // Criar diretório se não existir
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    
    // Gerar nome do arquivo se não for fornecido
    if (!$newFilename) {
        $fileInfo = pathinfo($file['name']);
        $extension = strtolower($fileInfo['extension']);
        $newFilename = uniqid() . '.' . $extension;
    }
    
    $destination = $directory . $newFilename;
    
    // Fazer upload do arquivo
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $newFilename;
    }
    
    return false;
}

/**
 * Gera um slug a partir de um texto
 * 
 * @param string $text - Texto para converter em slug
 * @return string - Slug gerado
 */
function gerarSlug($text) {
    // Converter para minúsculas
    $text = strtolower($text);
    
    // Remover acentos
    $text = preg_replace('/[áàãâä]/u', 'a', $text);
    $text = preg_replace('/[éèêë]/u', 'e', $text);
    $text = preg_replace('/[íìîï]/u', 'i', $text);
    $text = preg_replace('/[óòõôö]/u', 'o', $text);
    $text = preg_replace('/[úùûü]/u', 'u', $text);
    $text = preg_replace('/[ç]/u', 'c', $text);
    
    // Substituir caracteres especiais por traços
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    
    // Remover traços duplicados
    $text = preg_replace('/-+/', '-', $text);
    
    // Remover traços no início e fim
    $text = trim($text, '-');
    
    return $text;
}

/**
 * Obtém a idade formatada a partir de uma data de nascimento
 * 
 * @param string $dataNascimento - Data de nascimento no formato Y-m-d
 * @return string - Idade formatada (anos, meses ou dias)
 */
function calcularIdade($dataNascimento) {
    if (empty($dataNascimento)) return '';
    
    $now = new DateTime();
    $birth = new DateTime($dataNascimento);
    $interval = $now->diff($birth);
    
    if ($interval->y > 0) {
        return $interval->y . ($interval->y === 1 ? ' ano' : ' anos');
    } elseif ($interval->m > 0) {
        return $interval->m . ($interval->m === 1 ? ' mês' : ' meses');
    } else {
        return $interval->d . ($interval->d === 1 ? ' dia' : ' dias');
    }
}

/**
 * Verifica se um CPF é válido
 * 
 * @param string $cpf - CPF a ser validado
 * @return bool - Retorna true se o CPF for válido, false caso contrário
 */
function validarCPF($cpf) {
    // Remover caracteres especiais
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    // Verificar se tem 11 dígitos
    if (strlen($cpf) != 11) {
        return false;
    }
    
    // Verificar se todos os dígitos são iguais, o que tornaria o CPF inválido
    if (preg_match('/^(\d)\1*$/', $cpf)) {
        return false;
    }
    
    // Calcular o primeiro dígito verificador
    $sum = 0;
    for ($i = 0; $i < 9; $i++) {
        $sum += (int) $cpf[$i] * (10 - $i);
    }
    $remainder = $sum % 11;
    $digit1 = ($remainder < 2) ? 0 : 11 - $remainder;
    
    // Verificar o primeiro dígito verificador
    if ($cpf[9] != $digit1) {
        return false;
    }
    
    // Calcular o segundo dígito verificador
    $sum = 0;
    for ($i = 0; $i < 10; $i++) {
        $sum += (int) $cpf[$i] * (11 - $i);
    }
    $remainder = $sum % 11;
    $digit2 = ($remainder < 2) ? 0 : 11 - $remainder;
    
    // Verificar o segundo dígito verificador
    return $cpf[10] == $digit2;
}

/**
 * Formata um CPF com pontos e traço
 * 
 * @param string $cpf - CPF a ser formatado
 * @return string - CPF formatado ou o valor original se inválido
 */
function formatarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    if (strlen($cpf) != 11) {
        return $cpf;
    }
    
    return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}

/**
 * Formata um número de telefone
 * 
 * @param string $telefone - Telefone a ser formatado
 * @return string - Telefone formatado ou o valor original se inválido
 */
function formatarTelefone($telefone) {
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    $len = strlen($telefone);
    
    if ($len == 11) {
        // Celular com DDD
        return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
    } elseif ($len == 10) {
        // Telefone fixo com DDD
        return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 4) . '-' . substr($telefone, 6);
    } elseif ($len == 9) {
        // Celular sem DDD
        return substr($telefone, 0, 5) . '-' . substr($telefone, 5);
    } elseif ($len == 8) {
        // Telefone fixo sem DDD
        return substr($telefone, 0, 4) . '-' . substr($telefone, 4);
    }
    
    // Retorna o original se não corresponder a nenhum formato esperado
    return $telefone;
}

/**
 * Formata um CEP
 * 
 * @param string $cep - CEP a ser formatado
 * @return string - CEP formatado ou o valor original se inválido
 */
function formatarCEP($cep) {
    $cep = preg_replace('/[^0-9]/', '', $cep);
    
    if (strlen($cep) != 8) {
        return $cep;
    }
    
    return substr($cep, 0, 5) . '-' . substr($cep, 5, 3);
}

/**
 * Envia um e-mail
 * 
 * @param string $to - Endereço de e-mail do destinatário
 * @param string $subject - Assunto do e-mail
 * @param string $message - Corpo do e-mail
 * @param array $headers - Cabeçalhos adicionais (opcional)
 * @return bool - Retorna true se o e-mail for enviado com sucesso, false caso contrário
 */
function enviarEmail($to, $subject, $message, $headers = []) {
    // Definir cabeçalhos padrão
    $defaultHeaders = [
        'From' => 'no-reply@adotaai.com',
        'Reply-To' => 'contato@adotaai.com',
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/html; charset=UTF-8'
    ];
    
    // Mesclar com cabeçalhos adicionais
    $headers = array_merge($defaultHeaders, $headers);
    
    // Converter array de cabeçalhos para string
    $headersString = '';
    foreach ($headers as $name => $value) {
        $headersString .= "$name: $value\r\n";
    }
    
    // Enviar e-mail
    return mail($to, $subject, $message, $headersString);
}

/**
 * Limita um texto em um número de caracteres
 * 
 * @param string $text - Texto a ser limitado
 * @param int $limit - Número máximo de caracteres
 * @param string $end - String para indicar que o texto foi cortado
 * @return string - Texto limitado
 */
function limitarTexto($text, $limit, $end = '...') {
    if (mb_strlen($text, 'UTF-8') <= $limit) {
        return $text;
    }
    
    $text = mb_substr($text, 0, $limit, 'UTF-8');
    $lastSpace = mb_strrpos($text, ' ', 0, 'UTF-8');
    
    // Cortar no último espaço para não quebrar palavras
    if ($lastSpace !== false) {
        $text = mb_substr($text, 0, $lastSpace, 'UTF-8');
    }
    
    return $text . $end;
}

/**
 * Formata um valor monetário no padrão brasileiro
 * 
 * @param float $valor - Valor a ser formatado
 * @param bool $comSimbolo - Se deve incluir o símbolo R$
 * @return string - Valor formatado
 */
function formatarMoeda($valor, $comSimbolo = true) {
    $valorFormatado = number_format($valor, 2, ',', '.');
    return $comSimbolo ? "R$ {$valorFormatado}" : $valorFormatado;
}

/**
 * Log de atividades do sistema
 * 
 * @param string $acao - Ação realizada
 * @param string $descricao - Descrição detalhada
 * @param int|null $usuario_id - ID do usuário que realizou a ação
 * @return void
 */
function logActivity($acao, $descricao, $usuario_id = null) {
    if (is_null($usuario_id) && isLoggedIn()) {
        $usuario_id = $_SESSION['user_id'];
    }
    
    $conn = getConnection();
    $ip = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    
    $stmt = $conn->prepare("
        INSERT INTO logs_atividades (usuario_id, acao, descricao, ip, user_agent, data_hora)
        VALUES (:usuario_id, :acao, :descricao, :ip, :user_agent, NOW())
    ");
    
    $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
    $stmt->bindParam(':acao', $acao);
    $stmt->bindParam(':descricao', $descricao);
    $stmt->bindParam(':ip', $ip);
    $stmt->bindParam(':user_agent', $userAgent);
    
    $stmt->execute();
}

/**
 * Verifica se uma string contém apenas letras e espaços
 * 
 * @param string $str - String a ser verificada
 * @return bool - Retorna true se a string contiver apenas letras e espaços, false caso contrário
 */
function somenteLetras($str) {
    return preg_match('/^[\pL\s]+$/u', $str);
}

/**
 * Conta o número de interesses em um pet
 * 
 * @param int $pet_id - ID do pet
 * @return int - Número de interesses
 */
function contarInteresses($pet_id) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT COUNT(*) FROM interesses WHERE pet_id = :pet_id");
    $stmt->bindParam(':pet_id', $pet_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchColumn();
}

/**
 * Verifica se um usuário já demonstrou interesse em um pet
 * 
 * @param int $user_id - ID do usuário
 * @param int $pet_id - ID do pet
 * @return bool - Retorna true se o usuário já demonstrou interesse, false caso contrário
 */
function usuarioJaDemonstrouInteresse($user_id, $pet_id) {
    $conn = getConnection();
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM interesses 
        WHERE usuario_id = :user_id AND pet_id = :pet_id
    ");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':pet_id', $pet_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchColumn() > 0;
}

/**
 * Obter URL da imagem principal de um pet
 * 
 * @param int $pet_id - ID do pet
 * @return string|null - URL da imagem principal ou null se não encontrada
 */
function getImagemPrincipalPet($pet_id) {
    $conn = getConnection();
    $stmt = $conn->prepare("
        SELECT arquivo FROM imagens_pets
        WHERE pet_id = :pet_id AND imagem_principal = 1
        LIMIT 1
    ");
    $stmt->bindParam(':pet_id', $pet_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        return UPLOAD_URL . $result['arquivo'];
    }
    
    return null;
}

/**
 * Obtém o nome do dono de um pet
 * 
 * @param int $pet_id - ID do pet
 * @return string - Nome do dono
 */
function getNomeDonoPet($pet_id) {
    $conn = getConnection();
    $stmt = $conn->prepare("
        SELECT u.nome FROM pets p
        INNER JOIN usuarios u ON p.usuario_id = u.id
        WHERE p.id = :pet_id
    ");
    $stmt->bindParam(':pet_id', $pet_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['nome'] : 'Desconhecido';
}
/**
 * Obtém o nome da categoria de um pet
 * 
 * @param int $pet_id - ID do pet
 * @return string - Nome da categoria
 */
function getNomeCategoriaPet($pet_id) {
    $conn = getConnection();
    $stmt = $conn->prepare("
        SELECT c.nome FROM pets p
        INNER JOIN categorias c ON p.categoria_id = c.id
        WHERE p.id = :pet_id
    ");
    $stmt->bindParam(':pet_id', $pet_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['nome'] : 'Desconhecida';
}
/**
 * Obtém o nome do estado de um pet
 * 
 * @param int $pet_id - ID do pet
 * @return string - Nome do estado
 */