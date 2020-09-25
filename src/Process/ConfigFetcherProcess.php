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
namespace Hyperf\ConfigAliyunAcm\Process;

use Hyperf\ConfigAliyunAcm\ClientInterface;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Process\AbstractProcess;
use Psr\Container\ContainerInterface;
use Swoole\Server;

class ConfigFetcherProcess extends AbstractProcess
{
    public $name = 'AliYunAcmFetcher';

    /**
     * @var Server
     */
    private $server;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var array
     */
    private $cacheConfig;

    /**
     * @var StdoutLoggerInterface
     */
    private $logger;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->client = $container->get(ClientInterface::class);
        $this->config = $container->get(ConfigInterface::class);
        $this->logger = $container->get(StdoutLoggerInterface::class);
    }

    public function bind($server): void
    {
        $this->server = $server;
        parent::bind($server);
    }

    public function isEnable($server): bool
    {
        return $server instanceof Server
            && $this->config->get('aliyun_acm.enable', false);
    }

    public function handle(): void
    {
        while (true) {
            $config = $this->client->pull();
            //第一次不执行配置同步，系统首次启动时已经拉取了最新的配置
            if (!empty($config) && is_null($this->cacheConfig)) {
                $this->cacheConfig = $config;
            }
            //配置有变更
            if (!empty($config) && $config !== $this->cacheConfig) {
                $this->cacheConfig = $config;

                //获取当前系统的所有自定义进程PID，并以此重启，除了当前AliYunAcmFetcher进程
                $file = config('aliyun_acm.process_file', BASE_PATH.'/runtime/aliyun.acm.process');
                $processes = file_get_contents($file);
                @unlink($file);
                touch($file);
                file_put_contents($file, getmypid()."\n", FILE_APPEND);
                $processes = trim($processes, "\n");
                $processes = explode("\n", $processes);
                foreach ($processes as $process) {
                    if ($this->process->pid == $process) {
                        continue;
                    }
                    \Swoole\Process::kill((int)$process, SIGINT);
                }

                //服务器worker,task热重启
                $this->server->reload();

                $this->logger->info('Config is updated!!');
            }

            sleep($this->config->get('aliyun_acm.interval', 5));
        }
    }
}
