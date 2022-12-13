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

declare (strict_types=1);

namespace think\admin\service;

use think\admin\Library;
use think\Service;

/**
 * 应用插件注册服务
 * Class PluginService
 * @package think\admin\service
 */
class PluginService extends Service
{
    /**
     * 应用插件名称
     * @var string
     */
    protected $appName = '';

    /**
     * 应用插件目录
     * @var string
     */
    protected $appPath = '';

    /**
     * 应用插件别名
     * @var string
     */
    protected $appAlias = '';

    /**
     * 应用命名空间
     * @var string
     */
    protected $appSpace = '';

    /**
     * 文件拷贝目录
     * @var string
     */
    protected $copyPath = '';

    /**
     * 当前插件配置
     * @var array
     */
    private static $addons = [];

    /**
     * 自动注册应用
     * @return void
     */
    public function boot(): void
    {
        // 初始化服务
        $this->initialize();

        $ref = new \ReflectionClass(static::class);
        $attr = explode('\\', $ref->getNamespaceName());

        // 应用命名空间名
        if (empty($this->appSpace)) {
            $this->appSpace = $ref->getNamespaceName();
        }

        // 应用插件路径计算
        if (empty($this->appPath) || !file_exists($this->appPath)) {
            $this->appPath = dirname($ref->getFileName());
        }

        // 应用插件计算名称及别名
        if (NodeService::namespace() === $attr[0]) array_shift($attr);
        if (empty($this->appName)) $this->appName = join('-', $attr);
        if (empty($this->appAlias)) $this->appAlias = join('-', $attr);

        // 注册应用插件信息
        static::add($this->appName, $this->appPath, $this->appAlias, $this->appSpace, $this->copyPath);
    }

    /**
     * 注册插件
     * @param string $appName 应用名称
     * @param string $appPath 应用目录
     * @param string $appAlias 应用别名
     * @param string $appSpace 应用空间
     * @param string $copyPath 应用资源
     * @return boolean
     */
    public static function add(string $appName, string $appPath, string $appAlias = '', string $appSpace = '', string $copyPath = ''): bool
    {
        if (file_exists($appPath) && is_dir($appPath)) {
            $appPath = rtrim($appPath, '\\/') . DIRECTORY_SEPARATOR;
            $appSpace = $appSpace ?: (Library::$sapp->config->get('app.app_namespace') ?: 'app') . "\\{$appName}";
            $copyPath = rtrim($copyPath ?: dirname($appPath) . DIRECTORY_SEPARATOR . 'stc', '\\/') . DIRECTORY_SEPARATOR;
            if (strlen($appAlias) > 0 && $appAlias !== $appName) Library::$sapp->config->set([
                'app_map' => array_merge(Library::$sapp->config->get('app.app_map', []), [$appAlias => $appName])
            ], 'app');
            self::$addons[$appName] = [$appPath, $appSpace, $copyPath, $appAlias];
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取所有插件
     * @return array [string][所在路径,应用空间,资源目录,应用别名]
     */
    public static function all(): array
    {
        return self::$addons;
    }

    /**
     * 服务初始化
     */
    protected function initialize()
    {
    }
}