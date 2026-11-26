<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/funcoes.php';

$base_url = '';

// Hash de uma senha inexistente. É usado quando o e-mail não existe, para que a
// verificação demore o mesmo tempo e não revele se o e-mail está cadastrado.
const HASH_FALSO = '$2y$10$IEVRIVUl8.sMXqZP4rwZZORsxY0e4rU8lN/6eYxGhmoe5zuPhlApS';

// Limite de tentativas erradas por sessão
const MAX_TENTATIVAS = 5;
const SEGUNDOS_BLOQUEIO = 60;

// Página para onde o usuário volta depois do login (lista segura, veja auth.php)
$redirecionar = destino_pos_login($_GET['redirecionar'] ?? $_POST['redirecionar'] ?? '');

// Quem já está logado vai direto para o destino
if (usuario_logado()) {
    header('Location: ' . $redirecionar);
    exit;
}

$erro  = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf();

    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    $bloqueado_ate = $_SESSION['login_bloqueado_ate'] ?? 0;

    if ($bloqueado_ate > time()) {
        $erro = 'Muitas tentativas incorretas. Aguarde ' . ($bloqueado_ate - time()) . ' segundo(s) e tente novamente.';
    } elseif ($email === '' || $senha === '') {
        $erro = 'Informe o e-mail e a senha.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail válido.';
    } else {
        $stmt = $pdo->prepare('SELECT id, nome, senha, tipo FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        // password_verify é sempre executado (com hash falso se o usuário não existir)
        $senha_confere = password_verify($senha, $usuario['senha'] ?? HASH_FALSO);

        if ($usuario && $senha_confere) {
            // Gera novo ID de sessão ao autenticar (evita fixação de sessão).
            // Os dados da sessão, como o carrinho, são mantidos.
            session_regenerate_id(true);
            unset($_SESSION['login_tentativas'], $_SESSION['login_bloqueado_ate']);

            $_SESSION['usuario_id']   = (int) $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_tipo'] = $usuario['tipo'];

            header('Location: ' . $redirecionar);
            exit;
        }

        // Falhou: conta a tentativa. Mensagem única para e-mail inexistente ou senha errada.
        $_SESSION['login_tentativas'] = ($_SESSION['login_tentativas'] ?? 0) + 1;
        if ($_SESSION['login_tentativas'] >= MAX_TENTATIVAS) {
            $_SESSION['login_bloqueado_ate'] = time() + SEGUNDOS_BLOQUEIO;
            $_SESSION['login_tentativas'] = 0;
        }

        $erro = 'E-mail ou senha incorretos.';
    }
}

$titulo_pagina = 'Login';
include __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            <h1 class="h3 mb-3" style="color: var(--azul-escuro);">Entrar</h1>

            <?php exibir_mensagem(); ?>

            <?php if ($erro !== ''): ?>
                <div class="alert alert-danger"><?= e($erro) ?></div>
            <?php endif; ?>

            <form method="post" action="login.php" novalidate>
                <?= csrf_campo() ?>
                <input type="hidden" name="redirecionar" value="<?= e($redirecionar) ?>">
                <div class="mb-3">
                    <label for="email" class="form-label">E-mail</label>
                    <input type="email" id="email" name="email" class="form-control" maxlength="150"
                           value="<?= e($email) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="senha" class="form-label">Senha</label>
                    <input type="password" id="senha" name="senha" class="form-control" maxlength="72" required>
                </div>
                <button type="submit" class="btn btn-tec w-100">Entrar</button>
            </form>

            <p class="mt-3 small text-center">Ainda não tem conta? <a href="cadastro.php">Cadastre-se</a></p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
