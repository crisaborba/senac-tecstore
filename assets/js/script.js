// Scripts gerais da TecStore.
// Funções específicas (carrinho, formulários, admin) serão adicionadas nas próximas etapas.
document.addEventListener('DOMContentLoaded', function () {
    // Fecha o menu mobile ao clicar em um link
    var links = document.querySelectorAll('#menuPrincipal .nav-link');
    var menu = document.getElementById('menuPrincipal');

    links.forEach(function (link) {
        link.addEventListener('click', function () {
            if (menu.classList.contains('show')) {
                bootstrap.Collapse.getOrCreateInstance(menu).hide();
            }
        });
    });
});

// Campos de quantidade: impede digitar valor menor que 1 ou maior que o estoque.
// (A validação definitiva é feita no servidor, em carrinho.php.)
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('input[type="number"][data-max]').forEach(function (campo) {
        campo.addEventListener('change', function () {
            var max = parseInt(campo.dataset.max, 10);
            var valor = parseInt(campo.value, 10);

            if (isNaN(valor) || valor < 1) {
                campo.value = 1;
            } else if (valor > max) {
                campo.value = max;
            }
        });
    });
});
