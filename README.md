# API Агрегатор - Laravel Backend

Проект представляет собой API агрегатор на Laravel с использованием Docker для развертывания всех необходимых сервисов.

## Требования к системе

- Docker и Docker Compose
- Git
- Минимум 2GB свободной оперативной памяти
- Свободный порт 8000 для веб-сервера
- Свободный порт 6379 для Redis (опционально)

## Структура проекта
```text
├── docker/
│   ├── nginx/
│   │   └── default.conf      # Конфигурация Nginx
│   └── php/
│       └── Dockerfile        # Dockerfile для PHP-FPM
├── src/                      # Laravel приложение
├── docker-compose.yml        # Конфигурация Docker Compose
└── README.md                # Документация проекта
```

## Установка и настройка

### 1. Клонирование репозитория

```bash
git clone https://gitlab.preax.ru/php/f99d8a8e-8e5.git
cd f99d8a8e-8e5
git checkout sprint-1-task-1
```

### 2. Запуск Docker-контейнеров

```bash
docker compose up -d --build
```

Эта команда запустит следующие сервисы:
- **app** — PHP-FPM контейнер с Laravel (PHP 8.2, pdo_pgsql, redis)
- **webserver** — Nginx веб-сервер (порт 8000)
- **db** — PostgreSQL 15 (база данных `laravel`, порт 5432 внутри сети)
- **redis** — Redis для кеширования и очередей (порт 6379)

Проверка статуса запущенных контейнеров:
```bash
docker compose ps
```

### 3. Настройка переменных окружения (.env)

Для быстрого запуска и проверки можно использовать готовый файл `.env.review`:

```bash
cp src/.env.review src/.env
```

Либо скопировать шаблон из `.env.example`:

```bash
cp src/.env.example src/.env
```

Убедитесь, что в файле `src/.env` указаны актуальные параметры подключения:

```env
APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=postgres
DB_PASSWORD=postgres

CACHE_STORE=redis
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 4. Установка зависимостей и запуск миграций

```bash
# Установка PHP-зависимостей через Composer внутри контейнера
docker compose exec app composer install

# Генерация уникального ключа приложения (если не был установлен ранее)
docker compose exec app php artisan key:generate

# Применение стандартных миграций Laravel к базе данных PostgreSQL
docker compose exec app php artisan migrate

# Настройка прав доступа к директориям storage и bootstrap/cache (при необходимости)
docker compose exec app chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
docker compose exec app chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
```

### 5. Создание файла для ревью (.env.review)

Для тестирования при ревью проекта используется файл конфигурации:

```bash
cp src/.env src/.env.review
```

## Доступ к приложению и API

После успешного запуска приложение доступно по адресам:
- **Веб-интерфейс (главная страница)**: [http://localhost:8000](http://localhost:8000)
- **API эндпоинты**: [http://localhost:8000/api](http://localhost:8000/api)

Пример проверки доступности через cURL:
```bash
curl -I http://localhost:8000
```

## Инструкции по тестированию приложения

### 1. Запуск автоматических тестов

Запуск тестов Laravel (Unit и Feature):
```bash
docker compose exec app php artisan test
```

### 2. Проверка статуса сервисов и окружения

```bash
# Проверка общей информации о конфигурации Laravel
docker compose exec app php artisan about

# Проверка статуса миграций базы данных
docker compose exec app php artisan migrate:status
```

### 3. Проверка подключения к PostgreSQL

```bash
# Просмотр созданных таблиц в базе данных laravel
docker compose exec db psql -U postgres -d laravel -c "\dt"
```

### 4. Проверка подключения к Redis

```bash
# Тест ping к Redis через artisan tinker
docker compose exec app php artisan tinker --execute="dump(Illuminate\Support\Facades\Redis::ping());"
```

## Полезные команды

```bash
# Просмотр логов контейнеров
docker compose logs -f app
docker compose logs -f webserver

# Остановка контейнеров
docker compose down

# Перезапуск контейнеров
docker compose restart

# Выполнение artisan команд внутри контейнера
docker compose exec app php artisan <command>

# Подключение к интерактивной консоли Tinker
docker compose exec app php artisan tinker
```

## Решение проблем

### Порт 8000 занят
Измените порт в `docker-compose.yml`:
```yaml
webserver:
  ports:
    - "8001:80"  # Используйте свободный порт
```

### Проблемы с правами доступа
```bash
docker compose exec app chown -R www-data:www-data /var/www/html
```

## Очистка Docker

```bash
docker compose down -v  # Удалит контейнеры и volumes
docker system prune     # Очистит неиспользуемые ресурсы
```

## Developer

- **Имя**: Артём
- **Ник**: pechkurofff
