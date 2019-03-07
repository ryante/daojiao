<?php

namespace app\admin\model;

use think\Model;

class Channel extends Model
{

    // 表名
    protected $name = 'channel';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    protected $append = [
        'type_text',
        'status_text',
        'url'
    ];

    public function getUrlAttr($value, $data)
    {
        return addon_url('cms/channel/index', [':diyname' => $data['diyname']]);
    }

    protected static function init()
    {
        self::afterInsert(function ($row) {
            $pk = $row->getPk();
            $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
        });
        self::afterDelete(function($row) {
            Archives::destroy(['channel_id' => $row['id']]);
            static $tree;
            if (!$tree)
            {
                $tree = \fast\Tree::instance();
                $tree->init(collection(Channel::order('weigh desc,id desc')->field('id,parent_id,name,type,status')->select())->toArray(), 'parent_id');
            }
            $child_ids = $tree->getChildrenIds($row['id']);
            if ($child_ids)
            {
                Channel::destroy(function($query) use($child_ids) {
                    $query->where('id', 'in', $child_ids);
                });
            }
        });
        self::afterUpdate(function($row) {
            
        });
    }

    public static function getTypeList()
    {
        return ['list' => __('List'), 'channel' => __('Channel'), 'link' => __('Link')];
    }

    public static function getStatusList()
    {
        return ['normal' => __('Normal'), 'hidden' => __('Hidden')];
    }

    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : $data['type'];
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : $data['status'];
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function model()
    {
        return $this->belongsTo('Modelx', 'model_id')->setEagerlyType(0);
    }

}
