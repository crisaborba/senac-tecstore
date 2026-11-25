<?php
require_once __DIR__ . '/auth.php';

// $base_url: caminho para a raiz do site.
// Páginas da raiz usam '' e páginas dentro de /admin definem '../' antes do include.
$base_url = $base_url ?? '';
$titulo_pagina = $titulo_pagina ?? 'TecStore';

// Quantidade de itens no carrinho (o carrinho será criado na Etapa 4)
$qtd_carrinho = 0;
if (!empty($_SESSION['carrinho'])) {
    $qtd_carrinho = array_sum($_SESSION['carrinho']);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($titulo_pagina) ?> | TecStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= $base_url ?>assets/css/style.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">

<header class="site-header">
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?= $base_url ?>index.php">
                <i class="bi bi-cpu"></i> TecStore
            </a>

            <div class="d-flex align-items-center gap-2 order-lg-last">
                <a class="btn btn-sm btn-outline-light position-relative" href="<?= $base_url ?>carrinho.php" aria-label="Carrinho">
                    <i class="bi bi-cart3"></i>
                    <span class="d-none d-sm-inline">Carrinho</span>
                    <span class="badge bg-light text-dark ms-1" id="contador-carrinho"><?= $qtd_carrinho ?></span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                        data-bs-target="#menuPrincipal" aria-controls="menuPrincipal"
                        aria-expanded="false" aria-label="Abrir menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>

            <div class="collapse navbar-collapse" id="menuPrincipal">
                <form class="d-flex my-3 my-lg-0 mx-lg-4 flex-grow-1" action="<?= $base_url ?>produtos.php" method="get" role="search">
                    <input class="form-control form-control-sm" type="search" name="busca"
                           placeholder="Buscar produtos" aria-label="Buscar produtos">
                    <button class="btn btn-sm btn-light ms-2" type="submit" aria-label="Pesquisar">
                        <i class="bi bi-search"></i>
                    </button>
                </form>

                <ul class="navbar-nav ms-lg-auto">
                    <li class="nav-item"><a class="nav-link" href="<?= $base_url ?>produtos.php">Produtos</a></li>
                    <?php if (!empty($_SESSION['usuario_id'])): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= $base_url ?>pedidos.php">Meus pedidos</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $base_url ?>logout.php">Sair</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?= $base_url ?>login.php"><i class="bi bi-person"></i> Entrar</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $base_url ?>cadastro.php">Cadastrar</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</header>

<main class="flex-grow-1">
