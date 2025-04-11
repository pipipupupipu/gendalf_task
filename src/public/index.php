<?php

/**
 * Базовый путь к проекту.
 * Используется для загрузки автозагрузчика Composer и конфигурационных файлов.
 */
$basePath = __DIR__ . '/../../';

/**
 * Подключение автозагрузчика Composer.
 * Автозагрузчик необходим для автоматической загрузки классов, используемых в приложении.
 */
require_once $basePath . 'vendor/autoload.php';

use App\Controllers\AuthController;
use App\Controllers\StorageController;
use App\Middlewares\AuthMiddleware;
use App\Services\ResponseService;
use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Routing\RouteCollectorProxy;
use Dotenv\Dotenv;

/**
 * Загрузка переменных окружения из файла .env.
 * Переменные окружения используются для настройки приложения, например, секретного ключа JWT.
 */
$dotenv = Dotenv::createImmutable($basePath);
$dotenv->load();

/**
 * Установка временной зоны по умолчанию.
 * Временная зона используется для корректной работы с датами и временем в приложении.
 */
date_default_timezone_set('Europe/Moscow');

/**
 * Создание контейнера зависимостей.
 * Контейнер используется для управления зависимостями и предоставления сервисов.
 */
$container = new Container();

/**
 * Регистрация сервиса EntityManager в контейнере зависимостей.
 * EntityManager используется для взаимодействия с базой данных через Doctrine ORM.
 */
$container->set('entityManager', fn() => require $basePath . 'config/doctrine.php');

/**
 * Регистрация секретного ключа JWT в контейнере зависимостей.
 * Этот ключ используется для подписи и проверки токенов JWT.
 */
$container->set('jwtSecret', $_ENV['JWT_SECRET']);

/**
 * Регистрация времени жизни токена JWT в контейнере зависимостей.
 * Это значение определяет, как долго токен будет действителен.
 */
$container->set('tokenLifetimeSeconds', $_ENV['TOKEN_LIFETIME_SECONDS']);

/**
 * Регистрация сервиса ResponseService в контейнере зависимостей.
 * ResponseService используется для формирования HTTP-ответов.
 */
$container->set(ResponseService::class, function () {
    return new ResponseService();
});

/**
 * Установка контейнера зависимостей для фабрики приложения Slim.
 */
AppFactory::setContainer($container);

/**
 * Создание экземпляра приложения Slim.
 * Slim — это микрофреймворк для создания веб-приложений.
 */
$app = AppFactory::create();

/**
 * Добавление middleware для парсинга тела запроса.
 * Middleware позволяет автоматически обрабатывать JSON и другие форматы данных в теле запроса.
 */
$app->addBodyParsingMiddleware();

/**
 * Настройка обработки ошибок.
 * Middleware для обработки ошибок позволяет настраивать поведение при возникновении исключений.
 */
$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorMiddleware->setErrorHandler(
    \Slim\Exception\HttpNotFoundException::class,
    function ($request, $exception, $displayErrorDetails) {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode(['error' => 'Not Found']));
        return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }
);

/**
 * Маршрут для аутентификации пользователя.
 * Обрабатывает POST-запросы на endpoint `/login` и выполняет вход пользователя.
 */
$app->post('/login', function ($request, $response) {
    $authController = new AuthController(
        $this->get('entityManager'),
        $this->get('jwtSecret'),
        $this->get('tokenLifetimeSeconds')
    );

    return $authController->login($request, $response);
});

/**
 * Группа маршрутов для работы с хранилищем.
 * Все маршруты в этой группе защищены middleware для проверки авторизации.
 */
$app->group('/storage', function (RouteCollectorProxy $group) {
    /**
     * Маршрут для получения содержимого пути.
     * Обрабатывает GET-запросы на endpoint `/storage/{path}`.
     */
    $group->get('/[{path:.+}]', [StorageController::class, 'getPathContent']);

    /**
     * Маршрут для создания или обновления содержимого пути.
     * Обрабатывает POST-запросы на endpoint `/storage/{path}`.
     */
    $group->post('/[{path:.+}]', [StorageController::class, 'setPathContent']);

    /**
     * Маршрут для удаления содержимого пути.
     * Обрабатывает DELETE-запросы на endpoint `/storage/{path}`.
     */
    $group->delete('/[{path:.+}]', [StorageController::class, 'deletePathContent']);
})->add(
        new AuthMiddleware(
            $app->getContainer()->get('jwtSecret'),
            $app->getContainer()->get('entityManager')
        ),
    );

/**
 * Запуск приложения Slim.
 */
$app->run();