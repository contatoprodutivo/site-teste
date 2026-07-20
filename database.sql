CREATE DATABASE IF NOT EXISTS curso_powerbi
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE curso_powerbi;

CREATE TABLE IF NOT EXISTS inscricoes_curso (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(150) NOT NULL,
    cpf CHAR(11) NOT NULL,
    email VARCHAR(190) NOT NULL,
    telefone VARCHAR(11) NOT NULL,
    empresa VARCHAR(150) NOT NULL,
    consentimento TINYINT(1) NOT NULL DEFAULT 1,
    data_inscricao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_inscricoes_curso_cpf (cpf),
    KEY idx_inscricoes_curso_email (email),
    KEY idx_inscricoes_curso_data (data_inscricao)
) ENGINE=InnoDB;
