<?php

namespace App\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;
use App\Entities\Session;

/**
 * Класс User представляет пользователя в системе.
 * 
 * Сущность связана с таблицей 'users' в базе данных и содержит информацию о логине,
 * пароле и связанных сессиях пользователя.
 */
#[Entity]
#[Table('users')]
class User
{
    /**
     * Уникальный идентификатор пользователя.
     */
    #[Id]
    #[Column, GeneratedValue]
    private int $id;

    /**
     * Логин пользователя.
     * 
     * @var string
     */
    #[Column(type: 'string', length: 255, unique: true)]
    private string $login;

    /**
     * Хэшированный пароль пользователя.
     * 
     * @var string
     */
    #[Column]
    private string $password;

    /**
     * Коллекция сессий, связанных с пользователем.
     * 
     * @var Collection<int, Session>
     */
    #[OneToMany(targetEntity: Session::class, mappedBy: 'user', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $sessions;

    /**
     * Конструктор класса User.
     * 
     * Инициализирует коллекцию сессий как пустую ArrayCollection.
     */
    public function __construct()
    {
        $this->sessions = new ArrayCollection();
    }

    /**
     * Возвращает уникальный идентификатор пользователя.
     * 
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Возвращает логин пользователя.
     * 
     * @return string
     */
    public function getLogin(): string
    {
        return $this->login;
    }

    /**
     * Возвращает хэшированный пароль пользователя.
     * 
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Устанавливает логин пользователя.
     * 
     * @param string $login Новый логин пользователя.
     */
    public function setLogin(string $login): void
    {
        $this->login = $login;
    }

    /**
     * Устанавливает пароль пользователя.
     * 
     * Пароль хэшируется с использованием алгоритма BCRYPT.
     * 
     * @param string $password Новый пароль пользователя.
     */
    public function setPassword(string $password): void
    {
        $this->password = password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Проверяет, соответствует ли переданный пароль хэшированному паролю пользователя.
     * 
     * @param string $password Пароль для проверки.
     * @return bool Результат проверки пароля.
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    /**
     * Возвращает коллекцию сессий, связанных с пользователем.
     * 
     * @return Collection<int, Session>
     */
    public function getSessions(): Collection
    {
        return $this->sessions;
    }

    /**
     * Добавляет новую сессию к пользователю.
     * 
     * Если сессия уже не связана с пользователем, она добавляется в коллекцию,
     * и устанавливается обратная связь с пользователем.
     * 
     * @param Session $session Сессия для добавления.
     */
    public function addSession(Session $session): void
    {
        if (!$this->sessions->contains($session)) {
            $this->sessions->add($session);
            $session->setUser($this);
        }
    }

    /**
     * Удаляет сессию из коллекции сессий пользователя.
     * 
     * Если сессия существует в коллекции, она удаляется, и обратная связь с пользователем очищается.
     * 
     * @param Session $session Сессия для удаления.
     */
    public function removeSession(Session $session): void
    {
        if ($this->sessions->contains($session)) {
            $this->sessions->removeElement($session);
            $session->setUser(null);
        }
    }
}