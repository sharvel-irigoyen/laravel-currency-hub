# Currency Hub Scraper

Este proyecto es un microservicio basado en Laravel y Docker para extraer el tipo de cambio paralelo (compra/venta) de [cuantoestaeldolar.pe](https://cuantoestaeldolar.pe/) y exponerlo vía API.

## Características

- **Web Scraping**: Implementado con [Roach PHP](https://roach-php.dev/).
- **API Segura**: Endpoint protegido con Laravel Sanctum.
- **Background Jobs**: Cola de trabajos con reintentos (3 veces) y notificaciones de fallo.
- **Notificaciones**: Soporte para Telegram, Slack, Email y WhatsApp (configurar drivers).
- **Infraestructura**: Dockerizado con Nginx, MySQL, Redis y PHP-FPM 8.4.

## Instalación y Despliegue

### Requisitos Previos
- Docker y Docker Compose
- Git

### Desarrollo Local

1. Clonar el repositorio:
   ```bash
   git clone <repo-url>
   cd currency-hub
   ```

2. Configurar entorno:
   ```bash
   cp .env.example .env
   # Configurar DB_*, REDIS_*, MAIL_*, TELEGRAM_*, SLACK_* en .env
   ```

3. Levantar contenedores:
   ```bash
   docker compose up -d --build
   ```

4. Instalar dependencias (automático en start, pero manual si es necesario):
   ```bash
   docker exec currency-hub-php composer install
   docker exec currency-hub-php php artisan migrate
   ```

### Uso

#### Ejecutar Scraping Manualmente
```bash
docker exec currency-hub-php php artisan roach:run App\\Spiders\\CurrencySpider
# O despachar el Job
docker exec currency-hub-php php artisan tinker --execute="App\Jobs\ScrapeCurrencyJob::dispatch();"
```

#### API Endpoint
**GET** `/api/exchange-rate`
Header: `Authorization: Bearer <token>`

Para crear un token:
```bash
docker exec currency-hub-php php artisan tinker
> $user = User::create(['name'=>'Admin', 'email'=>'admin@admin.com', 'password'=>bcrypt('password')]);
> echo $user->createToken('api')->plainTextToken;
```

## Estructura del Proyecto

- `App\Spiders\CurrencySpider`: Lógica de extracción de datos.
- `App\Jobs\ScrapeCurrencyJob`: Job en cola que ejecuta el spider y maneja reintentos/fallos.
- `App\Models\ExchangeRate`: Modelo para almacenar los tipos de cambio.
- `docker-compose.yml`: Orquestación de contenedores.

## Notificaciones

Para habilitar notificaciones reales, instalar los canales necesarios:
```bash
composer require laravel-notification-channels/telegram
composer require laravel/slack-notification-channel
```
Y configurar las credenciales en `config/services.php`.
