<?php

namespace app\index\controller;

use app\common\controller\Frontend;

class Book extends Frontend
{

    protected $layout = '';

    public function _initialize()
    {
        parent::_initialize();
    }

    public function index() {
        $literature = input('param.literature/d');
        if (!is_numeric($literature)) {
            $this->error('參數有誤');
        }
        $version = input('param.version/d');
        $archiveInfo = db('archives')->where("id={$literature} and status='normal'")->find();
        if (empty($archiveInfo)) {
            $this->error("找不到相關數據");
        }

        $archiveExtInfo = db($archiveInfo['append_table'])->where("id={$literature}")->find();
        $archiveInfo = array_merge($archiveInfo, $archiveExtInfo);
        if (!empty($archiveExtInfo['version']) && !empty($version)) {
            $versionArr = json_decode($archiveExtInfo['version'], true);
            $versionName = $versionArr[$version];
            $archiveInfo['version'] = $versionArr;
        } else {
            $versionName = $archiveInfo['title'];
        }


        $channelInfo = db('channel')->field('name,model_id')->where("id={$archiveInfo['channel_id']}")->find();
        $channelName = $channelInfo['name'];
        $bookWhere = "literature={$literature}";
        $bookWhere .= empty($version) ? '' : " and version={$version}";
        $booksList = db('book')->where($bookWhere)->order("weigh desc,id desc")->select();

        $fieldsInfo['版本名稱'] = $versionName;
        $fieldsInfo['發布時間'] = date('Y-m-d', $archiveInfo['publishtime']);
        $fields = db('fields')->field('name,title,model_id')->where("model_id = {$channelInfo['model_id']}")->select();
        foreach($fields as $k => $v) {
            if ($v['name'] == 'version') {
                continue;
            }
            if (array_key_exists($v['name'], $archiveInfo)) {
                $fieldsInfo[$v['title']] = $archiveInfo[$v['name']];
            }
        }
        $fieldsInfo['標簽'] = $archiveInfo['tags'];
        $fieldsInfo['描述'] = $archiveInfo['description'];

        $firstPage = array_pop($booksList);
        $this->view->assign('channelName', $channelName);
        $this->view->assign('versionName', $versionName);
        $this->view->assign('fieldsInfo', $fieldsInfo);
        $this->view->assign('archiveInfo', $archiveInfo);
        $this->view->assign('booksList', $booksList);
        $this->view->assign('firstPage', $firstPage);
        return $this->view->fetch();
    }





}
