<?php
require_once __DIR__ . '/../includes/auth.php';
exigir_admin('../');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funcoes.php';

$base_url = '../';

// Números simples para o painel (consultas fixas, sem dados do usuário)
$total_produtos  = $pdo->query('SELECT COUNT(*) FROM produtos')->fetchColumn();
$produtos_ativos = $pdo->query('SELECT COUNT(*) FROM produtos WHERE ativo = 1')->fetchColumn();
$estoque_baixo   = $pdo->query('SELECT COUNT(*) FROM produtos WHERE ativo = 1 AND estoque <= 5')->fetchColumn();
$total_pedidos   = $pdo->query('SELECT COUNT(*) FROM pedidos')->fetchColumn();
$total_vendido   = $pdo->query('SELECT COALESCE(SUM(valor_total), 0) FROM pedidos')->fetchColumn();
$total_clientes  = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo = 'cliente'")->fetchColumn();

$titulo_pagina = 'Painel administrativo';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admin_menu.php';
?>

<div class="container mt-3">
    <h1 class="h3 mb-3" style="color: var(--azul-escuro);">Painel administrativo</h1>
    <?php exibir_mensagem(); ?>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4">
            <div class="resumo-carrinho text-center">
                <div class="fs-3 fw-bold"><?= (int) $produtos_ativos ?></div>
                <div class="small">Produtos ativos (de <?= (int) $total_produtos ?>)</div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="resumo-carrinho text-center">
                <div class="fs-3 fw-bold"><?= (int) $estoque_baixo ?></div>
                <div class="small">Com estoque baixo (5 ou menos)</div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="resumo-carrinho text-center">
                <div class="fs-3 fw-bold"><?= (int) $total_clientes ?></div>
                <div class="small">Clientes cadastrados</div>
            </div>
        </div>
        <div class="col-6 col-md-6">
            <div class="resumo-carrinho text-center">
                <div class="fs-3 fw-bold"><?= (int) $total_pedidos ?></div>
                <div class="small">Pedidos realizados</div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="resumo-carrinho text-center">
                <div class="fs-3 fw-bold"><?= formatar_preco($total_vendido) ?></div>
                <div class="small">Valor total vendido</div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="produto_cadastro.php" class="btn btn-tec">Cadastrar produto</a>
        <a href="produtos.php" class="btn btn-outline-tec">Gerenciar produtos</a>
        <a href="pedidos.php" class="btn btn-outline-tec">Ver pedidos</a>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
