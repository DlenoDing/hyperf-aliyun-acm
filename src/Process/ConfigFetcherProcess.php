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
            if (empty($config)) {
                goto WHILE_STEP;
            }
            //第一次不执行配置同步，系统首次启动时已经拉取了最新的配置
            if (is_null($this->cacheConfig)) {
                $this->cacheConfig = $config;
                goto WHILE_STEP;
            }
            //检查配置是否有变更
            $check = $this->checkArrayDiff($config, $this->cacheConfig);
            if (!$check) {
                goto WHILE_STEP;
            }
            //合并配置
            $config = $this->arrayMerge($this->cacheConfig, $config);
            //缓存配置
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


            WHILE_STEP:

            sleep($this->config->get('aliyun_acm.interval', 5));
        }
    }

    private function checkArrayDiff($array1, $array2)
    {
        foreach ($array1 as $key => $value) {
            //判断数组每个元素是否是数组
            if (is_array($value)) {
                //判断第二个数组是否存在key
                if (!isset($array2[$key])) {
                    return true;
                    //判断第二个数组key是否是一个数组
                } elseif (!is_array($array2[$key])) {
                    return true;
                } else {
                    $diff = $this->checkArrayDiff($value, $array2[$key]);
                    if ($diff) {
                        return true;
                    }
                }
            } elseif (!array_key_exists($key, $array2) || $value !== $array2[$key]) {
                return true;
            }
        }
        return false;
    }

    private function arrayMerge($arr1, $arr2)
    {
        $rs = [];

        $keys = array_unique(array_merge($arr2?array_keys($arr2):[], $arr1?array_keys($arr1):[]));
        foreach($keys as $k){
            if (isset($arr2[$k]) && is_array($arr2[$k])) {
                $rs[$k] = $this->arrayMerge($arr1[$k], $arr2[$k]);
            } else {
                $rs[$k] = isset($arr2[$k])?$arr2[$k]:$arr1[$k];
            }
        }
        return $rs;
    }
}
