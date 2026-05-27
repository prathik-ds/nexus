<?php
/**
 * Nexus Fest Database Connection — Singleton Pattern
 *
 * WHY SINGLETON?
 * - Hostinger Premium shared hosting has a strict MySQL connection limit (~25-75).
 * - With 600+ students, each page load must reuse the SAME connection object
 *   within a single script execution instead of opening new ones.
 * - require_once already prevents the file running twice, but the Singleton
 *   pattern enforces one PDO object even if getInstance() is called multiple times.
 *
 * WHAT THIS FILE DOES:
 * 1. Opens exactly ONE PDO connection per PHP script execution (Singleton).
 * 2. Disables persistent connections (ATTR_PERSISTENT = false) which can
 *    orphan connections on shared hosts and exhaust the pool quickly.
 * 3. Registers a shutdown function to null out the PDO object so PHP releases
 *    the MySQL connection as soon as the script finishes (before FPM pool recycles).
 * 4. Logs connection errors to PHP's error log without leaking credentials to users.
 */

class Database
{
    // ── Hostinger MySQL credentials ──────────────────────────────────────────
    // On Hostinger, the host is usually '127.0.0.1' or a dedicated DB host.
    // Update these values to match your Hostinger hPanel → Databases → MySQL.
    private static string $host    = '127.0.0.1';
    private static string $dbName  = 'nexusfest';  // your DB name on Hostinger
    private static string $user    = 'root';             // your DB username on Hostinger
    private static string $pass    = '';                 // your DB password on Hostinger
    private static string $charset = 'utf8mb4';

    /** The single shared PDO instance for this script execution */
    private static ?PDO $instance = null;

    /**
     * Private constructor: prevents `new Database()` from outside.
     */
    private function __construct() {}

    /**
     * Returns (and lazily creates) the single PDO connection.
     * All code that needs DB access should call Database::getInstance().
     *
     * @throws RuntimeException if the connection cannot be established.
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                self::$host,
                self::$dbName,
                self::$charset
            );

            $options = [
                // Throw exceptions on errors (never silently fail)
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

                // Return rows as associative arrays by default
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

                // Use real prepared statements (prevents SQL injection + avoids
                // emulated prepares that can cause type-casting bugs)
                PDO::ATTR_EMULATE_PREPARES   => false,

                // ── KEY FIX for Hostinger "Too many connections" ──────────
                // NEVER use persistent connections on shared hosting.
                // Persistent connections bypass the connection limit tracking
                // and can hold connections open across requests, exhausting
                // the MySQL pool very fast with 600+ users.
                PDO::ATTR_PERSISTENT         => false,

                // Short connection & query timeout (seconds).
                // Prevents slow/hung queries from holding connections open.
                PDO::ATTR_TIMEOUT            => 10,
            ];

            try {
                self::$instance = new PDO($dsn, self::$user, self::$pass, $options);

                // Register a shutdown function so the connection is explicitly
                // released the moment this PHP script finishes executing.
                // This is the MOST EFFECTIVE way to free MySQL connections quickly
                // on shared hosting where FPM worker recycling can be slow.
                register_shutdown_function(function () {
                    Database::closeConnection();
                });

            } catch (PDOException $e) {
                // Log the real error internally (never expose credentials/stack trace)
                error_log('[Nexus Fest DB] Connection failed: ' . $e->getMessage());

                // Show a user-friendly error page instead of a blank white screen
                http_response_code(503);
                ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Unavailable — Nexus Fest</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #04060e;
            font-family: 'Segoe UI', system-ui, sans-serif;
            color: #94a3c7;
        }
        .box {
            text-align: center;
            max-width: 460px;
            padding: 48px 32px;
            background: rgba(15,22,41,0.9);
            border: 1px solid rgba(0,212,255,0.15);
            border-radius: 20px;
        }
        .icon { font-size: 3rem; margin-bottom: 16px; }
        h1 { color: #f0f4ff; font-size: 1.4rem; margin-bottom: 10px; }
        p  { font-size: 0.9rem; line-height: 1.6; }
        .retry {
            display: inline-block;
            margin-top: 24px;
            padding: 12px 28px;
            border: 1px solid rgba(0,212,255,0.4);
            border-radius: 10px;
            color: #00d4ff;
            text-decoration: none;
            font-size: 0.85rem;
            transition: background 0.2s;
        }
        .retry:hover { background: rgba(0,212,255,0.08); }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon">⚡</div>
        <h1>Database Temporarily Unavailable</h1>
        <p>The server is experiencing high load. Please wait a moment and try again.</p>
        <a href="javascript:location.reload()" class="retry">Retry Now</a>
    </div>
</body>
</html>
                <?php
                exit;
            }
        }

        return self::$instance;
    }

    /**
     * Explicitly closes the database connection and frees resources.
     * Called automatically by the registered shutdown function.
     * Can also be called manually at the end of a long script.
     */
    public static function closeConnection(): void
    {
        self::$instance = null;
    }
}

// ── Backwards-compatibility shim ────────────────────────────────────────────
// Every existing PHP file uses `$pdo->prepare(...)` directly.
// This one line makes the singleton work WITHOUT touching any other file.
$pdo = Database::getInstance();