<?php

use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;

/**
 * Подключение конфигурационного файла Doctrine.
 * Этот файл содержит настройки и возвращает экземпляр EntityManager,
 * который используется для работы с базой данных через Doctrine ORM.
 */
require __DIR__ . '/../config/doctrine.php';

/**
 * Инициализация и запуск консольного приложения Doctrine.
 * 
 * ConsoleRunner предоставляет интерфейс командной строки для работы с Doctrine ORM.
 * В данном случае используется SingleManagerProvider, который предоставляет один
 * экземпляр EntityManager для всех команд Doctrine.
 * 
 * @param SingleManagerProvider $entityManagerProvider Провайдер EntityManager,
 *        обеспечивающий доступ к единственному экземпляру EntityManager.
 */
ConsoleRunner::run(
    new SingleManagerProvider($entityManager)
);