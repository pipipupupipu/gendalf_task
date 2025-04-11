<?php

use App\Listeners\SessionCleanupListener;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Пути к директориям, содержащим сущности Doctrine.
 */
$paths = [__DIR__ . '/../src/Entities'];

/**
 * Настройка конфигурации Doctrine ORM с использованием атрибутов метаданных.
 * 
 * @param array $paths Пути к директориям сущностей.
 * @param bool $isDevMode Режим разработки (true) или продакшн (false).
 */
$config = ORMSetup::createAttributeMetadataConfiguration(
    paths: $paths,
    isDevMode: true,
);

/**
 * Параметры подключения к базе данных
 */
$connectionParams = require __DIR__ . '/db_config.php';

/**
 * Создание соединения с базой данных через DriverManager.
 */
$connection = DriverManager::getConnection($connectionParams);

/**
 * Инициализация менеджера событий Doctrine.
 */
$eventManager = new EventManager();

/**
 * Создание экземпляра EntityManager для работы с базой данных через Doctrine ORM.
 */
$entityManager = new EntityManager($connection, $config, $eventManager);

/**
 * Инициализация слушателя событий для очистки сессий.
 * Этот слушатель будет вызываться перед сохранением сущности в базу данных.
 */
$sessionCleanupListener = new SessionCleanupListener($entityManager);
$eventManager->addEventListener(\Doctrine\ORM\Events::prePersist, $sessionCleanupListener);

/**
 * Возвращает экземпляр EntityManager для использования в приложении.
 */
return $entityManager;