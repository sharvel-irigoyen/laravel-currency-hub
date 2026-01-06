# Currency Hub Scraper API

Un microservicio API-First basado en Laravel y Docker para extraer, almacenar y servir el tipo de cambio paralelo y Sunat de Perú (fuente: cuantoestaeldolar.pe). Diseñado para ser seguro, escalable y fácil de desplegar.

## Características

-   **API Restful y Segura**: Endpoints protegidos con Laravel Sanctum. Respuestas estandarizadas en JSON.
-   **Seguridad por Defecto**: La ruta raíz `/` retorna 404 en producción para evitar exposición innecesaria. Acceso a documentación restringido a entorno local.
-   **Web Scraping Robusto**: Implementado con [Roach PHP](https://roach-php.dev/), capaz de extraer datos dinámicos (Next.js) y manejar selectores complejos.
-   **Multi-Origen**: Soporte para tipos de cambio "Paralelo" y "Sunat" con extracción diferenciada.
-   **Background Jobs con Redis**: Scraping asíncrono gestionado por colas para no bloquear la aplicación.
-   **Testing Automatizado**: Suite de pruebas completa (Feature & Unit) con Pest/PHPUnit.
-   **Infraestructura Dockerizada**: Stack completo con Nginx, PHP-FPM 8.4, MySQL 8 y Redis.

---

## 🚀 Instalación y Despliegue

### Requisitos Previos

-   Docker y Docker Compose
-   Git

### Pasos para Desarrollo Local

1.  **Clonar el repositorio**:
    ```bash
    git clone <repo-url>
    cd currency-hub
    ```

2.  **Configurar variables de entorno**:
    ```bash
    cp .env.example .env
    # Ajustar DB_PASSWORD, REDIS_HOST etc. si es necesario.
    # Por defecto funciona con la configuración de docker-compose.yml.
    ```

3.  **Levantar servicios**:
    ```bash
    docker compose up -d --build
    ```

4.  **Instalación inicial**:
    ```bash
    # Instalar dependencias de PHP
    docker exec currency-hub-php composer install

    # Ejecutar migraciones
    docker exec currency-hub-php php artisan migrate

    # Generar Key de aplicación
    docker exec currency-hub-php php artisan key:generate

    # (Opcional) Generar token de acceso para desarrollo
    docker exec currency-hub-php php artisan dev:token
    ```

---

## 📡 Uso de la API

La API está protegida por Sanctum. Todas las peticiones deben incluir el header `Authorization: Bearer <token>`.
La ruta base es `/api`.

### 1. Obtener Tipo de Cambio Reciente

Obtiene el último tipo de cambio registrado.

**Endpoint:** `GET /api/exchange-rate`

**Parámetros (Query Params):**

-   `type` (opcional): Filtra por tipo de cambio. Valores: `parallel` (por defecto) o `sunat`.
-   `date` (opcional): Filtra por una fecha específica (`YYYY-MM-DD`). Por defecto es hoy.

**Ejemplo de Petición (Paralelo):**
```bash
curl -H "Authorization: Bearer <TOKEN>" \
     -H "Accept: application/json" \
     "http://localhost:8081/api/exchange-rate"
```

**Ejemplo de Petición (Sunat):**
```bash
curl -H "Authorization: Bearer <TOKEN>" \
     -H "Accept: application/json" \
     "http://localhost:8081/api/exchange-rate?type=sunat"
```

**Respuesta Exitosa (200 OK):**
```json
{
    "data": {
        "id": 24,
        "source": "cuantoestaeldolar.pe",
        "type": "sunat",
        "type_label": "Sunat",
        "buy": 3.358,
        "sell": 3.368,
        "updated_at": "2026-01-06T02:07:21+00:00",
        "time_ago": "2 minutes ago"
    }
}
```

**Respuestas de Error:**
-   `401 Unauthorized`: Token inválido o ausente.
-   `404 Not Found`: No hay datos disponibles para la fecha/tipo solicitados.

---

## 🕷️ Scraping Manual y Programado

El scraping se ejecuta automáticamente cada hora (configurado en `routes/console.php` y ejecutado por el contenedor `scheduler`).

Para forzar una ejecución manual:

```bash
# Método 1: Ejecutar el Job inmediatamente (vía Queue)
docker exec currency-hub-php php artisan tinker --execute="App\Jobs\ScrapeCurrencyJob::dispatch();"

# Método 2: Ejecutar vía Scheduler (si es la hora o forzando test)
docker exec currency-hub-php php artisan schedule:test
```

---

## ✅ Testing

Para ejecutar la suite de pruebas automatizadas:

```bash
docker exec currency-hub-php php artisan test
```

Esto validará:
-   Respuestas correctas de la API (200, 401, 404).
-   Filtros de búsqueda.
-   Inserción correcta en base de datos.
-   Ejecución del spider.

---

## 🛠️ Comandos Útiles

| Acción | Comando Docker |
| :--- | :--- |
| **Generar Token Dev** | `docker exec currency-hub-php php artisan dev:token` |
| **Ver Logs Laravel** | `tail -f storage/logs/laravel.log` |
| **Reiniciar Colas** | `docker exec currency-hub-php php artisan queue:restart` |
| **Limpiar Caché** | `docker exec currency-hub-php php artisan optimize:clear` |
