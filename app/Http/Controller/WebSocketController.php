<?php

declare(strict_types=1);

namespace App\Http\Controller;

use Hyperf\Contract\OnCloseInterface;
use Hyperf\Contract\OnMessageInterface;
use Hyperf\Contract\OnOpenInterface;
use Hyperf\Redis\Redis;

class WebSocketController extends BaseController implements OnMessageInterface, OnOpenInterface, OnCloseInterface
{

    protected $cache;


    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __construct()
    {
        $this->cache = $this->container->get(Redis::class);
    }

    public function onClose($server, int $fd, int $reactorId): void
    {
//        var_dump('closed' . $reactorId);
    }

    public function onMessage($server, $frame): void
    {
        $data = json_encode(["Recv:{$frame->data}"]);
        $server->push($frame->fd, $data);
    }

    public function onOpen($server, $request): void
    {
        $this->cache->set($request->get['key'], $request->fd);
        $this->cache->expire($request->get['key'], 7200);
        $data = json_encode(["opened"]);
        $server->push($request->fd, $data);
    }
}