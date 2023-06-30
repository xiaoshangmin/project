<?php

namespace App\Http\Traits;

use App\Constants\ErrorCode;
use Hyperf\Context\Context;
use Psr\Http\Message\ResponseInterface;

trait ApiResponse
{

    /**
     * @param array $data
     * @param int $code
     * @return ResponseInterface
     */
    public function success(array $data = [], int $code = ErrorCode::SUCCESS): ResponseInterface
    {
        $response = Context::get(ResponseInterface::class);
        $body = [
            'code' => $code,
            'message' => ErrorCode::getMessage($code),
            'data' => $data,
        ];
        return $response->json($body);
    }

    /**
     * @param int $code
     * @param array $data
     * @return ResponseInterface
     */
    public function fail(int $code = ErrorCode::FAIL, array $data = []): ResponseInterface
    {
        $response = Context::get(ResponseInterface::class);
        $body = [
            'code' => $code,
            'message' => ErrorCode::getMessage($code),
            'data' => $data,
        ];
        return $response->json($body);
    }
}