CREATE DATABASE IF NOT EXISTS gestao_academica CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gestao_academica;

DROP TABLE IF EXISTS avaliacoes;
DROP TABLE IF EXISTS pautas;
DROP TABLE IF EXISTS matriculas;
DROP TABLE IF EXISTS plano_estudos;
DROP TABLE IF EXISTS ucs;
DROP TABLE IF EXISTS cursos;
DROP TABLE IF EXISTS fichas_aluno;
DROP VIEW IF EXISTS contas;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    perfil_id INT NOT NULL
);

CREATE VIEW contas AS 
SELECT id AS ID, nome AS NOME, email AS EMAIL, password AS PASSWORD, perfil_id AS PERFIL_ID 
FROM users;

CREATE TABLE fichas_aluno (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    dados TEXT,
    foto VARCHAR(255),
    estado ENUM('rascunho', 'submetida', 'Aprovada', 'Rejeitada') DEFAULT 'rascunho',
    observacoes TEXT,
    validado_por INT,
    data_validacao DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (validado_por) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE cursos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    ativo TINYINT(1) DEFAULT 1
);

CREATE TABLE ucs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    ativo TINYINT(1) DEFAULT 1
);

CREATE TABLE plano_estudos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    curso_id INT NOT NULL,
    uc_id INT NOT NULL,
    ano INT NOT NULL,
    semestre INT NOT NULL,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
    FOREIGN KEY (uc_id) REFERENCES ucs(id) ON DELETE CASCADE
);

CREATE TABLE matriculas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    curso_id INT NOT NULL,
    estado VARCHAR(50) DEFAULT 'Pendente',
    observacoes TEXT,
    validado_por INT,
    data_pedido DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_validacao DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
    FOREIGN KEY (validado_por) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE pautas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uc_id INT NOT NULL,
    ano_letivo VARCHAR(20) NOT NULL,
    epoca VARCHAR(50) NOT NULL,
    criado_por INT NOT NULL,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uc_id) REFERENCES ucs(id) ON DELETE CASCADE,
    FOREIGN KEY (criado_por) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE avaliacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pauta_id INT NOT NULL,
    aluno_id INT NOT NULL,
    nota VARCHAR(10),
    FOREIGN KEY (pauta_id) REFERENCES pautas(id) ON DELETE CASCADE,
    FOREIGN KEY (aluno_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Utilizadores de teste. A password para todos é: 123456
INSERT INTO users (nome, email, password, perfil_id) VALUES 
('Gestor Admin', 'gestor@escola.pt', '$2y$10$HnDNnOUGvshskh4pEfp6m.KML42cKsSGeIY6.pJgrnQnQNehJDudu', 1),
('Aluno Teste', 'aluno@escola.pt', '$2y$10$HnDNnOUGvshskh4pEfp6m.KML42cKsSGeIY6.pJgrnQnQNehJDudu', 2),
('Funcionario Sec', 'funcionario@escola.pt', '$2y$10$HnDNnOUGvshskh4pEfp6m.KML42cKsSGeIY6.pJgrnQnQNehJDudu', 3);
