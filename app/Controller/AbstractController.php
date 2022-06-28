<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Controller;

use App\Constants\ErrorCode;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Container\ContainerInterface;

abstract class AbstractController
{
    #[Inject]
    protected ContainerInterface $container;

    #[Inject]
    protected RequestInterface $request;

    #[Inject]
    protected ResponseInterface $response;

    public function success(array $data = [], int $code = ErrorCode::SUCCESS): PsrResponseInterface
    {
        $response = [
            'code' => $code,
            'message' => ErrorCode::getMessage($code),
            'data' => $data,
        ];
        return $this->response->json($response);
    }

    public function fail(int $code = ErrorCode::FAIL,array $data = []): PsrResponseInterface
    {
        $response = [
            'code' => $code,
            'message' => ErrorCode::getMessage($code),
            'data' => $data,
        ];
        return $this->response->json($response);
    }
}
