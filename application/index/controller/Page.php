<?php

namespace app\index\controller;

use app\common\controller\Frontend;

class Page extends Frontend
{

    protected $layout = '';

    public function _initialize()
    {
        parent::_initialize();
    }

    public function index($id) {
        if (!is_numeric($id)) {
            $this->error('參數有誤');
        }
        $page = db('page')->where("status='normal' and id={$id}")->order("weigh desc,id desc")->find();
        $this->view->assign('page', $page);
        $this->view->assign('title',$page['title']);
        return $this->view->fetch();
    }





}
