# Currency & Metals Hub API

An API-First microservice based on Laravel and Docker for extracting, storing, and serving financial data in real-time. It provides unified information from two main sources:

1.  **Exchange Rate (Peru)**: Parallel Dollar and Sunat (Source: *cuantoestaeldolar.pe*).
2.  **Precious Metals (Global)**: Gold, Silver, Platinum, Palladium, and Rhodium (Source: *Kitco*).

Designed to be secure, scalable, and easy to deploy.

## Features

-   **Secure & Restful API**: Endpoints protected with Laravel Sanctum. Standardized JSON responses.
-   **Security by Default**: The root route `/` returns 404 in production. Documentation access restricted locally.
-   **Robust Web Scraping**: Implemented with [Roach PHP](https://roach-php.dev/), capable of extracting dynamic data (Next.js) from multiple sources simultaneously.
-   **Metals Logistics**: Automatic unit conversion (Ounces/Grams) and purity factor calculation (18K, 925, etc.).
-   **Background Jobs with Redis**: Asynchronous scraping managed by queues for high availability.
-   **Automated Testing**: Complete test suite (Feature & Unit) with Pest/PHPUnit.
-   **Dockerized Infrastructure**: Full stack with Nginx, PHP-FPM 8.2+, MySQL 8, and Redis.

---

## 🚀 Installation and Deployment

### Prerequisites

-   Docker and Docker Compose
-   Git

### Deployment Steps (Local or Production)

1.  **Clone the repository**:
    ```bash
    git clone <repo-url>
    cd currency-hub
    ```

2.  **Configure environment variables**:
    ```bash
    cp .env.example .env
    # Production: Change APP_ENV=production, APP_DEBUG=false
    # Adjust DB/Redis credentials.
    # Important: If your passwords have '$', use '$$' to escape in docker-compose.
    ```

3.  **Start services**:
    ```bash
    docker compose up -d --build
    ```
    > **Note:** Database migrations run **automatically** when the container starts.

4.  **Generate Access Token (Production/Dev)**:
    To consume the API, you need to generate a token for your client.
    ```bash
    docker compose exec currency-hub-php php artisan api:create-token "Client Name" "email@client.com"
    ```
    *This command will create the user (if it doesn't exist) and display the token on screen. Save it in a safe place.*

---

## 📚 API Documentation

The API is protected by **Laravel Sanctum**. All requests must include the header:
`Authorization: Bearer <your-token>`

### 1. Exchange Rates (Soles/Dollars)

Get the current or historical exchange rate (Source: *cuantoestaeldolar.pe*).

**Endpoint:** `GET /api/exchange-rate`

| Parameter | Type | Optional | Description |
| :--- | :--- | :--- | :--- |
| `type` | `string` | Yes | `parallel` (default) or `sunat`. |
| `date` | `date` | Yes | Format `YYYY-MM-DD`. Default: Today. |

**Response Example:**
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

### 2. Precious Metals (Kitco)

APIs to get prices for Gold, Silver, Platinum, Palladium, and Rhodium. Supports unit conversion, purity calculation (karats), and historical search.

#### A. General Listing (Dashboard)
Returns the *latest* recorded price for all supported metals.

**Endpoint:** `GET /api/precious-metals`

**Response Example:**
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

#### B. Metal Detail (Filters and Conversions)
Get the price of a specific metal with advanced conversion options.

**Endpoint:** `GET /api/precious-metals/{metal}`

| Parameter (Path) | Values |
| :--- | :--- |
| `metal` | `GOLD`, `SILVER`, `PLATINUM`, `PALLADIUM`, `RHODIUM` |

| Parameter (Query) | Description | Example |
| :--- | :--- | :--- |
| `unit` | Weight unit. `OZ` (default) or `GRAM`. | `?unit=GRAM` |
| `purity` | Purity factor. See table below. | `?purity=18K` |
| `date` | Historical Date (`YYYY-MM-DD`). | `?date=2026-01-08` |
| `time` | Hour (`HH`) or Exact Time (`HH:mm`). | `?time=14:30` |

**Supported Purities Table:**
- **Gold:** `24K` (1.0), `22K` (0.916), `18K` (0.750), `14K` (0.583), `10K` (0.417).
- **Silver:** `999` (0.999), `STERLING` or `925` (0.925), `COIN` (0.900).
- **Platinum/Palladium:** `950`, `900`, `850`.

**Usage Examples:**

**1. Price of 18K Gold in Grams:**
`GET /api/precious-metals/GOLD?unit=GRAM&purity=18K`
```json
{
    "data": {
        "metal": "GOLD",
        "purity": "18K",
        "unit": "GRAM",
        "price": 64.50, // (Ounce Price * 0.750) / 31.1035
        ...
    }
}
```

**2. Historical Silver Price (Specific Time):**
`GET /api/precious-metals/SILVER?date=2025-12-25&time=10:00`
---

## 🕷️ Manual and Scheduled Scraping

Scraping runs automatically **every day at 08:00 AM** (configured in `routes/console.php` and executed by the `scheduler` container).

To force a manual execution:

```bash
# Run the Job immediately (via Queue)
docker compose exec currency-hub-php php artisan tinker --execute="App\Jobs\ScrapeCurrencyJob::dispatch();"
```

---

## ✅ Testing

To run the automated test suite:

```bash
docker compose exec currency-hub-php php artisan test
```

This will validate:
-   Correct API responses (200, 401, 404).
-   Search filters.
-   Correct database insertion.
-   Spider execution.

---

## 🛠️ Useful Commands

| Action | Docker Command |
| :--- | :--- |
| **Generate API Token** | `docker compose exec currency-hub-php php artisan api:create-token <Name> <Email>` |
| **View Worker Logs** | `docker logs -f currency-hub-worker` |
| **Restart Queues** | `docker compose exec currency-hub-php php artisan queue:restart` |
| **Clear Cache** | `docker compose exec currency-hub-php php artisan optimize:clear` |
