<?php
declare(strict_types=1);

namespace App\Controller;

use Hyperf\Contract\OnCloseInterface;
use Hyperf\Contract\OnMessageInterface;
use Hyperf\Contract\OnOpenInterface;
use Hyperf\Redis\Redis;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;

class WebSocketController extends AbstractController implements OnMessageInterface, OnOpenInterface, OnCloseInterface
{

    protected $cache;


    public function __construct()
    {
        $this->cache = $this->container->get(Redis::class);
    }

    public function onClose($server, int $fd, int $reactorId): void
    {
//        var_dump('closed' . $reactorId);
    }

    public function onMessage($server, Frame $frame): void
    {
        $data = json_encode(["Recv:{$frame->data}"]);
        $server->push($frame->fd, $data);
    }

    public function onOpen($server, Request $request): void
    {
        $this->cache->set($request->get['key'], $request->fd);
        $this->cache->expire($request->get['key'], 7200);
        $data = json_encode(["opened"]);
        $server->push($request->fd, $data);
    }
}