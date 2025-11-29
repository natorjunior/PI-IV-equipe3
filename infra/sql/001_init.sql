-- Script de inicialização completo do Banco de Dados Peneirada

-- 1. Criação do Banco
CREATE DATABASE IF NOT EXISTS peneirada CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE peneirada;

-- 2. Tabela de Usuários (Atualizada com Capa, Bio, Sobre e Localização)
CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  senha VARCHAR(255) NOT NULL,
  nascimento DATE DEFAULT NULL,
  avatar VARCHAR(255) DEFAULT NULL,
  
  -- Campos adicionados para o Perfil Completo
  capa VARCHAR(255) DEFAULT NULL,
  bio TEXT DEFAULT NULL,          -- Frase curta (Manchete)
  sobre TEXT DEFAULT NULL,        -- Texto longo (Sobre)
  localizacao VARCHAR(100) DEFAULT 'Brasil',
  
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabela de Posts
CREATE TABLE IF NOT EXISTS posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  content TEXT,
  image VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabela de Amizades (Conexões)
CREATE TABLE IF NOT EXISTS amizades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL, -- Quem enviou o pedido/adicionou
    amigo_id INT NOT NULL,   -- Quem foi adicionado
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (amigo_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    
    -- Garante que não existam amizades duplicadas entre as mesmas duas pessoas na mesma direção
    UNIQUE KEY unique_amizade (usuario_id, amigo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Tabela para Criar Curtidas
CREATE TABLE IF NOT EXISTS likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (user_id, post_id) -- Garante que um usuário só curte 1 vez
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;