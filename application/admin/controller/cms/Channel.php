<?php

namespace app\admin\controller\cms;

use app\common\controller\Backend;
use app\admin\model\Channel as ChannelModel;
use fast\Tree;

/**
 * 栏目表
 *
 * @icon fa fa-circle-o
 */
class Channel extends Backend
{

    protected $channelList = [];
    protected $modelList = [];
    protected $noNeedRight = ['get_model_list'];

    /**
     * Channel模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->request->filter(['strip_tags']);
        $this->model = model('Channel');

        $tree = Tree::instance();
        $tree->init(collection($this->model->order('weigh desc,id desc')->select())->toArray(), 'parent_id');
        $this->channelList = $tree->getTreeList($tree->getTreeArray(0), 'name');
        $this->modelList = \app\admin\model\Modelx::order('id asc')->select();

        $this->view->assign("modelList", $this->modelList);
        $this->view->assign("channelList", $this->channelList);
        $this->view->assign("typeList", ChannelModel::getTypeList());
        $this->view->assign("statusList", ChannelModel::getStatusList());
    }

    /**
     * 查看
     */
    public function index()
    {

        if ($this->request->isAjax())
        {
            $search = $this->request->request("search");
            //构造父类select列表选项数据
            $list = [];
            if ($search)
            {
                foreach ($this->channelList as $k => $v)
                {
                    if (stripos($v['name'], $search) !== false || stripos($v['nickname'], $search) !== false)
                    {
                        $list[] = $v;
                    }
                }
            }
            else
            {
                $list = $this->channelList;
            }
            $modelNameArr = [];
            foreach ($this->modelList as $k => $v)
            {
                $modelNameArr[$v['id']] = $v['name'];
            }
            foreach ($list as $k => &$v)
            {
                $v['model_name'] = $v['model_id'] && isset($modelNameArr[$v['model_id']]) ? $modelNameArr[$v['model_id']] : __('None');
            }
            $total = count($list);
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * Selectpage搜索
     * 
     * @internal
     */
    public function selectpage()
    {
        return parent::selectpage();
    }

    public function get_model_list()
    {
        
    }

    public function del($ids = "") {
        $archives = db('archives')->field('id')->where("channel_id", 'in', $ids)->find();
        if (!empty($archives)) {
            $this->error(__('The library exist content'));
        }
        return parent::del($ids);
    }

}
