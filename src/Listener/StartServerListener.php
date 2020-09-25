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
use Hyperf\Framework\Event\BeforeMainServerStart;
use Psr\Container\ContainerInterface;

class StartServerListener implements ListenerInterface
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
            BeforeMainServerStart::class,
        ];
    }

    /**
     * @param BeforeMainServerStart $event
     */
    public function process(object $event)
    {
        if (! $this->config->get('aliyun_acm.enable', false)) {
            return;
        }
        //主服务启动前，还原自定义进程PID记录文件
        $file = config('aliyun_acm.process_file', BASE_PATH.'/runtime/aliyun.acm.process');
        @unlink($file);
        touch($file);
        //var_dump('============Server Start!!================');
    }
}
