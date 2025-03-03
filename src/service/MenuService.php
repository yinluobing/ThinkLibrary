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

use think\admin\extend\DataExtend;
use think\admin\model\SystemMenu;
use think\admin\Service;

/**
 * 系统菜单管理服务
 * Class MenuService
 * @package app\admin\service
 */
class MenuService extends Service
{

    /**
     * 获取可选菜单节点
     * @param boolean $force 强制刷新
     * @return array
     * @throws \ReflectionException
     */
    public static function getList(bool $force = false): array
    {
        static $nodes = [];
        if (empty($force) && count($nodes) > 0) return $nodes; else $nodes = [];
        foreach (NodeService::getMethods($force) as $node => $method) {
            if ($method['ismenu']) $nodes[] = ['node' => $node, 'title' => $method['title']];
        }
        return $nodes;
    }

    /**
     * 获取系统菜单树数据
     * @return array
     * @throws \ReflectionException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getTree(): array
    {
        $menus = SystemMenu::mk()->where(['status' => 1])->order('sort desc,id asc')->select()->toArray();
        if (function_exists('admin_menu_filter')) admin_menu_filter($menus);
        return static::filter(DataExtend::arr2tree($menus));
    }

    /**
     * 后台主菜单权限过滤
     * @param array $menus 当前菜单列表
     * @return array
     * @throws \ReflectionException
     */
    private static function filter(array $menus): array
    {
        foreach ($menus as $key => &$menu) {
            if (!empty($menu['sub'])) {
                $menu['sub'] = static::filter($menu['sub']);
            }
            if (!empty($menu['sub'])) {
                $menu['url'] = '#';
            } elseif (empty($menu['url']) || $menu['url'] === '#' || !(empty($menu['node']) || AdminService::check($menu['node']))) {
                unset($menus[$key]);
            } elseif (preg_match('#^(https?:)?//\w+#i', $menu['url'])) {
                if ($menu['params']) $menu['url'] .= (strpos($menu['url'], '?') === false ? '?' : '&') . $menu['params'];
            } else {
                $node = join('/', array_slice(str2arr($menu['url'], '/'), 0, 3));
                $menu['url'] = admuri($menu['url']) . ($menu['params'] ? '?' . $menu['params'] : '');
                if (!AdminService::check($node)) unset($menus[$key]);
            }
        }
        return $menus;
    }
}