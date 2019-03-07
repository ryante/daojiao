<?php

namespace app\common\controller;

use think\Config;
use think\Controller;
use think\Hook;
use think\Lang;
use think\Session;

class Frontend extends Controller
{

    /**
     * 布局模板
     * @var string
     */
    protected $layout = '';

    public function _initialize()
    {
        //移除HTML标签
        $this->request->filter('strip_tags');
        $modulename = $this->request->module();
        $controllername = strtolower($this->request->controller());
        $actionname = strtolower($this->request->action());

        // 如果有使用模板布局
        if ($this->layout)
        {
            $this->view->engine->layout('layout/' . $this->layout);
        }

        // 语言检测
        $lang = strip_tags(Lang::detect());

        $site = Config::get("site");

        $upload = \app\common\model\Config::upload();

        // 上传信息配置后
        Hook::listen("upload_config_init", $upload);

        // 配置信息
        $config = [
            'site'           => array_intersect_key($site, array_flip(['name', 'cdnurl', 'version', 'timezone', 'languages'])),
            'upload'         => $upload,
            'modulename'     => $modulename,
            'controllername' => $controllername,
            'actionname'     => $actionname,
            'jsname'         => 'frontend/' . str_replace('.', '/', $controllername),
            'moduleurl'      => rtrim(url("/{$modulename}", '', false), '/'),
            'language'       => $lang
        ];

        Config::set('upload', array_merge(Config::get('upload'), $upload));

        // 配置信息后
        Hook::listen("config_init", $config);
        $this->loadlang($controllername);
        $this->assign('site', $site);
        $this->assign('config', $config);
        $this->assign('admin', Session::get('admin'));
        //单页文章
        $singlePages = db('page')->where("status = 'normal'")->order('weigh desc,id desc')->select();

        //文库
        $libraries = db('channel')->field('id,model_id,name,image,type')->where("status = 'normal' and parent_id=0")->order('weigh desc,id desc')->select();//供左侧，首页用
        $allLibraries = db('channel')->field('id,model_id,name,image,type')->where("status = 'normal' and type='list'")->order('parent_id asc,weigh desc,id desc')->select();//供所有搜索项用
        $this->libraries = $allLibraries;
        //标签
        $tags = db('tags')->field('id,name')->order('id desc')->select();
        $this->assign('singlePages', $singlePages);
        $this->assign('libraries', $libraries);
        $this->assign('tags', $tags);
    }

    /**
     * 加载语言文件
     * @param string $name
     */
    protected function loadlang($name)
    {
        Lang::load(APP_PATH . $this->request->module() . '/lang/' . Lang::detect() . '/' . str_replace('.', '/', $name) . '.php');
    }

    /**
     * 渲染配置信息
     * @param mixed $name 键名或数组
     * @param mixed $value 值
     */
    protected function assignconfig($name, $value = '')
    {
        $this->view->config = array_merge($this->view->config ? $this->view->config : [], is_array($name) ? $name : [$name => $value]);
    }

}
