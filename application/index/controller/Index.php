<?php

namespace app\index\controller;

use app\common\controller\Frontend;

class Index extends Frontend
{

    protected $layout = '';

    public function _initialize()
    {
        parent::_initialize();
    }

    public function index() {
        $searchFields = $this->getSearchFields($this->libraries);

        $this->view->assign('title','首頁');
        $this->view->assign('searchFields', $searchFields);
        return $this->view->fetch('index');
    }


    public function dashboard() {
        $searchFields = $this->getSearchFields($this->libraries);
        $this->view->assign('title','首頁');
        $this->view->assign('searchFields', $searchFields);
        return $this->view->fetch('index');
    }

    /**
     * 获取所有文献的搜索项
     * @param $libraries 指定的文库
     * @param array $checkedLibData 文库ID["文库id1,文库id2.."]]
     * @param array $checkedFiledsData 某个文库选中的字段["文库id" => ["搜索的字段1,搜索的字段2..."]]
     * @return array|string
     */
    public function getSearchFields($libraries, $checkedLibData = [], $checkedFiledsData = []) {
        if (empty($libraries) || count($libraries) < 1) {
            return '';
        }
        $data = [];
        foreach ($libraries as $key => $value) {
            if (empty($value['model_id'])) {
                continue;
            }
            $fields = db('fields')->field('id,name,title,false as checked')->where(" `search`='allow' and `model_id`={$value['model_id']}")->order('weigh desc,id desc')->select();
            if (!empty($fields) && !empty($checkedFiledsData)) {
                foreach($fields as $k => $val) {
                    if (empty($checkedFiledsData[$value['model_id']])) {
                        continue;
                    }
                    if (in_array($val['name'], $checkedFiledsData[$value['model_id']])) {
                        $fields[$k]['checked'] = true;
                    }
                }

            }
            $libraries[$key]['fields'] = $fields;

            $modelInfo = db("model")->field("table")->where("id = {$value['model_id']}")->find();
            $table = "fa_" . $modelInfo['table'];
            $libraries[$key]['append_table'] = $table;

            if (empty($checkedLibData) || !in_array($value['id'], $checkedLibData)) {
                $libraries[$key]['checked'] = false;
            } else {
                $libraries[$key]['checked'] = true;
            }
            $data[$value['model_id']] = $libraries[$key];
        }

        return $data;
    }


}
