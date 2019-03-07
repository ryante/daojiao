<?php

namespace app\admin\model;

use app\admin\model\Channel;
use app\admin\model\Archives;
use think\Model;


class Book extends Model
{
    // 表名
    protected $name = 'book';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 追加属性
    protected $append = [

    ];
    

    protected static function init()
    {
        self::afterInsert(function ($row) {
            $pk = $row->getPk();
            $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
        });
    }



    public function archives()
    {
        return $this->belongsTo('Archives', 'literature')->setEagerlyType(0);
    }



    







}
