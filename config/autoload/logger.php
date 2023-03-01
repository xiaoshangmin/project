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

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

return [
    'default' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    'filename' => BASE_PATH . '/runtime/logs/info.log',
                    'level' => Logger::INFO,
                ],
                'formatter' => [
                    'class' => \Monolog\Formatter\LineFormatter::class,
                    'constructor' => [
                        'applicationName' => 'project',
                        'allowInlineLineBreaks' => true,
                    ],
                ],
            ],
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    'filename' => BASE_PATH . '/runtime/logs/error.log',
                    'level' => Logger::ERROR,
                ],
                'formatter' =>[
                    'class' => \Monolog\Formatter\JsonFormatter::class,
                    'constructor' => [
                        'batchMode' => \Monolog\Formatter\JsonFormatter::BATCH_MODE_JSON,
                        'appendNewline' => true,
                    ],
                ],
            ],
        ],

    ],
];
