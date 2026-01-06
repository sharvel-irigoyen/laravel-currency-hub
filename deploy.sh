#!/bin/bash

# Detener el script si hay algún error
set -e

echo "🚀 Iniciando despliegue profesional para Currency Hub..."

# 1. Actualizar código fuente
echo "⬇️  Bajando últimos cambios de Git..."
git pull origin master

# 2. Construir la imagen nueva (necesario si cambió el Dockerfile)
echo "🏗️  Construyendo imagen de producción..."
docker compose build currency-hub-php

# 3. SEGURIDAD: Ajustar permisos en el HOST
echo "🛡️  Blindando seguridad y permisos..."

# A. Proteger .env (Dueño: Tú / Grupo: www-data(33) / Permisos: 640)
if [ -f .env ]; then
    sudo chown :33 .env
    sudo chmod 640 .env
    echo "  ✅ Archivo .env asegurado."
else
    echo "  ⚠️  No se encontró .env (¿Es la primera vez?)"
fi

# B. Entregar carpetas de escritura al usuario 33 (www-data)
# Limpiamos node_modules para evitar errores de permisos
rm -rf node_modules
mkdir -p vendor node_modules app/Policies
sudo chown -R 33:33 storage bootstrap/cache vendor public node_modules
echo "  ✅ Permisos de escritura asignados a www-data (ID 33)."

# 4. Levantar Servicios
echo "🚀 Levantando contenedores..."
# --remove-orphans limpia contenedores viejos si cambiaste nombres
docker compose up -d --remove-orphans

echo "⏳ Esperando a que el contenedor instale dependencias (composer)..."
docker exec currency-hub-php sh -c 'while [ ! -f vendor/autoload.php ]; do sleep 2; echo "  ...esperando vendor"; done'
echo "✅ Dependencias listas."

# 5. Mantenimiento (Opcional pero recomendado)
echo "🧹 Ejecutando limpieza de caché y optimización..."
docker exec currency-hub-php php artisan optimize:clear
docker exec currency-hub-php php artisan config:cache
docker exec currency-hub-php php artisan route:cache
docker exec currency-hub-php php artisan view:cache

echo "✅ ¡DESPLIEGUE FINALIZADO CON ÉXITO! 🎉"
