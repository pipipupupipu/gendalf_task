<?php

namespace App\Listeners;

use App\Entities\Session;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Слушатель для очистки просроченных сессий.
 *
 * Этот класс реагирует на событие `prePersist` и удаляет все просроченные сессии
 * из базы данных перед сохранением новой сессии.
 */
class SessionCleanupListener
{
    /**
     * Менеджер сущностей Doctrine.
     *
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * Конструктор слушателя.
     *
     * @param EntityManagerInterface $entityManager Менеджер сущностей Doctrine.
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Метод, вызываемый перед сохранением сущности.
     *
     * Если сохраняемая сущность является экземпляром `Session`,
     * удаляет все просроченные сессии из базы данных.
     *
     * @param PrePersistEventArgs $args Аргументы события prePersist.
     *
     * @return void
     */
    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Session) {
            return;
        }

        $now = new \DateTime();

        $queryBuilder = $this->entityManager->createQueryBuilder();

        $expiredSessions = $queryBuilder
            ->select('s')
            ->from(Session::class, 's')
            ->where('s.expiresAt < :now')
            ->setParameter('now', $now) 
            ->getQuery()
            ->getResult();

        foreach ($expiredSessions as $session) {
            $this->entityManager->remove($session);
        }
    }
}