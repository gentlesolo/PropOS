<?php
/**
 * PropOS — Browser-Based Installer
 *
 * HOW TO USE (no SSH required):
 *   1. Edit APP_ROOT below to match your server's Laravel root path
 *   2. Edit INSTALLER_PASSWORD to a strong temporary password
 *   3. Upload this file to public_html/install.php
 *   4. Visit https://yourdomain.com/install.php in your browser
 *   5. Delete this file when installation is complete
 *
 * IMPORTANT: Delete install.php after setup. Leaving it accessible is a security risk.
 */

// ─── EDIT THESE BEFORE UPLOADING ─────────────────────────────────────────────

// Absolute path to your Laravel root (NOT public_html, the folder above or beside it)
// cPanel example: /home/yourusername/privatematch
// If unsure, check File Manager — find the 'artisan' file and copy its directory path
const APP_ROOT_HINT = '/home/yourusername/privatematch'; // used as placeholder only

// Temporary password to access this installer — change before uploading!
const INSTALLER_PASSWORD = '12345';

// ─────────────────────────────────────────────────────────────────────────────

define('INSTALLER_VERSION', '1.1');
define('PUBLIC_HTML', __DIR__);

@ini_set('display_errors', 0);
@error_reporting(E_ALL);
set_time_limit(300);

session_start();

if (isset($_GET['fix_root_storage'])) {
    if (empty($_SESSION['authed'])) {
        die('Unauthorized');
    }
    header('Content-Type: text/plain');
    $root = app_root();
    $pubHtml = dirname($root) . '/public_html';

    echo "Fixing Root Storage...\n";

    $rootStorage = $root . '/storage';
    if (is_link($rootStorage) || file_exists($rootStorage)) {
        if (is_link($rootStorage)) {
            if (unlink($rootStorage)) {
                echo "Successfully deleted broken symlink at $rootStorage\n";
            } else {
                echo "Failed to delete symlink at $rootStorage\n";
            }
        } else {
            $backup = $rootStorage . '_backup_' . time();
            if (rename($rootStorage, $backup)) {
                echo "Renamed existing directory/file at $rootStorage to $backup\n";
            } else {
                echo "Failed to rename existing directory/file at $rootStorage\n";
            }
        }
    }

    $dirs = [
        $rootStorage,
        $rootStorage . '/app',
        $rootStorage . '/app/public',
        $rootStorage . '/framework',
        $rootStorage . '/framework/cache',
        $rootStorage . '/framework/cache/data',
        $rootStorage . '/framework/sessions',
        $rootStorage . '/framework/views',
        $rootStorage . '/logs'
    ];

    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            if (mkdir($dir, 0775, true)) {
                echo "Created directory: $dir\n";
            } else {
                echo "Failed to create directory: $dir\n";
            }
        } else {
            echo "Directory already exists: $dir\n";
        }
    }

    $pubLink = $pubHtml . '/storage';
    if (is_link($pubLink) || file_exists($pubLink)) {
        if (is_link($pubLink)) {
            unlink($pubLink);
        } else {
            rename($pubLink, $pubLink . '_backup_' . time());
        }
    }

    $source = $rootStorage . '/app/public';
    if (symlink($source, $pubLink)) {
        echo "Created symlink: $pubLink -> $source\n";
    } else {
        echo "Failed to create symlink: $pubLink -> $source (disabled by host? Fallback routing will be used)\n";
    }

    exit;
}

if (isset($_GET['diag'])) {
    if (empty($_SESSION['authed'])) {
        die('Unauthorized');
    }
    header('Content-Type: text/plain');
    $root = app_root();
    $pubHtml = PUBLIC_HTML;

    if (isset($_GET['rename_from']) && isset($_GET['rename_to'])) {
        $from = $_GET['rename_from'];
        $to = $_GET['rename_to'];
        $parent = dirname($root);
        $actualPubHtml = $parent . '/public_html';

        $fromPath = null;
        foreach ([$root, $pubHtml, $parent, $actualPubHtml] as $dir) {
            $test = $dir . '/' . $from;
            if (file_exists($test)) {
                $fromPath = $test;
                break;
            }
        }

        if ($fromPath) {
            $toPath = dirname($fromPath) . '/' . $to;
            if (rename($fromPath, $toPath)) {
                echo "SUCCESS: Renamed $fromPath to $toPath\n\n";
            } else {
                echo "ERROR: Failed to rename $fromPath to $toPath\n\n";
            }
        } else {
            echo "ERROR: Source path $from does not exist in app_root, public_html, parent, or actual public_html\n\n";
        }
    }

    echo "APP ROOT: $root\n";
    echo "PUBLIC HTML: $pubHtml\n";
    $parent = dirname($root);
    echo "PARENT DIR: $parent\n\n";

    echo "--- Files in PARENT DIR ---\n";
    if (is_dir($parent)) {
        foreach (scandir($parent) as $f) {
            if ($f === '.' || $f === '..')
                continue;
            $path = "$parent/$f";
            $type = is_link($path) ? '[LINK -> ' . readlink($path) . ']' : (is_dir($path) ? '[DIR]' : '[FILE]');
            echo "$f $type - Writable: " . (is_writable($path) ? 'Yes' : 'No') . "\n";
        }
    } else {
        echo "PARENT DIR is not a directory!\n";
    }

    $actualPubHtml = $parent . '/public_html';
    echo "\n--- Files in ACTUAL PUBLIC_HTML ($actualPubHtml) ---\n";
    if (is_dir($actualPubHtml)) {
        foreach (scandir($actualPubHtml) as $f) {
            if ($f === '.' || $f === '..')
                continue;
            $path = "$actualPubHtml/$f";
            $type = is_link($path) ? '[LINK -> ' . readlink($path) . ']' : (is_dir($path) ? '[DIR]' : '[FILE]');
            echo "$f $type - Writable: " . (is_writable($path) ? 'Yes' : 'No') . "\n";
        }
    } else {
        echo "ACTUAL PUBLIC_HTML is not a directory!\n";
    }

    echo "\n--- Files in APP ROOT ---\n";
    if (is_dir($root)) {
        foreach (scandir($root) as $f) {
            if ($f === '.' || $f === '..')
                continue;
            $path = "$root/$f";
            echo "$f " . (is_dir($path) ? '[DIR]' : '[FILE]') . " - Writable: " . (is_writable($path) ? 'Yes' : 'No') . "\n";
        }
    } else {
        echo "APP ROOT is not a directory!\n";
    }

    echo "\n--- Files in PUBLIC HTML ---\n";
    if (is_dir($pubHtml)) {
        foreach (scandir($pubHtml) as $f) {
            if ($f === '.' || $f === '..')
                continue;
            $path = "$pubHtml/$f";
            $type = is_link($path) ? '[LINK -> ' . readlink($path) . ']' : (is_dir($path) ? '[DIR]' : '[FILE]');
            echo "$f $type - Writable: " . (is_writable($path) ? 'Yes' : 'No') . "\n";
        }
    } else {
        echo "PUBLIC HTML is not a directory!\n";
    }
    exit;
}

// ─── HELPERS ─────────────────────────────────────────────────────────────────

function app_root(): string
{
    if (isset($_SESSION['app_root']))
        return rtrim($_SESSION['app_root'], '/\\');

    // Auto-detect common layouts
    $docRoot = __DIR__;
    $parent1 = dirname($docRoot);
    $parent2 = dirname($parent1);
    $folderName = basename($docRoot);

    $candidates = [
        $docRoot,                     // If installer is placed directly in the app root
        $parent1,                     // If installer is in a subfolder like 'public' or 'myapp'
        $parent1 . '/' . $folderName,
        $parent2 . '/' . $folderName,
        $parent1 . '/myapp',
        $parent1 . '/main',
        $parent1 . '/privatematch',
        $parent2 . '/myapp',
        $parent2 . '/main',
        $parent2 . '/privatematch',
    ];

    foreach ($candidates as $c) {
        if (file_exists($c . '/artisan'))
            return $c;
    }
    return APP_ROOT_HINT;
}

function env_path(): string
{
    return app_root() . '/.env';
}

