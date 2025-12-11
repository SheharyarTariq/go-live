#!/bin/bash

echo "======================================"
echo "Testing Login Endpoint"
echo "======================================"
echo ""

# Test 1: Successful login
echo "Test 1: Login with valid credentials"
echo "--------------------------------------"
echo "Request:"
echo '{
  "email": "test@example.com",
  "password": "password123"
}'
echo ""
echo "Response:"
curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }' | jq .

echo ""
echo ""

# Test 2: Failed login - invalid password
echo "Test 2: Login with invalid password"
echo "--------------------------------------"
echo "Request:"
echo '{
  "email": "test@example.com",
  "password": "wrongpassword"
}'
echo ""
echo "Response:"
curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "wrongpassword"
  }' | jq .

echo ""
echo ""

# Test 3: Failed login - non-existent user
echo "Test 3: Login with non-existent email"
echo "--------------------------------------"
echo "Request:"
echo '{
  "email": "nonexistent@example.com",
  "password": "password123"
}'
echo ""
echo "Response:"
curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "nonexistent@example.com",
    "password": "password123"
  }' | jq .

echo ""
echo ""

# Test 4: Using the token to access protected endpoint
echo "Test 4: Using token to access protected endpoint"
echo "--------------------------------------"
echo "First, login and extract token..."

TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }' | jq -r '.token')

echo "Token received: ${TOKEN:0:50}..."
echo ""
echo "Now accessing protected endpoint with token:"
curl -s -X GET "http://localhost:8000/api/users" \
  -H "Authorization: Bearer $TOKEN" | jq .

echo ""
echo "======================================"
echo "Tests completed!"
echo "======================================"
