<?php

namespace App\Services;

use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\ResponseFactory;

class ResponseService
{
    private ResponseFactory $responseFactory;

    public function __construct()
    {
        $this->responseFactory = new ResponseFactory();
    }

    /**
     * Создает ответ об ошибке.
     *
     * @param string $message Сообщение об ошибке.
     * @param int $statusCode HTTP-статус код.
     * @return ResponseInterface Ответ с ошибкой.
     */
    public function createErrorResponse(string $message, int $statusCode): ResponseInterface
    {
        $responseData = ['error' => $message];
        $response = $this->responseFactory->createResponse($statusCode);
        $response->getBody()->write(json_encode($responseData));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Создает успешный ответ.
     *
     * @param array $data Данные для ответа.
     * @param int $statusCode HTTP-статус код.
     * @return ResponseInterface Успешный ответ.
     */
    public function createSuccessResponse(array $data, int $statusCode): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($statusCode);
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }
}