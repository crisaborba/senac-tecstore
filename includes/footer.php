</main>

<footer class="site-footer mt-5">
    <div class="container py-4">
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <h2 class="h6 fw-bold">TecStore</h2>
                <p class="small mb-0">Celulares, notebooks, monitores, placas de vídeo, componentes e computadores montados.</p>
            </div>
            <div class="col-6 col-md-4">
                <h2 class="h6 fw-bold">Links</h2>
                <ul class="list-unstyled small mb-0">
                    <li><a href="<?= $base_url ?>produtos.php">Produtos</a></li>
                    <li><a href="<?= $base_url ?>carrinho.php">Carrinho</a></li>
                    <li><a href="<?= $base_url ?>login.php">Minha conta</a></li>
                </ul>
            </div>
            <div class="col-6 col-md-4">
                <h2 class="h6 fw-bold">Contato</h2>
                <ul class="list-unstyled small mb-0">
                    <li>contato@tecstore.com</li>
                    <li>(00) 0000-0000</li>
                    <li>Seg a Sex, 9h às 18h</li>
                </ul>
            </div>
        </div>
        <hr>
        <p class="small text-center mb-0">&copy; <?= date('Y') ?> TecStore. Projeto acadêmico de desenvolvimento web.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $base_url ?>assets/js/script.js"></script>
</body>
</html>
