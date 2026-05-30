<?php

/**
 * Import dump hosting ke database lokal (.env DB_*).
 * Usage: php scripts/import-hosting-sql.php [--file=path] [--skip-drop]
 */

declare(strict_types=1);

$basePath = dirname(__DIR__);
require $basePath . '/vendor/autoload.php';

$app = require $basePath . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$defaultFile = $basePath . '/database/dari-hosting/u961291009_toedjoe_shopee.sql';
$file = $defaultFile;
$skipDrop = false;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--file=')) {
        $file = substr($arg, 7);
    }
    if ($arg === '--skip-drop') {
        $skipDrop = true;
    }
}

if (!is_readable($file)) {
    fwrite(STDERR, "File tidak ditemukan: {$file}\n");
    exit(1);
}

$host = env('DB_HOST', '127.0.0.1');
$port = (int) env('DB_PORT', 3306);
$database = env('DB_DATABASE', '');
$user = env('DB_USERNAME', 'root');
$pass = env('DB_PASSWORD', '');

if ($database === '') {
    fwrite(STDERR, "DB_DATABASE kosong di .env\n");
    exit(1);
}

echo "=== Import hosting → local ===\n";
echo "Database : {$database}\n";
echo "File     : {$file}\n";
echo 'Ukuran   : ' . round(filesize($file) / 1024 / 1024, 2) . " MB\n\n";

$mysqli = @new mysqli($host, $user, $pass, $database, $port);
if ($mysqli->connect_error) {
    fwrite(STDERR, 'Koneksi gagal: ' . $mysqli->connect_error . "\n");
    exit(1);
}

$mysqli->set_charset('utf8mb4');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!$skipDrop) {
    echo "Menghapus tabel lama...\n";
    $mysqli->query('SET FOREIGN_KEY_CHECKS=0');
    $result = $mysqli->query(
        "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = '" . $mysqli->real_escape_string($database) . "'"
    );
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $mysqli->query('DROP TABLE IF EXISTS `' . $row['TABLE_NAME'] . '`');
        }
        $result->free();
    }
    echo "Tabel lama dihapus.\n\n";
}

echo "Mengimpor SQL per statement...\n";
$mysqli->query('SET FOREIGN_KEY_CHECKS=0');
$mysqli->query('SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO"');
$mysqli->query("SET time_zone = '+00:00'");

$count = importSqlFileLineByLine($mysqli, $file);

$mysqli->query('SET FOREIGN_KEY_CHECKS=1');
$mysqli->close();

echo "Import selesai ({$count} statement).\n\n";
echo "Menjalankan migrasi tabel baru...\n";

passthru('php "' . $basePath . '/artisan" migrate --force', $migrateCode);

if ($migrateCode !== 0) {
    fwrite(STDERR, "Migrasi tambahan gagal. Jalankan: php artisan migrate --force\n");
    exit(1);
}

echo "\nSelesai. Verifikasi: php scripts/verify-import.php\n";

function importSqlFileLineByLine(mysqli $mysqli, string $path): int
{
    $handle = fopen($path, 'rb');
    if ($handle === false) {
        throw new RuntimeException('Tidak bisa buka file SQL');
    }

    $buffer = '';
    $count = 0;
    $lineNo = 0;

    while (($line = fgets($handle)) !== false) {
        $lineNo++;
        $trim = trim($line);

        if ($trim === '' || str_starts_with($trim, '--')) {
            continue;
        }

        $buffer .= $line;

        if (!str_ends_with(rtrim($line), ';')) {
            continue;
        }

        $stmt = trim($buffer);
        $buffer = '';

        if ($stmt === '' || $stmt === ';') {
            continue;
        }

        if (!$mysqli->query($stmt)) {
            fclose($handle);
            throw new RuntimeException(
                "Baris ~{$lineNo}: " . $mysqli->error . "\n" . substr($stmt, 0, 300) . '...'
            );
        }

        $count++;
        if ($count % 50 === 0) {
            echo "  ... {$count} statement\n";
        }
    }

    fclose($handle);

    if (trim($buffer) !== '') {
        if (!$mysqli->query($buffer)) {
            throw new RuntimeException('Statement terakhir: ' . $mysqli->error);
        }
        $count++;
    }

    return $count;
}
