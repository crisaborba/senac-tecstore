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
