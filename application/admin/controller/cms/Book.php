<?php

namespace app\admin\controller\cms;

use app\admin\model\Channel;
use app\common\controller\Backend;
use fast\Tree;

/**
 * 内容表
 *
 * @icon fa fa-circle-o
 */
class Book extends Backend
{

    /**
     * Archives模型对象
     */
    protected $model = null;
    protected $noNeedRight = ['get_channel_fields', 'choose', 'searchlist'];
    protected static $versionOffset = 10000;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Book');


        $all = collection(db('archives')->field('id,title,append_table,deletetime')->order("weigh desc,id desc")->where("status='normal'")->select())->toArray();
        foreach ($all as $k => $v)
        {
            if (!empty($v['deletetime'])) {
                continue;
            }
           // $openedState = empty($_GET['archives']) ? false : ($v['id'] == $_GET['archives'] ? true : false);
            $openedState = empty($_GET['archives']) ? false : ($v['id'] == $_GET['archives'] ? true : false);
            $selectState = ($openedState && empty($_GET['version'])) ? true : false;
            $list[] = [
                'id'     => $v['id'],
                'parent' => '#',
                'text'   => mb_strlen($v['title']) > 10 ? mb_substr( $v['title'],0,10,"utf-8") . '...' : $v['title'],
                'type'   => 'page',
                'state'  => ['opened' => $openedState]//, 'selected' => $selectState],
            ];

            $versionInfo = db($v['append_table'])->field('version')->where("id={$v['id']}")->find();
            if (!empty($versionInfo['version'])) {
                $idnum = 0;
                $versionTmpArr = json_decode($versionInfo['version'], true);
                foreach($versionTmpArr as $key => $value) {
                    if (empty($value)) {
                        continue;
                    }
                    $idnum = $idnum + 1;
                    $versionState = empty($_GET['version']) ? false : (($idnum == $_GET['version'] && $v['id'] == $_GET['archives']) ? true : false);
                    $versionList[] = [
                        'id' => $v['id'] * self::$versionOffset + $idnum,
                        'parent' => $v['id'],
                        'text'   => mb_strlen($value) > 10 ? mb_substr($value, 0, 10, "utf-8") . '...' : $value,
                        'type'   => 'version',
                       // 'state'  => ['selected' => $versionState]
                    ];
                }
            }
        }
        $pageList = array_merge($list,$versionList);
        $this->assignconfig('channelList', $pageList);

    }

    /**
     * 查看
     */
    public function index()
    {
        $this->relationSearch = true;
        $this->searchFields = "archives.title";
        if ($this->request->isAjax())
        {
            //左侧栏目点击查询条件构造
            $filter = $this->request->param("filter");
            $whereTmp = $this->getWhere($filter);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $where = empty($whereTmp) ? '' : $whereTmp;
            if (!empty($this->request->param("search"))) {
                $where = empty($where) ? " archives.title like '%{$this->request->param("search")}%'" : $where . " and archives.title like '%{$this->request->param("search")}%'";
            }

            $total = $this->model
                ->with("archives")
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->with("archives")
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            if (!empty($list)) {
                foreach($list as $key => $value) {
                    $libraryInfo = db('channel')->field('name,model_id,id')->where("id={$value->library} and type='list'")->find();
                    $model = \app\admin\model\Modelx::get($libraryInfo['model_id']);
                    $addon = db($model['table'])->field('version')->where('id',  $value->archives->id)->find();
                    if (!empty($addon['version'])) {
                        $versionArr = json_decode($addon['version'], true);
                        $list[$key]->version_name = empty($versionArr[$value->version]) ? '' : $versionArr[$value->version];
                    } else {
                        $list[$key]->version_name = '';
                    }
                    $list[$key]->library = $libraryInfo['name'];
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个方法
     * 因此在当前控制器中可不用编写增删改查的代码,如果需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 读取文库,文献列表
     */
    public function choose() {
        $channelId = $this->request->get('channel_id');
        $archivesId = $this->request->get('archives_id');
        if (!$channelId) {
            $data = db('channel')->field('id as value,name,parent_id,type')->order('parent_id asc,weigh desc,id desc')->select();
            if (!empty($data)) {
                foreach($data as $key => $value) {
                    $tmpArr[$value['value']] = $value;
                }
                foreach($tmpArr as $k => &$v) {
                    if ($v['type'] == 'list') {
                        $name = $v['name'];
                        if ($v['parent_id'] > 0) {
                            $name = $tmpArr[$v['parent_id']]['name'] . '-' . $v['name'];
                        }
                        $list[] = ['value' => $k, 'name' => $name];
                    }

                }
            }
        } elseif (!$archivesId) {
            $list = db('archives')->field('id as value,title as name')->where("channel_id={$channelId} and deletetime is null ")->order('weigh desc,id desc')->select();
        } else {
            $channelInfo = db('channel')->field('model_id')->where("id={$channelId}")->find();
            $modelInfo = db('model')->field('table')->where("id={$channelInfo['model_id']}")->find();
            $appendInfo = db($modelInfo['table'])->field('version')->where("id={$archivesId}")->find();
            if (empty($appendInfo['version'])) {
                $list = [];
            } else {
                $versionInfo = json_decode($appendInfo['version'], true);
                if (!empty($versionInfo)) {
                    foreach($versionInfo as $key => $value) {
                        if (empty($value)) {
                            continue;
                        }
                        $list[] = ['value' => $key, 'name' => $value];
                    }
                }

            }
        }


        $this->success('', null, $list);
    }

    /**
     * 搜索下拉列表
     */
    public function searchlist()
    {
        $list = db('channel')->field('id,name')->order('weigh desc,id desc')->select();

        $data = ['searchlist' => $list];
        $this->success('', null, $data);
    }

    /**
     * 左侧边栏点击构造条件
     * @param $filter
     * @return bool|string
     */
    public function getWhere($filter) {
        if (empty($filter)) {
            return false;
        }
        $where = '';
        $filterArr = json_decode($filter, true);
        if (!empty($filterArr['literature'])) {
            $literatureQueryArr = [];
            $versionQueryArr = [];
            $literatureArrTmp = explode(",", $filterArr['literature']);
            foreach($literatureArrTmp as $key => $value) {
                if ($value > self::$versionOffset * 10 ) {
                    $literatureTmp = (int)($value / self::$versionOffset);
                    if (in_array($literatureTmp, $literatureQueryArr)) {
                        continue;
                    }
                    $versionTmp = $value % self::$versionOffset;
                    $versionQueryArr[] = ['literature' => $literatureTmp, 'version' => $versionTmp];
                } else {
                    $literatureQueryArr[] = $value;
                }
            }
            if (!empty($literatureQueryArr)) {
                $where = "literature in (" . implode(',', $literatureQueryArr) .")";
            }
            if (!empty($versionQueryArr)) {
                $where = empty($where) ? '' : $where . " or ";
                $countVersion = count($versionQueryArr);
                foreach($versionQueryArr as $key => $value) {
                    $where .=  " (literature = {$value['literature']} and version = {$value['version']})";
                    $where .= $key == $countVersion - 1 ? '' : ' or ';
                }
            }
        }
        return $where;
    }


    public function selectversion($version) {
        if (empty($version)) {

            $sql = "SELECT a.id,a.version,'科儀文本' as tb,b.title From fa_keyi a INNER JOIN fa_archives b ON a.id=b.id  and a.version !=''
UNION
SELECT a.id,a.version,'呂祖全書' as tb,b.title From fa_beike a INNER JOIN fa_archives b ON a.id=b.id   and a.version !=''
UNION
SELECT a.id,a.version,'碑刻全集' as tb,b.title From fa_lvzhu a INNER JOIN fa_archives b ON a.id=b.id   and a.version !='' ";

        } else {
            $sql = "SELECT a.id,a.version,'科儀文本' as tb,b.title From fa_keyi a INNER JOIN fa_archives b ON a.id=b.id  and a.version like '%{$version}%'
UNION
SELECT a.id,a.version,'呂祖全書' as tb,b.title From fa_beike a INNER JOIN fa_archives b ON a.id=b.id   and a.version like '%{$version}%'
UNION
SELECT a.id,a.version,'碑刻全集' as tb,b.title From fa_lvzhu a INNER JOIN fa_archives b ON a.id=b.id   and a.version like '%{$version}%' ";
        }
        $list = \think\Db::query($sql);
        if (empty($list)) {
            return false;
        }
        $data = [];
        foreach($list as $key => $value) {
            $version = json_decode($value['version'], true);
            foreach($version as $k => $v) {
                if (empty($v) || empty($k)) {
                    continue;
                }
                $title = $value['tb'] . ' ' . $value['title']. ' ' . $v;
                $data[] = ['id' => $value['id'], 'version' => $title, 'version_num' => $value['id'] . '_' . $k];
            }
        }
        return json(['list' => $data, 'total' => count($data)]);
    }

}
