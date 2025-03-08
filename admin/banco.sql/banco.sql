CREATE DATABASE WB;

USE WB;

CREATE TABLE WBLogin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    CPF VARCHAR(255) NOT NULL UNIQUE,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    user_level ENUM('admin', 'funcionario', 'rh', 'visitante') NOT NULL DEFAULT 'visitante',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

DELETE FROM WBLogin WHERE id > 0;

-- Insert users with different access levels
INSERT INTO WBLogin (CPF, username, password, email, user_level) VALUES 
('00000000001', 'WB Admin', PASSWORD('123'), 'wbmanutencao@gmail.com', 'admin'),
('00000000002', 'RH User', PASSWORD('123'), 'rh@wbmanutencao.com', 'rh'),
('00000000003', 'Func User', PASSWORD('123'), 'func@wbmanutencao.com', 'funcionario'),
('00000000004', 'Visit User', PASSWORD('123'), 'visit@wbmanutencao.com', 'visitante');
CREATE TABLE team (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user VARCHAR(255) NOT NULL,
    date_order DATE NOT NULL,
    status VARCHAR(50) NOT NULL,
    image VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    contact_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO contact_messages (company_name, contact_name, email, phone, message) VALUES
('Empresa A', 'Contato A', 'contatoa@empresa.com', '123456789', 'Mensagem de teste A'),
('Empresa B', 'Contato B', 'contatob@empresa.com', '987654321', 'Mensagem de teste B');



CREATE TABLE curriculos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telefone VARCHAR(20) NOT NULL,
    cargo_interesse VARCHAR(100) NOT NULL,
    linkedin VARCHAR(255),
    mensagem TEXT,
    resumo_profissional TEXT NOT NULL,
    experiencia TEXT NOT NULL,
    formacao TEXT NOT NULL,
    curriculo_pdf VARCHAR(255),
    data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP
); 

CREATE TABLE IF NOT EXISTS `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tarefa` varchar(255) NOT NULL,
  `responsavel` varchar(100) NOT NULL,
  `status` enum('pendente','em_andamento','concluido') NOT NULL DEFAULT 'pendente',
  `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE tasks 
ADD COLUMN responsavel_id INT,
ADD FOREIGN KEY (responsavel_id) REFERENCES wblogin(id);

-- Tabela para solicitações de acesso
CREATE TABLE IF NOT EXISTS solicitacoes_acesso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    cpf VARCHAR(11) NOT NULL UNIQUE,
    cargo VARCHAR(100) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    status ENUM('pendente', 'aprovado', 'rejeitado') DEFAULT 'pendente',
    data_solicitacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- Alterar a tabela wblogin (remover campos desnecessários)
ALTER TABLE wblogin
    ADD COLUMN IF NOT EXISTS email VARCHAR(255) UNIQUE,
    ADD COLUMN IF NOT EXISTS password VARCHAR(255),
    ADD COLUMN IF NOT EXISTS user_level ENUM('admin', 'funcionario', 'rh', 'visitante') DEFAULT 'visitante'; 

    CREATE TABLE IF NOT EXISTS historico_solicitacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    cpf VARCHAR(11) NOT NULL,
    cargo VARCHAR(100) NOT NULL,
    status ENUM('aprovado', 'rejeitado') NOT NULL,
    data_solicitacao TIMESTAMP,
    data_processamento TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);