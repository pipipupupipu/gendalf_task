<?php

namespace App\Controllers;

use App\Entities\Session;
use App\Entities\User;
use App\Services\ResponseService;
use App\Services\TokenService;
use Doctrine\ORM\EntityManager;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

/**
 * Контроллер для аутентификации пользователей.
 */
class AuthController
{
    /**
     * @var EntityManager Менеджер сущностей Doctrine.
     */
    private EntityManager $entityManager;

    /**
     * Сервис для формирования ответов.
     *
     * @var ResponseService
     */
    private ResponseService $responseService;

    /**
     * Сервис для работы с токенами.
     *
     * @var TokenService
     */
    private TokenService $tokenService;

    /**
     * Время жизни токена в секундах.
     *
     * @var int
     */
    private int $tokenLifetimeSeconds;

    /**
     * Конструктор контроллера.
     *
     * @param EntityManager $entityManager Менеджер сущностей Doctrine.
     * @param string $jwtSecret Секретный ключ для JWT.
     * @param int $tokenLifetimeSeconds Время жизни токена в секундах.
     */
    public function __construct(EntityManager $entityManager, string $jwtSecret, int $tokenLifetimeSeconds)
    {
        $this->entityManager = $entityManager;
        $this->responseService = new ResponseService();
        $this->tokenService = new TokenService($jwtSecret);
        $this->tokenLifetimeSeconds = $tokenLifetimeSeconds;
    }

    /**
     * Обрабатывает запрос на авторизацию пользователя.
     *
     * @param Request $request PSR-7 объект запроса.
     * @param Response $response PSR-7 объект ответа.
     * @return Response PSR-7 объект ответа с результатом авторизации.
     */
    public function login(Request $request, Response $response): Response
    {   
        try {
            $authData = $this->extractDataFromRequest($request);
            $user = $this->authorize($authData);
        } catch (\RuntimeException $e) {
            return $this->responseService->createErrorResponse($e->getMessage(), $e->getCode());
        }
        
        $session = $this->createSession(['userId' => $user->getId()]);
        $user->addSession($session);

        $this->entityManager->persist($session);
        $this->entityManager->flush();

        $responseData = [
            'token' => $session->getToken(),
            'expires_at' => $session->getExpiresAt()->format('Y-m-d H:i:s'),
        ];

        return $this->responseService->createSuccessResponse($responseData, 200);
    }

    /**
     * Извлекает данные из тела запроса.
     *
     * @param Request $request PSR-7 объект запроса.
     * @return array Данные запроса (логин и пароль).
     * @throws \RuntimeException Если логин или пароль отсутствуют.
     */
    private function extractDataFromRequest(Request $request): array
    {
        $data = $request->getParsedBody();

        $login = $data['login'] ?? '';
        $password = $data['password'] ?? '';
        
        if (empty($login) || empty($password)) {
            throw new \RuntimeException('Логин и пароль обязательны', 400);
        }

        return $data;
    }

    /**
     * Авторизует пользователя по данным запроса.
     *
     * @param array $authData Данные запроса (логин и пароль).
     * @return User Авторизованный пользователь.
     * @throws \RuntimeException Если пользователь не найден или пароль неверный.
     */
    private function authorize(array $authData): User
    {
        $login = $authData['login'];
        $password = $authData['password'];

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['login' => $login]);
        
        if (!$user || !$user->verifyPassword($password)) {
            throw new \RuntimeException('Неправильный логин или пароль', 401);
        }

        return $user;
    }

    /**
     * Создает новую сессию для пользователя.
     *
     * @param array $payload Данные для токена (например, user_id).
     * @return Session Новая сессия.
     */
    private function createSession(array $payload): Session
    {
        $session = new Session();
        $session->setToken(
            $this->tokenService->generateToken($payload, $this->tokenLifetimeSeconds)
        );
        $session->setExpiresAt(new \DateTime("+" . $this->tokenLifetimeSeconds . " seconds"));

        return $session;
    }
}