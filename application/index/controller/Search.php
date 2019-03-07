<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use app\index\model\Book;

class Search extends Frontend
{

    protected $layout = '';

    public function _initialize()
    {
        parent::_initialize();
    }

    public function index() {
        $kw = input("get.keyword/s",'','trim');
        if (empty($kw)) {
            $this->error("關鍵詞不能為空","/");
        }
        $libIdsArr =  input("get.lib_ids/a");
        $filedsArr =  input("get.fields/a");
        $curentPage = input("get.page/i", 1);

        $this->view->assign('keyword', $kw);
        $searchFields = controller('index')->getSearchFields($this->libraries, $libIdsArr, $filedsArr);

        $options = [
            "path" => request()->baseUrl(),
            "query" => [
                'keyword' => $kw,
                'lib_ids' => $libIdsArr,
                'fields' => $filedsArr,
            ],
        ];
        $pageLists = $this->search($filedsArr, $searchFields, $kw, $curentPage, $options);

        $pageRender = $pageLists->render();
        $this->view->assign('title','搜索');
        $this->view->assign('searchFields', $searchFields);
        $this->view->assign('list', $pageLists);
        $this->view->assign('page', $pageRender);
        return $this->view->fetch('index');
    }

    public function pageSearch() {

        $kw = input("get.keyword/s",'','trim');
        if (empty($kw)) {
            $this->error("關鍵詞不能為空","/");
        }

        $library =  input("get.library/i");
        $literature =  input("get.literature/i");
        $version =  input("get.version/i");
        $bookid = input("get.bookid");
        $range = input("get.range/i", 1);
        $curentPage = input("get.page/i", 1);

        $options = [
            "path" => request()->baseUrl(),
            "query" => [
                'keyword' => $kw,
                'library' => $library,
                'literature' => $literature,
                'version' => $version,
                'bookid' => $bookid,
                'range' => $range,
            ],
        ];

        $pageLists = $this->pageContentSearch($options, $curentPage);
        $pageRender = $pageLists->render();

        $archivesInfo = db("archives")->field("title,append_table")->where("id={$literature}")->find();
        $libInfo = db("channel")->field("name")->where("id=$library")->find();
        $bookInfo['archive_title'] = $archivesInfo['title'];
        $bookInfo['lib_title'] = $libInfo['name'];
        if (!empty($version)) {
            $versionInfo = db($archivesInfo['append_table'])->field("version")->where("id={$literature}")->find();
            $versionArr =json_decode($versionInfo['version'], true);
            $bookInfo['version_title'] = $versionArr[$version];
        }
        $this->view->assign('title','搜索');
        $this->view->assign('list', $pageLists);
        $this->view->assign('page', $pageRender);
        $this->view->assign('keyword', $kw);
        $this->view->assign('bookInfo', $bookInfo);
        return $this->view->fetch('page_search');
    }

    public function pageContentSearch($options,  $page = 1) {
        $limit = "";
        switch($options['query']['range']) {
            case 1:
                $where = "id in ({$options["query"]["bookid"]}) and content LIKE '%{$options["query"]["keyword"]}%'";
                break;
            case 2:
                $where = "literature={$options['query']['literature']} and content LIKE '%{$options["query"]["keyword"]}%'";
                $where .= empty($options['query']['version']) ? "" : " and version={$options['query']['version']} ";
                break;
            case 3:
                $where = " library={$options["query"]["library"]}  and content LIKE '%{$options["query"]["keyword"]}%' ";
                break;
        }

        $lists = db()->query("select * from fa_book where {$where} order by weigh asc {$limit}");
        $libList = db()->query("select library,literature,version from fa_book where {$where} group by library,literature,version order by weigh asc {$limit}");

        if ($options['query']['range'] == 1) {
            $total = count($lists);
        } else {
            $total = db()->query("select count('id') total from fa_book where {$where}");
            $total = $total[0]['total'];
        }


        if (!empty($lists)) {
            foreach($lists as $key => &$value) {
                //找出该EBOOK的页面排序
                $bookAll = db('book')->where("literature={$value['literature']} and version={$value['version']} ")->order("weigh desc,id desc")->column("id");
                $pageSort = array_search($value['id'], $bookAll);
                $value['page_sort'] = $pageSort * 2 + 2;

                $archivesInfo = db("archives")->field("title,append_table")->where("id={$value['literature']}")->find();
                $value['archive_title'] = $archivesInfo['title'];
                $libInfo = db("channel")->field("name")->where("id={$value['library']}")->find();
                $value['lib_title'] = $libInfo['name'];
                if (!empty($value['version'])) {
                    $versionInfo = db($archivesInfo['append_table'])->field("version")->where("id={$value['literature']}")->find();
                    $versionArr =json_decode($versionInfo['version'], true);
                    $value['version_title'] = $versionArr[$value['version']];
                }
                $value['content'] = trim(strip_tags($value['content']));
                $value['content'] = str_replace($options['query']['keyword'], "<code>{$options['query']['keyword']}</code>",  $value['content']);
            }
        }

        //按文献分类显示
        foreach ($libList as $k => $v) {
            foreach ($lists as $i => $j) {
                if ($j['literature'] == $v['literature'] && $j['version'] == $v['version']) {
                    $libList[$k]['lib_title'] = $j['lib_title'];
                    $libList[$k]['archive_title'] = $j['archive_title'];
                    $libList[$k]['version_title'] = $j['version_title'];
                    $libList[$k]['book_content'][] = $j;
                }
            }
        }
        $verPage = config("paginate.list_rows");
        $pagelist = new \think\paginator\driver\Bootstrap($libList, $verPage, $page, $total, false, $options);
        return $pagelist;
    }

