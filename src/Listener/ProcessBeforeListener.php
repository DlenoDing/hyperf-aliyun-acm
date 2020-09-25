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
namespace Hyperf\ConfigAliyunAcm\Listener;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Process\Event\BeforeCoroutineHandle;
use Hyperf\Process\Event\BeforeProcessHandle;
use Psr\Container\ContainerInterface;

class ProcessBeforeListener implements ListenerInterface
{
    /**
     * @var ConfigInterface
     */
    protected $config;

    public function __construct(ContainerInterface $container)
    {
        $this->config = $container->get(ConfigInterface::class);
    }

    public function listen(): array
    {
        return [
            BeforeProcessHandle::class,
            BeforeCoroutineHandle::class,
        ];
    }

    public function process(object $event)
    {
        if (! $this->config->get('aliyun_acm.enable', false)) {
            return;
        }
        //自定义进程启动前，把当前的每个进程PID写入对应文件
        $file = config('aliyun_acm.process_file', BASE_PATH.'/runtime/aliyun.acm.process');
        if (!file_exists($file)) {
            touch($file);
        }
        file_put_contents($file, getmypid()."\n", FILE_APPEND);
    }
}
