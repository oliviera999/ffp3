#!/bin/bash

# Script de test curl pour /post-data-test
# Simule exactement les requêtes envoyées par l'ESP32

echo "=========================================="
echo "TEST CURL - POST DATA TEST"
echo "Date: $(date)"
echo "=========================================="

# Configuration
BASE_URL="http://localhost/ffp3"
API_KEY="fdGTMoptd5CD2ert3"
SENSOR="esp32-wroom"
VERSION="11.35"

echo "URL de test: ${BASE_URL}/post-data-test"
echo "API Key: $API_KEY"
echo ""

# Test 1: Requête minimale
echo "1. TEST REQUÊTE MINIMALE"
echo "========================="
curl -X POST "${BASE_URL}/post-data-test" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "api_key=${API_KEY}" \
  -d "sensor=${SENSOR}" \
  -d "version=${VERSION}" \
  -w "\nHTTP Code: %{http_code}\nTime: %{time_total}s\n" \
  -v

echo ""
echo ""

# Test 2: Requête complète (simulation ESP32)
echo "2. TEST REQUÊTE COMPLÈTE (SIMULATION ESP32)"
echo "============================================="
curl -X POST "${BASE_URL}/post-data-test" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "api_key=${API_KEY}" \
  -d "sensor=${SENSOR}" \
  -d "version=${VERSION}" \
  -d "TempAir=28.0" \
  -d "Humidite=61.0" \
  -d "TempEau=28.0" \
  -d "EauPotager=209" \
  -d "EauAquarium=210" \
  -d "EauReserve=209" \
  -d "diffMaree=-2" \
  -d "Luminosite=228" \
  -d "etatPompeAqua=1" \
  -d "etatPompeTank=0" \
  -d "etatHeat=0" \
  -d "etatUV=0" \
  -d "bouffeMatin=8" \
  -d "bouffeMidi=12" \
  -d "bouffeSoir=19" \
  -d "tempsGros=2" \
  -d "tempsPetits=2" \
  -d "aqThreshold=18" \
  -d "tankThreshold=80" \
  -d "chauffageThreshold=18" \
  -d "tempsRemplissageSec=5" \
  -d "limFlood=8" \
  -d "WakeUp=0" \
  -d "FreqWakeUp=6" \
  -d "bouffePetits=1" \
  -d "bouffeGros=1" \
  -d "mail=oliv.arn.lau@gmail.com" \
  -d "mailNotif=checked" \
  -d "resetMode=0" \
  -w "\nHTTP Code: %{http_code}\nTime: %{time_total}s\n" \
  -v

echo ""
echo ""

# Test 3: Comparaison avec PROD
echo "3. TEST COMPARAISON PROD vs TEST"
echo "================================="
echo "Test PROD (/post-data):"
curl -X POST "${BASE_URL}/post-data" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "api_key=${API_KEY}" \
  -d "sensor=${SENSOR}" \
  -d "version=${VERSION}" \
  -d "TempAir=28.0" \
  -d "Humidite=61.0" \
  -d "TempEau=28.0" \
  -w "\nHTTP Code: %{http_code}\nTime: %{time_total}s\n" \
  -s

echo ""
echo "Test TEST (/post-data-test):"
curl -X POST "${BASE_URL}/post-data-test" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "api_key=${API_KEY}" \
  -d "sensor=${SENSOR}" \
  -d "version=${VERSION}" \
  -d "TempAir=28.0" \
  -d "Humidite=61.0" \
  -d "TempEau=28.0" \
  -w "\nHTTP Code: %{http_code}\nTime: %{time_total}s\n" \
  -s

echo ""
echo ""

# Test 4: Vérification des logs
echo "4. VÉRIFICATION DES LOGS"
echo "========================"
echo "Logs post-data (dernières 10 lignes):"
if [ -f "../var/logs/post-data.log" ]; then
    tail -n 10 "../var/logs/post-data.log"
else
    echo "❌ Fichier de log non trouvé: ../var/logs/post-data.log"
fi

echo ""
echo "=========================================="
echo "TESTS TERMINÉS"
echo "=========================================="
