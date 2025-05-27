-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 27-Maio-2025 às 19:55
-- Versão do servidor: 9.1.0
-- versão do PHP: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `db_vibra`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `avaliacao`
--

DROP TABLE IF EXISTS `avaliacao`;
CREATE TABLE IF NOT EXISTS `avaliacao` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `id_ponto_turistico` int NOT NULL,
  `id_evento_cultural` int DEFAULT NULL,
  `nota` int NOT NULL,
  `comentario` varchar(1000) DEFAULT NULL,
  `data` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_ponto_turistico` (`id_ponto_turistico`),
  KEY `id_evento_cultural` (`id_evento_cultural`)
) ;

--
-- Extraindo dados da tabela `avaliacao`
--

INSERT INTO `avaliacao` (`id`, `id_usuario`, `id_ponto_turistico`, `id_evento_cultural`, `nota`, `comentario`, `data`) VALUES
(3, 1, 6, NULL, 5, 'oi', '2025-05-22'),
(5, 1, 7, NULL, 5, 'a', '2025-05-22');

-- --------------------------------------------------------

--
-- Estrutura da tabela `cidade`
--

DROP TABLE IF EXISTS `cidade`;
CREATE TABLE IF NOT EXISTS `cidade` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `estado` varchar(50) NOT NULL,
  `pais` varchar(100) NOT NULL,
  `descricao` varchar(500) DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Extraindo dados da tabela `cidade`
--

INSERT INTO `cidade` (`id`, `nome`, `estado`, `pais`, `descricao`, `latitude`, `longitude`) VALUES
(14, 'Crato', 'CE', 'Brasil', 'Cidade Crato.', -7.2469, -39.3929),
(13, 'Juazeiro do Norte', 'CE', 'Brasil', 'Uma bela cidade', -7.205, -39.327);

-- --------------------------------------------------------

--
-- Estrutura da tabela `evento_cultural`
--

DROP TABLE IF EXISTS `evento_cultural`;
CREATE TABLE IF NOT EXISTS `evento_cultural` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_cidade` int NOT NULL,
  `nome` varchar(255) NOT NULL,
  `descricao` varchar(500) DEFAULT NULL,
  `horario_abertura` datetime NOT NULL,
  `horario_fechamento` datetime NOT NULL,
  `local_evento` varchar(255) NOT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `tipo` varchar(50) NOT NULL,
  `taxaentrada` double DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pendente' COMMENT 'pendente, aprovado, reprovado',
  `data_cadastro` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data do cadastro original',
  PRIMARY KEY (`id`),
  KEY `id_cidade` (`id_cidade`),
  KEY `idx_status_evento` (`status`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Extraindo dados da tabela `evento_cultural`
--

INSERT INTO `evento_cultural` (`id`, `id_cidade`, `nome`, `descricao`, `horario_abertura`, `horario_fechamento`, `local_evento`, `latitude`, `longitude`, `tipo`, `taxaentrada`, `status`, `data_cadastro`) VALUES
(6, 13, 'Festival do Inverno', 'Festival', '2025-05-22 15:43:00', '2025-05-22 19:00:00', 'Evento', NULL, NULL, 'FestasShows', 25, 'aprovado', '2025-05-22 18:44:43'),
(9, 14, 'Expocrato', 'A espera acabou! O maior evento de música e cultura do interior do Ceará já tem data marcada para agitar o Cariri. A Expocrato 2025 acontece de 11 a 20 de julho, no Parque de Exposições Pedro Felício Cavalcante, no Crato. Prepare-se para viver 9 noites inesquecíveis (exceto no dia 14) com os maiores artistas do Brasil em um espetáculo de luz, som e emoção.', '2025-07-11 00:00:00', '2025-07-21 00:00:00', 'Parque de Exposições Pedro Felício Cavalcante', -7.2405, -39.4134, 'FestasShows', 0, 'aprovado', '2025-05-27 17:13:12');

-- --------------------------------------------------------

--
-- Estrutura da tabela `favoritos`
--

DROP TABLE IF EXISTS `favoritos`;
CREATE TABLE IF NOT EXISTS `favoritos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `id_ponto_turistico` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_ponto_turistico` (`id_ponto_turistico`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `midia`
--

DROP TABLE IF EXISTS `midia`;
CREATE TABLE IF NOT EXISTS `midia` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_ponto_turistico` int DEFAULT NULL,
  `id_evento_cultural` int DEFAULT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `url_arquivo` varchar(500) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_ponto_turistico` (`id_ponto_turistico`),
  KEY `id_evento_cultural` (`id_evento_cultural`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Extraindo dados da tabela `midia`
--

INSERT INTO `midia` (`id`, `id_ponto_turistico`, `id_evento_cultural`, `tipo`, `url_arquivo`) VALUES
(13, NULL, 9, 'imagem', 'uploads/eventos_culturais/evento_cultural_6835f2a8544c19.95049339.jpg'),
(12, NULL, 6, 'imagem', 'uploads/eventos_culturais/evento_cultural_682f709b9295b9.92731125.jpg'),
(11, 7, NULL, 'imagem', 'uploads/pontos_turisticos/ponto_turistico_682f6aaabf7591.72142215.jpg'),
(10, NULL, 5, 'imagem', 'uploads/eventos_culturais/evento_cultural_682a1f1dc708e8.16209521.png'),
(9, 6, NULL, 'imagem', 'uploads/pontos_turisticos/ponto_turistico_682a1ed9ddb8c4.70782536.png');

-- --------------------------------------------------------

--
-- Estrutura da tabela `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `selector` varchar(64) DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `selector` (`selector`),
  KEY `id_usuario` (`id_usuario`),
  KEY `idx_selector_pr` (`selector`)
) ENGINE=MyISAM AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Extraindo dados da tabela `password_resets`
--

INSERT INTO `password_resets` (`id`, `id_usuario`, `token_hash`, `selector`, `expires_at`) VALUES
(37, 15, '$2y$10$tv5RaxLR9GpKjRIFrSolaek20gS1EuzyP5APjx0CwH//bLnBzArA6', 'e1d47eca398e2109', '2025-05-27 17:53:47'),
(38, 14, '$2y$10$b1pOMCorX08YLryt1koZ8ufoaS29QgjndHDVGAYNrs5RL7usLekbm', '47ce2b826d8b06c4', '2025-05-27 17:54:50');

-- --------------------------------------------------------

--
-- Estrutura da tabela `ponto_turistico`
--

DROP TABLE IF EXISTS `ponto_turistico`;
CREATE TABLE IF NOT EXISTS `ponto_turistico` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_cidade` int NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` varchar(500) DEFAULT NULL,
  `tipo` varchar(50) NOT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `horario_abertura` time DEFAULT NULL,
  `horario_fechamento` time DEFAULT NULL,
  `taxaentrada` double DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pendente' COMMENT 'pendente, aprovado, reprovado',
  `data_cadastro` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data do cadastro original',
  PRIMARY KEY (`id`),
  KEY `id_cidade` (`id_cidade`),
  KEY `idx_status_ponto` (`status`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Extraindo dados da tabela `ponto_turistico`
--

INSERT INTO `ponto_turistico` (`id`, `id_cidade`, `nome`, `descricao`, `tipo`, `endereco`, `latitude`, `longitude`, `horario_abertura`, `horario_fechamento`, `taxaentrada`, `status`, `data_cadastro`) VALUES
(7, 13, 'Estátua do Padre Cícero na colina do Horto', 'Estatua', 'Monumento', 'Colina do Horto S/N, 63012-010', -7.16667, -39.33333, '16:20:00', '17:20:00', NULL, 'aprovado', '2025-05-22 18:19:22');

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuario`
--

DROP TABLE IF EXISTS `usuario`;
CREATE TABLE IF NOT EXISTS `usuario` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(50) NOT NULL,
  `senha` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `tipo` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Extraindo dados da tabela `usuario`
--

INSERT INTO `usuario` (`id`, `nome`, `email`, `senha`, `tipo`) VALUES
(1, 'admin', 'admin@admin.com', '$2y$10$OeKWiZieH.MoNnAMnPPnuer8E3SkdMC9te3myC24yKhRR1i36.3IK', 'admin'),
(14, 'Wesley', 'wesley_furtadopessoa@outlook.com', '$2y$10$wAPHgUg5sBRgsQ7gOoZVNOvfvUUPv8zfov2EaL7JBJqxvdLlmqsW6', 'cliente');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
