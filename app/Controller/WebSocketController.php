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

    public function onClose($server, int $fd, int $reactorId): void
    {
        var_dump('closed');
    }

    public function onMessage($server, Frame $frame): void
    {
        $server->push($frame->fd, "Recv:{$frame->data}");
    }

    public function onOpen($server, Request $request): void
    {
        $cache = $this->container->get(Redis::class);
        $cache->set('websocket_1', $request->fd);
        $cache->expire('websocket_1', 7200);
        $server->push($request->fd, 'Opened');
    }
}