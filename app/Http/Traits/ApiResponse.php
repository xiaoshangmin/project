<?php
declare(strict_types=1);
namespace App\Http\Traits;

use App\Constants\ErrorCode;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

trait ApiResponse
{
    /**
     * @param array|string $data
     * @param int $code
     * @return PsrResponseInterface
     */
    public function success(mixed $data = [], int $code = ErrorCode::SUCCESS): PsrResponseInterface
    {
        $response = \Hyperf\Support\make(ResponseInterface::class);
        $body = [
            'code' => $code,
            'message' => ErrorCode::getMessage($code),
            'data' => $data,
        ];
        return $response->json($body);
    }

    /**
     * @param int $code
     * @param array|string|int $data
     * @return PsrResponseInterface
     */
    public function fail(int $code = ErrorCode::FAIL, mixed $data = []): PsrResponseInterface
    {
        $response = \Hyperf\Support\make(ResponseInterface::class);
        $body = [
            'code' => $code,
            'message' => ErrorCode::getMessage($code),
            'data' => $data,
        ];
        return $response->json($body);
    }
}