define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'template', 'adminlte'], function ($, undefined, Backend, Table, Form, Template) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: '/admin/cms/archives/index',
                    table: 'archives',
                },
            });



            //$(function(){
            //    $("#page-search-form").on("submit", function(){
            //        var kw = $('#keyword').val();
            //        kw = $.trim(kw);
            //        if (!kw) {
            //            Toastr.error("請輸入搜索的關鍵詞！");
            //            $('#keyword').focus();
            //            return false;
            //        }
            //        var type = $('#select').val();
            //        var url = window.location.toString();
            //        var features = url.split("#features/")[1];
            //
            //        //当前内文搜索
            //        if (type == 1 && features && bookViewType != 'pic') {
            //           var left_page = '#page_' + (features - 1);
            //           var left_page_html = $(left_page).text();
            //            if (left_page_html) {
            //                left_page_html = left_page_html.replace(/<[^>]+>/g,"");
            //                var re = new RegExp(kw,"g");
            //                left_page_html = left_page_html.replace(re, '<span class="choose_text">' + kw + '</span>');
            //                $(left_page).html(left_page_html);
            //            }
            //            if (bookViewType == 'text') {
            //                var right_page = '#page_' + features;
            //                var right_page_html =$(right_page).text();
            //                if (right_page_html != 'undefined') {
            //                    right_page_html = right_page_html.replace(/<[^>]+>/g,"");
            //                    var re = new RegExp(kw,"g");
            //                    right_page_html = right_page_html.replace(re, '<span class="choose_text">' + kw + '</span>');
            //                    $(right_page).html(right_page_html);
            //                }
            //            }
            //            return false;
            //        }
            //
            //
            //        if (features) {
            //            $('#features_num').val(features);
            //        }
            //        return true;
            //    })
            //})






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


