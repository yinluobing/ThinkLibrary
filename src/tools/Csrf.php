<?php

// +----------------------------------------------------------------------
// | Library for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2018 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://library.thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | github 仓库地址 ：https://github.com/zoujingli/ThinkLibrary
// +----------------------------------------------------------------------

namespace library\tools;

/**
 * 表单CSRF表单令牌
 * Class Csrf
 * @package library\tools
 */
class Csrf
{

    /**
     * 检查表单CSRF验证
     * @return boolean
     */
    public static function checkFormToken()
    {
        $field = input('csrf-token-name', '__token__');
        return input($field, '--') === session($field, '', 'csrf');
    }

    /**
     * 清理表单CSRF信息
     * @param string $name
     */
    public static function clearFormToken($name)
    {
        is_null($name) ? session(null, 'csrf') : session($name, null, 'csrf');
    }

    /**
     * 生成表单CSRF信息
     * @return array
     */
    public static function buildFormToken()
    {
        session($name = 'csrf-token-value-' . uniqid(), $value = md5(uniqid()), 'csrf');
        return ['name' => $name, 'token' => $value];
    }

    /**
     * 返回视图内容
     * @param string $tpl 模板名称
     * @param array $vars 模板变量
     */
    public static function fetchTemplate($tpl = '', $vars = [])
    {
        throw new \think\exception\HttpResponseException(view($tpl, $vars, 200, function ($html) {
            return preg_replace_callback('/<\/form>/i', function () {
                $csrf = self::buildFormToken();
                return "<input type='hidden' name='{$csrf['name']}' value='{$csrf['token']}'><input type='hidden' name='csrf-token-name' value='{$csrf['name']}'></form>";
            }, $html);
        }));
    }
}