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

use Hyperf\Command\Event\BeforeHandle;
use Hyperf\ConfigAliyunAcm\ClientInterface;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BeforeWorkerStart;
use Hyperf\Process\Event\BeforeProcessHandle;
use Psr\Container\ContainerInterface;

class BootProcessListener implements ListenerInterface
{
    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    /**
     * @var ClientInterface
     */
    protected $client;

    public function __construct(ContainerInterface $container)
    {
        $this->config = $container->get(ConfigInterface::class);
        $this->logger = $container->get(StdoutLoggerInterface::class);
        $this->client = $container->get(ClientInterface::class);
    }

    public function listen(): array
    {
        return [
            BeforeWorkerStart::class,
            BeforeProcessHandle::class,
            BeforeHandle::class,
        ];
    }

    public function process(object $event)
    {
        //Worker,Process启动前同步配置到对应进程
        if (! $this->config->get('aliyun_acm.enable', false)) {
            return;
        }
        if ($config = $this->client->pull()) {
            $this->updateConfig($config);
        }
    }

    protected function updateConfig(array $config)
    {
        foreach ($config as $key => $value) {
            if (is_string($key)) {
                $this->config->set($key, $value);
                $this->logger->info(sprintf('[%d]Config [%s] is updated', getmypid(), $key));
            }
        }
    }
}
