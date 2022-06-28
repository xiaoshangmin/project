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

use Monolog\Formatter\LogstashFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

$formatter = [
    'class' => LogstashFormatter::class,
    'constructor' => [
        'applicationName' => 'project',
    ],
];

return [
    'default' => [
        'handler' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    'filename' => BASE_PATH . '/runtime/logs/info.log',
                    'level' => Logger::INFO,
                ],
                'formatter' => $formatter,
            ],
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    'filename' => BASE_PATH . '/runtime/logs/error.log',
                    'level' => Logger::ERROR,
                ],
                'formatter' => $formatter,
            ],
        ],

    ],
];
