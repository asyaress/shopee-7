# Setup Laravel — Shopee Profit Hub
# Jalankan di PowerShell: .\setup.ps1

$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot

Write-Host "=== 1/5 Composer install ===" -ForegroundColor Cyan
if (-not (Test-Path "vendor\autoload.php")) {
    composer install --no-interaction
}

Write-Host "=== 2/5 File .env ===" -ForegroundColor Cyan
if (-not (Test-Path ".env")) {
    Copy-Item ".env.example" ".env"
    Write-Host "  .env dibuat dari .env.example — edit DB_* dan kredensial Shopee." -ForegroundColor Yellow
}

Write-Host "=== 3/5 APP_KEY ===" -ForegroundColor Cyan
php artisan key:generate --force

Write-Host "=== 4/5 Storage link ===" -ForegroundColor Cyan
php artisan storage:link 2>$null

Write-Host "=== 5/5 Database ===" -ForegroundColor Cyan
Write-Host "  Pastikan MySQL jalan dan database sudah dibuat (lihat DB_DATABASE di .env)." -ForegroundColor Yellow
Write-Host "  Import data hosting (sama dengan server):" -ForegroundColor Yellow
Write-Host "    php scripts/import-hosting-sql.php" -ForegroundColor White
Write-Host "    atau: .\scripts\import-hosting.ps1" -ForegroundColor White
Write-Host "  Verifikasi: php scripts/verify-import.php" -ForegroundColor Yellow

Write-Host ""
Write-Host "Selesai! Jalankan server:" -ForegroundColor Green
Write-Host "  php artisan serve" -ForegroundColor White
Write-Host "  Buka http://127.0.0.1:8000/login (admin / admin default)" -ForegroundColor White
