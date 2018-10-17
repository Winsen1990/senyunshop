var rankTopTen=[];
var RanksPosition=[];
var Players={};
var audio_CutdownPlayer,audio_NewPlayer,audio_Outride,audio_Gameover;

function findUserByID(c,a){
    if($.isArray(a)){
        var b=a.length;
        while(b--){
            if(a[b]["mid"]==c){
                return b
            }
        }

        return -1
    }else{
        return -1
    }
}

function mess(b){
    var c=Math.floor,h=Math.random,a=b.length,f,e,d,g=c(a/2)+1;
    while(g--){f=c(h()*a);e=c(h()*a);
        if(f!==e){d=b[f];
            b[f]=b[e];
            b[e]=d
        }
    }

    return b
}

function RndRank(){
    mess(rankTopTen)
}

(function(b){
    var c=0;
    var d=["ms","moz","webkit","o"];
    for(var a=0;a<d.length&&!b.requestAnimationFrame;++a){
        b.requestAnimationFrame=b[d[a]+"RequestAnimationFrame"];
        b.cancelAnimationFrame=b[d[a]+"CancelAnimationFrame"]||b[d[a]+"CancelRequestAnimationFrame"]
    }

    if(!b.requestAnimationFrame){
        b.requestAnimationFrame=function(i,f){
            var e=new Date().getTime();
            var g=Math.max(0,16-(e-c));
            var h=b.setTimeout(function(){i(e+g)},g);
            c=e+g;
            return h
        }
    }

    if(!b.cancelAnimationFrame){
        b.cancelAnimationFrame=function(e){
            clearTimeout(e)
        }
    }
})(window);

(function(a){
    a.GameTimer=function(b,c){
        this.__fn=b;
        this.__timeout=c;
        this.__running=false;
        this.__lastTime=Date.now();
        this.__stopcallback=null
    };

    a.GameTimer.prototype.__runer=function(){
        if(Date.now()-this.__lastTime>=this.__timeout){
            this.__lastTime=Date.now();
            this.__fn.call(this)
        }

        if(this.__running){
            a.requestAnimationFrame(this.__runer.bind(this))
        }else{
            if(typeof this.__stopcallback==="function"){
                a.setTimeout(this.__stopcallback,100)
            }
        }
    };

    a.GameTimer.prototype.start=function(){
        this.__running=true;
        this.__runer()
    };

    a.GameTimer.prototype.stop=function(b){
        this.__running=false;
        this.__stopcallback=b
    }
})(window);

var tick=1000;
var LineLength=$(window).width();
var PlayStep=16;
var flgGameStop=false;
var mainTick=new GameTimer(function(){
    $.each(RanksPosition,function(c,d){
        var e=d+PlayStep;
        if(c==0||e<RanksPosition[c-1]-size*3/4){
            RanksPosition[c]=e
        }
    });

    setTopLeft();

    if(flgGameStop){
        window.clearTimeout(tmr_GameDataLoad);
        mainTick.stop();
        var b=$(".tracklist").width()-size+diff;
        for(var a=0;a<RanksPosition.length;a++){
            RanksPosition[a]=b}setTopLeft()
    }
},tick);

function gameTick(){
    $.each(RanksPosition,function(a,b){
        var c=parseInt(b)+LineLength/(CUTDOWN_TIME/(tick/1000));
        if(a==0||c<RanksPosition[a-1]-size*3/4){RanksPosition[a]=c
        }
    });

    setTopLeft()
}

function setTopLeft(){
    $(".tracklist .player").each(function(){
        var b=$(this),a=b.attr("uid");
        if(findUserByID(a,rankTopTen)<0){
            b.remove().removeClass("rotateout")
        }
    });

    $.each(rankTopTen,function(b,d){
        var c=d.mid;
        if($(".player.player"+c).attr("uid")!=c){
            if(Players[c]){
                if(!Players[c].$elm){
                    var a=Players[c].$elm=$PlayeSeed.clone().addClass("player"+c).attr("uid",c);
                    a.find(".head").css({"background-image":"url("+rankTopTen[b]["avatar"]+")"}).addClass("shake");
                    a.find(".nickname").html(rankTopTen[b]["nick_name"]);

                    if(audio_NewPlayer){
                        audio_NewPlayer.play()
                    }
                }else{
                    if(audio_Outride){
                        audio_Outride.play()
                    }
                }

                var e=RanksPosition[b]-size*2;
                if(e<0){
                    e=0
                }

                Players[c].$elm.css({left:e,top:lineHeight*b+diff}).appendTo(".tracklist")
            }else{

            }
        }else{
            $(".player.player"+c).css({left:RanksPosition[b]+"px",top:lineHeight*b+diff})
        }
    })
}

