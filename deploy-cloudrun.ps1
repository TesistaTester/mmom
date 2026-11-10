<#
.SYNOPSIS
    Script de despliegue a Google Cloud Run con Base de Datos Supabase.
.DESCRIPTION
    Despliega el backend MMOM en Google Cloud Run configurado para conectarse a Supabase.
.EXAMPLE
    .\deploy-cloudrun.ps1 -ProjectId "mi-proyecto-gcp" -SupabaseHost "db.xxxx.supabase.co" -SupabasePassword "MiPassword123"
#>
param (
    [Parameter(Mandatory=$false)]
    [string]$ProjectId = "",

    [Parameter(Mandatory=$false)]
    [string]$Region = "us-central1",

    [Parameter(Mandatory=$false)]
    [string]$ServiceName = "mmom-backend",

    [Parameter(Mandatory=$false)]
    [string]$SupabaseHost = "",

    [Parameter(Mandatory=$false)]
    [string]$SupabasePassword = "",

    [Parameter(Mandatory=$false)]
    [string]$AppKey = ""
)

if ([string]::IsNullOrWhiteSpace($ProjectId)) {
    $ProjectId = gcloud config get-value project 2>$null
    if ([string]::IsNullOrWhiteSpace($ProjectId)) {
        Write-Error "Por favor especifica el ID del proyecto de GCP: .\deploy-cloudrun.ps1 -ProjectId TU_PROJECT_ID"
        exit 1
    }
}

if ([string]::IsNullOrWhiteSpace($AppKey)) {
    # Intentar leer APP_KEY del archivo .env local si existe
    if (Test-Path ".env") {
        $envKey = (Get-Content .env | Select-String "^APP_KEY=").Line
        if ($envKey) {
            $AppKey = $envKey -replace "^APP_KEY=", ""
        }
    }
    if ([string]::IsNullOrWhiteSpace($AppKey)) {
        Write-Host "Generando clave de aplicación temporal..." -ForegroundColor Yellow
        $AppKey = "base64:" + [Convert]::ToBase64String((1..32 | ForEach-Object { Get-Random -Minimum 0 -Maximum 256 }))
    }
}

Write-Host "=========================================================" -ForegroundColor Cyan
Write-Host " Desplegando MMOM en Google Cloud Run + Supabase" -ForegroundColor Cyan
Write-Host "=========================================================" -ForegroundColor Cyan
Write-Host "Proyecto GCP: $ProjectId"
Write-Host "Región:       $Region"
Write-Host "Servicio:     $ServiceName"

$envVars = @(
    "APP_NAME=MMOM",
    "APP_ENV=production",
    "APP_DEBUG=false",
    "APP_KEY=$AppKey",
    "LOG_CHANNEL=stderr",
    "DB_CONNECTION=pgsql",
    "DB_DATABASE=postgres",
    "DB_PORT=5432",
    "DB_USERNAME=postgres",
    "DB_SSLMODE=require",
    "RUN_MIGRATIONS=true",
    "SESSION_DRIVER=database",
    "CACHE_STORE=database"
)

if (-not [string]::IsNullOrWhiteSpace($SupabaseHost)) {
    $envVars += "DB_HOST=$SupabaseHost"
}
if (-not [string]::IsNullOrWhiteSpace($SupabasePassword)) {
    $envVars += "DB_PASSWORD=$SupabasePassword"
}

$envVarsString = $envVars -join ","

Write-Host "`nIniciando compilación y despliegue con Cloud Build..." -ForegroundColor Yellow

gcloud run deploy $ServiceName `
    --source . `
    --project $ProjectId `
    --region $Region `
    --platform managed `
    --allow-unauthenticated `
    --set-env-vars "$envVarsString"

if ($LASTEXITCODE -eq 0) {
    Write-Host "`n¡Despliegue completado con éxito!" -ForegroundColor Green
} else {
    Write-Host "`nOcurrió un error en el despliegue." -ForegroundColor Red
}
