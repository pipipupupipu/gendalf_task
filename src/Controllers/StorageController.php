<?php

namespace App\Controllers;

use App\Services\ResponseService;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use App\Services\StorageService;

class StorageController
{
    private string $storagePath;
    private string $basePath;

    private ResponseService $responseService;
    private StorageService $storageService;

    public function __construct()
    {
        
        $this->responseService = new ResponseService();
        $this->storageService = new StorageService();
        $this->storagePath = $this->storageService->normalizePath(__DIR__ . '/../../storage/');
    }

    /**
     * Получение содержимого по пути.
     */
    public function getPathContent(Request $request, Response $response, array $args): Response
    {
        return $this->handleRequest($request, $response, $args, function ($fullPath) use ($response) {

            if (is_dir($fullPath)) {
                $result = $this->storageService->dir($fullPath);
                return $this->responseService->createSuccessResponse(['result' => $result], 200);

            } else {
                $fileData = $this->storageService->getFileData($fullPath);

                $response = $response->withHeader('Content-Type', $fileData['mime'])
                ->withHeader('Content-Disposition', 'attachment; filename="' . $fileData['filename'] . '"')
                ->withHeader('Content-Length', $fileData['size']);

                $response->getBody()->write($fileData['content']);

                return $response;
            }
        });
    }

    /**
     * Установка содержимого по пути.
     */
    public function setPathContent(Request $request, Response $response, array $args): Response
    {
        return $this->handleRequest($request, $response, $args, function ($fullPath) use ($request) {

            $uploadedFiles = $request->getUploadedFiles();

            try {
                $this->storageService->mkdir(rtrim($fullPath, '/'));
            } catch (\RuntimeException $e) {
                if (empty($uploadedFiles)) {
                    throw $e;
                }
            }
            
            if (!empty($uploadedFiles)) {
                foreach ($uploadedFiles as $key => $uploadedFile){
                    $filename = $uploadedFile->getClientFilename();
                    $newPath = $this->storageService->joinPath($fullPath, $filename);

                    $this->storageService->upload($newPath, $uploadedFile);
                }
            }
        });
    }

    /**
     * Удаление содержимого по пути.
     */
    public function deletePathContent(Request $request, Response $response, array $args): Response
    {
        return $this->handleRequest($request, $response, $args, function ($fullPath) {
            if (strcasecmp($fullPath, $this->basePath) === 0) {
                throw new \RuntimeException('Невозможно удалить корневой каталог', 400);
            }
            $this->storageService->rm($fullPath);
        });
    }
    /**
     * Общий метод для обработки запроса.
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @param callable $callback Функция, выполняющая основную логику.
     * @return Response
     */
    private function handleRequest(Request $request, Response $response, array $args, callable $callback): Response
    {
        $this->setBasePath($request);

        try {
            $relativePath = $args['path'] ?? '';
            $fullPath = $this->getFullPath($relativePath);

            $response = $callback($fullPath) ?? $this->responseService->createSuccessResponse(['result' => 'ok'], 200);
            
            return $response;
        } catch (\RuntimeException $e) {
            $errorMessage = str_replace($this->basePath, "", $e->getMessage());
            
            return $this->responseService->createErrorResponse($errorMessage, $e->getCode());
        } catch (\TypeError $e) {
            return $this->responseService->createErrorResponse('Ошибка параметров запроса', 400);
        }
    }

    /**
     * Установка корневого каталога пользователя.
     */
    private function setBasePath(Request $request): void
    {
        $this->basePath = $this->storagePath . "/" . $request->getAttribute('userId');
    }

    /**
     * Получение полного пути с проверкой безопасности.
     *
     * @param string $relativePath Относительный путь.
     * @return string Полный путь.
     * @throws \RuntimeException Если путь небезопасен.
     */
    private function getFullPath(string $relativePath): string
    {
        $fullPath = $this->storageService->joinPath($this->basePath, $relativePath);
        $normalizedPath = $this->storageService->normalizePath($fullPath);

        if (strpos($normalizedPath, $this->basePath) !== 0) {
            throw new \RuntimeException("Доступ запрещен: попытка выйти за пределы базовой директории.", 403);
        }

        return $normalizedPath;
    }
}