function showGameResult(){
    var b=$(".result-layer").show();
    var d=$(".result-label",b).show().addClass("pulse");
    var a=$(".result-cup",b).hide();
    var c=3;

    if(audio_Gameover){
        audio_Gameover.play()
    }

    window.setTimeout(function(){
        d.fadeOut(function(){
            a.show(function(){
                if(c>=1&&rankTopTen[0]){
                    window.setTimeout(function(){
                        var e=$PlayeSeed.clone().addClass("result").css({left:"50%","margin-left":"-65px",width:"160px",height:"160px",bottom:"150px"});

                        e.find(".head").css({"background-image":"url("+rankTopTen[0]["avatar"]+")"}).addClass("shake");
                        e.find(".nickname").html(rankTopTen[0]["nick_name"]);e.appendTo(a).addClass("bounce")},800)
                }

                if(c>=2&&rankTopTen[1]){
                    window.setTimeout(function(){
                        var e=$PlayeSeed.clone().addClass("result").css({left:"40px",width:"100px",height:"100px",bottom:"120px"});
                        e.find(".head").css({"background-image":"url("+rankTopTen[1]["avatar"]+")"}).addClass("shake");
                        e.find(".nickname").html(rankTopTen[1]["nick_name"]);
                        e.appendTo(a).addClass("bounce")},1800)
                }

                if(c>=3&&rankTopTen[2]){
                    window.setTimeout(function(){
                        var e=$PlayeSeed.clone().addClass("result").css({right:"30px",width:"70px",height:"70px",bottom:"100px"});
                        e.find(".head").css({"background-image":"url("+rankTopTen[2]["avatar"]+")"}).addClass("shake");
                        e.find(".nickname").html(rankTopTen[2]["nick_name"]);
                        e.appendTo(a).addClass("bounce")},2800)
                }
            })
        }).removeClass("pulse")},1000)
}

var $PlayeSeed,lineHeight,diff=10;
var size;
var resizePart=window.WBActivity.resize=function(){
    var b=$(".Panel.Track"),a=b.find(".tracklist").children();
    size=lineHeight=b.height()/SHAKE_LINE;
    roundLength=$(".Panel.Track .tracklist").width()-size;
    a.each(function(){
        $(this).css({height:size,"line-height":size+"px","font-size":size*3/5+"px"}).find(".track-start,.track-end").css({width:size+"px",height:size+"px"})
    });

    $PlayeSeed=$('<div class="player"><div class="head"></div><div class="nickname"></div></div>').css({width:size-diff*2,height:size-diff*2})
};

var start=window.WBActivity.start=function(){
    window.WBActivity.hideLoading();
    $(".Panel.Top").css({top:0});
    $(".Panel.Bottom").css({bottom:0});
    $(".Panel.Track").css({display:"block",opacity:1});
    $.joinUser.show(PATH_ACTIVITY+"shake_parter.php",function(){
        var f=document.getElementById("Audio_CutdownPlayer");
        if(f.play){
            audio_CutdownPlayer=f
        }

        var e=document.getElementById("Audio_NewPlayer");
        if(e.play){
            audio_NewPlayer=e
        }

        var d=document.getElementById("Audio_Outride");

        if(d.play){
            audio_Outride=d
        }

        var b=document.getElementById("Audio_Gameover");
        if(b.play){
            audio_Gameover=b
        }

        createTrack();
        resizePart();
        for(var a=0;a<SHAKE_LINE;a++){
            RanksPosition[a]=0
        }

        $(".game-start").on("click",function(g){
            cutdown_start()
        });

        $(".button.reset").on("click",function(){
            nextRound()
        });

        $(".button.result").on("click",function(){
            c()
        });

        function c(h){
            //window.location.href = "shake.php?act=reward&id="+scene_id;
            //return false;

            var g="shake.php?act=reward_ajax&id="+scene_id;
            if(h!=undefined){
                g+="&rotate_id="+h
            }

            $.showPage(g)
        }
    })
};

