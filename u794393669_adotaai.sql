-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 07, 2025 at 06:45 PM
-- Server version: 10.11.10-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u794393669_adotaai`
--

-- --------------------------------------------------------

--
-- Table structure for table `adocoes`
--

CREATE TABLE `adocoes` (
  `id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `interesse_id` int(11) DEFAULT NULL,
  `status` enum('Pendente','Aprovado','Rejeitado','Finalizado') DEFAULT 'Pendente',
  `mensagem` text DEFAULT NULL,
  `data_solicitacao` timestamp NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `data_adocao` datetime DEFAULT NULL COMMENT 'Data em que a adoção foi efetivamente confirmada'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `icone` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categorias`
--

INSERT INTO `categorias` (`id`, `nome`, `icone`) VALUES
(1, 'Cachorro', NULL),
(2, 'Gato', NULL),
(3, 'Passarinho', NULL),
(4, 'Roedor', NULL),
(5, 'Réptil', NULL),
(6, 'Outros', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `imagens_pets`
--

CREATE TABLE `imagens_pets` (
  `id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `arquivo` varchar(255) NOT NULL,
  `imagem_principal` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `imagens_pets`
--

INSERT INTO `imagens_pets` (`id`, `pet_id`, `arquivo`, `imagem_principal`) VALUES
(1, 1, 'pet_67f3322d787b5.png', 1),
(2, 2, 'pet_67f332f45f03c.png', 1),
(3, 3, 'pet_67f332fb85bd4.png', 1),
(4, 4, 'pet_67f333092e5c3.png', 1),
(5, 5, 'pet_67f3336befcb4.png', 1),
(6, 6, 'pet_67f3338055089.png', 1);

-- --------------------------------------------------------

--
-- Table structure for table `interesses`
--

CREATE TABLE `interesses` (
  `id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `data_interesse` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `status` enum('pendente','aprovado','rejeitado') NOT NULL DEFAULT 'pendente',
  `mensagem` text NOT NULL,
  `data_atualizacao` datetime DEFAULT NULL,
  `observacao_admin` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `logs_atividades`
--

CREATE TABLE `logs_atividades` (
  `descricao` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pets`
--

CREATE TABLE `pets` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `idade` varchar(50) DEFAULT NULL,
  `porte` varchar(50) DEFAULT NULL,
  `sexo` enum('Macho','Fêmea') NOT NULL,
  `descricao` text DEFAULT NULL,
  `status_saude` text DEFAULT NULL,
  `vacinacao` text DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` varchar(50) DEFAULT NULL,
  `status` enum('Disponível','Adotado','Cancelado') DEFAULT 'Disponível',
  `usuario_id` int(11) NOT NULL,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pets`
--

INSERT INTO `pets` (`id`, `nome`, `categoria_id`, `idade`, `porte`, `sexo`, `descricao`, `status_saude`, `vacinacao`, `endereco`, `cidade`, `estado`, `status`, `usuario_id`, `data_cadastro`) VALUES
(1, 'Titico', 2, NULL, NULL, '', 'Gato com vacinas em dia, calmo e castrado.', NULL, NULL, NULL, NULL, NULL, 'Disponível', 1, '2025-04-07 02:02:21'),
(2, 'Titico', 2, NULL, NULL, '', 'Gato com vacinas em dia, calmo e castrado.', NULL, NULL, NULL, NULL, NULL, 'Disponível', 1, '2025-04-07 02:05:40'),
(3, 'Titico', 2, NULL, NULL, '', 'Gato com vacinas em dia, calmo e castrado.', NULL, NULL, NULL, NULL, NULL, 'Disponível', 1, '2025-04-07 02:05:47'),
(4, 'Titico', 2, NULL, NULL, '', 'Gato com vacinas em dia, calmo e castrado.', NULL, NULL, NULL, NULL, NULL, 'Disponível', 1, '2025-04-07 02:06:01'),
(5, 'Titico', 2, NULL, NULL, '', 'Gato com vacinas em dia, calmo e castrado.', NULL, NULL, NULL, NULL, NULL, 'Disponível', 1, '2025-04-07 02:07:39'),
(6, 'Titico', 2, NULL, NULL, '', 'Gato com vacinas em dia, calmo e castrado.', NULL, NULL, NULL, NULL, NULL, 'Disponível', 1, '2025-04-07 02:08:00');

-- --------------------------------------------------------

--
-- Table structure for table `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` varchar(50) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp(),
  `ultimo_acesso` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `telefone`, `endereco`, `cidade`, `estado`, `cep`, `is_admin`, `data_cadastro`, `ultimo_acesso`) VALUES
(1, 'Administrador', 'admin@adotaai.com', '$2y$10$1u1klhgSPnHSsdHHFBvaM.luApEdWdgOGcM3x9hwVyRSYNiIVwVbm', NULL, NULL, NULL, NULL, NULL, 1, '2025-04-06 20:34:14', '2025-04-07 15:35:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `adocoes`
--
ALTER TABLE `adocoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pet_id` (`pet_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_adocoes_status` (`status`),
  ADD KEY `adocoes_interesse_fk` (`interesse_id`);

--
-- Indexes for table `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `imagens_pets`
--
ALTER TABLE `imagens_pets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pet_id` (`pet_id`);

--
-- Indexes for table `interesses`
--
ALTER TABLE `interesses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pets`
--
ALTER TABLE `pets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_pets_categoria` (`categoria_id`),
  ADD KEY `idx_pets_status` (`status`);

--
-- Indexes for table `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `adocoes`
--
ALTER TABLE `adocoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `imagens_pets`
--
ALTER TABLE `imagens_pets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `interesses`
--
ALTER TABLE `interesses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pets`
--
ALTER TABLE `pets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `adocoes`
--
ALTER TABLE `adocoes`
  ADD CONSTRAINT `adocoes_ibfk_1` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `adocoes_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `adocoes_interesse_fk` FOREIGN KEY (`interesse_id`) REFERENCES `interesses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `imagens_pets`
--
ALTER TABLE `imagens_pets`
  ADD CONSTRAINT `imagens_pets_ibfk_1` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pets`
--
ALTER TABLE `pets`
  ADD CONSTRAINT `pets_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`),
  ADD CONSTRAINT `pets_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
