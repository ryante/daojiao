define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'template'], function ($, undefined, Backend, Table, Form, Template) {
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
            Template.helper("Moment", Moment);


            //当内容渲染完成后
            table.on('post-body.bs.table', function (e, settings, json, xhr) {
                if (!archives) {
                    return ;
                }
                if (version) {
                    var idnum = parseInt(archives) * 10000 + parseInt(version);
                } else {
                    var idnum = archives;
                }
                var obj = $('#' + idnum + '_anchor');
                if (!obj.hasClass('jstree-clicked')) {
                    obj.click();
                } else {
                    archives = '';
                }
                //obj.click();
            });


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
                sortOrder: 'asc',
                commonSearch: false,
                templateView: true,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), operate: false},
                        {field: 'Literature', title: __('Literature'), operate: false, visible: false},
                        {field: 'library', title: __('Library'),  align: 'left', operate: false, /*searchList: $.getJSON('cms/book/searchlist'),*/ formatter: Table.api.formatter.label},
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
                        {field: 'weigh', title: __('Weigh'), operate: true},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ],
                queryParams: function (params) {
                    if (!archives) {
                        return params;
                    }
                    if (version) {
                        var idnum = parseInt(archives) * 10000 + parseInt(version);
                    } else {
                        var idnum = archives;
                    }
                    params.filter = JSON.stringify({literature: idnum});
                    params.op = JSON.stringify({literature: 'in'});
                    return params;
                },
            });


            // 为表格绑定事件
            Table.api.bindevent(table);

            require(['jstree'], function () {
                //全选和展开
                $(document).on("click", "#checkall", function () {
                    $("#channeltree").jstree($(this).prop("checked") ? "check_all" : "uncheck_all");
                });
                $(document).on("click", "#expandall", function () {
                    $("#channeltree").jstree($(this).prop("checked") ? "open_all" : "close_all");
                });
                $('#channeltree').on("changed.jstree", function (e, data) {
                    var options = table.bootstrapTable('getOptions');
                    options.pageNumber = 1;
                    options.queryParams = function (params) {
                        params.filter = JSON.stringify(data.selected.length > 0 ? {literature: data.selected.join(",")} : {});
                        params.op = JSON.stringify(data.selected.length > 0 ? {literature: 'in'} : {});
                        return params;
                    };
                    table.bootstrapTable('refresh', {});
                    return false;
                });
                $('#channeltree').jstree({
                    "themes": {
                        "stripes": true
                    },
                    "checkbox": {
                        "keep_selected_style": false,
                    },
                    "types": {
                        "page": {
                            "icon": "fa fa-folder-open",
                        },
                        "version": {
                            "icon": "fa fa-tags",
                        },
                        "disabled": {
                            "check_node": false,
                            "uncheck_node": false
                        }
                    },
                    'plugins': ["types", "checkbox"],
                    "core": {
                        "multiple": true,
                        'check_callback': true,
                        "data": Config.channelList
                    }
                });


            });


            require(['dragsort'], function () {
                //绑定拖动排序
                $("tbody", table).dragsort({
                    itemSelector: '.col-sm-4',
                    dragSelector: "a.btn-dragsort",
                    dragEnd: function () {
                        var data = table.bootstrapTable('getData');
                        var current = data[parseInt($(this).data("index"))];
                        var options = table.bootstrapTable('getOptions');
                        //改变的值和改变的ID集合
                        var ids = $.map($("tbody tr:visible", table), function (tr) {
                            return data[parseInt($(tr).data("index"))][options.pk];
                        });
                        var changeid = current[options.pk];
                        var pid = typeof current.pid != 'undefined' ? current.pid : '';
                        var params = {
                            url: table.bootstrapTable('getOptions').extend.dragsort_url,
                            data: {
                                ids: ids.join(','),
                                changeid: changeid,
                                pid: pid,
                                field: Table.config.dragsortfield,
                                orderway: options.sortOrder,
                                table: options.extend.table
                            }
                        };
                        Fast.api.ajax(params, function (data) {
                            table.bootstrapTable('refresh');
                        });
                    },
                    placeHolderTemplate: ""
                });
            });





        },
        add: function () {
            Controller.api.bindevent();
            //chk();
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

