<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 应用设置
// +----------------------------------------------------------------------

use think\facade\Env;

defined('DS') || define('DS', DIRECTORY_SEPARATOR);

return [
    // 应用地址
    'app_host'         => Env::get('app.host', ''),
    // 应用的命名空间
    'app_namespace'    => '',
    // 是否启用路由
    'with_route'       => true,
    // 是否启用事件
    'with_event'       => true,
    // 自动多应用模式
    'auto_multi_app'   => true,
    // 应用映射（自动多应用模式有效）
    'app_map'          => [],
    // 域名绑定（自动多应用模式有效）
    'domain_bind'      => [],
    // 禁止URL访问的应用列表（自动多应用模式有效）
    'deny_app_list'    => [],
    // 默认应用
    'default_app'      => '',

    'app_express'      => true,
    // 默认时区
    'default_timezone' => 'Asia/Shanghai',
    // 异常页面的模板文件
    'exception_tmpl'   => app()->getRootPath() . 'public/statics/exception.tpl',
    // 错误显示信息,非调试模式有效
    'error_message'    => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg'   => false,
    // 没有开启消息队列命令或者定时任务命令的提醒开关
    'console_remind'   => true,
    // admin路由前缀
    'admin_prefix'     => 'admin',
    //代码生成功能生成前端文件的路径
    'admin_template_path' => dirname(root_path()) . DS . 'template' . DS . 'admin' . DS . 'src' . DS,
    //在保存crud的是否是否直接生成文件
    'crud_make'        => true
];