    public function search($fieldsArr = '', $searchFields, $keyword, $page = 1, $options) {
        if (empty($fieldsArr) || count($fieldsArr) < 1) {
            $range = "all";
            $sqlArr = $this->buildSearchAllSql($searchFields, $keyword);
        } else {
            $range = "area";
            $sqlArr = $this->buildSearchAreaSql($searchFields, $fieldsArr, $keyword);
        }
        $totalRes = db()->query($sqlArr['count_sql']);
        $total = 0;
        foreach($totalRes as $key => $val) {
            $total += $val['total'];
        }
        $verPage = config("paginate.list_rows");
        $start = $page > 1 ? ($page - 1 ) * $verPage : 0;
        $lists = db()->query($sqlArr['query_sql'] . " limit {$start},{$verPage}");
        $this->getSearchDetailInfo($lists, $searchFields, $keyword, $range);
        $pagelist = new \think\paginator\driver\Bootstrap($lists, $verPage, $page, $total, false, $options);
        return $pagelist;
    }


    public function buildSearchAllSql($searchFields, $keyword) {
        $querySql = "";
        $countSql = "";
        end($searchFields);
        $searchFieldsLastKey = key($searchFields);

        foreach($searchFields as $key => $val) {
            if (empty($val['fields']) || count($val['fields']) < 1) {
                continue;
            }
            $sqlTmp = '';
            $countSqlTmp = "select count(DISTINCT a.`id`) total,'{$val['append_table']}' as `lib` from fa_archives a INNER JOIN {$val['append_table']} b on a.id=b.id left JOIN fa_book c on c.literature=a.id INNER JOIN fa_channel d on d.id=a.channel_id where a.title like '%{$keyword}%' or a.description like '%{$keyword}%' or  b.version like '%{$keyword}%'  or  c.content like '%{$keyword}%'";
            $querySqlTmp = "select a.id,a.title,a.description,a.append_table,b.version,c.version as book_version,d.model_id,d.name as library from fa_archives a INNER JOIN {$val['append_table']} b on a.id=b.id left JOIN fa_book c on c.literature=a.id INNER JOIN fa_channel d on d.id=a.channel_id where a.title like '%{$keyword}%' or a.description like '%{$keyword}%' or  b.version like '%{$keyword}%' or  c.content like '%{$keyword}%'  ";
            end($val['fields']);
            $fieldsLastKey = key($val['fields']);
            foreach($val['fields'] as $k => $v) {
                $sqlTmp .= " or b.{$v['name']} like '%{$keyword}%' ";
                $qySqlTmp = $k == $fieldsLastKey ? $sqlTmp . " group by a.id,c.version " : $sqlTmp;
            }
            $sqlTmp = $key == $searchFieldsLastKey ? $sqlTmp : $sqlTmp . " union ";
            $qySqlTmp = $key == $searchFieldsLastKey ? $qySqlTmp : $qySqlTmp . " union ";
            $querySql .= $querySqlTmp . $qySqlTmp;
            $countSql .= $countSqlTmp . $sqlTmp;
        }
        return ['count_sql' => $countSql, "query_sql" => $querySql];
    }