var tmr_cutdown_start;
var cutdown_start=function(){
    $.joinUser.hide();
    join_status=1;
    var a=$(".cutdown-start"),b=SHAKE_INFO.ready_time*1+1;
    a.html("").show().css({"margin-left":-a.width()/2+"px","margin-top":-a.height()/2+"px","font-size":a.height()*0.7+"px","line-height":a.height()+"px"}).addClass("cutdownan-imation");
    tmr_cutdown_start=window.setInterval(function(){
        b--;
        if(b==0){
            $.getJSON(PATH_ACTIVITY+"/shake_status.php?callback=",{scene_id:scene_id,action:"start"},function(c){
                if(c.ret==0){
                    a.html("GO!")
                }else{
                    zAlert.Alert({
                        closed:false,
                        content:"游戏初始参数错误，请刷新重试",
                        callback:function() {
                            zAlert.Close();
                            window.location.reload()
                        }
                    })
                }
            }).fail(function(){
                zAlert.Alert({
                    closed:false,
                    content:"游戏初始参数错误，请刷新重试",
                    callback:function(){
                        zAlert.Close();
                        window.location.reload()
                    }
                })
            })
        }else{
            if(b<0){
                window.clearInterval(tmr_cutdown_start);
                a.hide();
                gameTimeRun();
                showSlogan()
            }else{
                audio_CutdownPlayer.play();
                a.html(b)
            }
        }
    },1000)};

var tmr_GameDataLoad;

var gameTimeRun=function(){
    $.getJSON(PATH_ACTIVITY+"/shake_sort.php?callback=",{scene_id:scene_id,rotate_id:CURR_ROUND_ID},function(d){
        if(d.ret==0&&d.data){
            if($.isArray(d.data["players"])){
                rankTopTen=d.data["players"].slice(0,SHAKE_LINE);
                var a=rankTopTen.length;
                while(a--){
                    var c=rankTopTen[a]["mid"];
                    if(!Players[c]){
                        Players[c]={data:rankTopTen[a]}
                    }
                }
            }

            if(d.data["status"]*1==1){
                gameTick();
                tmr_GameDataLoad=window.setTimeout(gameTimeRun,tick)
            }else{
                if(d.data["status"]*1==-1){
                    var e=$(".tracklist").width()-size+diff;
                    for(var b=0; b<RanksPosition.length;b++){
                        RanksPosition[b]=e
                    }

                    setTopLeft();

                    window.setTimeout(function(){
                        showGameResult();
                        hideSlogan()
                    },660)
                }
            }
        }else{
            zAlert.Alert({
                content:"无法获得游戏数据，与游戏服务器断开，请刷新重试！",
                callback:function(){
                    zAlert.Close()
                }
            })
        }
    })
};

var roundTime,roundLength;
var nextRound=function(){
    $.getJSON(PATH_ACTIVITY+"/shake_status.php?callback=",{scene_id:scene_id,action:"restart"},function(a){
        if(a.ret!=0){
            alert(res.msg)
        }else{
            join_status=0;
            window.location.reload()
        }
    })
};

function createTrack(){
    var b="";
    for(var a=0;a<SHAKE_LINE;a++){
        b+='<div class="trackline"><div class="track-start">'+(a+1)+'</div><div class="track-end"></div></div>'
    }

    $(b).appendTo(".Track .tracklist").hide().each(function(c){
        var d=$(this);
        window.setTimeout(function(){d.show().addClass("leftfadein")},100*c)
    })
}

function showScore(b){
    var a="/activity/shake/winner_list.php?id="+scene_id;
    if(b!=undefined){
        a+="&rotate_id="+b
    }

    $.showPage(a)
}

var tmr_slogan;
function showSlogan(){
    $(".Panel.Top").css({top:"-"+$(".Panel.Top").height()+"px"});
    $(".Panel.Bottom").css({bottom:"-"+$(".Panel.Bottom").height()+"px"});

    var c=($.isArray(SHAKE_INFO.slogan_list)&&SHAKE_INFO.slogan_list.length>0)?SHAKE_INFO.slogan_list:SLOGANS;
    var a=c.length;
    var b=$(".Panel.SloganList").css({top:"-15%"}).show();
    b.css({top:0,"line-height":b.height()+"px"});
    tmr_slogan=window.setInterval(function(){
        b.html(c[Math.floor(Math.random()*a)])
    },1000)
}

function hideSlogan(){
    window.clearInterval(tmr_slogan);
    $(".Panel.SloganList").hide();
    $(".Panel.Top").css({top:0});
    $(".Panel.Bottom").css({bottom:0})
};