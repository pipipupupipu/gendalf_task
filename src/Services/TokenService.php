<?php 

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use stdClass;

/**
 * Сервис для работы с JWT-токенами.
 *
 * Этот класс предоставляет методы для генерации и валидации JWT-токенов.
 */
class TokenService
{
    /**
     * Секретный ключ для подписи токенов.
     *
     * @var string
     */
    private string $secretKey;

    /**
     * Конструктор класса TokenService.
     *
     * @param string $secretKey Секретный ключ, используемый для подписи токенов.
     */
    public function __construct(string $secretKey)
    {
        $this->secretKey = $secretKey;
    }

    /**
     * Генерация JWT-токена.
     *
     * @param array $payload Данные, которые будут закодированы в токене (например, ['user_id' => 1]).
     * @param int $expirationTime Время жизни токена в секундах.
     * @return string Сгенерированный JWT-токен.
     */
    public function generateToken(array $payload, int $expirationTime): string
    {
        $issuedAt = time();
        $expire = $issuedAt + $expirationTime; 

        $data = [
            'iat' => $issuedAt,
            'exp' => $expire, 
            'data' => $payload
        ];

        return JWT::encode($data, $this->secretKey, 'HS256');
    }

    /**
     * Получение данных JWT-токена.
     *
     * @param string $token Токен для расшифровки.
     * @return stdClass Расшифрованные данные токена.
     * @throws \RuntimeException Если токен недействителен или просрочен.
     */
    public function decodeToken(string $token): stdClass
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            return $decoded;
        } catch (\Exception $e) {
            throw new \RuntimeException('Некорректный токен: ' . $e->getMessage(), 400);
        }
    }
}

?>