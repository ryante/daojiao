$(function () {
    function AddFavorite(sURL, sTitle) {
        if (/firefox/i.test(navigator.userAgent)) {
            return false;
        } else if (window.external && window.external.addFavorite) {
            window.external.addFavorite(sURL, sTitle);
            return true;
        } else if (window.sidebar && window.sidebar.addPanel) {
            window.sidebar.addPanel(sTitle, sURL, "");
            return true;
        } else {
            var touch = (navigator.userAgent.toLowerCase().indexOf('mac') != -1 ? 'Command' : 'CTRL');
            alert('请使用 ' + touch + ' + D 添加到收藏夹.');
            return false;
        }
    }
    // 点击收藏
    $(".addbookbark").attr("rel", "sidebar").click(function () {
        return !AddFavorite(window.location.href, $(this).attr("title"));
    });
    // 点赞
    $(document).on("click", ".product-like-wrapper > a", function () {
        var ids = JSON.parse(localStorage.getItem("vote"));
        ids = $.isArray(ids) ? ids : [];
        var id = $(this).data("id");
        if ($.inArray(id, ids) > -1) {
            alert("你已经投过票了");
            return false;
        }
        $.ajax({
            type: "post",
            data: $(this).data(),
            dataType: 'json',
            success: function (ret) {
                if (ret.code === 1) {
                    ids.push(id);
                    $(".like-bar-wrapper .bar span").css("width", ret.data.likeratio + "%");
                    $(".like-bar-wrapper .num i").text(ret.data.likes);
                    $(".like-bar-wrapper .num span").text(ret.data.dislikes);
                    localStorage.setItem("vote", JSON.stringify(ids));
                }
            }
        });
    });
    // 回到顶部
    $('#back-to-top').on('click', function (e) {
        e.preventDefault();
        $('html,body').animate({
            scrollTop: 0
        }, 700);
    });
    // 如果是PC则移除navbar的dropdown点击事件
    if (!/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|Mobi/i.test(navigator.userAgent)) {
        $("#navbar-collapse [data-toggle='dropdown']").removeAttr("data-toggle");
    }
    $.fn.typeahead.Constructor.prototype.click = function (e) {

    };
    // 搜索自动完成
    $("#searchinput").typeahead({
        onSelect: function (item) {
            location.href = item.value.url;
        },
        grepper: function (data) {
            return data;
        },
        render: function (items) {
            var that = this;
            items = $(items).map(function (i, item) {
                var i = $(that.options.item);
                i.data("value", item);
                i.find('a').attr('href', item.url);
                i.find('a').html('<h5>' + item.title + '</h5>');
                return i[0];
            });
            items.first().addClass('active');
            that.$menu.css("width", "250px");
            that.$menu.html(items);
            return that;
        },
        alignWidth: false,
        ajax: {
            url: $("#searchinput").data("typeahead-url"),
            valueField: "url",
            method: "post",
            dataType: "JSON",
            preDispatch: function (query) {
                return {
                    search: query
                };
            },
            preProcess: function (data) {
                return data;
            }
        }
    });
    // 友言评论
    if ($("#uyan_frame").size() > 0) {
        var jsfile = $("<script type='text/javascript' src='http://v2.uyan.cc/code/uyan.js?uid=" + CMS.uyan_id + "'>");
        $("head").append(jsfile);
    }
    // 百度分享
    if ($(".bdsharebuttonbox").size() > 0) {
        window._bd_share_config = {"common": {"bdSnsKey": {}, "bdText": "", "bdMini": "2", "bdMiniList": false, "bdPic": "", "bdStyle": "0", "bdSize": "116"}, "share": {}};
        with (document)
            0[(getElementsByTagName('head')[0] || body).appendChild(createElement('script')).src = 'http://bdimg.share.baidu.com/static/api/js/share.js?v=89860593.js?cdnversion=' + ~(-new Date() / 36e5)];
    }
});