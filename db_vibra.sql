-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 13/06/2025 às 20:17
-- Versão do servidor: 9.1.0
-- Versão do PHP: 8.3.14

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
-- Estrutura para tabela `avaliacao`
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

-- --------------------------------------------------------

--
-- Estrutura para tabela `cidade`
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
) ENGINE=MyISAM AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `cidade`
--

INSERT INTO `cidade` (`id`, `nome`, `estado`, `pais`, `descricao`, `latitude`, `longitude`) VALUES
(16, 'Salvador', 'BA', 'Brasil', 'A primeira capital do Brasil, rica em cultura, música e história afro-brasileira. Famosa pelo Pelourinho.', -12.9777, -38.5016),
(20, 'Crato', 'CE', 'Brasil', 'Conhecida como \"Oásis do Sertão\", Crato é uma cidade no sul do Ceará, famosa por sua rica natureza na Chapada do Araripe.', -7.2341, -39.4093),
(15, 'Rio de Janeiro', 'RJ', 'Brasil', 'Conhecida como Cidade Maravilhosa, é famosa por suas praias, carnaval e o Cristo Redentor.', -22.9068, -43.1729),
(17, 'Gramado', 'RS', 'Brasil', 'Charmosa cidade na Serra Gaúcha, com forte influência europeia, conhecida pelo frio e pelo festival de cinema.', -29.3754, -50.8775),
(18, 'Foz do Iguaçu', 'PR', 'Brasil', 'Lar das famosas Cataratas do Iguaçu, uma das sete maravilhas naturais do mundo.', -25.5428, -54.5829),
(19, 'São Paulo', 'SP', 'Brasil', 'A maior cidade do Brasil, um centro financeiro e cultural vibrante com uma vasta gama de eventos e gastronomia.', -23.5505, -46.6333),
(21, 'Juazeiro do Norte', 'CE', 'Brasil', 'Principal centro de romaria da América Latina, atraindo milhões de devotos de Padre Cícero Romão Batista durante todo o ano.', -7.2132, -39.3156),
(22, 'Barbalha', 'CE', 'Brasil', 'Famosa pela Festa do Pau da Bandeira de Santo Antônio, um dos maiores e mais importantes eventos culturais e folclóricos do Brasil.', -7.3075, -39.3039);

-- --------------------------------------------------------

--
-- Estrutura para tabela `evento_cultural`
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
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `evento_cultural`
--

INSERT INTO `evento_cultural` (`id`, `id_cidade`, `nome`, `descricao`, `horario_abertura`, `horario_fechamento`, `local_evento`, `latitude`, `longitude`, `tipo`, `taxaentrada`, `status`, `data_cadastro`) VALUES
(12, 16, 'Carnaval de Salvador', 'Considerado uma das maiores festas de rua do planeta, com trios elétricos percorrendo os circuitos Barra-Ondina e Campo Grande.', '2026-02-12 17:00:00', '2026-02-18 05:00:00', 'Circuitos Barra-Ondina e Campo Grande', -12.9777, -12.9777, 'FestasShows', 0, 'aprovado', '2025-06-11 15:18:46'),
(10, 19, 'Lollapalooza Brasil 2026', 'Edição anual do famoso festival de música internacional, com diversos palcos e artistas de rock, pop e eletrônico.', '2026-03-27 11:00:00', '2026-03-29 23:30:00', 'Autódromo de Interlagos, São Paulo', -23.7036, -46.6975, 'FestasShows', 0, 'aprovado', '2025-06-11 15:14:57'),
(11, 17, 'Festival de Cinema de Gramado', 'Um dos mais importantes festivais de cinema do Brasil e da América Latina. Exibe filmes e premia os melhores com o cobiçado troféu \"Kikito\".', '2025-08-09 18:00:00', '2025-08-17 23:00:00', 'Palácio dos Festivais, Gramado', -29.3792, -50.8767, 'TeatrosEspetáculos', 0, 'aprovado', '2025-06-11 15:16:36'),
(13, 15, 'Rock in Rio 2026', 'O lendário festival de rock e música pop retorna à Cidade do Rock para mais uma edição histórica com grandes nomes da música mundial.', '2026-09-18 14:00:00', '2026-09-27 04:00:00', 'Parque Olímpico (Cidade do Rock)', -22.9756, -43.3895, 'FestasShows', 0, 'aprovado', '2025-06-11 15:20:22'),
(14, 18, 'Festival das Cataratas', 'Evento anual focado no fomento do turismo, inovação e geração de negócios para o setor. Reúne profissionais da área de todo o mundo.', '2025-07-02 09:00:00', '2025-07-04 18:00:00', 'Rafain Palace Hotel & Convention', -25.5161, -25.5161, 'CongressosPalestras', 0, 'aprovado', '2025-06-11 15:22:18');

