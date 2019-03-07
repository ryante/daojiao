define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'template', 'adminlte'], function ($, undefined, Backend, Table, Form, Template) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: '/index/archives/index',
                    table: 'archives',
                },

            });




        },

    };
    return Controller;
});