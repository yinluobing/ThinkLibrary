<?php

// +----------------------------------------------------------------------
// | Library for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2022 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: https://gitee.com/zoujingli/ThinkLibrary
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | gitee 仓库地址 ：https://gitee.com/zoujingli/ThinkLibrary
// | github 仓库地址 ：https://github.com/zoujingli/ThinkLibrary
// +----------------------------------------------------------------------

namespace think\admin\support\command;

use think\admin\Command;
use think\admin\extend\PhinxExtend;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

/**
 * 组件安装指令
 * Class Publish
 * @package think\admin\support\command
 */
class Publish extends Command
{

    /**
     * 任务参数配置
     * @return void
     */
    public function configure()
    {
        $this->setName('vendor:publish');
        $this->addOption('force', 'f', Option::VALUE_NONE, 'Overwrite any existing files');
        $this->setDescription('Publish any publishable assets from vendor packages');
    }

    /**
     * 任务合并执行
     * @param \think\console\Input $input
     * @param \think\console\Output $output
     * @return null|void
     */
    public function execute(Input $input, Output $output)
    {
        $this->parse()->plugin()->output->writeln('<info>Succeed!</info>');
    }

    /**
     * 安装数据库
     * @return $this
     */
    private function plugin(): Publish
    {
        // 执行模块安装处理
        foreach ($this->app->config->get('app.addons', []) as $path) {
            [$path] = explode('@', $path);
            // 复制数据库脚本
            $frdir = rtrim($path, '\\/') . DIRECTORY_SEPARATOR . 'database';
            PhinxExtend::copyfile($frdir, with_path('database/migrations'), [], false, false);
            // 复制静态资料文件
            $frdir = rtrim($path, '\\/') . DIRECTORY_SEPARATOR . 'public';
            PhinxExtend::copyfile($frdir, with_path('public'), [], false, false);
        }
        // 执行数据库脚本
        $this->app->console->call('migrate:run');
        return $this;
    }

    /**
     * 解析 json 包
     * @return $this
     */
    private function parse(): Publish
    {
        $force = $this->input->getOption('force');
        if (is_file($path = $this->app->getRootPath() . 'vendor/composer/installed.json')) {
            $packages = json_decode(@file_get_contents($path), true);
            // Compatibility with Composer 2.0
            if (isset($packages['packages'])) $packages = $packages['packages'];
            $services = [];
            foreach ($packages as $package) {
                if (!empty($package['extra']['think']['services'])) {
                    $services = array_merge($services, (array)$package['extra']['think']['services']);
                }
                // 配置目录
                if (!empty($package['extra']['think']['config'])) {
                    $configPath = $this->app->getConfigPath();
                    $installPath = $this->app->getRootPath() . 'vendor/' . $package['name'] . DIRECTORY_SEPARATOR;
                    foreach ((array)$package['extra']['think']['config'] as $name => $file) {
                        $source = $installPath . $file;
                        $target = $configPath . $name . '.php';
                        if (is_file($target) && !$force) {
                            $this->output->info("File {$target} exist!");
                            continue;
                        }
                        if (!is_file($source)) {
                            $this->output->info("File {$source} not exist!");
                            continue;
                        }
                        copy($source, $target);
                    }
                }
            }
            $header = '// This file is automatically generated at:' . date('Y-m-d H:i:s') . PHP_EOL . 'declare (strict_types = 1);' . PHP_EOL;
            $content = '<?php ' . PHP_EOL . $header . "return " . var_export($services, true) . ';';
            file_put_contents($this->app->getRootPath() . 'vendor/services.php', $content);
        }
        return $this;
    }
}