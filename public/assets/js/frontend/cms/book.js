define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'cms/book/index',
                    add_url: 'cms/book/add',
                    edit_url: 'cms/book/edit',
                    del_url: 'cms/book/del',
                    multi_url: 'cms/book/multi',
                    table: 'book',
                }
            });

            $.extend($.fn.bootstrapTable.locales[Table.defaults.locale], {
                formatSearch: function () {
                    return __('Literature');
                }
            });
            var table = $("#table");

            //在普通搜索提交搜索前
            table.on('common-search.bs.table', function (event, table, params, query) {
                //这里可以对params值进行修改,从而影响搜索条件
                var arr = JSON.parse(params.filter);
                if (arr['version']) {
                    var tmp = arr['version'].split("_");
                    if (tmp[0] && tmp[1]) {
                        params.filter = JSON.stringify({literature: tmp[0], version: tmp[1]});
                        params.op = JSON.stringify({literature: '=', version: '='});
                        return params;
                    }
                }

                if (arr['literature']) {
                    params.filter = JSON.stringify({});
                    params.op = JSON.stringify({});
                    return params;
                }

                return params;
            });

            //在普通搜索渲染后
            table.on('post-common-search.bs.table', function (event, table) {
                $("input[name='version']").addClass("selectpage").data("source", "cms/book/selectversion").data("primaryKey", "version_num").data("field", "version").data("orderBy", "id desc");
                Form.events.cxselect($("form", table.$commonsearch));
                Form.events.selectpage($("form", table.$commonsearch));
            });



            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), operate: false},
                        {field: 'Literature', title: __('Literature'), operate: false, visible: false},
                        {field: 'library', title: __('Library'),  align: 'left', searchList: $.getJSON('cms/book/searchlist'), formatter: Table.api.formatter.label},
                        {field: 'archives.title', title: __('Literature'), operate: 'LIKE %...%', formatter: Table.api.formatter.search},
                        {field: 'version', title: __('Version'),
                            formatter: function(value, row, index){
                                if (value == 0 || row.version_name == '') {
                                    return '-';
                                }
                                return  row.version_name;
                            }
                        },
                        {field: 'image', title: __('Image'),  operate: false, formatter: Table.api.formatter.image},
                        {field: 'createtime', title: __('Createtime'),  operate: false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'),  formatter: Table.api.formatter.datetime, operate: 'BETWEEN', type: 'datetime', addclass: 'datetimepicker', data: 'data-date-format="YYYY-MM-DD"'},
                        {field: 'weigh', title: __('Weigh'), operate: false},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ],
            });



            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});