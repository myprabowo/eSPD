<?php
/**
 * audit_logger.php
 * Logs activity to the central 'audit_logs' table in 'sistempelatihan' database.
 */

function get_client_ip(): string {
    $ip = null;
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip    = trim($parts[0]);
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip ?: 'Unknown';
}

/**
 * Insert a row into audit_logs.
 *
 * @param string      $activity_type  e.g. "LOGIN", "INSERT", "SOFT DELETE"
 * @param string      $details
 * @param string|null $user_override  Use a specific user (e.g. for failed login)
 */
function log_activity(string $activity_type, string $details, ?string $user_override = null): void {
    try {
        $user      = $user_override ?? current_username();
        $role      = current_role();
        $timestamp = date('Y-m-d H:i:s');
        $ip        = get_client_ip();

        db_execute(
            "INSERT INTO audit_logs (timestamp, user, role, activity, details, ip_address)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$timestamp, $user, $role, $activity_type, $details, $ip]
        );
    } catch (Throwable $e) {
        // Silently fail — audit logging must never crash the app
    }
}
