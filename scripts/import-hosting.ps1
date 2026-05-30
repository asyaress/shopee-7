# Import database hosting ke local (sama dengan .env DB_DATABASE)
# Usage: .\scripts\import-hosting.ps1

$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot\..

Write-Host "=== Import data hosting ke database lokal ===" -ForegroundColor Cyan
Write-Host "Database target: lihat DB_DATABASE di .env" -ForegroundColor Yellow
Write-Host ""

$confirm = Read-Host "Ini akan MENGHAPUS semua tabel di database lokal lalu import ulang. Lanjut? (y/N)"
if ($confirm -notmatch '^[yY]') {
    Write-Host "Dibatalkan." -ForegroundColor Yellow
    exit 0
}

php scripts/import-hosting-sql.php
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Write-Host ""
Write-Host "Tips:" -ForegroundColor Green
Write-Host "  - Set SHOPEE env di .env sama hosting (prod) jika perlu sync API"
Write-Host "  - Nama toko di config/monitoring.php shop_names"
Write-Host "  - php artisan serve"
