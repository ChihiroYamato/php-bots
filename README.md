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
        "paquettg/php-html-parser": "^3.1",
        "symfony/console": "^6.1"
    },
```
### **Structure:**
Структура проекта представлена следующим деревом
- app
    - console >> директория cli скриптов
        - [application.php](./app/console/application.php) >> точка входа в приложение с cli
    - public >> корневая директория сервера Nginx
        - [index.php](./app/public/index.php) >> точка входа в приложение с браузера
    - src
        - controllers >> директория классов контроллеров проекта
        - core >> директория классов модели проекта
        - secrets >> директория с файлами токенов
        - views >> директория классов представления проекта
        - [const.php](./app/src/const.php) >> файл с основными константами
        - `env_const.php` >> *(нужно создать)* файл с константами настроек. [Подробнее](#env_constphp)
        - [env_const.example.php](./app/src/env_const.example.php) >> пример с константами
    - vendor
    - [bot](./app/bot) >> bash скрипт - альяс для вызова [исполняемого файла](./app/console/application.php)
    - [composer.json](./app/composer.json)
    - [composer.lock](./app/composer.lock)
- docker_logs >> логи докер контейнеров
- images >> директория докер образов
- project_database >> хранилище бд
- project_logs >> логи проекта
- `.env` >> *(нужно создать)* файл с настройками путей для сборки docker [Подробнее](#env)
- [.env.example](./.env.example) >> пример настроек для сборки docker
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
- установить OAuth 2.0 Client IDs и скачать JSON ключ в папку `./app/src/secrets/youtube/`
- установить для редиректа страницу `/youtube_auth`
- авторизоваться через страницу `/youtube_auth` в youtube аккаунте, который будет использоваться в качестве чат бота, oAuth token для аккаунта будет сохранен в папку `./app/src/secrets/youtube/`
```diff
- Из-за ограничений по квоте на ежесуточное количество запросов по Google API, настроен запасной Google Cloud Platform проект с секретными ключами и токенами бот пользователя по пути `./app/src/secrets/youtube_reserve/` (подробнее в разделе Youtube)
```

Так же для корректной работы модуля smart ответов необходимо настроить сервис [Dialogflow](https://dialogflow.cloud.google.com/), создать для него проект и сохранить JSON ключ так же в папку `./app/src/secrets/dialogflow/`

```diff
- Все бот модули требуют загруженный дамб базы данных
```
## **Base usage**
____
#### **Youtube:**
Модуль Youtube бота настроен и готов к работе из коробки, но следующие моменты можно прояснить:
```php
// Параметры подключения задаются в специальном классе, в который передается название Google Cloud
// Platform проекта, путь к секретному ключу и токену бот пользователя
$connect = new Anet\App\YouTubeHelpers\ConnectParams('name', './client_secret', './oAuth_token')

// Создание oAuth токена бота для подключения к youTube серверу, принимает класс с
// параметрами подключения
Anet\App\Bots\YouTube::createAuthTokken(ConnectParams $connect);
```
```php
// Создание объекта бота
$youtubeBot = new Anet\App\Bots\YouTube(ConnectParams $connect, 'https://youtube.com/watch?v=<id>');