function read_env(): array
{
    $env = [];
    if (!file_exists(env_path()))
        return $env;
    foreach (file(env_path(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#'))
            continue;
        [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
        $env[trim($k)] = trim($v, " \t\n\r\"'");
    }
    return $env;
}

function write_env(array $values): void
{
    $template = file_exists(app_root() . '/.env.example')
        ? file_get_contents(app_root() . '/.env.example')
        : '';

    // Build complete .env string
    $lines = [];
    $covered = [];

    foreach (explode("\n", $template) as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            $lines[] = $line;
            continue;
        }
        $key = explode('=', $trimmed, 2)[0];
        if (array_key_exists($key, $values)) {
            $val = $values[$key];
            $needsQuote = preg_match('/[\s#"\'\\\\]/', (string) $val) || $val === '';
            $lines[] = $key . '=' . ($needsQuote ? '"' . addcslashes((string) $val, '"\\') . '"' : $val);
            $covered[$key] = true;
        } else {
            $lines[] = $line;
        }
    }

    // Append any keys not in template
    foreach ($values as $k => $v) {
        if (!isset($covered[$k])) {
            $needsQuote = preg_match('/[\s#"\'\\\\]/', (string) $v) || $v === '';
            $lines[] = $k . '=' . ($needsQuote ? '"' . addcslashes((string) $v, '"\\') . '"' : $v);
        }
    }

    file_put_contents(env_path(), implode("\n", $lines) . "\n");
}

function generate_app_key(): string
{
    return 'base64:' . base64_encode(random_bytes(32));
}

function run_artisan(string $command, array $params = []): array
{
    // Reset environment so Laravel reads our new .env
    foreach (array_keys($_ENV) as $k) {
        if (
            str_starts_with($k, 'APP_') || str_starts_with($k, 'DB_') ||
            str_starts_with($k, 'CACHE_') || str_starts_with($k, 'SESSION_') ||
            str_starts_with($k, 'QUEUE_') || str_starts_with($k, 'MAIL_')
        ) {
            putenv($k);
            unset($_ENV[$k], $_SERVER[$k]);
        }
    }

    ob_start();
    try {
        require_once app_root() . '/vendor/autoload.php';
        $app = require app_root() . '/bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $status = $kernel->call($command, $params);
        $output = $kernel->output();
        $kernel->terminate(new \Symfony\Component\Console\Input\ArrayInput([]), new \Symfony\Component\Console\Output\NullOutput());
        ob_end_clean();
        return ['ok' => $status === 0, 'output' => $output, 'error' => null];
    } catch (Throwable $e) {
        ob_end_clean();
        return ['ok' => false, 'output' => '', 'error' => $e->getMessage()];
    }
}

function test_db(string $host, string $port, string $db, string $user, string $pass): bool
{
    try {
        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
        new PDO($dsn, $user, $pass, [PDO::ATTR_TIMEOUT => 5]);
        return true;
    } catch (PDOException) {
        return false;
    }
}

function check_requirements(): array
{
    $results = [];

    // PHP version
    $phpOk = version_compare(PHP_VERSION, '8.3.0', '>=');
    $results[] = [
        'label' => 'PHP ' . PHP_VERSION,
        'ok' => $phpOk,
        'note' => $phpOk ? '' : 'PHP 8.3+ required. Change via cPanel → PHP Selector.'
    ];

    // Extensions
    $exts = ['pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath', 'fileinfo', 'curl', 'zip'];
    foreach ($exts as $ext) {
        $ok = extension_loaded($ext);
        $results[] = [
            'label' => "ext: {$ext}",
            'ok' => $ok,
            'note' => $ok ? '' : "Enable via cPanel → Select PHP Version → Extensions."
        ];
    }

    // vendor/
    $vendorOk = is_dir(app_root() . '/vendor');
    $results[] = [
        'label' => 'vendor/ directory',
        'ok' => $vendorOk,
        'note' => $vendorOk ? '' : 'Run pre-deploy-build on your local machine, then upload all files including vendor/.'
    ];

    // public/build/
    $buildOk = is_dir(app_root() . '/public/build');
    $results[] = [
        'label' => 'public/build/ (compiled assets)',
        'ok' => $buildOk,
        'note' => $buildOk ? '' : 'Run pre-deploy-build locally to compile assets, then upload public/build/.'
    ];

    // Writable directories
    $writableDirs = [
        app_root() . '/storage',
        app_root() . '/storage/logs',
        app_root() . '/storage/framework/cache',
        app_root() . '/storage/framework/sessions',
        app_root() . '/storage/framework/views',
        app_root() . '/bootstrap/cache',
    ];
    foreach ($writableDirs as $dir) {
        if (!is_dir($dir))
            @mkdir($dir, 0775, true);
        $ok = is_writable($dir);
        $short = str_replace(app_root(), '', $dir);
        $results[] = [
            'label' => "writable: {$short}",
            'ok' => $ok,
            'note' => $ok ? '' : "Set permissions to 775 in cPanel File Manager → right-click → Permissions."
        ];
    }

    return $results;
}

function step(): int
{
    return (int) ($_SESSION['step'] ?? 0);
}
function advance(int $to): void
{
    $_SESSION['step'] = $to;
}
function flash(string $msg): void
{
    $_SESSION['flash'] = $msg;
}
function get_flash(): string
{
    $m = $_SESSION['flash'] ?? '';
    unset($_SESSION['flash']);
    return $m;
}
function p(string $key, string $default = ''): string
{
    return htmlspecialchars($_POST[$key] ?? $default, ENT_QUOTES);
}

// ─── REQUEST HANDLING ─────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Step 0: password
    if ($action === 'auth') {
        if (hash_equals(INSTALLER_PASSWORD, $_POST['password'] ?? '')) {
            $_SESSION['authed'] = true;
            $_SESSION['app_root'] = rtrim($_POST['app_root'] ?? app_root(), '/\\');
            advance(1);
        } else {
            flash('Incorrect password.');
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    // Require auth for all further steps
    if (empty($_SESSION['authed'])) {
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    // Step 2: save DB config
    if ($action === 'save_db') {
        $_SESSION['cfg']['db_host'] = trim($_POST['db_host'] ?? 'localhost');
        $_SESSION['cfg']['db_port'] = trim($_POST['db_port'] ?? '3306');
        $_SESSION['cfg']['db_name'] = trim($_POST['db_name'] ?? '');
        $_SESSION['cfg']['db_user'] = trim($_POST['db_user'] ?? '');
        $_SESSION['cfg']['db_pass'] = $_POST['db_pass'] ?? '';

        if (
            !test_db(
                $_SESSION['cfg']['db_host'],
                $_SESSION['cfg']['db_port'],
                $_SESSION['cfg']['db_name'],
                $_SESSION['cfg']['db_user'],
                $_SESSION['cfg']['db_pass']
            )
        ) {
            flash('❌ Cannot connect to database. Check credentials and try again.');
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        flash('✅ Database connection successful.');
        advance(3);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    // Step 3: save App + Mail config
    if ($action === 'save_app') {
        $_SESSION['cfg']['app_name'] = trim($_POST['app_name'] ?? 'PropOS Platform');
        $_SESSION['cfg']['app_url'] = rtrim(trim($_POST['app_url'] ?? ''), '/');
        $_SESSION['cfg']['app_env'] = $_POST['app_env'] ?? 'production';
        $_SESSION['cfg']['app_debug'] = isset($_POST['app_debug']) ? 'true' : 'false';
        $_SESSION['cfg']['mail_mailer'] = $_POST['mail_mailer'] ?? 'smtp';
        $_SESSION['cfg']['mail_host'] = trim($_POST['mail_host'] ?? '');
        $_SESSION['cfg']['mail_port'] = trim($_POST['mail_port'] ?? '465');
        $rawScheme = $_POST['mail_scheme'] ?? 'smtps';
        $_SESSION['cfg']['mail_scheme'] = $rawScheme === 'ssl' ? 'smtps' : $rawScheme;
        $_SESSION['cfg']['mail_user'] = trim($_POST['mail_user'] ?? '');
        $_SESSION['cfg']['mail_pass'] = $_POST['mail_pass'] ?? '';
        $_SESSION['cfg']['mail_from'] = trim($_POST['mail_from'] ?? '');
        $_SESSION['cfg']['mail_name'] = trim($_POST['mail_name'] ?? 'PropOS Platform');
        $_SESSION['cfg']['resend_key'] = trim($_POST['resend_key'] ?? '');
        advance(4);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    // Step 4: save Services config
    if ($action === 'save_services') {
        $_SESSION['cfg']['ai_provider'] = $_POST['ai_provider'] ?? 'claude';
        $_SESSION['cfg']['anthropic_key'] = trim($_POST['anthropic_key'] ?? '');
        $_SESSION['cfg']['openai_key'] = trim($_POST['openai_key'] ?? '');
        $_SESSION['cfg']['gemini_key'] = trim($_POST['gemini_key'] ?? '');
        $_SESSION['cfg']['deepseek_key'] = trim($_POST['deepseek_key'] ?? '');
        $_SESSION['cfg']['stripe_pk'] = trim($_POST['stripe_pk'] ?? '');
        $_SESSION['cfg']['stripe_sk'] = trim($_POST['stripe_sk'] ?? '');
        $_SESSION['cfg']['stripe_wh'] = trim($_POST['stripe_wh'] ?? '');
        $_SESSION['cfg']['stripe_cur'] = strtoupper(trim($_POST['stripe_cur'] ?? 'USD'));
        $_SESSION['cfg']['paystack_pk'] = trim($_POST['paystack_pk'] ?? '');
        $_SESSION['cfg']['paystack_sk'] = trim($_POST['paystack_sk'] ?? '');
        $_SESSION['cfg']['paystack_cur'] = strtoupper(trim($_POST['paystack_cur'] ?? 'NGN'));
        advance(5);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    if ($action === 'retry_install') {
        $_SESSION['step'] = 5;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    // Quick fix: patch only mail settings in .env without re-running migrations
    if ($action === 'fix_mail') {
        $envFile = app_root() . '/.env';
        $patched = false;
        if (file_exists($envFile)) {
            $content = file_get_contents($envFile);
            // Replace ssl with smtps everywhere in mail config
            $content = preg_replace('/^(MAIL_SCHEME=)ssl$/m', '${1}smtps', $content);
            $content = preg_replace('/^(MAIL_ENCRYPTION=)ssl$/m', '${1}smtps', $content);
            // If MAIL_SCHEME line doesn't exist at all, append it
            if (!preg_match('/^MAIL_SCHEME=/m', $content)) {
                $content .= "\nMAIL_SCHEME=smtps\n";
            }
            if (!preg_match('/^MAIL_ENCRYPTION=/m', $content)) {
                $content .= "\nMAIL_ENCRYPTION=smtps\n";
            }
            file_put_contents($envFile, $content);
            $patched = true;
        }
        // Clear config cache so the new values take effect
        run_artisan('config:clear');
        run_artisan('config:cache');
        $fixLog = $patched
            ? [['ok' => true, 'msg' => '.env patched: MAIL_SCHEME=smtps, MAIL_ENCRYPTION=smtps'], ['ok' => true, 'msg' => 'Config cache refreshed']]
            : [['ok' => false, 'msg' => '.env file not found at ' . app_root()]];
        $_SESSION['fix_mail_log'] = $fixLog;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    // Quick fix: switch to log mailer so registration never crashes even if SMTP is down
    if ($action === 'use_log_mailer') {
        $envFile = app_root() . '/.env';
        $patched = false;
        if (file_exists($envFile)) {
            $content = file_get_contents($envFile);
            $content = preg_replace('/^MAIL_MAILER=.*/m', 'MAIL_MAILER=log', $content);
            if (!preg_match('/^MAIL_MAILER=/m', $content)) {
                $content .= "\nMAIL_MAILER=log\n";
            }
            file_put_contents($envFile, $content);
            $patched = true;
        }
        run_artisan('config:clear');
        run_artisan('config:cache');
        $fixLog = $patched
            ? [['ok' => true, 'msg' => 'MAIL_MAILER=log — emails are now written to storage/logs/laravel.log instead of being sent. Registration will no longer crash.'], ['ok' => true, 'msg' => 'Config cache refreshed. ✅ You can now register users.']]
            : [['ok' => false, 'msg' => '.env file not found at ' . app_root()]];
        $_SESSION['fix_mail_log'] = $fixLog;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    // Quick fix: configure Resend as the mailer with the given API key
    if ($action === 'use_resend') {
        $resendKey = trim($_POST['resend_key'] ?? '');
        $fromAddr = trim($_POST['resend_from'] ?? '');
        $envFile = app_root() . '/.env';
        $patched = false;
        if (file_exists($envFile) && $resendKey) {
            $content = file_get_contents($envFile);
            $content = preg_replace('/^MAIL_MAILER=.*/m', 'MAIL_MAILER=resend', $content);
            if (!preg_match('/^MAIL_MAILER=/m', $content))
                $content .= "\nMAIL_MAILER=resend\n";
            $content = preg_replace('/^RESEND_API_KEY=.*/m', 'RESEND_API_KEY=' . $resendKey, $content);
            if (!preg_match('/^RESEND_API_KEY=/m', $content))
                $content .= "\nRESEND_API_KEY={$resendKey}\n";
            if ($fromAddr) {
                $content = preg_replace('/^MAIL_FROM_ADDRESS=.*/m', 'MAIL_FROM_ADDRESS=' . $fromAddr, $content);
            }
            file_put_contents($envFile, $content);
            $patched = true;
        }
        run_artisan('config:clear');
        run_artisan('config:cache');
        $fixLog = $patched
            ? [['ok' => true, 'msg' => 'MAIL_MAILER=resend configured with your API key.'], ['ok' => true, 'msg' => 'Config cache refreshed. ✅ Emails will now be sent via Resend.']]
            : [['ok' => false, 'msg' => empty($resendKey) ? 'No API key provided.' : '.env file not found at ' . app_root()]];
        $_SESSION['fix_mail_log'] = $fixLog;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    // Fix storage link — tries symlink first, then PHP-proxy fallback (for hosts with symlinks disabled)
    if ($action === 'fix_storage_link') {
        $root = app_root();
        $pubHtml = PUBLIC_HTML;
        $target = $pubHtml . '/storage';
        $source = $root . '/storage/app/public';
        $log = [];

        // ── 1. Remove stale symlink / conflicting file ─────────────────────────
        if (is_link($target)) {
            if (@unlink($target)) {
                $log[] = ['ok' => true, 'msg' => 'Removed stale symlink at public_html/storage.'];
            } else {
                $log[] = ['ok' => false, 'msg' => 'Could not remove stale symlink — try deleting it manually via File Manager.'];
            }
        } elseif (is_file($target)) {
            // Phantom file blocking directory creation
            if (@unlink($target)) {
                $log[] = ['ok' => true, 'msg' => 'Removed phantom file at public_html/storage.'];
            } else {
                $log[] = ['ok' => false, 'msg' => 'Could not remove phantom file at public_html/storage.'];
            }
        }

        // ── 2. Try symlink ─────────────────────────────────────────────────────
        if (!file_exists($target)) {
            if (@symlink($source, $target)) {
                $log[] = ['ok' => true, 'msg' => '✅ Symlink created: public_html/storage → ' . $source];
                $_SESSION['fix_mail_log'] = $log;
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $log[] = ['ok' => false, 'msg' => 'Host has symlinks disabled — using PHP-proxy fallback instead.'];
            }
        }

        // ── 3. Ensure source directory exists ─────────────────────────────────
        if (!is_dir($source)) {
            @mkdir($source, 0775, true);
            $log[] = ['ok' => true, 'msg' => 'Created storage/app/public directory.'];
        }

        // ── 4. PHP-proxy fallback: the public-index.php bridge already handles
        //      /storage/* requests by streaming from storage/app/public/.
        //      We just need to ensure no stale public_html/storage directory
        //      blocks the requests from reaching index.php. ─────────────────────
        $log[] = ['ok' => true, 'msg' => '✅ PHP-proxy fallback is active: images served via public-index.php bridge.'];
        $log[] = ['ok' => true, 'msg' => 'Profile photos will load correctly. No further action needed.'];

        // ── 5. Also update the index.php bridge to embed the correct APP_PATH ──
        $bridgeSrc = $root . '/deploy/public-index.php';
        if (file_exists($bridgeSrc)) {
            $bridge = file_get_contents($bridgeSrc);
            $bridge = preg_replace(
                "/define\('APP_PATH',\s*[^;]+\);/",
                "define('APP_PATH', '" . addslashes($root) . "');",
                $bridge
            );
            if (file_put_contents($pubHtml . '/index.php', $bridge) !== false) {
                $log[] = ['ok' => true, 'msg' => 'public_html/index.php refreshed with correct APP_PATH.'];
            }
        }

        $_SESSION['fix_mail_log'] = $log;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    // Quick fix: clear application cache
    if ($action === 'clear_cache') {
        $root = app_root();
        
        // Force clear physical cache files
        foreach (glob("$root/bootstrap/cache/*.php") as $file) {
            @unlink($file);
        }
        $cachePath = "$root/storage/framework/cache/data";
        if (is_dir($cachePath)) {
            $iter = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($cachePath, \FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iter as $item) {
                $item->isDir() ? @rmdir($item->getRealPath()) : @unlink($item->getRealPath());
            }
        }

        $r1 = run_artisan('cache:clear');
        $r2 = run_artisan('config:clear');
        $r3 = run_artisan('view:clear');

        $_SESSION['fix_mail_log'] = [
            ['ok' => true, 'msg' => 'Physical cache files deleted.'],
            ['ok' => $r1['ok'], 'msg' => 'cache:clear — ' . ($r1['ok'] ? 'done' : $r1['error'] ?? $r1['output'])],
            ['ok' => $r2['ok'], 'msg' => 'config:clear — ' . ($r2['ok'] ? 'done' : $r2['error'] ?? $r2['output'])],
        ];
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    // Step 5: run install
    if ($action === 'install') {
        $cfg = $_SESSION['cfg'] ?? [];
        $appKey = generate_app_key();
        $root = app_root();

        $envValues = [
            'APP_NAME' => $cfg['app_name'] ?? 'PropOS Platform',
            'APP_ENV' => $cfg['app_env'] ?? 'production',
            'APP_KEY' => $appKey,
            'APP_DEBUG' => $cfg['app_debug'] ?? 'false',
            'APP_URL' => $cfg['app_url'] ?? '',
            'APP_LOCALE' => 'en',
            'APP_FALLBACK_LOCALE' => 'en',
            'APP_FAKER_LOCALE' => 'en_US',
            'APP_MAINTENANCE_DRIVER' => 'file',
            'BCRYPT_ROUNDS' => '12',
            'LOG_CHANNEL' => 'stack',
            'LOG_STACK' => 'single',
            'LOG_DEPRECATIONS_CHANNEL' => 'null',
            'LOG_LEVEL' => 'error',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $cfg['db_host'] ?? 'localhost',
            'DB_PORT' => $cfg['db_port'] ?? '3306',
            'DB_DATABASE' => $cfg['db_name'] ?? '',
            'DB_USERNAME' => $cfg['db_user'] ?? '',
            'DB_PASSWORD' => $cfg['db_pass'] ?? '',
            'SESSION_DRIVER' => 'database',
            'SESSION_LIFETIME' => '120',
            'SESSION_ENCRYPT' => 'false',
            'SESSION_PATH' => '/',
            'SESSION_DOMAIN' => 'null',
            'SESSION_SECURE_COOKIE' => str_starts_with($cfg['app_url'] ?? '', 'https') ? 'true' : 'false',
            'BROADCAST_CONNECTION' => 'log',
            'FILESYSTEM_DISK' => 'public',  // Required: photos stored on Storage::disk('public')
            'QUEUE_CONNECTION' => 'database',
            'CACHE_STORE' => 'database',
            // Disabled by default on fresh install — enable once platform is stable
            'WORKFLOW_AUTOMATIONS_ENABLED' => 'false',
            'MAIL_MAILER' => $cfg['mail_mailer'] ?? 'smtp',
            'MAIL_ENCRYPTION' => ($cfg['mail_port'] ?? '465') === '587' ? 'tls' : 'smtps',
            'MAIL_SCHEME' => ($cfg['mail_port'] ?? '465') === '587' ? 'tls' : 'smtps',
            'MAIL_HOST' => $cfg['mail_host'] ?? '',
            'MAIL_PORT' => $cfg['mail_port'] ?? '465',
            'MAIL_USERNAME' => $cfg['mail_user'] ?? '',
            'MAIL_PASSWORD' => $cfg['mail_pass'] ?? '',
            'MAIL_FROM_ADDRESS' => $cfg['mail_from'] ?? '',
            'MAIL_FROM_NAME' => $cfg['mail_name'] ?? 'PropOS Platform',
            'RESEND_API_KEY' => $cfg['resend_key'] ?? '',
            'AI_PROVIDER' => $cfg['ai_provider'] ?? 'claude',
            'ANTHROPIC_API_KEY' => $cfg['anthropic_key'] ?? '',
            'CLAUDE_MODEL' => 'claude-sonnet-4-6',
            'OPENAI_API_KEY' => $cfg['openai_key'] ?? '',
            'OPENAI_MODEL' => 'gpt-4o',
            'GEMINI_API_KEY' => $cfg['gemini_key'] ?? '',
            'GEMINI_MODEL' => 'gemini-2.0-flash',
            'DEEPSEEK_API_KEY' => $cfg['deepseek_key'] ?? '',
            'DEEPSEEK_MODEL' => 'deepseek-chat',
            'STRIPE_PUBLISHABLE_KEY' => $cfg['stripe_pk'] ?? '',
            'STRIPE_SECRET_KEY' => $cfg['stripe_sk'] ?? '',
            'STRIPE_WEBHOOK_SECRET' => $cfg['stripe_wh'] ?? '',
            'STRIPE_CURRENCY' => $cfg['stripe_cur'] ?? 'USD',
            'PAYSTACK_PUBLIC_KEY' => $cfg['paystack_pk'] ?? '',
            'PAYSTACK_SECRET_KEY' => $cfg['paystack_sk'] ?? '',
            'PAYSTACK_CURRENCY' => $cfg['paystack_cur'] ?? 'NGN',
            'VITE_APP_NAME' => $cfg['app_name'] ?? 'PropOS Platform',
        ];

        $log = [];

        // Write .env
        try {
            write_env($envValues);
            $log[] = ['ok' => true, 'msg' => '.env file written'];
        } catch (Throwable $e) {
            $log[] = ['ok' => false, 'msg' => '.env write failed: ' . $e->getMessage()];
            $_SESSION['install_log'] = $log;
            advance(6);
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        // Ensure storage directories exist
        $storageDirs = [
            "$root/storage/app/public",
            "$root/storage/framework/cache/data",
            "$root/storage/framework/sessions",
            "$root/storage/framework/views",
            "$root/storage/logs",
            "$root/bootstrap/cache",
        ];
        foreach ($storageDirs as $dir) {
            @mkdir($dir, 0775, true);
        }
        $log[] = ['ok' => true, 'msg' => 'Storage directories created'];

        // Clean vendor/composer/installed.json to remove missing package references (e.g. dev-only packages)
        $installedJson = "$root/vendor/composer/installed.json";
        if (file_exists($installedJson)) {
            try {
                $data = json_decode(file_get_contents($installedJson), true);
                if ($data && (isset($data['packages']) || is_array($data))) {
                    $packages = $data['packages'] ?? $data;
                    $cleaned = [];
                    foreach ($packages as $pkg) {
                        $installPath = $pkg['install-path'] ?? '';
                        if ($installPath) {
                            $absolutePath = rtrim("$root/vendor/composer/$installPath", '/\\');
                            if (is_dir($absolutePath)) {
                                $cleaned[] = $pkg;
                            }
                        } else {
                            $cleaned[] = $pkg;
                        }
                    }
                    if (isset($data['packages'])) {
                        $data['packages'] = $cleaned;
                    } else {
                        $data = $cleaned;
                    }
                    file_put_contents($installedJson, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                }
            } catch (Throwable $e) {
                // Ignore any issues during cleaning to avoid blocking installation
            }
        }

        // Clear bootstrap cache to fix cross-OS path issues
        foreach (glob("$root/bootstrap/cache/*.php") as $file) {
            @unlink($file);
        }

        // Forcefully delete physical cache files to prevent __PHP_Incomplete_Class
        $cachePath = "$root/storage/framework/cache/data";
        if (is_dir($cachePath)) {
            $iter = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($cachePath, \FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iter as $item) {
                $item->isDir() ? @rmdir($item->getRealPath()) : @unlink($item->getRealPath());
            }
        }

        // Clear application object cache
        run_artisan('cache:clear');

        // Clear package cache explicitly
        run_artisan('package:clear');

        // Discover packages on the new environment
        run_artisan('package:discover');

        // Migrate
        $result = run_artisan('migrate:fresh', ['--force' => true]);
        $log[] = ['ok' => $result['ok'], 'msg' => 'Database migrations: ' . ($result['ok'] ? 'complete' : $result['error'] ?? $result['output'])];

        if ($result['ok']) {
            // Seed
            $seed = run_artisan('db:seed', ['--force' => true]);
            $log[] = ['ok' => $seed['ok'], 'msg' => 'Database seeders: ' . ($seed['ok'] ? 'complete' : $seed['error'] ?? $seed['output'])];

            // Storage link
            if (!is_link("$root/public/storage")) {
                $link = run_artisan('storage:link');
                $log[] = ['ok' => $link['ok'], 'msg' => 'Storage link: ' . ($link['ok'] ? 'created' : $link['error'] ?? $link['output'])];
            } else {
                $log[] = ['ok' => true, 'msg' => 'Storage link: already exists'];
            }

            // Cache
            foreach (['config:cache', 'route:cache', 'view:cache', 'event:cache'] as $cmd) {
                $r = run_artisan($cmd);
                $log[] = ['ok' => $r['ok'], 'msg' => $cmd . ': ' . ($r['ok'] ? 'done' : $r['error'] ?? $r['output'])];
            }

            // Remove any published Livewire assets to force Livewire to use its dynamic PHP route
            // This is required because many shared hosts block web access to ANY folder named /vendor
            $lwPub = "$root/public/vendor/livewire";
            $lwPubHtml = PUBLIC_HTML . "/vendor/livewire";

            foreach ([$lwPub, $lwPubHtml] as $lwDir) {
                if (is_dir($lwDir)) {
                    foreach (glob("$lwDir/*") as $file) {
                        @unlink($file);
                    }
                    @rmdir($lwDir);
                }
            }
            $log[] = ['ok' => true, 'msg' => 'Livewire assets: using dynamic internal route (bypassing vendor block)'];
        }

        // Set permissions
        $writable = ["$root/storage", "$root/bootstrap/cache"];
        foreach ($writable as $dir) {
            if (is_dir($dir)) {
                $changed = @chmod($dir, 0775);
                // Recurse
                $iter = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::SELF_FIRST
                );
                foreach ($iter as $item) {
                    @chmod($item->getPathname(), $item->isDir() ? 0775 : 0664);
                }
            }
        }
        $log[] = ['ok' => true, 'msg' => 'Permissions set (storage 775, files 664)'];

        // Set up public_html bridge files
        $bridged = [];
        $publicSrc = "$root/public";
        $pubHtml = PUBLIC_HTML;

        // .htaccess
        if (file_exists("$publicSrc/.htaccess")) {
            copy("$publicSrc/.htaccess", "$pubHtml/.htaccess");
            $bridged[] = '.htaccess';
        }
        // Static files
        foreach (['favicon.ico', 'robots.txt'] as $f) {
            if (file_exists("$publicSrc/$f")) {
                copy("$publicSrc/$f", "$pubHtml/$f");
                $bridged[] = $f;
            }
        }
        // assets/livewire/ directory (containing Livewire files)
        if (is_dir("$publicSrc/assets/livewire") && !is_dir("$pubHtml/assets/livewire")) {
            @mkdir("$pubHtml/assets/livewire", 0775, true);
            foreach (glob("$publicSrc/assets/livewire/*") as $file) {
                if (is_file($file)) {
                    copy($file, "$pubHtml/assets/livewire/" . basename($file));
                }
            }
            $bridged[] = 'assets/livewire/ (copied)';
        }

        // build/ directory
        if (is_dir("$publicSrc/build") && !is_dir("$pubHtml/build")) {
            // Try symlink, fallback to copy notice
            if (!@symlink("$publicSrc/build", "$pubHtml/build")) {
                $_SESSION['copy_build'] = true; // signal to show manual step
            } else {
                $bridged[] = 'build/ (symlinked)';
            }
        }
        // public/storage → public_html/storage (symlink preferred; PHP proxy is the fallback)
        $pubStorage = "$pubHtml/storage";
        if (!file_exists($pubStorage) && !is_link($pubStorage)) {
            if (@symlink("$publicSrc/storage", $pubStorage)) {
                $bridged[] = 'storage/ (symlinked)';
            } else {
                // Host has symlinks disabled — the index.php bridge streams /storage/* via PHP.
                // Just ensure the source directory exists so uploads have somewhere to go.
                @mkdir("$root/storage/app/public", 0775, true);
                $log[] = ['ok' => true, 'msg' => 'storage/ symlink blocked by host — PHP-proxy fallback active via index.php bridge.'];
            }
        }
        // public/vendor → public_html/vendor (for Livewire)
        if (is_dir("$publicSrc/vendor") && !file_exists("$pubHtml/vendor")) {
            @symlink("$publicSrc/vendor", "$pubHtml/vendor") && $bridged[] = 'vendor/ (symlinked)';
        }

        $log[] = ['ok' => true, 'msg' => 'public_html bridge files: ' . implode(', ', $bridged)];

        // index.php bridge
        $bridgeFile = dirname(__FILE__, 1) . '/../deploy/public-index.php';
        if (file_exists("$root/deploy/public-index.php")) {
            $bridge = file_get_contents("$root/deploy/public-index.php");
            $bridge = preg_replace(
                "/define\('APP_PATH',\s*[^;]+\);/",
                "define('APP_PATH', '" . addslashes($root) . "');",
                $bridge
            );
            file_put_contents("$pubHtml/index.php", $bridge);
            $log[] = ['ok' => true, 'msg' => 'index.php bridge installed in public_html'];
        }

        // php.ini / .user.ini
        $phpIni = "$root/deploy/php.ini.shared";
        if (file_exists($phpIni)) {
            copy($phpIni, "$pubHtml/.user.ini");
            copy($phpIni, "$pubHtml/php.ini");
            $log[] = ['ok' => true, 'msg' => '.user.ini / php.ini deployed'];
        }

        $_SESSION['install_log'] = $log;
        $_SESSION['app_key'] = $appKey;
        advance(6);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    // Step 6: delete installer
    if ($action === 'delete_installer') {
        session_destroy();
        @unlink(__FILE__);
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Done</title></head><body style="font:16px sans-serif;padding:2rem;background:#0f0f13;color:#e4e4e7"><p>✅ Installer deleted. <a href="/" style="color:#a855f7">Go to your site →</a></p></body></html>';
        exit;
    }
}

// ─── RENDER HELPERS ───────────────────────────────────────────────────────────

function page(string $title, string $body, int $currentStep = 0): void
{
    $steps = ['Login', 'Requirements', 'Database', 'App & Mail', 'Services', 'Install', 'Done'];
    $stepCount = count($steps);
    ob_start(); ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title><?= htmlspecialchars($title) ?> —
            <?= htmlspecialchars($_SESSION['cfg']['app_name'] ?? 'PropOS Platform') ?> Installer
        </title>
        <style>
            *,
            *::before,
            *::after {
                box-sizing: border-box;
                margin: 0;
                padding: 0
            }

            body {
                font-family: 'Segoe UI', system-ui, sans-serif;
                background: #0d0d14;
                color: #e4e4e7;
                min-height: 100vh;
                padding: 2rem 1rem
            }

            a {
                color: #a855f7
            }

            .wrap {
                max-width: 680px;
                margin: 0 auto
            }

            .brand {
                font-size: 1.5rem;
                font-weight: 800;
                letter-spacing: -.05em;
                background: linear-gradient(135deg, #a855f7, #ec4899);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                margin-bottom: 1.5rem;
                text-align: center
            }

            .steps {
                display: flex;
                gap: 0;
                margin-bottom: 2rem;
                border-radius: 8px;
                overflow: hidden;
                background: #1a1a25
            }

            .steps span {
                flex: 1;
                text-align: center;
                padding: .5rem .25rem;
                font-size: .72rem;
                color: #6b6b80;
                border-right: 1px solid #2d2d3d
            }

            .steps span:last-child {
                border-right: none
            }

            .steps span.active {
                background: linear-gradient(135deg, #a855f720, #ec489920);
                color: #c084fc;
                font-weight: 600
            }

            .steps span.done {
                color: #34d399
            }

            .card {
                background: #13131f;
                border: 1px solid #2d2d3d;
                border-radius: 12px;
                padding: 2rem
            }

            h2 {
                font-size: 1.25rem;
                font-weight: 700;
                margin-bottom: 1.5rem;
                color: #f4f4f5
            }

            .field {
                margin-bottom: 1.25rem
            }

            label {
                display: block;
                font-size: .85rem;
                color: #a1a1aa;
                margin-bottom: .4rem
            }

            input,
            select {
                width: 100%;
                padding: .65rem .85rem;
                background: #1a1a25;
                border: 1px solid #2d2d3d;
                border-radius: 8px;
                color: #e4e4e7;
                font-size: .9rem;
                outline: none;
                transition: border .15s
            }

            input:focus,
            select:focus {
                border-color: #a855f7
            }

            .hint {
                font-size: .78rem;
                color: #6b6b80;
                margin-top: .3rem
            }

            .row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 1rem
            }

            .btn {
                display: inline-flex;
                align-items: center;
                gap: .5rem;
                padding: .7rem 1.5rem;
                border-radius: 8px;
                font-weight: 600;
                font-size: .9rem;
                cursor: pointer;
                border: none;
                transition: opacity .15s
            }

            .btn-primary {
                background: linear-gradient(135deg, #a855f7, #ec4899);
                color: #fff
            }

            .btn-primary:hover {
                opacity: .85
            }

            .btn-ghost {
                background: #1a1a25;
                border: 1px solid #2d2d3d;
                color: #e4e4e7
            }

            .btn-ghost:hover {
                border-color: #a855f7
            }

            .btn-danger {
                background: #7f1d1d;
                color: #fca5a5;
                border: none
            }

            .alert {
                padding: .75rem 1rem;
                border-radius: 8px;
                font-size: .875rem;
                margin-bottom: 1.25rem
            }

            .alert-err {
                background: #1f0a0a;
                border: 1px solid #7f1d1d;
                color: #fca5a5
            }

            .alert-ok {
                background: #052e16;
                border: 1px solid #14532d;
                color: #86efac
            }

            .check-row {
                display: flex;
                align-items: flex-start;
                gap: .75rem;
                padding: .5rem 0;
                border-bottom: 1px solid #1f1f2e
            }

            .check-row:last-child {
                border-bottom: none
            }

            .badge {
                width: 20px;
                height: 20px;
                flex-shrink: 0;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: .75rem;
                margin-top: .1rem
            }

            .badge-ok {
                background: #052e16;
                color: #4ade80
            }

            .badge-fail {
                background: #1f0a0a;
                color: #f87171
            }

            .check-label {
                flex: 1;
                font-size: .875rem
            }

            .check-note {
                font-size: .78rem;
                color: #f87171;
                margin-top: .2rem
            }

            .log-row {
                display: flex;
                align-items: flex-start;
                gap: .75rem;
                padding: .4rem 0;
                font-size: .85rem
            }

            .section-label {
                font-size: .75rem;
                font-weight: 700;
                letter-spacing: .08em;
                text-transform: uppercase;
                color: #6b6b80;
                margin: 1.25rem 0 .75rem
            }

            .cron-box {
                background: #0a0a12;
                border: 1px solid #2d2d3d;
                border-radius: 8px;
                padding: 1rem;
                font-family: monospace;
                font-size: .78rem;
                color: #c084fc;
                word-break: break-all;
                margin: .5rem 0 1rem
            }

            .copy-hint {
                font-size: .75rem;
                color: #6b6b80
            }

            .check-grid {
                display: grid;
                gap: .5rem;
                margin: 1rem 0
            }

            .check-item {
                display: flex;
                align-items: center;
                gap: .5rem;
                font-size: .875rem
            }

            .check-item::before {
                content: '☐';
                color: #6b6b80
            }

            .check-item.done::before {
                content: '☑';
                color: #4ade80
            }

            select option {
                background: #1a1a25
            }
        </style>
    </head>

    <body>
        <div class="wrap">
            <div class="brand"><?= htmlspecialchars($_SESSION['cfg']['app_name'] ?? 'PropOS Platform') ?></div>
            <?php if ($currentStep > 0): ?>
                <div class="steps">
                    <?php foreach ($steps as $i => $s): ?>
                        <span
                            class="<?= $i < $currentStep ? 'done' : ($i === $currentStep ? 'active' : '') ?>"><?= htmlspecialchars($s) ?></span>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
            <?php $flash = get_flash();
            if ($flash): ?>
                <div class="alert <?= str_starts_with($flash, '✅') ? 'alert-ok' : 'alert-err' ?>">
                    <?= htmlspecialchars($flash) ?>
                </div>
            <?php endif ?>
            <div class="card">
                <?= $body ?>
            </div>
            <p style="text-align:center;font-size:.75rem;color:#3f3f50;margin-top:1.5rem">
                <?= htmlspecialchars($_SESSION['cfg']['app_name'] ?? 'PropOS Platform') ?> Installer
                v<?= INSTALLER_VERSION ?> &mdash; Delete this file after setup
            </p>
        </div>
    </body>

    </html>
    <?php
    echo ob_get_clean();
}

// ─── STEP RENDERERS ───────────────────────────────────────────────────────────

function render_step0(): void
{
    ob_start(); ?>
    <h2>🔐 Installer Access</h2>
    <div
        style="background:#1a1a25; padding:1rem; border:1px solid #a855f7; border-radius:8px; margin-bottom:1rem; font-size:0.85rem; font-family:monospace;">
        <strong style="color:#a855f7">Server Debug Info:</strong><br>
        <span style="color:#a1a1aa">Absolute path of this installer:</span><br>
        <?= htmlspecialchars(__DIR__) ?><br><br>
        <span style="color:#a1a1aa">Folders inside this directory:</span><br>
        <?php
        $dirs = @scandir(__DIR__) ?: [];
        $folders = array_filter($dirs, fn($d) => $d !== '.' && $d !== '..' && is_dir(__DIR__ . '/' . $d));
        echo htmlspecialchars(implode(', ', $folders) ?: 'None');
        ?><br><br>
        <span style="color:#a1a1aa">Does 'artisan' exist here?</span>
        <?= file_exists(__DIR__ . '/artisan') ? '<span style="color:#4ade80">Yes</span>' : '<span style="color:#f87171">No</span>' ?>
    </div>
    <p style="color:#a1a1aa;font-size:.9rem;margin-bottom:1.5rem">
        Enter the installer password you set in <code style="color:#c084fc">INSTALLER_PASSWORD</code>
        and the absolute server path to your Laravel root directory.
    </p>
    <form method="post">
        <input type="hidden" name="action" value="auth">
        <div class="field">
            <label>Laravel Root Path on Server</label>
            <input type="text" name="app_root" value="<?= htmlspecialchars(app_root()) ?>"
                placeholder="/home/username/privatematch" autocomplete="off">
            <p class="hint">In cPanel File Manager, navigate to the folder containing <code>artisan</code> and copy its
                path.</p>
        </div>
        <div class="field">
            <label>Installer Password</label>
            <input type="password" name="password" placeholder="Enter installer password" autocomplete="off">
        </div>
        <button type="submit" class="btn btn-primary">Continue →</button>
    </form>
    <?php
    page('Login', ob_get_clean(), 0);
}

function render_step1(): void
{
    $checks = check_requirements();
    $allOk = array_reduce($checks, fn($c, $r) => $c && $r['ok'], true);
    ob_start(); ?>
    <h2>✅ Requirements Check</h2>
    <div style="margin-bottom:1.5rem">
        <?php foreach ($checks as $c): ?>
            <div class="check-row">
                <div class="badge <?= $c['ok'] ? 'badge-ok' : 'badge-fail' ?>"><?= $c['ok'] ? '✓' : '✗' ?></div>
                <div>
                    <div class="check-label"><?= htmlspecialchars($c['label']) ?></div>
                    <?php if ($c['note']): ?>
                        <div class="check-note"><?= htmlspecialchars($c['note']) ?></div><?php endif ?>
                </div>
            </div>
        <?php endforeach ?>
    </div>
    <?php if ($allOk): ?>
        <form method="post" action="?step=2">
            <input type="hidden" name="action" value="save_db">
            <?php render_db_fields() ?>
            <div style="margin-top:1.5rem">
                <button type="submit" class="btn btn-primary">Test Connection & Continue →</button>
            </div>
        </form>
    <?php else: ?>
        <div class="alert alert-err">Fix the issues above, then <a href="<?= $_SERVER['PHP_SELF'] ?>">reload this page</a>.
        </div>
    <?php endif ?>
    <?php
    page('Requirements', ob_get_clean(), 1);
}

function render_db_fields(): void
{
    $cfg = $_SESSION['cfg'] ?? [];
    ?>
    <div class="section-label">Database Configuration (MySQL)</div>
    <div class="row">
        <div class="field"><label>Host</label><input type="text" name="db_host"
                value="<?= htmlspecialchars($cfg['db_host'] ?? 'localhost') ?>">
            <p class="hint">Usually "localhost" on shared hosting</p>
        </div>
        <div class="field"><label>Port</label><input type="text" name="db_port"
                value="<?= htmlspecialchars($cfg['db_port'] ?? '3306') ?>"></div>
    </div>
    <div class="field"><label>Database Name</label><input type="text" name="db_name"
            value="<?= htmlspecialchars($cfg['db_name'] ?? '') ?>" placeholder="cpanelusername_dbname"></div>
    <div class="row">
        <div class="field"><label>Username</label><input type="text" name="db_user"
                value="<?= htmlspecialchars($cfg['db_user'] ?? '') ?>" placeholder="cpanelusername_dbuser"></div>
        <div class="field"><label>Password</label><input type="password" name="db_pass"
                value="<?= htmlspecialchars($cfg['db_pass'] ?? '') ?>"></div>
    </div>
    <p class="hint">Create the database in cPanel → MySQL Databases first.</p>
    <?php
}

function render_step3(): void
{
    $cfg = $_SESSION['cfg'] ?? [];
    ob_start(); ?>
    <h2>⚙️ Application & Mail</h2>
    <form method="post">
        <input type="hidden" name="action" value="save_app">
        <div class="section-label">Application</div>
        <div class="field"><label>Site Name</label><input type="text" name="app_name"
                value="<?= htmlspecialchars($cfg['app_name'] ?? 'PropOS Platform') ?>"></div>
        <div class="field"><label>Site URL (with https://)</label><input type="text" name="app_url"
                value="<?= htmlspecialchars($cfg['app_url'] ?? 'https://') ?>" placeholder="https://yourdomain.com"></div>
        <div class="row">
            <div class="field">
                <label>Environment</label>
                <select name="app_env">
                    <option value="production" <?= ($cfg['app_env'] ?? '') === 'production' ? 'selected' : '' ?>>production
                    </option>
                    <option value="staging" <?= ($cfg['app_env'] ?? '') === 'staging' ? 'selected' : '' ?>>staging</option>
                </select>
            </div>
            <div class="field" style="padding-top:1.5rem">
                <label><input type="checkbox" name="app_debug" <?= ($cfg['app_debug'] ?? 'false') === 'true' ? 'checked' : '' ?>> Enable debug mode</label>
                <p class="hint">Leave unchecked in production</p>
            </div>
        </div>

        <div class="section-label">Mail (SMTP)</div>
        <div class="row">
            <div class="field">
                <label>Mail Driver</label>
                <select name="mail_mailer">
                    <option value="smtp" <?= ($cfg['mail_mailer'] ?? '') === 'smtp' ? 'selected' : '' ?>>SMTP</option>
                    <option value="log" <?= ($cfg['mail_mailer'] ?? '') === 'log' ? 'selected' : '' ?>>Log (testing only)
                    </option>
                </select>
            </div>
            <div class="field">
                <label>Encryption</label>
                <select name="mail_scheme">
                    <option value="ssl" <?= ($cfg['mail_scheme'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (port 465)</option>
                    <option value="tls" <?= ($cfg['mail_scheme'] ?? '') === 'tls' ? 'selected' : '' ?>>TLS (port 587)</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="field"><label>SMTP Host</label><input type="text" name="mail_host"
                    value="<?= htmlspecialchars($cfg['mail_host'] ?? '') ?>" placeholder="mail.yourdomain.com"></div>
            <div class="field"><label>SMTP Port</label><input type="text" name="mail_port"
                    value="<?= htmlspecialchars($cfg['mail_port'] ?? '465') ?>"></div>
        </div>
        <div class="row">
            <div class="field"><label>SMTP Username</label><input type="text" name="mail_user"
                    value="<?= htmlspecialchars($cfg['mail_user'] ?? '') ?>" placeholder="noreply@yourdomain.com"></div>
            <div class="field"><label>SMTP Password</label><input type="password" name="mail_pass"
                    value="<?= htmlspecialchars($cfg['mail_pass'] ?? '') ?>"></div>
        </div>
        <div class="row">
            <div class="field"><label>From Address</label><input type="email" name="mail_from"
                    value="<?= htmlspecialchars($cfg['mail_from'] ?? '') ?>" placeholder="noreply@yourdomain.com"></div>
            <div class="field"><label>From Name</label><input type="text" name="mail_name"
                    value="<?= htmlspecialchars($cfg['mail_name'] ?? 'PropOS Platform') ?>"></div>
        </div>
        <button type="submit" class="btn btn-primary">Save & Continue →</button>
    </form>
    <?php
    page('App & Mail', ob_get_clean(), 3);
}

function render_step4(): void
{
    $cfg = $_SESSION['cfg'] ?? [];
    ob_start(); ?>
    <h2>🔌 Services & Payments</h2>
    <form method="post">
        <input type="hidden" name="action" value="save_services">

        <div class="section-label">AI Provider</div>
        <div class="field">
            <label>Default AI Provider</label>
            <select name="ai_provider">
                <?php foreach (['claude', 'openai', 'gemini', 'deepseek'] as $p): ?>
                    <option value="<?= $p ?>" <?= ($cfg['ai_provider'] ?? 'claude') === $p ? 'selected' : '' ?>><?= ucfirst($p) ?>
                    </option>
                <?php endforeach ?>
            </select>
        </div>
        <div class="field"><label>Anthropic (Claude) API Key</label><input type="text" name="anthropic_key"
                value="<?= htmlspecialchars($cfg['anthropic_key'] ?? '') ?>" placeholder="sk-ant-..."></div>
        <div class="field"><label>OpenAI API Key (optional)</label><input type="text" name="openai_key"
                value="<?= htmlspecialchars($cfg['openai_key'] ?? '') ?>" placeholder="sk-..."></div>
        <div class="field"><label>Gemini API Key (optional)</label><input type="text" name="gemini_key"
                value="<?= htmlspecialchars($cfg['gemini_key'] ?? '') ?>"></div>
        <div class="field"><label>DeepSeek API Key (optional)</label><input type="text" name="deepseek_key"
                value="<?= htmlspecialchars($cfg['deepseek_key'] ?? '') ?>" placeholder="sk-..."></div>

        <div class="section-label">Stripe Payments</div>
        <div class="field"><label>Publishable Key</label><input type="text" name="stripe_pk"
                value="<?= htmlspecialchars($cfg['stripe_pk'] ?? '') ?>" placeholder="pk_live_..."></div>
        <div class="field"><label>Secret Key</label><input type="password" name="stripe_sk"
                value="<?= htmlspecialchars($cfg['stripe_sk'] ?? '') ?>" placeholder="sk_live_..."></div>
        <div class="row">
            <div class="field"><label>Webhook Secret</label><input type="text" name="stripe_wh"
                    value="<?= htmlspecialchars($cfg['stripe_wh'] ?? '') ?>" placeholder="whsec_..."></div>
            <div class="field"><label>Currency</label><input type="text" name="stripe_cur"
                    value="<?= htmlspecialchars($cfg['stripe_cur'] ?? 'USD') ?>" maxlength="3"></div>
        </div>

        <div class="section-label">Paystack Payments</div>
        <div class="row">
            <div class="field"><label>Public Key</label><input type="text" name="paystack_pk"
                    value="<?= htmlspecialchars($cfg['paystack_pk'] ?? '') ?>" placeholder="pk_live_..."></div>
            <div class="field"><label>Secret Key</label><input type="password" name="paystack_sk"
                    value="<?= htmlspecialchars($cfg['paystack_sk'] ?? '') ?>" placeholder="sk_live_..."></div>
        </div>
        <div class="field"><label>Currency</label><input type="text" name="paystack_cur"
                value="<?= htmlspecialchars($cfg['paystack_cur'] ?? 'NGN') ?>" maxlength="3" style="width:8rem"></div>

        <button type="submit" class="btn btn-primary">Save & Continue →</button>
    </form>
    <?php
    page('Services', ob_get_clean(), 4);
}

function render_step5(): void
{
    $cfg = $_SESSION['cfg'] ?? [];
    ob_start(); ?>
    <h2>🚀 Install</h2>
    <p style="color:#a1a1aa;font-size:.9rem;margin-bottom:1.5rem">
        Review your settings, then click <strong>Install Now</strong>. This will write the .env file,
        run database migrations, seed initial data, and cache your application.
        This may take 30–90 seconds.
    </p>

    <div class="section-label">Summary</div>
    <table style="width:100%;font-size:.85rem;border-collapse:collapse;margin-bottom:1.5rem">
        <?php
        $summary = [
            'App URL' => $cfg['app_url'] ?? '—',
            'Environment' => $cfg['app_env'] ?? '—',
            'Database' => ($cfg['db_user'] ?? '?') . '@' . ($cfg['db_host'] ?? '?') . '/' . ($cfg['db_name'] ?? '?'),
            'Mail' => ($cfg['mail_mailer'] ?? '—') . ' → ' . ($cfg['mail_host'] ?? ''),
            'AI Provider' => $cfg['ai_provider'] ?? '—',
        ];
        foreach ($summary as $k => $v): ?>
            <tr style="border-bottom:1px solid #1f1f2e">
                <td style="padding:.4rem 0;color:#6b6b80;width:40%"><?= htmlspecialchars($k) ?></td>
                <td style="padding:.4rem 0;color:#e4e4e7"><?= htmlspecialchars($v) ?></td>
            </tr>
        <?php endforeach ?>
    </table>

    <form method="post"
        onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='Installing…'">
        <input type="hidden" name="action" value="install">
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:1rem">
            ⚡ Install Now
        </button>
    </form>
    <p style="text-align:center;font-size:.78rem;color:#6b6b80;margin-top:.75rem">Do not close this tab while installing.
    </p>
    <?php
    page('Install', ob_get_clean(), 5);
}

function render_step6(): void
{
    $log = $_SESSION['install_log'] ?? [];
    $root = app_root();
    $phpBin = PHP_BINARY ?: 'php83';

    $allOk = array_reduce($log, fn($c, $r) => $c && $r['ok'], true);
    ob_start(); ?>
    <h2><?= $allOk ? '🎉 Installation Complete!' : '⚠️ Installation Finished with Errors' ?></h2>

    <div class="section-label">Installation Log</div>
    <?php foreach ($log as $entry): ?>
        <div class="log-row">
            <span style="color:<?= $entry['ok'] ? '#4ade80' : '#f87171' ?>"><?= $entry['ok'] ? '✔' : '✗' ?></span>
            <span style="font-size:.85rem"><?= htmlspecialchars($entry['msg']) ?></span>
        </div>
    <?php endforeach ?>

    <?php if (!empty($_SESSION['fix_mail_log'])): ?>
        <div class="section-label" style="margin-top:1.5rem">Mail Config Patch Result</div>
        <?php foreach ($_SESSION['fix_mail_log'] as $entry): ?>
            <div class="log-row">
                <span style="color:<?= $entry['ok'] ? '#4ade80' : '#f87171' ?>"><?= $entry['ok'] ? '✔' : '✗' ?></span>
                <span style="font-size:.85rem"><?= htmlspecialchars($entry['msg']) ?></span>
            </div>
        <?php endforeach ?>
        <?php unset($_SESSION['fix_mail_log']); ?>
    <?php endif ?>

    <div class="section-label" style="margin-top:1.5rem">Switch to Resend (recommended)</div>
    <form method="post" style="margin-bottom:.75rem">
        <input type="hidden" name="action" value="use_resend">
        <div class="field">
            <label>Resend API Key</label>
            <input type="text" name="resend_key" placeholder="re_xxxxxxxxxxxx" autocomplete="off">
            <div class="hint">Get your key free at <a href="https://resend.com" target="_blank">resend.com</a></div>
        </div>
        <div class="field">
            <label>From Address (must be a verified domain in Resend)</label>
            <input type="email" name="resend_from" placeholder="noreply@yourdomain.com">
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:1rem">
            ✉️ Switch to Resend
        </button>
    </form>

    <div class="section-label" style="margin-top:1.5rem">Quick Fixes</div>
    <div style="display:grid;gap:.75rem;margin-bottom:1.5rem">

        <?php
        // Show storage status
        $pubHtmlDir = PUBLIC_HTML;
        $storageTarget = $pubHtmlDir . '/storage';
        $storageOk = is_link($storageTarget) || is_dir($storageTarget);
        ?>
        <div
            style="background:#0a0a12;border:1px solid <?= $storageOk ? '#14532d' : '#7f1d1d' ?>;border-radius:8px;padding:.75rem 1rem;font-size:.82rem;color:<?= $storageOk ? '#86efac' : '#fca5a5' ?>">
            <?= $storageOk ? '✅' : '⚠' ?> <strong>Profile Image Storage:</strong>
            <?php if (is_link($storageTarget)): ?>
                Symlink exists → <?= htmlspecialchars(readlink($storageTarget)) ?>
            <?php elseif (is_dir($storageTarget)): ?>
                Physical directory exists (PHP-proxy bridge active)
            <?php else: ?>
                <span style="color:#fca5a5">Not configured — click Fix below.</span>
            <?php endif ?>
        </div>

        <form method="post">
            <input type="hidden" name="action" value="fix_storage_link">
            <button type="submit" class="btn btn-primary"
                style="width:100%;justify-content:center;padding:.85rem;background:linear-gradient(135deg,#6366f1,#4f46e5)">
                🖼 Fix Profile Image Uploads (Storage Bridge)
            </button>
            <p class="hint" style="text-align:center;margin-top:.4rem">Tries symlink first; falls back to PHP proxy if host
                blocks symlinks.</p>
        </form>

        <form method="post">
            <input type="hidden" name="action" value="fix_mail">
            <button type="submit" class="btn btn-ghost" style="width:100%;justify-content:center;padding:.85rem">
                🔧 Fix SMTP Scheme (ssl → smtps)
            </button>
        </form>

        <form method="post">
            <input type="hidden" name="action" value="clear_cache">
            <button type="submit" class="btn btn-ghost" style="width:100%;justify-content:center;padding:.85rem;color:#eab308;border-color:#ca8a04">
                🧹 Clear Application Cache (cache:clear)
            </button>
        </form>

        <form method="post">
            <input type="hidden" name="action" value="retry_install">
            <button type="submit" class="btn btn-ghost"
                style="width:100%;justify-content:center;padding:.85rem;color:#fca5a5;border-color:#7f1d1d"
                onclick="return confirm('This wipes the database and reruns all migrations. Continue?')">
                🔄 Retry Full Installation (wipes database!)
            </button>
        </form>

    </div>

    <?php
    $logPath = "$root/storage/logs/laravel.log";
    if (file_exists($logPath) && is_readable($logPath)) {
        $size = filesize($logPath);
        $fp = fopen($logPath, 'r');
        if ($fp) {
            if ($size > 20000)
                fseek($fp, -20000, SEEK_END);
            $content = fread($fp, 20000);
            fclose($fp);

            // Find the last exception start to keep it clean
            $pos = strrpos($content, '[202');
            if ($pos !== false) {
                $content = substr($content, $pos);
            }

            echo '<div class="section-label" style="margin-top:1.5rem">Server Error Log (Latest Error)</div>';
            echo '<pre style="background:#18181b;color:#f87171;padding:1rem;border-radius:8px;font-size:0.75rem;overflow-x:auto;white-space:pre-wrap;max-height:400px;overflow-y:auto;">';
            echo htmlspecialchars($content);
            echo '</pre>';
        }
    }
    ?>

    <?php if (isset($_SESSION['copy_build'])): ?>
        <div class="alert alert-err" style="margin-top:1rem">
            ⚠ Symlinks are disabled on this host. Manually copy <code>public/build/</code> to <code>public_html/build/</code>
            using cPanel File Manager.
        </div>
    <?php endif ?>

    <div class="section-label" style="margin-top:1.5rem">Required: Cron Jobs</div>
    <p style="font-size:.85rem;color:#a1a1aa;margin-bottom:.75rem">
        Go to <strong>cPanel → Cron Jobs</strong> and add the following entries exactly as shown.
    </p>

    <p style="font-size:.8rem;color:#a1a1aa;margin-bottom:.25rem">Every minute — Laravel Scheduler:</p>
    <div class="cron-box">* * * * * <?= htmlspecialchars($phpBin) ?>     <?= htmlspecialchars($root) ?>/artisan schedule:run >>
        /dev/null 2>&1</div>

    <p style="font-size:.8rem;color:#a1a1aa;margin-bottom:.25rem">Every minute — Queue Worker:</p>
    <div class="cron-box">* * * * * <?= htmlspecialchars($phpBin) ?>     <?= htmlspecialchars($root) ?>/artisan queue:work
        --stop-when-empty --tries=3 --timeout=90 --max-jobs=20 >> <?= htmlspecialchars($root) ?>/storage/logs/queue-cron.log
        2>&1</div>

    <p style="font-size:.8rem;color:#a1a1aa;margin-bottom:.25rem">Daily 3 AM — Cleanup:</p>
    <div class="cron-box">0 3 * * * <?= htmlspecialchars($phpBin) ?>     <?= htmlspecialchars($root) ?>/artisan platform:cleanup
        >> /dev/null 2>&1</div>
    <p class="copy-hint">⚠ Adjust the PHP binary path if needed (find yours via cPanel → Select PHP Version).</p>

    <div class="section-label" style="margin-top:1.5rem">Post-Install Checklist</div>
    <div class="check-grid">
        <?php $checklist = [
            'Cron jobs added in cPanel',
            'SSL/HTTPS enabled (cPanel → SSL/TLS → Let\'s Encrypt)',
            'Stripe webhook added at: ' . ($cfg['app_url'] ?? 'https://yourdomain.com') . '/webhooks/stripe',
            'Test login and registration',
            'Test payment flow (use test card 4242 4242 4242 4242)',
            'Delete this installer file (button below)',
        ];
        foreach ($checklist as $item): ?>
            <div class="check-item"><?= htmlspecialchars($item) ?></div>
        <?php endforeach ?>
    </div>

    <div style="margin-top:2rem;padding-top:1.5rem;border-top:1px solid #2d2d3d">
        <p style="font-size:.85rem;color:#f87171;margin-bottom:.75rem">
            ⚠ <strong>Security:</strong> Delete this installer file now. Leaving it accessible is a security risk.
        </p>
        <form method="post" onsubmit="return confirm('Delete the installer file? This cannot be undone.')">
            <input type="hidden" name="action" value="delete_installer">
            <button type="submit" class="btn btn-danger">🗑 Delete install.php</button>
        </form>
    </div>
    <?php
    page('Done', ob_get_clean(), 6);
}

// ─── MAIN ROUTING ─────────────────────────────────────────────────────────────

if (empty($_SESSION['authed'])) {
    render_step0();
    exit;
}

match (step()) {
    0, 1 => render_step1(),
    2 => render_step1(),  // DB fields rendered inside requirements step
    3 => render_step3(),
    4 => render_step4(),
    5 => render_step5(),
    6 => render_step6(),
    default => render_step1(),
};
