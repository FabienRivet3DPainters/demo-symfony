# run-load-test.ps1
# Lance les tests de charge K6 en local en lisant les variables depuis .env.local
# Usage : .\k6\run-load-test.ps1

$envFile = Join-Path $PSScriptRoot "../.env.local"

if (-not (Test-Path $envFile)) {
    Write-Error "Fichier .env.local introuvable : $envFile"
    exit 1
}

# Chargement des variables d'environnement depuis .env.local
Get-Content $envFile | ForEach-Object {
    if ($_ -match '^([^#][^=]*)=(.*)$') {
        [System.Environment]::SetEnvironmentVariable($matches[1].Trim(), $matches[2].Trim())
    }
}

# Vérification des variables requises
@('BASE_URL', 'K6_USERNAME', 'K6_PASSWORD') | ForEach-Object {
    if (-not [System.Environment]::GetEnvironmentVariable($_)) {
        Write-Error "Variable $_ manquante dans .env.local"
        exit 1
    }
}

Write-Host "Lancement des tests de charge K6..." -ForegroundColor Cyan
Write-Host "BASE_URL : $env:BASE_URL" -ForegroundColor Gray

k6 run `
    --env BASE_URL="$env:BASE_URL" `
    --env K6_USERNAME="$env:K6_USERNAME" `
    --env K6_PASSWORD="$env:K6_PASSWORD" `
    "$PSScriptRoot/load-test.js"