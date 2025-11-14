<?php
// php/auth/bootstrap.php
declare(strict_types=1);

/*
 * Arranca sesión + helpers de CSRF + helpers de usuario.
 */

// --- Sesión ---
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// --- CSRF ---
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_input(): string {
    $t = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="_csrf" value="'.$t.'">';
}

function csrf_verify(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $sent  = $_POST['_csrf'] ?? '';
        $saved = $_SESSION['csrf_token'] ?? '';
        if (!is_string($sent) || !is_string($saved) || $sent === '' || !hash_equals($saved, $sent)) {
            http_response_code(400);
            exit('CSRF token inválido o ausente.');
        }
    }
}

// --- Usuario actual ---
function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

// --- Requerir login ---
function require_login() {
    if (empty($_SESSION['user'])) {
        $login = dirname($_SERVER['SCRIPT_NAME']) . "/../login.php?denied=1";
        header("Location: $login");
        exit;
    }
}

