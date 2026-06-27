<?php
/**
 * auth.php — eSPD Session-based Authentication
 */

function session_start_safe(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.save_handler', 'files');
        ini_set('session.save_path', sys_get_temp_dir());
        session_start();
    }
}

function check_login(string $username, string $password): array {
    $username = trim($username);
    $password = trim($password);
    if ($username === '' || $password === '') {
        return ['status' => false, 'role' => null, 'username' => null];
    }
    $rows = db_mysql_query(
        "SELECT id, username, password, display_name, role FROM users WHERE username = ? AND password = ? LIMIT 1",
        [$username, $password]
    );
    if (count($rows) === 1) {
        return [
            'status'   => true,
            'role'     => $rows[0]['role'],
            'username' => $rows[0]['username'], // Gunakan username (ID unik) untuk created_by
            'display_name' => $rows[0]['display_name'] ?: $rows[0]['username']
        ];
    }
    return ['status' => false, 'role' => null, 'username' => null];
}

function do_login(string $username, string $role, string $display_name = ''): void {
    session_regenerate_id(true);
    $_SESSION['logged_in'] = true;
    $_SESSION['username']  = $username;
    $_SESSION['role']      = $role;
    $_SESSION['display_name'] = $display_name ?: $username;
}

function do_logout(): void {
    session_unset();
    session_destroy();
}

function require_login(): void {
    if (empty($_SESSION['logged_in'])) {
        // Check if this is an AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || 
            (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'json'))) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Sesi habis. Silakan login kembali.']);
            exit;
        }
        header('Location: index.php');
        exit;
    }
}

function has_role(string ...$roles): bool {
    return in_array($_SESSION['role'] ?? '', $roles, true);
}

function require_role(string ...$roles): void {
    if (!has_role(...$roles)) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Akses Ditolak.']);
            exit;
        }
        header('Location: index.php?page=dashboard');
        exit;
    }
}

function current_user(): string {
    return $_SESSION['display_name'] ?? $_SESSION['username'] ?? 'Guest';
}

function current_username(): string {
    return $_SESSION['username'] ?? '';
}

function current_role(): string {
    return $_SESSION['role'] ?? 'Guest';
}
