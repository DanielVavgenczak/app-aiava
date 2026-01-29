#!/bin/bash
cd docker
docker compose -f docker-compose.yml -f docker-compose.local.yml up -d --build
echo "✅ Ambiente local pronto! http://localhost:8080"