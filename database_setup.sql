
-- Tabela de usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    endereco VARCHAR(255),
    cidade VARCHAR(100),
    estado VARCHAR(50),
    cep VARCHAR(10),
    is_admin BOOLEAN DEFAULT FALSE,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acesso TIMESTAMP NULL
);

-- Inserir usuário admin padrão (senha: admin123456)
INSERT INTO usuarios (nome, email, senha, is_admin) VALUES 
('Administrador', 'admin@adotaai.com', '\$2y\$10$uKr.xhLh2KbSCFQP9A0r8.qT.QsADITtbNxogsmXHbdgy7LR5fWOy', TRUE);

-- Tabela de categorias de pets
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    icone VARCHAR(100)
);

-- Inserir categorias padrão
INSERT INTO categorias (nome) VALUES 
('Cachorro'), ('Gato'), ('Passarinho'), ('Roedor'), ('Réptil'), ('Outros');

-- Tabela de pets
CREATE TABLE IF NOT EXISTS pets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    categoria_id INT NOT NULL,
    idade VARCHAR(50),
    porte VARCHAR(50),
    sexo ENUM('Macho', 'Fêmea') NOT NULL,
    descricao TEXT,
    status_saude TEXT,
    vacinacao TEXT,
    endereco VARCHAR(255),
    cidade VARCHAR(100),
    estado VARCHAR(50),
    status ENUM('Disponível', 'Adotado', 'Cancelado') DEFAULT 'Disponível',
    usuario_id INT NOT NULL,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Tabela de imagens dos pets
CREATE TABLE IF NOT EXISTS imagens_pets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pet_id INT NOT NULL,
    arquivo VARCHAR(255) NOT NULL,
    imagem_principal BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE
);

-- Tabela de solicitações de adoção
CREATE TABLE IF NOT EXISTS adocoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pet_id INT NOT NULL,
    usuario_id INT NOT NULL,
    status ENUM('Pendente', 'Aprovado', 'Rejeitado', 'Finalizado') DEFAULT 'Pendente',
    mensagem TEXT,
    data_solicitacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Índices para otimização
CREATE INDEX idx_pets_categoria ON pets(categoria_id);
CREATE INDEX idx_pets_status ON pets(status);
CREATE INDEX idx_adocoes_status ON adocoes(status);