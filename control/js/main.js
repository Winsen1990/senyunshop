/**
 * Created by araolin on 2015/7/13.
 */
/*  头部用户注销下拉框 */
$(function(){
    $('a.navbar-member').click(function(){
        var showValue = $('#dropdown-menu').css('display'); //返回到id = show的div的display的值
        if(showValue == "" || showValue == "none"){
            $('#dropdown-menu').css('display', 'block');
            $('.navbar-right').addClass('open');
        }else if(showValue == "block"){
            $('#dropdown-menu').css('display', 'none');
            $('.navbar-right').removeClass('open');
        }
    })
})
/*  END 头部用户注销下拉框 */

//    $(function(){
//        $("ul.submenu").hide();
//        var urla = window.location.href;
//        tmp = urla.split("/");
//        wza = tmp[tmp.length - 1];
//        wza = wza.split("?")[0];
//        $("#sidebar ul li a").each(function () {
//            var href = $(this).attr("href");
//            if ($(this).attr("href") == wza) {
//                $(this).addClass("current");
//            }else {
//                $(this).removeClass("current");
//
//            }   });
//        $("span.nav-top-item").click(function(){
//            $("span.nav-top-item").css({"background-color":"#293038","color":"#aeb9c2"})
//            $(this).css({"background-color":"#22282e","color":"#ffffff"});
//            $(this).parent().find('ul li').removeClass("menu_chioce");
//            $(".menu_chioce").parent().slideUp(200);
//            $(this).parent().find('ul.submenu').slideToggle();
//            $(this).parent().find('ul li').addClass("menu_chioce");
//        });
//    })

/* 侧栏导航条 */
function menu1(dx, dx1, dx3) {
    var urla = window.location.href;
    tmp = urla.split("/");
    temp = tmp[tmp.length - 1];
    wza = temp.split("?")[0];
    if( temp.split("?")[1] ) {
        param = temp.split("?")[1];
        params = param.split("&");
        console.log(param);

        if( isNaN(params[1]) ) {
            console.log(params[0]);
            wza += '?' + params[0];
        } else {
            for( var i = 0; i < params.length; i++ ) {
                temp = params[i].split("=");
                if( temp[0] == 'act' ) {
                    wza += '?' + params[i];
                }
            }
        }
    }

    $(dx).slideDown();
    $("a.nav-top-item").removeClass("hover");
    $(dx3).addClass("hover").siblings().removeClass("hover");
    if( dx == '') {
        $("#sidebar ul li a").each(function () {
            var href = $(this).attr("href");
            if ($(this).attr("href") == wza) {
                $(this).addClass("current");
            }else {
                $(this).removeClass("current");
            }
        })
    } else {
        $("#sidebar ul.submenu li a").each(function () {
            var href = $(this).attr("href");
            if ($(this).attr("href") == wza) {
                $(this).addClass("current");
            }else {
                $(this).removeClass("current");
            }
        })
    }
    /*
    if( $('#sidebar').height() < 700 ) {
        $('#sidebar').css('height', '720px');
    }
    */

}
/* END 侧栏导航条 */
/* tab切换 */
$(function() {

    //Default Action
    $(".tab_content").hide(); //Hide all content
    $("ul.tabs li:first").addClass("active").show(); //Activate first tab
    $(".tab_content:first").show(); //Show first tab content

    //On Click Event
    $("ul.tabs li").click(function() {
        $("ul.tabs li").removeClass("active"); //Remove any "active" class
        $(this).addClass("active"); //Add "active" class to selected tab
        $(".tab_content").hide(); //Hide all tab content
        var activeTab = $(this).find("a").attr("href"); //Find the rel attribute value to identify the active tab + content
        $(activeTab).fadeIn(); //Fade in the active content
        return false;
    });
});
/* END tab切换*/
/* 自动发布 */
$('input[name=isAutoPublish]').change(function(){
    if( $(this).val() == '1' ) {
        $('input[name="publishTime"]').removeAttr('disabled');
    } else {
        $('input[name="publishTime"]').attr('disabled', 'disabled');
    }
});
/* END 自动发布 */
/* 型号是否一致 */
$('input[name=no_diff]').change(function(){
    if( $(this).val() == '1' ) {
        $('#size').css('display', 'block');
        $('#size-detail').css('display', 'none');
    } else {
        $('#size').css('display', 'none');
        $('#size-detail').css('display', 'block');
    }
});
/* END 型号是否一致 */