    public function buildSearchAreaSql($searchFields, $fieldsArr, $keyword) {
        $querySql = "";
        $countSql = "";
        end($fieldsArr);
        $searchFieldsLastKey = key($fieldsArr);

        $libId = array_keys($fieldsArr);
        foreach($searchFields as $key => $val) {
            if (empty($val['fields']) || count($val['fields']) < 1 || !in_array($key, $libId)) {
                continue;
            }
            $sqlTmp = '';
            $countSqlTmp = "select count(DISTINCT a.`id`) total,'{$val['append_table']}' as `lib` from fa_archives a INNER JOIN {$val['append_table']} b on a.id=b.id left JOIN fa_book c on c.literature=a.id INNER JOIN fa_channel d on d.id=a.channel_id where a.title like '%{$keyword}%' or a.description like '%{$keyword}%' or  b.version like '%{$keyword}%'";
            $querySqlTmp = "select a.id,a.title,a.description,a.append_table,b.version,c.version as book_version,d.model_id,d.name as library from fa_archives a INNER JOIN {$val['append_table']} b on a.id=b.id left JOIN fa_book c on c.literature=a.id INNER JOIN fa_channel d on d.id=a.channel_id where a.title like '%{$keyword}%' or a.description like '%{$keyword}%' or  b.version like '%{$keyword}%'  ";

            foreach($fieldsArr[$key] as $k => $v) {
                $sqlTmp .= " or b.{$v} like '%{$keyword}%' ";
                $qySqlTmp = $k == count($fieldsArr[$key]) - 1 ? $sqlTmp . " group by a.id,c.version " : $sqlTmp;
            }
            $sqlTmp = $key == $searchFieldsLastKey ? $sqlTmp : $sqlTmp . " union ";
            $qySqlTmp = $key == $searchFieldsLastKey ? $qySqlTmp : $qySqlTmp . " union ";
            $querySql .= $querySqlTmp . $qySqlTmp;
            $countSql .= $countSqlTmp . $sqlTmp;
        }
        return ['count_sql' => $countSql, "query_sql" => $querySql];
    }

    public function getSearchDetailInfo(&$lists, $searchFields, $kw, $range = "all") {
        foreach($lists as $key => $val) {
            $table = str_replace('fa_', '', $searchFields[$val['model_id']]['append_table']);//去掉表前缀
            $fieldsTmp = $searchFields[$val['model_id']]['fields'];
            if (empty($fieldsTmp) || count($fieldsTmp) < 1) {
                continue;
            }
            $fieldsStr = '';
            foreach($fieldsTmp as $k => $v) {
                if ($v['name'] == 'version') {
                    continue;
                }
                //区域搜索
                if ($range == 'area') {
                    if (!$v['checked']) {
                        continue;
                    }
                }
                $fieldsStr .= $k == 0 ? " `{$v['name']}` as '{$v['title']}'" : ", `{$v['name']}` as '{$v['title']}'";
                $fieldsStr = trim($fieldsStr, ',');
            }
            $fieldsInfo = db($table)->field($fieldsStr)->where("id={$val['id']}")->find();
            if ($range == 'all') {
                $fieldsInfo['描述'] = $lists[$key]['description'];
            }

            if (!empty($fieldsInfo)) {
                foreach($fieldsInfo as $i => &$j) {
                    if (stripos($j, $kw) !== false) {
                        $j = str_replace($kw, "<code>{$kw}</code>", $j);
                    }
                }
            }

            $lists[$key]['fields_info'] = $fieldsInfo;
            $lists[$key]['version'] = $val['version'] ? json_decode($val['version'], true) : '';


            $lists[$key]['version_name'] =  $val['version'] && $val['book_version'] ? $lists[$key]['version'][$val['book_version']] : '';
            $lists[$key]['title'] = str_replace($kw, "<code>{$kw}</code>", $lists[$key]['title']);
            $lists[$key]['version_name'] = str_replace($kw, "<code>{$kw}</code>", $lists[$key]['version_name']);

            if ($range = 'all') {
                //todo 页内容搜索
                $where = $val['book_version'] ? "literature={$val['id']} and version={$val['book_version']} and content like '%{$kw}%'" : "literature={$val['id']} and content like '%{$kw}%'";
                $ebook = db("book")->field("id,literature,version,content")->where($where)->select();
                if (empty($ebook)) {
                    $lists[$key]['book_content'] = '';
                    continue;
                }

                //找出该EBOOK的页面排序
                $bookAll = db('book')->where("literature={$val['id']} and version={$val['book_version']} ")->order("weigh desc,id desc")->column("id");

                $lists[$key]['book_content'] = $ebook;
                foreach( $lists[$key]['book_content'] as $key => &$value) {
                    //找出该EBOOK的页面排序
                    $pageSort = array_search($value['id'], $bookAll);
                    $value['page_sort'] = $pageSort * 2 + 2;
                    $value['content'] = strip_tags($value['content']);
                    $value['content'] = trim(str_replace(["\n", "\r"], '',$value['content']));
                    $value['content'] =  str_replace($kw, "<code>{$kw}</code>", $value['content']);
                }
            }
        }

    }

}
