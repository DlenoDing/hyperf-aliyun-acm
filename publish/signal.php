<?php
declare(strict_types=1);
return [
    'handlers' => [
        Hyperf\ConfigAliyunAcm\Signal\ProcessStopHandler::class,
    ],
    'timeout' => 5.0,
];