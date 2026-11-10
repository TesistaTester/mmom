# Ejecutar desde PowerShell 7 o Windows PowerShell 5.1, dentro de la carpeta descomprimida.
$ErrorActionPreference = 'Stop'
Set-Location (Split-Path -Parent $PSScriptRoot)
Write-Host 'MMOM E8: pruebas locales, sin certificar integracion.'
if (-not (Get-Command python -ErrorAction SilentlyContinue)) { throw 'Instala Python 3.10+ y PHP 8.3+ para ejecutar el orquestador.' }
python .\scripts\run_e8_local.py
if ($LASTEXITCODE -ne 0) { throw 'Hay pruebas locales fallidas. Revisar docs/EVIDENCIA_E8_LOCAL.json' }
Write-Host 'Revisar resultados. Para integracion seguir GUIA_INSTALACION_WINDOWS_POWERSHELL.md.'
