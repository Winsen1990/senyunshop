var PATH_ACTIVITY="activity/shake/";
var global_a = null;
if(!Array.prototype.indexOf){
    Array.prototype.indexOf=function(b){
        var a=this.length;
        var c=Number(arguments[1])||0;
        c=(c<0)?Math.ceil(c):Math.floor(c);
        if(c<0){c+=a}
        for(;c<a;c++){
            if(c in this&&this[c]===b){
                return c
            }
        }

        return -1
    }
}

(function(a,b){
    global_a = a;
    window.sessionStorage.setItem("loginkey", 1);
    b.fn.scroll_subtitle=function(){
        return this.each(function(){
            var c=b(this);
            if(c.children().length>1){
                a.setInterval(function(){
                    var e=c.children(),d=b(e[0]);
                    d.slideUp(2000,function(){
                        d.remove().appendTo(c).show()
                    })
                },
                    5000)
            }
        })
    };

    b.preloadImages=function(c,j){
        if(b.isArray(c)){
            var e=c.length;
            if(e>0){
                var h=0,d=function(){
                    h++;
                    if(h>=e){
                        if(typeof j=="function"){
                            a.setTimeout(j,100)
                        }
                    }
                };

                for(var g=0;g<e;g++){
                    var f=new Image();
                    f.onload=d;
                    f.onerror=d;
                    f.src=c[g]
                }
            }
        }
    };

    b.getUrlParam=function(c){
        var d=new RegExp("(^|&)"+c+"=([^&]*)(&|$)");
        var e=a.location.search.substr(1).match(d);
        if(e!=null){
            return unescape(e[2])
        }

        return null
    };

    b.fn.toFillText=function(){
        return this.each(function(){
            var c=b(this),e=c.html(),f=c.height();
            c.html("");
            var g=b("<div>"+e+"</div>").appendTo(c);
            g.css("font-size","12px");
            for(var d=12;d<200;d++){
                if(g.height()>f){
                    c.css("font-size",(d-2)+"px").html(e);
                    break
                }else{
                    g.css("font-size",d+"px")
                }
            }
        })
    };

    b.fillText=function(c){
        var e=c.html(),f=c.height();
        c.html("");
        var g=b("<div>"+e+"</div>").appendTo(c);
        g.css("font-size","12px");
        for(var d=12;d<200;d++){
            if(g.height()>f){
                c.css("font-size",(d-2)+"px").html(e);
                break
            }else{
                g.css("font-size",d+"px")
            }
        }
    };

    b.showPage=function(c){
        var g=b('<div class="frame-dialog"><iframe frameborder="0" src="'+c+'"></iframe><div class="closebutton"></div></div>');
        var e;
        g.appendTo("body").show().on("click",".closebutton",function(){
            g.hide(function(){
                g.remove();
                g=null
            });

            clearInterval(e)
        });

        var d=b(".frame-dialog").height(),f=d-200;
        e=setInterval(function(){
            b("iframe").contents().find(".member-list").ready(function(){
                b("iframe").contents().find(".member-list").css({height:f+"px"})}
            )},1000)
    };

    a.WBActivity={
        showLoginForm:function(){
            b(".loginform").fadeIn()
        },

        hideLoginForm:function(){
            b(".loginform").fadeOut()
        },

        showLoading:function(){
            b(".loader").fadeIn()
        },

        hideLoading:function(){
            b(".loader").fadeOut()
        }
    };

    b(function(){
        b(".top_title").scroll_subtitle();
        b(".button-login").on("click",function(){
            a.WBActivity.showLoading();
            b.getJSON(
                PATH_ACTIVITY+"verify.php?callback=",
                {scene_id:scene_id,password:b("#password").val()},
                function(e){
                    if(e&&e.ret==0&&e.data==1){
                        a.sessionStorage.setItem("loginkey",1);
                        a.WBActivity.hideLoginForm();
                        a.WBActivity.start()
                    }else{
                        alert("密码错误")
                    }
                }
            ).complete(function(){
                a.WBActivity.hideLoading()
            })
        });

        b(".mp_account_codeimage").on("click",function(){
            b(".bigmpcodebar").slideDown()
        });

        b(".bigmpcodebar .closebutton").on("click",function(){
            b(".bigmpcodebar").slideUp()
        });

        b(".navbaritem.fullscreen").on("click",function(){
            b.toggleFullScreen()
        });

        var c="_wb_islogin"+scene_id,d=a.sessionStorage.getItem("loginkey");

        if(!d){
            a.WBActivity.hideLoading();
            a.WBActivity.showLoginForm()
        }else{
            a.WBActivity.start()
        }
    });

    b(a).on("resize",function(){
        a.WBActivity.resize()
    })
})(window,jQuery);

