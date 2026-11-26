-- TecStore - Banco de dados
-- Importe este arquivo pelo phpMyAdmin (aba "Importar") ou pelo terminal.

CREATE DATABASE IF NOT EXISTS tecstore
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE tecstore;

-- Remove as tabelas antigas (ordem inversa das dependências)
DROP TABLE IF EXISTS itens_pedido;
DROP TABLE IF EXISTS pedidos;
DROP TABLE IF EXISTS produtos;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS usuarios;

-- ---------------------------------------------------------
-- Usuários
-- ---------------------------------------------------------
CREATE TABLE usuarios (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome          VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL,
    senha         VARCHAR(255) NOT NULL,
    tipo          ENUM('cliente', 'admin') NOT NULL DEFAULT 'cliente',
    data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_usuarios_email (email)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Categorias
-- ---------------------------------------------------------
CREATE TABLE categorias (
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome      VARCHAR(80) NOT NULL,
    descricao VARCHAR(255) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_categorias_nome (nome)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Produtos
-- Além dos campos pedidos, foram incluídas 3 colunas de apoio:
--   ativo         -> permite desativar o produto sem apagá-lo
--   destaque      -> controla a seção "Em destaque" da página inicial
--   data_cadastro -> controla a seção "Recentes" da página inicial
-- ---------------------------------------------------------
CREATE TABLE produtos (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    categoria_id  INT UNSIGNED NOT NULL,
    nome          VARCHAR(150) NOT NULL,
    descricao     TEXT NULL,
    preco         DECIMAL(10,2) NOT NULL,
    estoque       INT UNSIGNED NOT NULL DEFAULT 0,
    imagem        VARCHAR(255) NULL,
    ativo         TINYINT(1) NOT NULL DEFAULT 1,
    destaque      TINYINT(1) NOT NULL DEFAULT 0,
    data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_produtos_categoria (categoria_id),
    CONSTRAINT fk_produtos_categoria FOREIGN KEY (categoria_id)
        REFERENCES categorias (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_produtos_preco CHECK (preco >= 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Pedidos
-- ---------------------------------------------------------
CREATE TABLE pedidos (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id      INT UNSIGNED NOT NULL,
    data_pedido     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    forma_pagamento ENUM('pix', 'boleto', 'cartao') NOT NULL,
    valor_total     DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_pedidos_usuario (usuario_id),
    CONSTRAINT fk_pedidos_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_pedidos_total CHECK (valor_total >= 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Itens do pedido
-- preco_unitario guarda o preço no momento da compra
-- ---------------------------------------------------------
CREATE TABLE itens_pedido (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    pedido_id      INT UNSIGNED NOT NULL,
    produto_id     INT UNSIGNED NOT NULL,
    quantidade     INT UNSIGNED NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_itens_pedido (pedido_id),
    KEY idx_itens_produto (produto_id),
    CONSTRAINT fk_itens_pedido FOREIGN KEY (pedido_id)
        REFERENCES pedidos (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_itens_produto FOREIGN KEY (produto_id)
        REFERENCES produtos (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_itens_quantidade CHECK (quantidade > 0),
    CONSTRAINT chk_itens_preco CHECK (preco_unitario >= 0)
) ENGINE=InnoDB;

-- =========================================================
-- DADOS INICIAIS
-- =========================================================

-- Usuário administrador de demonstração
-- E-mail: admin@tecstore.com  |  Senha: admin123
INSERT INTO usuarios (nome, email, senha, tipo) VALUES
('Administrador', 'admin@tecstore.com', '$2y$10$KXLUrbpo5/dbIypR7hCPYOmlYPY1neFIhBHVrU9.tXG/1aVLeqYdi', 'admin');

-- Categorias
INSERT INTO categorias (nome, descricao) VALUES
('Celulares', 'Smartphones e acessórios'),
('Notebooks', 'Notebooks para estudo, trabalho e jogos'),
('Monitores', 'Monitores para escritório e jogos'),
('Placas de vídeo', 'Placas de vídeo para computadores'),
('Componentes', 'Processadores, memórias, SSDs e fontes'),
('Computadores montados', 'PCs completos prontos para uso');

-- Produtos fictícios (imagem NULL = usa a imagem padrão do site)
INSERT INTO produtos (categoria_id, nome, descricao, preco, estoque, imagem, destaque) VALUES
(1, 'Smartphone Nova X10 128GB', 'Tela de 6,5 polegadas, 128GB de armazenamento, 6GB de RAM e câmera tripla de 50MP.', 1499.90, 25, NULL, 1),
(1, 'Smartphone Nova Lite 64GB', 'Tela de 6,1 polegadas, 64GB de armazenamento, 4GB de RAM e bateria de 5000mAh.', 899.90, 40, NULL, 0),
(2, 'Notebook Orion 15 i5 8GB 256GB SSD', 'Notebook de 15,6 polegadas com processador i5, 8GB de RAM e SSD de 256GB.', 3299.00, 12, NULL, 1),
(2, 'Notebook Orion Gamer R7 16GB 512GB SSD', 'Notebook gamer com Ryzen 7, 16GB de RAM, SSD de 512GB e placa de vídeo dedicada.', 5999.00, 6, NULL, 1),
(3, 'Monitor Vision 24" Full HD 75Hz', 'Monitor de 24 polegadas, resolução Full HD, painel IPS e taxa de 75Hz.', 699.00, 30, NULL, 0),
(3, 'Monitor Vision Curvo 27" 144Hz', 'Monitor curvo de 27 polegadas, resolução QHD e taxa de atualização de 144Hz.', 1599.00, 10, NULL, 1),
(4, 'Placa de Vídeo Falcon 8GB', 'Placa de vídeo com 8GB GDDR6, ideal para jogos em Full HD e QHD.', 2399.00, 8, NULL, 1),
(4, 'Placa de Vídeo Falcon 12GB', 'Placa de vídeo com 12GB GDDR6, desempenho avançado para jogos e edição.', 3899.00, 4, NULL, 0),
(5, 'Processador Ryzen 5 5600', '6 núcleos e 12 threads, até 4,4GHz, soquete AM4.', 849.00, 20, NULL, 0),
(5, 'Memória RAM 16GB DDR4 3200MHz', 'Módulo de 16GB DDR4 com frequência de 3200MHz.', 259.90, 50, NULL, 0),
(5, 'SSD NVMe 1TB', 'SSD M.2 NVMe de 1TB com leitura de até 3500MB/s.', 399.00, 35, NULL, 0),
(5, 'Fonte 650W 80 Plus Bronze', 'Fonte de 650W com certificação 80 Plus Bronze e cabos modulares.', 389.00, 18, NULL, 0),
(6, 'PC Montado Office i3 8GB 240GB SSD', 'Computador completo para escritório e estudos, com i3, 8GB de RAM e SSD de 240GB.', 2199.00, 9, NULL, 0),
(6, 'PC Gamer Ryzen 5 16GB RTX 8GB', 'Computador gamer com Ryzen 5, 16GB de RAM, SSD de 512GB e placa de vídeo de 8GB.', 5499.00, 5, NULL, 1);
