# PHP Chatbots project
Проект по реализации чат-ботов на языке PHP для следующих web-ресурсов:
- [Youtube streming](https://www.youtube.com/)
## **Background**
____
### **Stack:**
*На проекте используются следующие технологии:*
- `PHP 8.1 (fpm)`
- `Nginx 1.21`
- `MySQL 8.0`
- `Redis 7.0`
- `Docker 20.10`
- `Docker-compose 1.29`
- `Composer 2.3`
### **Packages:**
*На проекте базово используются следующие php пакеты:*
```json
"require": {
    "guzzlehttp/guzzle": "^7.0",
    "google/apiclient": "^2.12.1",
    "google/cloud-dialogflow": "^0.25.0",
    "paquettg/php-html-parser": "^3.1"
}
```
### **Structure:**
Структура проекта представлена следующим деревом
- app
    - src
        - core >> директория пользовательских классов
        - secrets >> директория с файлами токенов
        - [const.php](./app/src/const.php) >> файл с основными константами
        - `env_const.php` >> *(нужно создать)* файл с константами настроек. [Подробнее](#env_constphp)
    - vendor
    - [composer.json](./app/composer.json)
    - [composer.lock](./app/composer.lock)
    - [youtube_auth.php](./app/youtube_auth.php) >> страница получения google oAuth token
    - [youtube.php](./app/youtube.php) >> исполняемый файл youtube бота
- docker_logs >> логи докер контейнеров
- images >> директория докер образов
- project_database >> хранилище бд
- project_logs >> логи проекта
- `.env` >> *(нужно создать)* файл с настройками путей для сборки docker. [Подробнее](#env)
- [docker-compose.yml](./docker-compose.yml) >> сборка docker
## **Install and config**
____
### **Installation**
Проект работает в docker контейнерах, перед запуском необходимо создать файл настроек `.env` в корне проекта ([подробнее](#env)). Затем произвести запуск со сборкой образов
```bash
docker-compose up --build -d
```
Зайти в докер контейнер проекта
```
docker-compose exec app bash
```
И проинициализировать composer
```
composer install
```
### **Configuration**
*Базовая конфигурация проекта включает создание файла констант по пути от корня проекта `./app/src/env_const.php` ([подробнее](#env_constphp)).*
*Далее конфигурация разбита по бот модулям:*
#### **Youtube:**
Для корректного подключения бота к серверам youtube для последующего извлечения стрим видео чата и отправления сообщений необходимо:
- Настроить [Google Cloud Platform](https://console.cloud.google.com) проект
- установить OAuth 2.0 Client IDs и скачать JSON ключ в папку `./app/src/secrets/`
- установить для редиректа страницу `./youtube_auth.php`
- авторизоваться через страницу `./youtube_auth.php` в youtube аккаунте, который будет использоваться в качестве чат бота, oAuth token для аккаунта будет сохранен в папку `./app/src/secrets/`

Так же для корректной работы модуля smart ответов необходимо настроить сервис [Dialogflow](https://dialogflow.cloud.google.com/), создать для него проект и сохранить JSON ключ так же в папку `./app/src/secrets/`

```diff
- Все бот модули требуют загруженный дамб базы данных
```
## **Base usage**
____
#### **Youtube:**
Модуль Youtube бота настроен и готов к работе из коробки, но следующие моменты можно прояснить:
```php
// Создание oAuth токена бота для подключения к youTube серверу
App\Anet\Bots\YouTube::createAuthTokken();
```
```php
// Создание объекта бота
$url = 'https://www.youtube.com/<видео id>';
$youtubeBot = new App\Anet\Bots\YouTube($url);

// Из коробки объект создается с передачей параметра командной строки
$youtubeBot = new App\Anet\Bots\YouTube($argv[1]);
```
```php
// Основной процесс проходит в методе listen() где входит в бесконечный цикл,
// прерывание может быть синициировано накоплением критичского кол-ва ошибок (по умолчанию 5)
$sec = 99;
$youtubeBot->listen($sec);
```
В проекте так же доступны методы отладки подключения
```php
// Тестовое подключение
$youtubeBot->testConnect();

// Тестовая отправка сообщения
$youtubeBot->testSend();
```
#### **Other:**
В проекте доступны статичные классы для логгирования в xml файл
```php
// сохранение массива данных (поддерживает многомерный массивы данных) в указанную категорию
// под указанный тип
$data = [
    // ...data
];
App\Anet\Helpers\Logger::logging('категория', $data, 'тип');
```
Вывод работы скрипта можно перенаправлять в специальный лог файл
```php
// логгирование происходит с указанием метки времени и указанной категории <System>
App\Anet\Helpers\Logger::print('System', 'Сообщение');
```
## **Annex**
_____
#### .env
Файл .env должен иметь следующий вид:
```
DB_PATH_HOST=./project_database
DB_PASSWORD=*пароль для БД*

APP_PATH_HOST=./app
APP_PATH_CONTAINER=/var/www/app

PROJECT_LOGS_HOST=./project_logs
PROJECT_LOGS_CONTAINER=/var/www/project_logs

PHP_INI_HOST=./images/php-fpm/php.ini
PHP_INI_CONTAINER=/usr/local/etc/php/conf.d/php.ini

NGINX_SETTINGS_HOST=./images/nginx/default.conf
NGINX_SETTINGS_CONTAINER=/etc/nginx/conf.d/default.conf

NGINX_LOGS_HOST=./docker_logs/nginx/
NGINX_LOGS_CONTAINER=/var/log/nginx/
```
#### env_const.php
Файл env_const.php должен иметь следующий вид:
```php
define('APP_NAME', ''); // Имя проекта Google Cloud Platform
define('APP_EMAIL', ''); // почта юзер бота
define('APP_USER_NAME', ''); // имя юзер бота
define('SMALL_TALK_ID', ''); // Имя проекта Dialogflow
define('USER_LISTEN_LIST', []); // список пользователей на постоянную прослушку
define('DB_USER_NAME', ''); // Имя пользователя БД
define('DB_PASSWORD', ''); // пароль БД
define('REDIS_PASS', ''); // пароль redis
```
