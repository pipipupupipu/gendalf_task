<?php

namespace App\Entities;

use App\Entities\User;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

/**
 * Класс Session представляет сессию пользователя в системе.
 * 
 * Сущность связана с таблицей 'sessions' в базе данных и содержит информацию о токене,
 * времени создания и времени истечения сессии.
 */
#[Entity]
#[Table('sessions')]
class Session
{
    /**
     * Уникальный идентификатор сессии.
     */
    #[Id]
    #[GeneratedValue]
    #[Column]
    private int $id;

    /**
     * Пользователь, которому принадлежит сессия.
     * 
     * @var User
     */
    #[ManyToOne(targetEntity: User::class, inversedBy: 'sessions')]
    #[JoinColumn('user_id', referencedColumnName: 'id', nullable: false)]
    private User $user;

    /**
     * Токен сессии.
     * 
     * @var string
     */
    #[Column(type: 'string', length: 500)]
    private string $token;

    /**
     * Время создания сессии.
     * 
     * @var \DateTimeInterface
     */
    #[Column(name: 'created_at', type: 'datetime')]
    private \DateTimeInterface $createdAt;

    /**
     * Время истечения сессии.
     * 
     * @var \DateTimeInterface
     */
    #[Column(name: 'expires_at', type: 'datetime')]
    private \DateTimeInterface $expiresAt;

    /**
     * Конструктор класса Session.
     * 
     * Инициализирует время создания сессии текущим временем.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * Возвращает уникальный идентификатор сессии.
     * 
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Возвращает пользователя, которому принадлежит сессия.
     * 
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }
    
    /**
     * Устанавливает пользователя для сессии.
     * 
     * @param User|null $user Пользователь, которому принадлежит сессия.
     */
    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    /**
     * Возвращает токен сессии.
     * 
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Устанавливает токен сессии.
     * 
     * @param string $token Токен сессии.
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * Возвращает время истечения сессии.
     * 
     * @return \DateTimeInterface
     */
    public function getExpiresAt(): \DateTimeInterface
    {
        return $this->expiresAt; 
    }

    /**
     * Устанавливает время истечения сессии.
     * 
     * @param \DateTimeInterface $expiresAt Время истечения сессии.
     */
    public function setExpiresAt(\DateTimeInterface $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    /**
     * Возвращает время создания сессии.
     * 
     * @return \DateTimeInterface
     */
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }
}