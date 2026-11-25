<?php
$titulo_pagina = 'Início';
include __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
    <section class="banner p-4 p-md-5 mb-4">
        <h1 class="h3 fw-bold" style="color: var(--azul-escuro);">Bem-vindo à TecStore</h1>
        <p class="mb-3">Tecnologia para o seu dia a dia: celulares, notebooks, monitores e componentes.</p>
        <a href="produtos.php" class="btn btn-tec">Ver produtos</a>
    </section>

    <section class="alert alert-secondary">
        Etapa 1 concluída: estrutura de pastas, cabeçalho, rodapé e identidade visual.
        Categorias e produtos serão carregados do banco na Etapa 3.
    </section>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
