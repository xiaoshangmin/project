<?php

namespace App\Exception\Traits;

use App\Constants\ErrorCode;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;

trait ExceptionHandlerTrait
{
    /**
     * 返回错误信息.
     *
     * @param ResponseInterface $response
     * @param int $errCode
     * @param string $message
     * @param array $payload
     * @return ResponseInterface
     */
    public function error(ResponseInterface $response, int $errCode = ErrorCode::UNKNOWN, string $message = '', array $payload = []): ResponseInterface
    {
        $body = array_merge($payload, [
            'code' => $errCode,
            'message' => !empty($message) ? $message : ErrorCode::getMessage($errCode),
        ]);

        $stream = new SwooleStream(json_encode($body));

        return $response
            ->withStatus(200)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream);
    }
}