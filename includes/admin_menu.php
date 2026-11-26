<?php
// Menu do painel administrativo. Usa $base_url ('../') definido na página.
$pagina_atual = basename($_SERVER['SCRIPT_NAME']);
function item_menu_admin($arquivo, $texto, $pagina_atual, $paginas_ativas = [])
{
    $ativo = ($pagina_atual === $arquivo || in_array($pagina_atual, $paginas_ativas, true)) ? ' active' : '';
    echo '<li class="nav-item"><a class="nav-link' . $ativo . '" href="' . $arquivo . '">' . $texto . '</a></li>';
}
?>
<div class="container mt-3">
    <ul class="nav nav-pills admin-menu flex-wrap">
        <?php item_menu_admin('index.php', 'Painel', $pagina_atual); ?>
        <?php item_menu_admin('produtos.php', 'Produtos', $pagina_atual, ['produto_cadastro.php', 'produto_editar.php']); ?>
        <?php item_menu_admin('pedidos.php', 'Pedidos', $pagina_atual, ['pedido.php']); ?>
        <li class="nav-item"><a class="nav-link" href="../index.php">Ver loja</a></li>
    </ul>
</div>