-- --------------------------------------------------------

--
-- Estrutura para tabela `favoritos`
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
-- Estrutura para tabela `midia`
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
) ENGINE=MyISAM AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `midia`
--

INSERT INTO `midia` (`id`, `id_ponto_turistico`, `id_evento_cultural`, `tipo`, `url_arquivo`) VALUES
(22, NULL, 10, 'imagem', 'uploads/eventos_culturais/evento_cultural_68499d71d4d0d2.40888734.jpg'),
(21, 15, NULL, 'imagem', 'uploads/pontos_turisticos/ponto_turistico_68499c495164d5.92615576.jpg'),
(20, 14, NULL, 'imagem', 'uploads/pontos_turisticos/ponto_turistico_68499bf058fe04.53437500.jpg'),
(19, 13, NULL, 'imagem', 'uploads/pontos_turisticos/ponto_turistico_68499b7ef1cc26.20499488.jpg'),
(18, 13, NULL, 'imagem', 'uploads/pontos_turisticos/ponto_turistico_68499b7ef15ad5.49445012.png'),
(17, 12, NULL, 'imagem', 'uploads/pontos_turisticos/ponto_turistico_68499ae9402714.17981986.jpg'),
(16, 11, NULL, 'imagem', 'uploads/pontos_turisticos/ponto_turistico_68499a73ee9044.32773059.jpg'),
(23, NULL, 11, 'imagem', 'uploads/eventos_culturais/evento_cultural_68499dd4a44001.37515159.jpg'),
(24, NULL, 12, 'imagem', 'uploads/eventos_culturais/evento_cultural_68499e56843325.90497428.jpg'),
(25, NULL, 13, 'imagem', 'uploads/eventos_culturais/evento_cultural_68499eb6eaccc4.73518295.png'),
(26, NULL, 14, 'imagem', 'uploads/eventos_culturais/evento_cultural_68499f2a135d41.95579772.jpg'),
(27, 16, NULL, 'imagem', 'uploads/pontos_turisticos/ponto_turistico_6849a34c6a5b46.09434301.jpg'),
(28, 17, NULL, 'imagem', 'uploads/pontos_turisticos/ponto_turistico_6849a3cf466234.96709240.jpg');

-- --------------------------------------------------------

--
-- Estrutura para tabela `password_resets`
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

-- --------------------------------------------------------

--
-- Estrutura para tabela `ponto_turistico`
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
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `ponto_turistico`
--

