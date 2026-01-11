# Currency & Metals Hub API

Un microservicio API-First basado en Laravel y Docker para extraer, almacenar y servir datos financieros en tiempo real. Provee información unificada de dos fuentes principales:

1.  **Tipo de Cambio (Perú)**: Dólar Paralelo y Sunat (Fuente: *cuantoestaeldolar.pe*).
2.  **Metales Preciosos (Global)**: Oro, Plata, Platino, Paladio y Rodio (Fuente: *Kitco*).

Diseñado para ser seguro, escalable y fácil de desplegar.

## Características

-   **API Restful y Segura**: Endpoints protegidos con Laravel Sanctum. Respuestas estandarizadas en JSON.
-   **Seguridad por Defecto**: La ruta raíz `/` retorna 404 en producción. Documentación restringida localmente.
-   **Web Scraping Robusto**: Implementado con [Roach PHP](https://roach-php.dev/), capaz de extraer datos dinámicos (Next.js) de múltiples fuentes simultáneamente.
-   **Logística de Metales**: Cálculo automático de conversión de unidades (Onzas/Gramos) y factores de pureza (18K, 925, etc.).
-   **Background Jobs con Redis**: Scraping asíncrono gestionado por colas para alta disponibilidad.
-   **Testing Automatizado**: Suite de pruebas completa (Feature & Unit) con Pest/PHPUnit.
-   **Infraestructura Dockerizada**: Stack completo con Nginx, PHP-FPM 8.2+, MySQL 8 y Redis.

---

## 🚀 Instalación y Despliegue

### Requisitos Previos

-   Docker y Docker Compose
-   Git

### Pasos para Despliegue (Local o Producción)

1.  **Clonar el repositorio**:
    ```bash
    git clone <repo-url>
    cd currency-hub
    ```

2.  **Configurar variables de entorno**:
    ```bash
    cp .env.example .env
    # Producción: Cambiar APP_ENV=production, APP_DEBUG=false
    # Ajustar credenciales de DB/Redis.
    # Importante: Si tus contraseñas tienen '$', usa '$$' para escapar en docker-compose.
    ```

3.  **Levantar servicios**:
    ```bash
    docker compose up -d --build
    ```
    > **Nota:** Las migraciones de base de datos se ejecutan **automáticamente** al iniciar el contenedor.

4.  **Generar Token de Acceso (Producción/Dev)**:
    Para consumir la API, necesitas generar un token para tu cliente.
    ```bash
    docker compose exec currency-hub-php php artisan api:create-token "Cliente Nombre" "email@cliente.com"
    ```
    *Este comando creará el usuario (si no existe) y mostrará el token en pantalla. Guárdalo en un lugar seguro.*

---

## 📚 Documentación de API

La API está protegida por **Laravel Sanctum**. Todas las peticiones deben incluir el header:
`Authorization: Bearer <tu-token>`

### 1. Tipos de Cambio (Soles/Dólares)

Obtén la tasa de cambio actual o histórica (Fuente: *cuantoestaeldolar.pe*).

**Endpoint:** `GET /api/exchange-rate`

| Parámetro | Tipo | Opcional | Descripción |
| :--- | :--- | :--- | :--- |
| `type` | `string` | Sí | `parallel` (default) o `sunat`. |
| `date` | `date` | Sí | Formato `YYYY-MM-DD`. Default: Hoy. |

**Ejemplo de Respuesta:**
```json
{
    "data": {
        "id": 24,
        "source": "cuantoestaeldolar.pe",
        "type": "sunat",
        "buy": 3.758,
        "sell": 3.768,
        "updated_at": "2026-01-10T08:00:00.000000Z"
    }
}
```

---

### 2. Metales Preciosos (Kitco)

APIs para obtener precios de Oro, Plata, Platino, Paladio y Rodio. Soporta conversión de unidades, cálculo de pureza (quilates) y búsqueda histórica.

#### A. Listado General (Dashboard)
Retorna el *último* precio registrado para todos los metales soportados.

**Endpoint:** `GET /api/precious-metals`

**Ejemplo de Respuesta:**
```json
{
    "data": [
        {
            "metal": "GOLD",
            "unit": "OZ",
            "currency": "USD",
            "price": 2650.40,
            "bid": 2650.40,
            "ask": 2651.40,
            "change_val": 15.20,
            "change_percent": 0.57,
            "market_time": "2026-01-10 16:59:59"
        },
        { "metal": "SILVER", ... }
    ]
}
```

#### B. Detalle de Metal (Filtros y Conversiones)
Obtén el precio de un metal específico con opciones avanzadas de conversión.

**Endpoint:** `GET /api/precious-metals/{metal}`

| Parámetro (Path) | Valores |
| :--- | :--- |
| `metal` | `GOLD`, `SILVER`, `PLATINUM`, `PALLADIUM`, `RHODIUM` |

| Parámetro (Query) | Descripción | Ejemplo |
| :--- | :--- | :--- |
| `unit` | Unidad de peso. `OZ` (default) o `GRAM`. | `?unit=GRAM` |
| `purity` | Factor de pureza. Ver tabla abajo. | `?purity=18K` |
| `date` | Fecha histórica (`YYYY-MM-DD`). | `?date=2026-01-08` |
| `time` | Hora (`HH`) o Hora Exacta (`HH:mm`). | `?time=14:30` |

**Tabla de Purezas Soportadas:**
- **Oro:** `24K` (1.0), `22K` (0.916), `18K` (0.750), `14K` (0.583), `10K` (0.417).
- **Plata:** `999` (0.999), `STERLING` o `925` (0.925), `COIN` (0.900).
- **Platino/Paladio:** `950`, `900`, `850`.

**Ejemplos de Uso:**

**1. Precio del Oro de 18 Quilates en Gramos:**
`GET /api/precious-metals/GOLD?unit=GRAM&purity=18K`
```json
{
    "data": {
        "metal": "GOLD",
        "purity": "18K",
        "unit": "GRAM",
        "price": 64.50, // (Precio Onza * 0.750) / 31.1035
        ...
    }
}
```

**2. Precio Histórico de la Plata (Hora específica):**
`GET /api/precious-metals/SILVER?date=2025-12-25&time=10:00`
---

## 🕷️ Scraping Manual y Programado

El scraping se ejecuta automáticamente **todos los días a las 08:00 AM** (configurado en `routes/console.php` y ejecutado por el contenedor `scheduler`).

Para forzar una ejecución manual:

```bash
# Ejecutar el Job inmediatamente (vía Queue)
docker compose exec currency-hub-php php artisan tinker --execute="App\Jobs\ScrapeCurrencyJob::dispatch();"
```

---

## ✅ Testing

Para ejecutar la suite de pruebas automatizadas:

```bash
docker compose exec currency-hub-php php artisan test
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
| **Generar Token API** | `docker compose exec currency-hub-php php artisan api:create-token <Nombre> <Email>` |
| **Ver Logs Worker** | `docker logs -f currency-hub-worker` |
| **Reiniciar Colas** | `docker compose exec currency-hub-php php artisan queue:restart` |
| **Limpiar Caché** | `docker compose exec currency-hub-php php artisan optimize:clear` |
