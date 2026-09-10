# API Агрегатор - Laravel Backend

Проект представляет собой API агрегатор на Laravel с использованием Docker для развертывания всех необходимых сервисов.

## Требования к системе

- Docker Desktop (или Docker Engine и Docker Compose)
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
git checkout sprint-1-task-2
```

### 2. Запуск Docker-контейнеров

> **Важно:** Перед выполнением команд убедитесь, что приложение Docker Desktop запущено и служба Docker активна.

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

# Применение миграций Laravel к базе данных PostgreSQL
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

## Доступ к приложению и API эндпоинты

После успешного запуска доступны следующие маршруты:
- **Веб-интерфейс**: [http://localhost:8000](http://localhost:8000)
- **Статус API**: `GET http://localhost:8000/api/status`
- **Регистрация пользователя**: `POST http://localhost:8000/api/auth/register`
- **Получение токена**: `POST http://localhost:8000/api/auth/token`

## Тестирование

### 1. Проверка работоспособности системы

```bash
# Проверка версии Laravel
docker compose exec app php artisan --version

# Проверка доступности главной страницы
curl http://localhost:8000

# Проверка эндпоинта статуса API
curl http://localhost:8000/api/status
```

### 2. Тестирование аутентификации через API

```bash
# Регистрация нового пользователя
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"name":"Alex","email":"alex@example.com","password":"password123"}'

# Получение нового API токена (аутентификация)
curl -X POST http://localhost:8000/api/auth/token \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"alex@example.com","password":"password123"}'
```

### 3. Автоматические тесты

```bash
docker compose exec app php artisan test
```

## Полезные команды

```bash
# Просмотр логов
docker compose logs -f app

# Остановка контейнеров
docker compose down

# Перезапуск контейнеров
docker compose restart

# Выполнение artisan команд внутри контейнера
docker compose exec app php artisan <command>

# Подключение к базе данных
docker compose exec db psql -U postgres -d laravel
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
- **Статус**: Спринт 1, Задача 2 выполнена