INSERT INTO `ponto_turistico` (`id`, `id_cidade`, `nome`, `descricao`, `tipo`, `endereco`, `latitude`, `longitude`, `horario_abertura`, `horario_fechamento`, `taxaentrada`, `status`, `data_cadastro`) VALUES
(12, 16, 'Pelourinho', 'Centro histórico de Salvador, famoso por sua arquitetura colonial portuguesa, ladeiras e intensa vida cultural.', 'Cultural', 'Largo do Pelourinho - Pelourinho, Salvador - BA, 40026-280', -12.9716, -38.5106, '09:00:00', '18:00:00', 0, 'aprovado', '2025-06-11 15:04:09'),
(11, 15, 'Cristo Redentor', 'Monumento art déco de Jesus Cristo no Rio de Janeiro, localizado no topo do morro do Corcovado.', 'Monumento', 'Parque Nacional da Tijuca - Alto da Boa Vista, Rio de Janeiro - RJ', -22.9519, -43.2105, '08:00:00', '19:00:00', 0, 'aprovado', '2025-06-11 15:02:11'),
(13, 17, 'Lago Negro', 'Um sereno lago artificial rodeado por árvores importadas da Floresta Negra da Alemanha e hortênsias.', 'Parque', 'Rua. J.A. Carazai - Bairro Planalto, Gramado - RS, 95670-000', -29.3828, -50.8642, '08:30:00', '18:00:00', 0, 'aprovado', '2025-06-11 15:06:38'),
(14, 18, 'Parque Nacional do Iguaçu', 'Parque que abriga o lado brasileiro das Cataratas do Iguaçu, com trilhas e mirantes espetaculares.', 'Natureza', 'BR-469, Km 18, Foz do Iguaçu - PR, 85855-750', -25.6953, -54.4367, '09:00:00', '18:00:00', 0, 'aprovado', '2025-06-11 15:08:32'),
(15, 19, 'MASP', 'O Museu de Arte de São Paulo Assis Chateaubriand é um dos mais importantes museus de arte do Hemisfério Sul.', 'Museu', 'Av. Paulista, 1578 - Bela Vista, São Paulo - SP, 01310-200', -23.5613, -46.6565, '10:00:00', '18:00:00', 0, 'aprovado', '2025-06-11 15:10:01'),
(16, 20, 'Geossítio Batateiras', 'Unidade de conservação com piscinas naturais formadas pelas águas do Rio Batateiras, ideal para banho e contato com a natureza.', 'Natureza', 'R. Dr. Mota, s/n - Batateiras, Crato - CE, 63100-000', -7.2625, -39.4317, '08:00:00', '16:00:00', 0, 'aprovado', '2025-06-11 15:39:56'),
(17, 21, 'Estátua do Padre Cícero', 'Monumento com 27 metros de altura na Colina do Horto, oferecendo uma vista panorâmica da cidade e sendo o principal ponto de peregrinação.', 'Religioso', 'Pátio dos Romeiros, s/n - Horto, Juazeiro do Norte - CE', -7.1994, -39.2908, '07:00:00', '18:00:00', 0, 'aprovado', '2025-06-11 15:42:07');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuario`
--

DROP TABLE IF EXISTS `usuario`;
CREATE TABLE IF NOT EXISTS `usuario` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(50) NOT NULL,
  `senha` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `status` enum('ativo','pendente_delecao','desativado') NOT NULL DEFAULT 'ativo',
  `delecao_solicitada_em` datetime DEFAULT NULL,
  `data_desativacao` datetime DEFAULT NULL,
  `descricao_perfil` text COMMENT 'Descrição do perfil do usuário',
  `url_foto_perfil` varchar(255) DEFAULT NULL COMMENT 'Caminho para a foto de perfil do usuário',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_status_delecao` (`status`,`delecao_solicitada_em`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `usuario`
--

INSERT INTO `usuario` (`id`, `nome`, `email`, `senha`, `tipo`, `status`, `delecao_solicitada_em`, `data_desativacao`, `descricao_perfil`, `url_foto_perfil`) VALUES
(1, 'admin', 'admin@admin.com', '$2y$10$LK7RUl4kystWaSVZZXwYCuRo1C9L8M7JWS5BVxLPvbqQFiE0uzulC', 'admin', 'ativo', NULL, NULL, 'Ola', 'uploads/fotos_perfil/user_1_1748815499.png');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
