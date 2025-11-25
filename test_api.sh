#!/bin/bash

# Test API Chatbot con curl
echo "=== TEST API CHATBOT ==="
echo ""

BASE_URL="http://localhost/pruebitaaa"

# Test 1: Preguntar total de paquetes
echo "Test 1: ¿Cuántos paquetes hay?"
curl -s -X POST "$BASE_URL/admin/api_chatbot.php" \
  -d "action=chat&input=¿Cuántos paquetes hay?" \
  -H "Cookie: PHPSESSID=test" \
  -c cookie.txt -b cookie.txt | jq '.'
echo ""

# Test 2: Paquetes pendientes  
echo "Test 2: Paquetes pendientes"
curl -s -X POST "$BASE_URL/admin/api_chatbot.php" \
  -d "action=chat&input=Paquetes pendientes" \
  -b cookie.txt | jq '.'
echo ""

# Test 3: Resumen
echo "Test 3: Dame un resumen"
curl -s -X POST "$BASE_URL/admin/api_chatbot.php" \
  -d "action=chat&input=Dame un resumen" \
  -b cookie.txt | jq '.'
echo ""
