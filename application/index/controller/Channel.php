<?php

namespace app\index\controller;

use app\common\controller\Frontend;

class Channel extends Frontend
{

    protected $layout = '';

    public function _initialize()
    {
        parent::_initialize();
    }

    public function index() {

        if ($this->request->isAjax())
        {
            $filter = input('param.filter');
            $filterArr = json_decode($filter, true);
            $list = db('channel')->field('id,name,image,items')->where("status='normal' and parent_id={$filterArr['cid']}")->order("weigh desc, id desc")->select();
            $total = count($list);
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        } else {
            $cid = input('param.cid');
            if (!is_numeric($cid)) {
                $this->error('參數有誤');
            }
            $chanelInfo = db('channel')->field('name,image,model_id')->where("status='normal' and id={$cid}")->order("weigh desc,id desc")->find();
            $libList = db('channel')->field('id,name,image,items')->where("status='normal' and parent_id={$cid}")->order("weigh desc, id desc")->select();
            $this->view->assign('cid', $cid);
            $this->view->assign('channel', $chanelInfo);
            $this->view->assign('libList', $libList);
        }
        $this->view->assign('title','文庫');
        return $this->view->fetch();
    }
}
