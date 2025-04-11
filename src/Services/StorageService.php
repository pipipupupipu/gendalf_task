<?php

namespace App\Services;

use RuntimeException;
use Slim\Psr7\UploadedFile;

class StorageService
{
    /**
     * Складывает базовый путь с пользовательским путем.
     *
     * @param string $basePath Базовая директория.
     * @param string $path Пользовательский путь.
     * @return string Полный путь.
     */
    public function joinPath(string $basePath, string $path): string
    {
        return rtrim($basePath, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Нормализует путь, удаляя лишние символы и разрешая относительные пути.
     *
     * @param string $path Путь для нормализации.
     * @return string Нормализованный путь.
     */
    public function normalizePath(string $path): string
    {
        $parts = explode('/', $path);
        $stack = [];
        foreach ($parts as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                array_pop($stack);
            } else {
                $stack[] = $part;
            }
        }
        return '/' . implode('/', $stack);
    }

    /**
     * Возвращает список файлов и директорий в указанной директории.
     *
     * @param string $path Путь к директории.
     * @return string Список файлов и директорий.
     * @throws RuntimeException Если директория не существует.
     */
    public function dir(string $path): string
    {
        if (!is_dir($path)) {
            throw new RuntimeException("Директория не найдена: $path", 404);
        }

        $files = array_diff(scandir($path), ['.', '..']);

        return implode(' ', $files);
    }

    /**
     * Возвращает информацию о файле.
     *
     * @param string $path Путь к файлу.
     * @return array Данные о файле (MIME-тип, размер, имя файла, содержимое).
     * @throws RuntimeException Если файл не существует.
     */
    public function getFileData(string $path): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException("Файл не найден: $path", 404);
        }

        $mimeType = mime_content_type($path) ?: 'application/octet-stream';
        $fileSize = filesize($path);
        $fileName = basename($path);

        ob_start();
        readfile($path);
        $buffer = ob_get_contents();
        ob_end_clean();

        return [
            'mime' => $mimeType,
            'size' => $fileSize,
            'filename' => $fileName,
            'content' => $buffer
        ];
    }

    /**
     * Помещает файл по указанному пути.
     *
     * @param string $path Путь, куда будет сохранен файл.
     * @param UploadedFile $file Объект загруженного файла.
     * @return bool True, если файл успешно загружен.
     * @throws RuntimeException Если произошла ошибка при перемещении файла.
     */
    public function upload(string $path, UploadedFile $file): bool
    {
        try {
            $file->moveTo($path);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Ошибка при загрузке файла: ' . $e->getMessage(), 400);
        }
        
        return true;
    }

    /**
     * Создает директорию.
     *
     * @param string $path Путь к директории.
     * @return bool True, если директория создана успешно.
     * @throws RuntimeException Если директория уже существует.
     */
    public function mkdir(string $path): bool
    {
        if (file_exists($path)) {
            throw new RuntimeException("Директория уже существует", 409);
        }

        return mkdir($path, 0777, true);
    }

    /**
     * Удаляет файл или директорию.
     *
     * @param string $path Путь к файлу или директории.
     * @return bool True, если удаление прошло успешно.
     * @throws RuntimeException Если файл или директория не существуют.
     */
    public function rm(string $path): bool
    {
        if (!file_exists($path)) {
            throw new RuntimeException("Файл или директория не найдены: $path", 404);
        }
        if (is_dir($path)) {
            return $this->deleteDirectory($path);
        }
        return unlink($path);
    }

    /**
     * Рекурсивно удаляет директорию.
     *
     * @param string $dir Путь к директории.
     * @return bool True, если директория удалена успешно.
     */
    private function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        return rmdir($dir);
    }
}