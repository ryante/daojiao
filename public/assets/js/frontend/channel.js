define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'adminlte'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: '/index/channel/index',
                    table: 'channel',
                }
            });


            // 初始化表格
        },

    };
    return Controller;
});