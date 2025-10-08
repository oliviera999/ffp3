# Script de test pour poster des données vers l'environnement TEST
# Teste l'endpoint post-data-test

$baseUrl = "https://iot.olution.info/ffp3/ffp3datas"

Write-Host "=== Test d'envoi de données vers l'environnement TEST ===" -ForegroundColor Cyan
Write-Host ""

# Paramètres de test
$testData = @{
    api_key = "fdGTMoptd5CD2ert3"
    sensor = "test_sensor"
    version = "test_v1.0"
    TempAir = "22.5"
    Humidite = "65"
    TempEau = "18.3"
    EauPotager = "45"
    EauAquarium = "38"
    EauReserve = "75"
    diffMaree = "12.5"
    Luminosite = "550"
    etatPompeAqua = "1"
    etatPompeTank = "0"
    etatHeat = "0"
    etatUV = "1"
    bouffeMatin = "1"
    bouffeMidi = "0"
    bouffePetits = "0"
    bouffeGros = "1"
    aqThreshold = "35"
    tankThreshold = "70"
    chauffageThreshold = "16"
    mail = "test@example.com"
    mailNotif = "notif@example.com"
    resetMode = "0"
    bouffeSoir = "0"
}

# Test 1: Endpoint moderne /public/post-data-test
Write-Host "Test 1: POST vers /public/post-data-test" -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/public/post-data-test" -Method POST -Body $testData -UseBasicParsing -ErrorAction Stop
    if ($response.StatusCode -eq 200) {
        Write-Host "  ✓ Succès (200)" -ForegroundColor Green
        Write-Host "  Réponse: $($response.Content)" -ForegroundColor Gray
    } else {
        Write-Host "  ⚠ Code inattendu: $($response.StatusCode)" -ForegroundColor Yellow
    }
} catch {
    Write-Host "  ✗ Erreur: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.Exception.Response) {
        $statusCode = $_.Exception.Response.StatusCode.value__
        Write-Host "  Code HTTP: $statusCode" -ForegroundColor Red
    }
}

Write-Host ""

# Test 2: Endpoint legacy post-ffp3-data2.php
Write-Host "Test 2: POST vers /post-ffp3-data2.php (legacy)" -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/post-ffp3-data2.php" -Method POST -Body $testData -UseBasicParsing -ErrorAction Stop
    if ($response.StatusCode -eq 200) {
        Write-Host "  ✓ Succès (200)" -ForegroundColor Green
        Write-Host "  Réponse: $($response.Content)" -ForegroundColor Gray
    } else {
        Write-Host "  ⚠ Code inattendu: $($response.StatusCode)" -ForegroundColor Yellow
    }
} catch {
    Write-Host "  ✗ Erreur: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.Exception.Response) {
        $statusCode = $_.Exception.Response.StatusCode.value__
        Write-Host "  Code HTTP: $statusCode" -ForegroundColor Red
    }
}

Write-Host ""

# Test 3: Vérifier l'affichage des données TEST
Write-Host "Test 3: GET /public/aquaponie-test (visualisation)" -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/public/aquaponie-test" -Method GET -UseBasicParsing -ErrorAction Stop
    if ($response.StatusCode -eq 200) {
        Write-Host "  ✓ Page accessible (200)" -ForegroundColor Green
        
        # Vérifier si des données sont présentes
        if ($response.Content -match "test_sensor") {
            Write-Host "  ✓ Données TEST détectées dans la page" -ForegroundColor Green
        } else {
            Write-Host "  ⚠ Aucune donnée TEST visible dans la page" -ForegroundColor Yellow
        }
    }
} catch {
    Write-Host "  ✗ Erreur: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "=== Test terminé ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "Pour vérifier manuellement dans la base de données:" -ForegroundColor Yellow
Write-Host "  SELECT * FROM ffp3Data2 ORDER BY reading_time DESC LIMIT 10;" -ForegroundColor Gray
Write-Host ""
Write-Host "Pour visualiser les données TEST:" -ForegroundColor Yellow
Write-Host "  $baseUrl/public/aquaponie-test" -ForegroundColor Gray


