define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'template'], function ($, undefined, Backend, Table, Form, Template) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'cms/archives/index',
                    add_url: 'cms/archives/add',
                    edit_url: 'cms/archives/edit',
                    del_url: 'cms/archives/del',
                    multi_url: 'cms/archives/multi',
                    table: 'archives',
                },

            });
            $.extend($.fn.bootstrapTable.locales[Table.defaults.locale], {
                formatSearch: function () {
                    return __('Title');
                }
            });

            var table = $("#table");

            //在表格内容渲染完成后回调的事件
            table.on('post-body.bs.table', function (e, settings, json, xhr) {
                $(".btn-editone", this)
                    .off("click")
                    .removeClass("btn-editone")
                    .addClass("btn-addtabs")
                    .prop("title", __('Edit'));
            });

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                commonSearch: false,
                sortName: 'weigh',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), sortable: true, operate: false,},
                        {field: 'channel_id', title: __('Channel_id'), visible: false, operate: false,},
                        {field: 'channel.name', title: __('Channel'), operate: 'LIKE %...%',formatter: function (value, row, index) {
                            return '<a href="javascript:;" class="searchit" data-field="channel_id" data-value="' + row.channel_id + '">' + value + '</a>';
                        }
                        },
                        {field: 'title', title: __('Title'), align: 'left',operate: 'LIKE %...%', formatter: function (value, row, index) {
                            return value + '<br>' + Table.api.formatter.flag.call(this, row['flag'], row, index);
                        }
                        },
                        {field: 'image', title: __('Image'), operate: false,visible: false, formatter: Table.api.formatter.image},
                        {field: 'weigh', title: __('Weigh'), visible: false, operate: false},
                        {field: 'views', title: __('Views'), sortable: true, visible: false, operate: false,},
                        {field: 'addon.author', title: __('Author'),operate: 'LIKE %...%',},
                        {field: 'addon.publish_locate', title: __('Publish_locate'),operate: 'LIKE %...%',},
                        {field: 'addon.publish_place', title: __('Publish_place'),operate: 'LIKE %...%',},
                        {field: 'addon.version', title: __('Version'),operate:false,
                            formatter: function(value, row, index){
                                if (!value) {
                                    return '<a href="cms/book?archives='+ row.id + '&ref=addtabs " class="label label-success  addtabsit lineh " >查看</a>';
                                }
                                var arr = value.split(',');
                                var str = '';
                                $.each(arr,function(idx,item){
                                    var version = parseInt(idx) + 1;
                                    str = str +  '<a href="cms/book?archives='+ row.id + '&version=' + version + '&ref=addtabs"  class="label label-success addtabsit lineh"><i class="fa fa-tag"></i>&nbsp;&nbsp;' + item + ' </a><br/>';
                                });
                                return str;
                            }
                        },
                        {field: 'url', title: __('Url'), visible: false, operate: false,formatter: function(value, row, index){
                            return '<a href="' + value + '" target="_blank" class="btn btn-default btn-xs"><i class="fa fa-link"></i></a>';
                        }},
                        {field: 'createtime', title: __('Createtime'), visible: false, operate: false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), sortable: true, operate: false, formatter: Table.api.formatter.datetime},
                        {field: 'status', title: __('Status'), operate: false, formatter: Table.api.formatter.status},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
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
                    console.log(data);
                    console.log(data.selected);
                    var options = table.bootstrapTable('getOptions');
                    options.pageNumber = 1;
                    options.queryParams = function (params) {
                        params.filter = JSON.stringify(data.selected.length > 0 ? {channel_id: data.selected.join(",")} : {});
                        params.op = JSON.stringify(data.selected.length > 0 ? {channel_id: 'in'} : {});
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
                        "channel": {
                            "icon": "fa fa-th",
                        },
                        "list": {
                            "icon": "fa fa-list",
                        },
                        "link": {
                            "icon": "fa fa-link",
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
            //$(document).on('click', '.lineh', function () {
            //    var channel = $(this).data('channel');
            //    var version = $(this).data('version');
            //    var url = "book/index/channel/" + channel;
            //    if (version) {
            //        url = url + '/version/' + version;
            //    }
            //    window.location.href = url;
            //});


            $(document).on('click', '.btn-move', function () {
                var ids = Table.api.selectedids(table);
                Layer.open({
                    title: __('Move'),
                    content: Template("channeltpl", {}),
                    btn: [__('Move')],
                    yes: function (index, layero) {
                        var channel_id = $("select[name='channel']", layero).val();
                        if (channel_id == 0) {
                            Toastr.error(__('Please select channel'));
                            return;
                        }
                        Fast.api.ajax({
                            url: "cms/archives/move/ids/" + ids.join(","),
                            type: "post",
                            data: {channel_id: channel_id},
                        }, function () {
                            table.bootstrapTable('refresh', {});
                            Layer.close(index);
                        });
                    },
                    success: function (layero, index) {
                    }
                });
            });
        },
        recyclebin: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    'dragsort_url': ''
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: 'cms/archives/recyclebin',
                pk: 'id',
                sortName: 'weigh',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'title', title: __('Title'), align: 'left'},
                        {field: 'image', title: __('Image'), operate: false, formatter: Table.api.formatter.image},
                        {field: 'deletetime', title: __('Deletetime'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', width: '130px', title: __('Operate'), table: table, events: Table.api.events.operate,
                            buttons: [
                                {name: 'Restore', text: __('Restore'), classname: 'btn btn-xs btn-info btn-restoreit', icon: 'fa fa-rotate-left', url: 'cms/archives/restore'},
                                {name: 'Destroy', text: __('Destroy'), classname: 'btn btn-xs btn-danger btn-destroyit', icon: 'fa fa-times', url: 'cms/archives/destroy'}
                            ],
                            formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            $(document).on('click', '.btn-destroyall', function () {
                var that = this;
                Layer.confirm(__('Are you sure you want to truncate?'), function () {
                    Fast.api.ajax($(that).attr("href"), function () {
                        table.bootstrapTable('refresh');
                    });
                });
                return false;
            });
            $(document).on('click', '.btn-restoreall,.btn-restoreit,.btn-destroyit', function () {
                Fast.api.ajax($(this).attr("href"), function () {
                    table.bootstrapTable('refresh');
                });
                return false;
            });
        },
        add: function () {
            $(document).on('change', '#c-channel_id', function () {
                Fast.api.ajax({url: 'cms/archives/get_channel_fields', data: {channel_id: $(this).val()}}, function (data) {
                    $("#extend").html(data.html);
                    Form.api.bindevent($("#extend"));
                    return false;
                });
            });
            $("#c-channel_id").trigger("change");
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
            Fast.api.ajax({url: 'cms/archives/get_channel_fields', data: {channel_id: $("#c-channel_id").val(), archives_id: $("#archive-id").val()}}, function (data) {
                $("#extend").html(data.html);
                Form.api.bindevent($("#extend"));
                return false;
            });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"), function () {
                    var obj = top.window.$("#nav ul li.active");
                    top.window.Toastr.success(__('Operation completed'));
                    top.window.$(".sidebar-menu a[url$='/cms/archives'][addtabs]").click();
                    top.window.$(".sidebar-menu a[url$='/cms/archives'][addtabs]").dblclick();
                    obj.find(".fa-remove").trigger("click");
                });
            },
        }
    };
    return Controller;
});