// Из коробки объект создается с передачей параметра командной строки через пакет symfony/console
$youtubeBot = new Anet\App\Bots\YouTube(ConnectParams $connect, $input->getArgument('params'));
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
```diff
!Из-за ограничения по квоте на ежесуточное количество запросов по Google API - в проекте настроено резевное подключение. после обработки из логов ошибки <The request cannot be completed because you have exceeded> youtube бот перезапускается с резервными параметрами
```
#### **Other:**
В проекте доступны статичные классы для логгирования в xml файл
```php
// сохранение массива данных (поддерживает многомерный массивы данных) в указанную категорию
// под указанный тип
$data = [
    // ...data
];
Anet\App\Helpers\Logger::logging('категория', $data, 'тип');
```
Вывод работы скрипта можно перенаправлять в специальный лог файл
```php
// логгирование происходит с указанием метки времени и указанной категории <System>
Anet\App\Helpers\Logger::print('System', 'Сообщение');
```
Так же разработан раздел мини игр для чата в расширяемых классах
```php
class NewGame extends \Anet\App\Games\Game {
    // Сперва необходимо задать базовые параметры игры в константах
    public const NAME = 'game name';
    public const COMMAND_HELP = 'command to call help';
    public const COMMAND_START = 'command to start game';
    protected const GAME_INIT_MESSAGE = 'commant to set init message';
    // для корректной работы достаточно реализации следующих методов
    public function getInitMessage() : string {
        // вывести сообщение при старте игры
    }
    public function step(string $answer) : array {
        // обработка хода по переданному сообщению
        /* массив должен иметь вид [
            'message' => 'ответное сообщение',
            'end' => <флаг конца игры, false для многоходовой игры>
        ]*/
    }
    protected function defeat(string $defeatMessage) : array {
        // обработка поражения игрока, должен вернуть массив вида выше с флагом 'end' => true
    }
    protected function victory(string $victoryMessage) : array {
        // обработка gj,tls игрока, должен вернуть массив вида выше с флагом 'end' => true
    }
}
// Далее прописать вызов игры через фабрику игр \Anet\App\Games
$games = new \Anet\App\Games()
$games->validateAndStarting(
    new Games\Towns(Anet\App\User\UserInterface $user),
    Anet\App\User\UserInterface $user, // экземпляр пользователя
    120, // таймаут для следующей игры
    55 // минимальный рейтинг для игры
);
```
## **Annex**
_____
#### .env
Файл .env должен иметь следующий вид:

[Пример в .env.example](./.env.example)
```
DB_PATH_HOST=./project_database
DB_PASSWORD=<pass>

APP_PATH_HOST=./app
APP_PATH_CONTAINER=/var/www/app

PROJECT_LOGS_HOST=./project_logs
PROJECT_LOGS_CONTAINER=/var/www/project_logs

PHP_INI_HOST=./images/php-fpm/php.ini
PHP_INI_CONTAINER=/usr/local/etc/php/conf.d/php.ini

NGINX_SETTINGS_HOST=./images/nginx/default.conf
NGINX_SETTINGS_CONTAINER=/etc/nginx/conf.d/default.conf

NGINX_PASS_HOST=./images/nginx/.htpasswd
NGINX_PASS_CONTAINER=/etc/nginx/.htpasswd

NGINX_LOGS_HOST=./docker_logs/nginx/
NGINX_LOGS_CONTAINER=/var/log/nginx/

REDIS_PASS=<pass>
```
#### env_const.php
Файл env_const.php должен иметь следующий вид:

[Пример в env_const.example.php](./app/src/env_const.example.php)
```php
define('YOUTUBE_APP_NAME', ''); // Имя проекта Google Cloud Platform
define('YOUTUBE_APP_EMAIL', ''); // почта юзер бота
define('YOUTUBE_APP_USER_NAME', ''); // имя юзер бота
define('SMALL_TALK_ID', ''); // Имя проекта Dialogflow
define('USER_LISTEN_LIST', []); // список пользователей на постоянную прослушку
define('DB_USER_NAME', ''); // Имя пользователя БД
define('DB_PASSWORD', ''); // пароль БД
define('REDIS_PASS', ''); // пароль redis

define('YOUTUBE_RESERVE', true); // Если необходимо установить резевный проект подключения к Google Cloud Platform
define('YOUTUBE_APP_NAME_RESERVE', ''); // резервное имя проекта
define('YOUTUBE_SECRETS_RESERVE', ''); // директория ключей (по аналогии с YOUTUBE_SECRETS)
define('YOUTUBE_CLIENT_SECRET_JSON_RESERVE', ''); // расположение секретного ключа (по аналогии с YOUTUBE_CLIENT_SECRET_JSON )
define('YOUTUBE_OAUTH_TOKEN_JSON_RESERVE', ''); // расположение токена пользователя (по аналогии с YOUTUBE_OAUTH_TOKEN_JSON)
```
#### Nginx
По умолчанию веб сервер закрыт паролем (файл по пути ./images/nginx/.htpasswd) файл пробрасывается в контейнер при запуске `docker-compose up`, по желанию файл можно не создавать.
