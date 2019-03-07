<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use app\index\model\Channel;

class Archives extends Frontend
{

    protected $layout = '';

    public function _initialize()
    {
        $this->model = model('Archives');
        parent::_initialize();
    }

    public function index() {

        //设置过滤方法
        $this->request->filter(['strip_tags']);
        $this->searchFields = "title";
        if ($this->request->isAjax())
        {

            $this->relationSearch = TRUE;
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->with('Channel')
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with('Channel')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            foreach($list as $key => $value) {
                $model_id = $value->channel->model_id;
                $model = \app\admin\model\Modelx::get($model_id);
                $addon = db($model['table'])->where('id',  $value->id)->find();
                if (!empty($addon['version'])) {
                    $versionArr = json_decode($addon['version'], true);
                    $versionStr = implode(',', $versionArr);
                    $addon['version'] = $versionStr;
                }

                $list[$key]['addon'] = $addon;
            }

            $result = array("total" => $total, "rows" => $list);
            return json($result);
        } else {
            $cid = input('param.cid');
            if (!is_numeric($cid)) {
                $this->error('參數有誤');
            }
            $chanelInfo = db('channel')->field('name,image,model_id')->where("status='normal' and id={$cid}")->order("weigh desc,id desc")->find();
            $fieldInfo = db('fields')->field('name,title,show')->where(['model_id' => $chanelInfo['model_id']])->select();
            $archiveList = db("archives")->field('id,channel_id,model_id,append_table,title')->where("channel_id={$cid} and deletetime is null ")->order("weigh desc,id desc")->select();
            if (!empty($archiveList)) {
                foreach($archiveList as $key => $value) {
                    $subInfo = db($value['append_table'])->where('id',  $value['id'])->find();
                    if (!empty($subInfo['version'])) {
                        $versionArr = json_decode($subInfo['version'], true);
                        $archiveList[$key]['version'] = $versionArr;
                    }
                }
            }
            $this->view->assign('show_version', false);
            foreach($fieldInfo as $key => $value) {
                $show = $value['show'] == 'allow' ? true :  false;
                if ($value['name'] == 'version') {
                    $this->view->assign('show_version', $show);
                    continue;
                }
                $fieldArr[$key]['field'] = 'addon.' . $value['name'];
                $fieldArr[$key]['title'] = $value['title'];
                $fieldArr[$key]['visible'] = $show;
            }
            $this->view->assign('title', $chanelInfo['name']);
            $this->view->assign('fieldstr', json_encode($fieldArr, true));
            $this->view->assign('channel', $chanelInfo);
            $this->view->assign('archiveList', $archiveList);
        }
        return $this->view->fetch();
    }



    /**
     * 生成查询所需要的条件,排序方式
     * @param mixed $searchfields 快速查询的字段
     * @param boolean $relationSearch 是否关联查询
     * @return array
     */
    protected function buildparams($searchfields = null, $relationSearch = null)
    {
        $searchfields = is_null($searchfields) ? $this->searchFields : $searchfields;
        $relationSearch = is_null($relationSearch) ? $this->relationSearch : $relationSearch;
        $search = $this->request->get("search", '');
        $filter = $this->request->get("filter", '');
        $op = $this->request->get("op", '', 'trim');
        $sort = $this->request->get("sort", "id");
        $order = $this->request->get("order", "DESC");
        $offset = $this->request->get("offset", 0);
        $limit = $this->request->get("limit", 0);
        $filter = json_decode($filter, TRUE);
        $op = json_decode($op, TRUE);
        $filter = $filter ? $filter : [];
        $where = [];
        $tableName = '';
        if ($relationSearch)
        {
            if (!empty($this->model))
            {
                $class = get_class($this->model);
                $name = basename(str_replace('\\', '/', $class));
                $tableName = $this->model->getQuery()->getTable($name) . ".";
            }
            $sort = stripos($sort, ".") === false ? $tableName . $sort : $sort;
        }

        if ($search)
        {
            $searcharr = is_array($searchfields) ? $searchfields : explode(',', $searchfields);
            foreach ($searcharr as $k => &$v)
            {
                $v = stripos($v, ".") === false ? $tableName . $v : $v;
            }
            unset($v);
            $where[] = [implode("|", $searcharr), "LIKE", "%{$search}%"];
        }
        foreach ($filter as $k => $v)
        {
            $sym = isset($op[$k]) ? $op[$k] : '=';
            if (stripos($k, ".") === false)
            {
                $k = $tableName . $k;
            }
            $sym = strtoupper(isset($op[$k]) ? $op[$k] : $sym);
            switch ($sym)
            {
                case '=':
                case '!=':
                    $where[] = [$k, $sym, (string) $v];
                    break;
                case 'LIKE':
                case 'NOT LIKE':
                case 'LIKE %...%':
                case 'NOT LIKE %...%':
                    $where[] = [$k, trim(str_replace('%...%', '', $sym)), "%{$v}%"];
                    break;
                case '>':
                case '>=':
                case '<':
                case '<=':
                    $where[] = [$k, $sym, intval($v)];
                    break;
                case 'IN':
                case 'IN(...)':
                case 'NOT IN':
                case 'NOT IN(...)':
                    $where[] = [$k, str_replace('(...)', '', $sym), explode(',', $v)];
                    break;
                case 'BETWEEN':
                case 'NOT BETWEEN':
                    $arr = array_slice(explode(',', $v), 0, 2);
                    if (stripos($v, ',') === false || !array_filter($arr))
                        continue;
                    //当出现一边为空时改变操作符
                    if ($arr[0] === '')
                    {
                        $sym = $sym == 'BETWEEN' ? '<=' : '>';
                        $arr = $arr[1];
                    }
                    else if ($arr[1] === '')
                    {
                        $sym = $sym == 'BETWEEN' ? '>=' : '<';
                        $arr = $arr[0];
                    }
                    $where[] = [$k, $sym, $arr];
                    break;
                case 'RANGE':
                case 'NOT RANGE':
                    $v = str_replace(' - ', ',', $v);
                    $arr = array_slice(explode(',', $v), 0, 2);
                    if (stripos($v, ',') === false || !array_filter($arr))
                        continue;
                    //当出现一边为空时改变操作符
                    if ($arr[0] === '')
                    {
                        $sym = $sym == 'RANGE' ? '<=' : '>';
                        $arr = $arr[1];
                    }
                    else if ($arr[1] === '')
                    {
                        $sym = $sym == 'RANGE' ? '>=' : '<';
                        $arr = $arr[0];
                    }
                    $where[] = [$k, str_replace('RANGE', 'BETWEEN', $sym) . ' time', $arr];
                    break;
                case 'LIKE':
                case 'LIKE %...%':
                    $where[] = [$k, 'LIKE', "%{$v}%"];
                    break;
                case 'NULL':
                case 'IS NULL':
                case 'NOT NULL':
                case 'IS NOT NULL':
                    $where[] = [$k, strtolower(str_replace('IS ', '', $sym))];
                    break;
                default:
                    break;
            }
        }
        $where = function($query) use ($where) {
            foreach ($where as $k => $v)
            {
                if (is_array($v))
                {
                    call_user_func_array([$query, 'where'], $v);
                }
                else
                {
                    $query->where($v);
                }
            }
        };
        return [$where, $sort, $order, $offset, $limit];
    }



}
