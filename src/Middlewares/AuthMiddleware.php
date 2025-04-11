<?php

namespace App\Middlewares;

use App\Entities\Session;
use App\Services\ResponseService;
use App\Services\TokenService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Request;

/**
 * Middleware для аутентификации пользователей через JWT токены и проверки сессий
 */
class AuthMiddleware
{
    /**
     * Сервис для работы с JWT токенами
     * 
     * @var TokenService
     */
    private TokenService $tokenService;

    /**
     * Сервис для формирования HTTP ответов
     * 
     * @var ResponseService 
     */
    private ResponseService $responseService;

    /**
     * Менеджер сущностей Doctrine ORM
     * 
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * Конструктор AuthMiddleware
     *
     * @param string $jwtSecret Секретный ключ для подписи JWT токенов
     * @param EntityManagerInterface $entityManager Менеджер сущностей Doctrine
     */
    public function __construct(string $jwtSecret, EntityManagerInterface $entityManager)
    {
        $this->tokenService = new TokenService($jwtSecret);
        $this->responseService = new ResponseService();
        $this->entityManager = $entityManager;
    }

    /**
     * Обработка входящего HTTP запроса
     *
     * @param ServerRequestInterface $request PSR-7 объект запроса
     * @param RequestHandlerInterface $handler Обработчик следующего middleware
     * @return ResponseInterface PSR-7 объект ответа
     * @throws \RuntimeException Если аутентификация не пройдена
     */
    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $token = $this->extractTokenFromRequest($request);
            $this->checkSessionExists($token);
            $tokenData = $this->tokenService->decodeToken($token);
            
            $request = $request->withAttribute('userId', $tokenData->data->userId);

            return $handler->handle($request);

        } catch (\RuntimeException $e) {
            return $this->responseService->createErrorResponse($e->getMessage(), 401);
        }
    }

    /**
     * Извлекает JWT токен из заголовков запроса
     *
     * @param Request $request PSR-7 объект запроса
     * @return string Извлеченный JWT токен
     * @throws \RuntimeException Если токен отсутствует или имеет неверный формат
     */
    private function extractTokenFromRequest(Request $request): string
    {
        $authData = $request->getHeader('auth');

        if (empty($authData)) {
            throw new \RuntimeException('Токен отсутствует в заголовках запроса');
        }

        $token = $authData[0];

        return $token;
    }

    /**
     * Проверяет существование активной сессии для токена
     *
     * @param string $token JWT токен для проверки
     * @return void
     * @throws \RuntimeException Если активная сессия не найдена
     */
    private function checkSessionExists(string $token): void
    {
        $session = $this->entityManager->getRepository(Session::class)
            ->findOneBy([
                'token' => $token,
            ]);

        if (!$session) {
            throw new \RuntimeException('Сессия не найдена', 401);
        }
    }
}