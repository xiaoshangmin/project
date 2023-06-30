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

namespace App\Exception\Handler;

use App\Constants\ErrorCode;
use App\Exception\Traits\ExceptionHandlerTrait;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class AppExceptionHandler extends ExceptionHandler
{
    use ExceptionHandlerTrait;

    protected array $expectExceptions = [
    ];

    public function __construct(protected StdoutLoggerInterface $logger)
    {
    }

    public function handle(Throwable $throwable, ResponseInterface $response): ResponseInterface
    {
//        $this->logger->error(sprintf('%s[%s] in %s', $throwable->getMessage(), $throwable->getLine(), $throwable->getFile()));
//        $this->logger->error($throwable->getTraceAsString());
        $this->logger($throwable, make(RequestInterface::class));
        // 记录错误日志
        if (!$this->shouldntReport($throwable)) {
            $this->logger($throwable, make(RequestInterface::class));
        }

        if ($throwable instanceof ValidationException) {
            return $this->error($response, ErrorCode::INVALID_PARAM, $throwable->validator->errors()->first());
        }

        $payload = \Hyperf\Config\config('app_debug', false) ?
            ['exception' => [
                'message' => $this->getMessage($throwable),
                'trace' => $throwable->getTraceAsString(),
            ],] : [];

        return $this->error($response, $this->getDefaultCode($throwable), '', $payload);
//        return $response->withHeader('Server', 'Hyperf')->withStatus(500)->withBody(new SwooleStream('Internal Server Error.'));
    }

    /**
     * @param \Throwable $throwable
     * @return int
     */
    public function getDefaultCode(Throwable $throwable): int
    {
        if (is_int($throwable->getCode()) && $throwable->getCode() != 0) {
            return $throwable->getCode();
        }

        return 500;
    }


    /**
     * 记录日志.
     * @param \Throwable $throwable
     * @param RequestInterface $request
     * @return false|void
     */
    public function logger(\Throwable $throwable, RequestInterface $request)
    {
        $this->logger->info('exception', [
            'req' => $request->all(),
            'url' => sprintf('%s %s', $request->getMethod(), $request->fullUrl()),
            'message' => $this->getMessage($throwable),
            'trace' => $throwable->getTraceAsString(),
        ]);
    }


    public function getMessage(\Throwable $throwable): string
    {
        $message = sprintf('%s[%s] in %s', $throwable->getMessage(), $throwable->getLine(), $throwable->getFile());

        if ($throwable instanceof ValidationException) {
            $message = $throwable->validator->errors()->all();
        }

        return $message;
    }

    /**
     * 过滤不需要记录日志的异常
     * @param Throwable $throwable
     * @return bool
     */
    public function shouldntReport(Throwable $throwable): bool
    {
        foreach ($this->expectExceptions as $exception) {
            if ($throwable instanceof $exception) {
                return true;
            }
        }

        return false;
    }

    /**
     * 判断该异常处理器是否要对该异常进行处理
     * @param Throwable $throwable
     * @return bool
     */
    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}
