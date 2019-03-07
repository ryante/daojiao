define(['jquery', 'bootstrap', 'frontend', 'addtabs', 'adminlte', 'form'], function ($, undefined, frontend, undefined, AdminLTE, Form) {
    var Controller = {
        index: function () {
            var obj = $("button[href ~= '#collapseOne']");
            var highSearch = "進階搜索 <i class='glyphicon glyphicon-chevron-down'></i>";
            var normalSearch = "普通搜索 <i class='glyphicon glyphicon-chevron-up'></i>";
            obj.on('click', function(){
                if (obj.hasClass('collapsed')) {
                    obj.html(normalSearch);
                } else {
                    obj.html(highSearch);
                }
            })

            //全选、取消全选
            var $root = $(".search-item"), parentCheckBox = $root.find("[data-id]");
            parentCheckBox.on('change', function() {
                var _self = $(this);
                _self.parents(".search-item").find("[data-pid] input").each(function() {
                    $(this).prop("checked", _self[0].checked);
                })

            })

            $("#form").on("submit", function(){
                var kw = $('#keyword').val();
                if (!kw) {
                    Toastr.error("請輸入搜索的關鍵詞！");
                    $('#keyword').focus();
                    return false;
                }
                return true;
            })


        },

    };

    return Controller;
});