/* 解决ie6/7/8/9不支持placeholder */
var JPlaceHolder = {
    //检测
    _check : function(){
        return 'placeholder' in document.createElement('input');
    },
    //初始化
    init : function(){
        if(!this._check()){
            this.fix();
        }
    },
    //修复
    fix : function(){
        jQuery(':input[placeholder]').each(function(index, element) {
            var self = $(this), txt = self.attr('placeholder');
            self.wrap($('<div></div>').css({position:'relative', zoom:'1', border:'none', background:'none', padding:'none', margin:'none'}));
            var pos = self.position(), h = self.outerHeight(true), paddingleft = self.css('padding-left');
            var holder = $('<span></span>').text(txt).css({position:'absolute', left:pos.left, top:pos.top, height:h, lienHeight:h, paddingLeft:paddingleft, color:'#aaa'}).appendTo(self.parent());
            self.focusin(function(e) {
                holder.hide();
            }).focusout(function(e) {
                if(!self.val()){
                    holder.show();
                }
            });
            holder.click(function(e) {
                holder.hide();
                self.focus();
            });
        });
    }
};
//执行
jQuery(function(){
    JPlaceHolder.init();
});
/* END 解决ie6/7/8/9不支持placeholder */
/* 管理员权限全选切换 */
$(function(){
    $('.purview').click(function(){
        var temp = $(this).val();
        if( $(this).is(':checked') ) {
            $('.' + temp).attr('checked', 'checked');
        } else {
            $('.' + temp).removeAttr('checked');
        }
    });

    $('.sub-purview').click(function(){
        var temp = $(this).attr('data-parent');
        if( $(this).is(':checked') ) {
            $('#' + temp).attr('checked', 'checked');
        } else {
            var still_checked = false;
            $('.' + temp).each(function(){
                if( $(this).is(':checked') ) {
                    still_checked = true;
                }
            });
            if( !still_checked ) {
                $('#' + temp).removeAttr('checked');
            }
        }
    });

    $('#all').click(function(){
        if( $(this).is(':checked') ) {
            $('input').attr('checked', 'checked');
        } else {
            $('input').removeAttr('checked');
        }
    });
});
/* END 管理员权限全选切换 */

/* 添加和编辑导航条表单验证 */
$(document).ready(function() {
    var validator = $("#navForm").validate({
        rules: {
            menuName: {
                required: true,
                maxlength: 32
            },
            menuUrl: {
                required: true,
                //url: true
            },
            menuSort:{
                number: true
            }
        },
        messages: {
            menuName: {
                required: "菜单名称不能为空！",
                maxlength: jQuery.validator.format("请输入一个长度最多是 {0} 的字符串"),
                remote: jQuery.validator.format("已经输入长度 {0}")
            },
            menuUrl: {
                required: "菜单链接不能为空！",
                //url: "请输入合法的网址！"
            },
            menuSort: {
                number: "请输入合法的数字！"
            }
        }
    });
});
/* END 添加和编辑导航条表单验证 */

/* 添加和编辑资讯分类表单验证 */
    $(document).ready(function() {
        var validator = $("#sectionForm").validate({
            rules: {
                articleCatName: {
                    required: true,
                    maxlength: 32
                },
                keywords: {
                    required: true
                },
                description:{
                    maxlength: 140
                },
                order_view: {
                    number: true
                }
            },
            messages: {
                articleCatName: {
                    required: "栏目名称不能为空！",
                    maxlength: jQuery.validator.format("请输入一个长度最多是 {0} 的字符串"),
                    remote: jQuery.validator.format("已经输入长度 {0}")
                },
                keywords: {
                    required: "分类关键词不能为空！"
                },
                description: {
                    maxlength: jQuery.validator.format("请输入一个长度最多是 {0} 的字符串")
                },
                order_view: {
                    number: "请输入合法的数字！"
                }
            }
        });
    });
/* END 添加和编辑资讯分类表单验证 */

