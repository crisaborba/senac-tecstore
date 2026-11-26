<?php
/*
 * Card de produto reutilizável.
 * Espera a variável $p (um produto do banco) e $base_url.
 */
$disp = disponibilidade($p['estoque']);
?>
<div class="col-6 col-md-4 col-lg-3">
    <div class="card card-produto">
        <img src="<?= e(caminho_imagem($p['imagem'], $base_url)) ?>" class="card-img-top" alt="<?= e($p['nome']) ?>">
        <div class="card-body d-flex flex-column">
            <h3 class="h6 card-title"><?= e($p['nome']) ?></h3>
            <p class="preco mb-1"><?= formatar_preco($p['preco']) ?></p>
            <p class="mb-3"><span class="badge <?= $disp['classe'] ?>"><?= $disp['texto'] ?></span></p>
            <a href="<?= $base_url ?>produto.php?id=<?= (int) $p['id'] ?>" class="btn btn-sm btn-outline-tec mt-auto">Ver detalhes</a>
        </div>
    </div>
</div>
