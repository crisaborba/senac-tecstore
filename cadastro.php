<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/funcoes.php';

$base_url = '';

// Quem já está logado não precisa se cadastrar
if (usuario_logado()) {
    header('Location: index.php');
    exit;
}

$erros = [];
$nome  = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf();

    $nome      = trim($_POST['nome'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $senha     = $_POST['senha'] ?? '';
    $confirmar = $_POST['confirmar_senha'] ?? '';

    // Validações no servidor
    if (mb_strlen($nome) < 3 || mb_strlen($nome) > 100) {
        $erros[] = 'Informe o nome completo (entre 3 e 100 caracteres).';
    }

    if (strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Informe um e-mail válido.';
    }

    if (strlen($senha) < 6) {
        $erros[] = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif (strlen($senha) > 72) {
        $erros[] = 'A senha deve ter no máximo 72 caracteres.';
    }

    if ($senha !== $confirmar) {
        $erros[] = 'A confirmação de senha não confere.';
    }

    // Verifica e-mail duplicado
    if (empty($erros)) {
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $erros[] = 'Este e-mail já está cadastrado.';
        }
    }

    // Grava o usuário. O tipo é sempre "cliente" (não vem do formulário).
    if (empty($erros)) {
        $hash = password_hash($senha, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare('INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)');
            $stmt->execute([$nome, $email, $hash, 'cliente']);

            definir_mensagem('success', 'Cadastro realizado com sucesso! Faça login para continuar.');
            header('Location: login.php');
            exit;
        } catch (PDOException $ex) {
            // 23000 = violação de chave única (e-mail cadastrado ao mesmo tempo)
            if ($ex->getCode() === '23000') {
                $erros[] = 'Este e-mail já está cadastrado.';
            } else {
                $erros[] = 'Não foi possível concluir o cadastro. Tente novamente.';
            }
        }
    }
}

$titulo_pagina = 'Cadastro';
include __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            <h1 class="h3 mb-3" style="color: var(--azul-escuro);">Criar conta</h1>

            <?php if (!empty($erros)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($erros as $erro): ?>
                            <li><?= e($erro) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="cadastro.php" novalidate>
                <?= csrf_campo() ?>
                <div class="mb-3">
                    <label for="nome" class="form-label">Nome completo</label>
                    <input type="text" id="nome" name="nome" class="form-control" maxlength="100"
                           value="<?= e($nome) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">E-mail</label>
                    <input type="email" id="email" name="email" class="form-control" maxlength="150"
                           value="<?= e($email) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="senha" class="form-label">Senha</label>
                    <input type="password" id="senha" name="senha" class="form-control" minlength="6" maxlength="72" required>
                    <div class="form-text">Mínimo de 6 caracteres.</div>
                </div>
                <div class="mb-3">
                    <label for="confirmar_senha" class="form-label">Confirmar senha</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" class="form-control" maxlength="72" required>
                </div>
                <button type="submit" class="btn btn-tec w-100">Cadastrar</button>
            </form>

            <p class="mt-3 small text-center">Já tem conta? <a href="login.php">Entrar</a></p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