(function(e,g){
    var h={
        supportsFullScreen:false,
        isFullScreen:function(){return false},
        requestFullScreen:function(){},
        cancelFullScreen:function(){},
        fullScreenEventName:"",
        prefix:""
    },c="webkit moz o ms khtml".split(" ");

    if(typeof document.cancelFullScreen!="undefined"){
        h.supportsFullScreen=true
    }else{
        for(var b=0,a=c.length;b<a;b++){
            h.prefix=c[b];

            if(typeof document[h.prefix+"CancelFullScreen"]!="undefined"){
                h.supportsFullScreen=true;
                break
            }
        }
    }

    if(h.supportsFullScreen){
        h.fullScreenEventName=h.prefix+"fullscreenchange";
        h.isFullScreen=function(){
            switch(this.prefix){
                case"":
                    return document.fullScreen;
                case"webkit":
                    return document.webkitIsFullScreen;
                default:
                    return document[this.prefix+"FullScreen"]
            }
        };

        h.requestFullScreen=function(i){
            return(this.prefix==="")?i.requestFullScreen():i[this.prefix+"RequestFullScreen"]()
        };

        h.cancelFullScreen=function(i){
            return(this.prefix==="")?document.cancelFullScreen():document[this.prefix+"CancelFullScreen"]()
        }
    }

    if(typeof jQuery!="undefined"){
        jQuery.fn.requestFullScreen=function(){
            return this.each(function(){
                var i=jQuery(this);
                if(h.supportsFullScreen){
                    h.requestFullScreen(i)
                }
            })
        }
    }

    e.fullScreenApi=h;

    g.toggleFullScreen=function(){
        if(h.isFullScreen()){
            h.cancelFullScreen(document.documentElement)
        }else{
            h.requestFullScreen(document.documentElement)
        }
    };

    g.djax=function(i,j,k,l){
        g.ajax({
            url:i,
            type:j,
            dataType:"json",
            data:k,
            success:function(m){
                l(m)
            },
            error:function(){
                alert("连接服务器失败，请检查网络!")
            }
        })
    };

    g.setRandom=function(o,j,m,p){
        if((((j-o)+1)<m||o>j)){
            return""
        }

        if(typeof p==="undefined"){
            p=true
        }

        var l=[],n=0;
        function k(){
            if(n<m){
                var i=Math.floor(Math.random()*j+o);
                if(p){
                    l.push(i);
                    n++
                }else{
                    if(l.indexOf(i)<0){
                        l.push(i);
                        n++
                    }
                }

                k()
            }else{
                return false
            }
        }

        k();

        return l
    };

    var d=0,f=0;
    var c=0;

    g.getUser=function(j,i){
        g.djax(j,"get",{from_id:i,scene_id:scene_id, c:c++},function(l){
            if(l.ret=="0"){
                if(l.data&&l.data.parters){
                    g(".join_num em").html(l.data.count);
                    var m=l.data.parters,k=m.length;
                    if(k){
                        var n='<div id="'+m[0].id+'" class="user-item"><div class="user-img"><img src="'+m[0].avatar+'" /></div><p>'+m[0].nick_name+"</p></div>";
                        f++;
                        if(f>10){
                            g(".users").html("");
                            f=1
                        }

                        g(n).appendTo(".users").animate({opacity:0.7},500);

                        i=m[0].id
                    }

                    if(join_status==0){
                        setTimeout(function(){g.getUser(j,i)},500)
                    }else{

                    }
                }
            }else{
                alert(l.msg)
            }
        })
    };

    g.joinUser={
        show:function(j,i){
            switch(join_status){
                case"-1":
                    g.joinUser.hide();
                    break;
                case"0":
                    g.joinUser.showRader();
                    d=1;
                    g.getUser(j,0);
                    break;
                case"1":
                    g("body").addClass("sameRound");
                    g(".line").removeClass("roundMove");
                    break;
                default:break
            }i()
        },

        hide:function(i){
            if(g(".join_user").size()){
                g(".join_user").animate({top:"-91%"},500,function(){
                    if(g.isFunction(i)){
                        i()
                    }
                })
            }
        },

        showRader:function(){
            if(g(".join_user").size()){
                var i=g(e).width();
                g(".join_user").animate({top:"0"},500);

                g(".radar").fadeIn(1).animate({left:i/2+220},500,function(){
                    g(".line").addClass("roundMove")
                });

                g(".codeImg").animate({left:i/2-220},500)
            }
        }
    };

    g.getObjNum=function(k){
        var l=0;
        if(!g.isEmptyObject(k)){
            for(var j in k){
                l++
            }
        }

        return l
    }
})(window,jQuery);

var scene_id=$.getUrlParam("scene_id");