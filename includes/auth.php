<?php
// Inicia a sessão PHP uma única vez em qualquer página.
// As funções de login e de proteção de páginas serão
// adicionadas na Etapa 5.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
