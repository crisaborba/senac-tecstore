<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/funcoes.php';

$titulo_pagina = 'Início';

// Categorias
$categorias = $pdo->query('SELECT id, nome, descricao FROM categorias ORDER BY nome')->fetchAll();

// Produtos em destaque (apenas ativos)
$stmt = $pdo->prepare('SELECT id, nome, preco, estoque, imagem FROM produtos
                       WHERE ativo = 1 AND destaque = 1
                       ORDER BY id LIMIT 4');
$stmt->execute();
$destaques = $stmt->fetchAll();

// Produtos mais recentes (apenas ativos)
$stmt = $pdo->prepare('SELECT id, nome, preco, estoque, imagem FROM produtos
                       WHERE ativo = 1
                       ORDER BY data_cadastro DESC, id DESC LIMIT 4');
$stmt->execute();
$recentes = $stmt->fetchAll();

$base_url = '';
include __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">

    <section class="banner p-4 p-md-5 mb-4">
        <h1 class="h3 fw-bold" style="color: var(--azul-escuro);">Bem-vindo à TecStore</h1>
        <p class="mb-3">Tecnologia para o seu dia a dia: celulares, notebooks, monitores e componentes.</p>
        <a href="produtos.php" class="btn btn-tec">Ver todos os produtos</a>
    </section>

    <section class="mb-5">
        <h2 class="titulo-secao">Categorias</h2>
        <div class="row g-3">
            <?php foreach ($categorias as $c): ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="produtos.php?categoria=<?= (int) $c['id'] ?>" class="categoria-link">
                        <span class="fw-semibold"><?= e($c['nome']) ?></span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="mb-5">
        <h2 class="titulo-secao">Produtos em destaque</h2>
        <?php if (empty($destaques)): ?>
            <p class="text-muted">Nenhum produto em destaque no momento.</p>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($destaques as $p): ?>
                    <?php include __DIR__ . '/includes/produto_card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="mb-4">
        <h2 class="titulo-secao">Produtos recentes</h2>
        <?php if (empty($recentes)): ?>
            <p class="text-muted">Nenhum produto cadastrado ainda.</p>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($recentes as $p): ?>
                    <?php include __DIR__ . '/includes/produto_card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
