CREATE TABLE IF NOT EXISTS usuario (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  senha VARCHAR(255) NOT NULL,
  data_nascimento DATE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

insert into usuario (nome, email, senha, data_nascimento) values
('João da Silva', 'joao@example.com', 'senha123', '1990-01-01'),
('Maria Oliveira', 'maria@example.com', 'senha456', '1985-05-15'),
('Pedro Santos', 'pedro@example.com', 'senha789', '2000-10-30');