/* 添加和编辑用户表单验证 */
$(document).ready(function() {
    var validator = $("#adminForm").validate({
        rules: {
            userName: {
                required: true,
                maxlength: 32,
                minlength: 2
            },
            password: {
                required: true,
                minlength: 5
            },
            screenName:{
                required: true,
                maxlength: 32
            },
            email: {
                required: true,
                email: true
            },
            phone: {
                required: true,
                number: true,
                minlength: 11,
                maxlength: 11
            }
        },
        messages: {
            userName: {
                required: "用户账号不能为空！",
                maxlength: jQuery.validator.format("账号长度最多是 {0} "),
                minlength: jQuery.validator.format("账号长度最少是 {0} "),
                remote: jQuery.validator.format("已经输入长度 {0}")
            },
            password: {
                required: "密码不能为空！",
                minlength: jQuery.validator.format("密码长度最少是 {0} ")
            },
            screenName: {
                required: "昵称不能为空！",
                maxlength: jQuery.validator.format("请输入一个长度最多是 {0} 的字符串")
            },
            email:{
                required: "邮箱地址不能为空！",
                email: "请填写合法的邮箱地址！"
            },
            phone: {
                required: "手机号码不能为空！",
                number: "请填写合法的手机号码！",
                minlength: "请填写合法的手机号码！",
                maxlength: "请填写合法的手机号码！"
            }
        }
    });
});
/* END 添加和编辑用户表单验证 */

/* 添加和编辑用户组表单验证 */
$(document).ready(function() {
    var validator = $("#roleForm").validate({
        rules: {
            name: {
                required: true,
                maxlength: 32
            }
        },
        messages: {
            name: {
                required: "用户组名称不能为空！",
                maxlength: jQuery.validator.format("请输入一个长度最多是 {0} 的字符串！"),
                remote: jQuery.validator.format("已经输入长度 {0}！")
            }
        }
    });
});
/* END 添加用户组表单验证 */

/* 添加和编辑产品分类表单验证 */
$(document).ready(function() {
    var validator = $("#categoryForm").validate({
        rules: {
            categoryName: {
                required: true,
                maxlength: 32
            },
            keywords: {
                required: true
            },
            description:{
                maxlength: 140
            }
        },
        messages: {
            categoryName: {
                required: "账号不能为空！",
                maxlength: jQuery.validator.format("请输入一个长度最多是 {0} 的字符串"),
                remote: jQuery.validator.format("已经输入长度 {0}")
            },
            keywords: {
                required: "分类关键词不能为空！"
            },
            description: {
                maxlength: jQuery.validator.format("请输入一个长度最多是 {0} 的字符串")
            }
        }
    });
});
/* END 添加和编辑产品分类表单验证 */

/* 系统设置表单验证 */
$(document).ready(function() {
    var validator = $("#sysconfForm").validate({
        rules: {
            siteName: {
                required: true,
                maxlength: 32
            },
            siteUrl: {
                required: true,
                url: true
            },
            logoUrl: {
                required: true,
                url: true
            },
            keywords: {
                required: true
            },
            description:{
                required: true,
                maxlength: 140
            },
            owner: {
                required: true,
                maxlength: 32
            },
            icp: {
                //required: true,
                maxlength: 32
            }
        },
        messages: {
            siteName: {
                required: "站点名称不能为空！",
                maxlength: jQuery.validator.format("请输入一个长度最多是 {0} 的字符串"),
                remote: jQuery.validator.format("已经输入长度 {0}")
            },
            siteUrl: {
                required: "站点地址不能为空！",
                url: "请输入合法的网址！"
            },
            logoUrl: {
                required: "logo地址不能为空！",
                url: "logo地址有误，请确认地址包含http://"
            },
            keywords: {
                required: "分类关键词不能为空！"
            },
            description: {
                required: "描述不能为空",
                maxlength: jQuery.validator.format("请输入一个 长度最多是 {0} 的字符串")
            },
            owner:{
                required: "网站主体不能为空！",
                maxlength: jQuery.validator.format("请输入一个 长度最多是 {0} 的字符串")
            },
            icp: {
                //required: "ICP备案号不能为空！",
                maxlength: jQuery.validator.format("账号长度最多是 {0} ")
            }
        }
    });
});
/* END 系统设置表单验证 */
