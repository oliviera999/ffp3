# Script de test des endpoints FFP3
# Exécuter : .\test-endpoints.ps1

$baseUrl = "https://iot.olution.info/ffp3/ffp3datas/public"

Write-Host "=== Test des Endpoints FFP3 ===" -ForegroundColor Cyan
Write-Host ""

$endpoints = @(
    @{Name="Aquaponie PROD"; Url="$baseUrl/aquaponie"; Type="Page"},
    @{Name="Aquaponie TEST"; Url="$baseUrl/aquaponie-test"; Type="Page"},
    @{Name="Dashboard PROD"; Url="$baseUrl/dashboard"; Type="Page"},
    @{Name="Dashboard TEST"; Url="$baseUrl/dashboard-test"; Type="Page"},
    @{Name="Tide Stats PROD"; Url="$baseUrl/tide-stats"; Type="Page"},
    @{Name="Tide Stats TEST"; Url="$baseUrl/tide-stats-test"; Type="Page"},
    @{Name="Control PROD"; Url="$baseUrl/control"; Type="Page"},
    @{Name="Control TEST"; Url="$baseUrl/control-test"; Type="Page"},
    @{Name="API Outputs PROD"; Url="$baseUrl/api/outputs/states/1"; Type="API"},
    @{Name="API Outputs TEST"; Url="$baseUrl/api/outputs-test/states/1"; Type="API"}
)

$results = @()

foreach ($endpoint in $endpoints) {
    Write-Host "Testing: $($endpoint.Name)..." -NoNewline
    
    try {
        $response = Invoke-WebRequest -Uri $endpoint.Url -Method GET -TimeoutSec 10 -ErrorAction Stop
        
        if ($response.StatusCode -eq 200) {
            Write-Host " ✓ OK (200)" -ForegroundColor Green
            
            # Vérifier si la page contient des erreurs PHP
            if ($endpoint.Type -eq "Page") {
                if ($response.Content -match "Fatal error|Parse error|Warning:|Notice:") {
                    Write-Host "  ⚠ ATTENTION: Erreurs PHP détectées dans le contenu" -ForegroundColor Yellow
                    $results += @{Endpoint=$endpoint.Name; Status="WARNING"; Code=200}
                } else {
                    $results += @{Endpoint=$endpoint.Name; Status="OK"; Code=200}
                }
            } else {
                $results += @{Endpoint=$endpoint.Name; Status="OK"; Code=200}
            }
        } else {
            Write-Host " ⚠ $($response.StatusCode)" -ForegroundColor Yellow
            $results += @{Endpoint=$endpoint.Name; Status="WARNING"; Code=$response.StatusCode}
        }
    }
    catch {
        $statusCode = $_.Exception.Response.StatusCode.value__
        Write-Host " ✗ ERREUR ($statusCode)" -ForegroundColor Red
        $results += @{Endpoint=$endpoint.Name; Status="ERROR"; Code=$statusCode}
    }
    
    Start-Sleep -Milliseconds 500
}

Write-Host ""
Write-Host "=== Résumé ===" -ForegroundColor Cyan
Write-Host ""

$okCount = ($results | Where-Object { $_.Status -eq "OK" }).Count
$warningCount = ($results | Where-Object { $_.Status -eq "WARNING" }).Count
$errorCount = ($results | Where-Object { $_.Status -eq "ERROR" }).Count

Write-Host "✓ OK: $okCount" -ForegroundColor Green
Write-Host "⚠ WARNINGS: $warningCount" -ForegroundColor Yellow
Write-Host "✗ ERREURS: $errorCount" -ForegroundColor Red

Write-Host ""

if ($errorCount -gt 0) {
    Write-Host "Endpoints en erreur:" -ForegroundColor Red
    $results | Where-Object { $_.Status -eq "ERROR" } | ForEach-Object {
        Write-Host "  - $($_.Endpoint) (Code: $($_.Code))" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "Test terminé!" -ForegroundColor Cyan


