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
namespace Hyperf\ConfigAliyunAcm\Signal;

use Hyperf\ConfigAliyunAcm\Process\ConfigFetcherProcess;
use Hyperf\Process\ProcessManager;
use Hyperf\Signal\SignalHandlerInterface;

class ProcessStopHandler implements SignalHandlerInterface
{
    public function listen(): array
    {
        return [
            [self::PROCESS, SIGTERM],
            [self::PROCESS, SIGINT],
        ];
    }

    public function handle(int $signal): void
    {
        if ($signal == SIGINT) {
            ConfigFetcherProcess::setRunning(false);
        }
        ProcessManager::setRunning(false);
    }
}
