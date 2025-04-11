<?php

use App\Entities\User;

require_once __DIR__.'/../vendor/autoload.php';

$entityManager = require_once __DIR__ . '/../config/doctrine.php';

if ($argc < 3) {
    echo "Использование: php scripts/add_user.php <login> <password>\n";
    exit(1);
}

$login = $argv[1];
$password = $argv[2];

try {

    $existingUser = $entityManager->getRepository(User::class)->findOneBy(['login' => $login]);
    if ($existingUser) {
        throw new \RuntimeException("Пользователь с логином '$login' уже существует");
    }

    $user = new User;
    $user->setLogin($login);
    $user->setPassword($password);

    $entityManager->persist($user);
    $entityManager->flush();

    mkdir(__DIR__ . '/../storage/' . $user->getId(), 0777, true);

    echo "Создан новый пользователь с ID = " . $user->getId() . "\n";

} catch (\RuntimeException $e) {
    echo "Ошибка:" . $e->getMessage() . "\n";
}

?>