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
namespace Hyperf\ConfigAliyunAcm;

use Hyperf\ConfigAliyunAcm\Listener\BootProcessListener;
use Hyperf\ConfigAliyunAcm\Listener\OnPipeMessageListener;
use Hyperf\ConfigAliyunAcm\Listener\ProcessBeforeListener;
use Hyperf\ConfigAliyunAcm\Listener\StartServerListener;
use Hyperf\ConfigAliyunAcm\Process\ConfigFetcherProcess;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                ClientInterface::class => Client::class,
            ],
            'processes' => [
                ConfigFetcherProcess::class,
            ],
            'listeners' => [
                StartServerListener::class,
                BootProcessListener::class,
                OnPipeMessageListener::class,
                ProcessBeforeListener::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for aliyun acm.',
                    'source' => __DIR__ . '/../publish/aliyun_acm.php',
                    'destination' => BASE_PATH . '/config/autoload/aliyun_acm.php',
                ],
                [
                    'id' => 'config',
                    'description' => 'The config for signal.',
                    'source' => __DIR__ . '/../publish/signal.php',
                    'destination' => BASE_PATH . '/config/autoload/signal.php',
                ],
            ],
        ];
    }
}
