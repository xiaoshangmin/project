<?php

declare(strict_types=1);

namespace App\Middleware\Auth;

use App\Constants\ErrorCode;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiniAuthMiddleware implements MiddlewareInterface
{
    protected ContainerInterface $container;

    protected RequestInterface $request;

    protected HttpResponse $response;

    public function __construct(ContainerInterface $container, HttpResponse $response, RequestInterface $request)
    {
        $this->container = $container;
        $this->response = $response;
        $this->request = $request;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {

        //不需要鉴权
//        if(strpos('qntoken', $request->getUri())){
//
//        }
        $white = ['/api/mini/temp/email/qntoken','/api/mini/temp/email/uploadFramesPic'];
        if (!in_array($this->request->getPathInfo(), $white)) {
            if (!miniSignCheck($this->request->all())) {
                return $this->response->json(
                    [
                        'code' => ErrorCode::FAIL,
                        'message' => '签名错误',
                        'data' => $this->request->all()
                    ]
                );
            }
        }
        return $handler->handle($request);
    }
}
