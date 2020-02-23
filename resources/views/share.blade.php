<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>麦达汇</title>
    <link href="{{asset(_CSS_.'/common.css')}}" type="text/css" rel="stylesheet">
</head>
<body>
    <div class="wrap">
{{--        <div class="head">邀请返钱</div>--}}
        <div class="con">
            <p class="text">邀请新用户注册立返<span>5-40</span>元</p>
            <button class="text_button">多邀多得&nbsp;&nbsp;上不封顶</button>
            <img src="{{asset('/images/hongbao.png')}}" class="hongbao">
             <p class="text yaoqingma" >我的邀请码：&nbsp;<i style="font-style:normal" id="yaoqingma">{{ $referral_code }}</i>&nbsp;&nbsp;</p>
            <div class="con_text">
                <p class="text text_title" id="title"></p>
                <div id="text_con"></div>
            </div>
        </div>
{{--        <button class="download">生成二维码</button>--}}
    </div>
</body>
<script>
        var url = 'http://'+window.location.host;

        var user_id= getQueryString("user_id") || "";
        ajax({
            url:url+"/api/index/invite?user_id="+user_id, //请求地址
            type:'POST',   //请求方式
            dataType:"json",     // 返回值类型的设定
            async:false,   //是否异步
            success:function (response,xml) {
                if(response.code=="1"){
                    document.getElementById("yaoqingma").innerText=response.data.referral_code;
                    document.getElementById("title").innerText=response.data.article.title
                    document.getElementById("text_con").innerHTML=response.data.article.content
                }
            },
            error:function (status) {
                console.log('状态码为'+status);   // 此处为执行成功后的代码
            }
    
        });
        function getQueryString(name) {
            var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
            var reg_rewrite = new RegExp("(^|/)" + name + "/([^/]*)(/|$)", "i");
            var r = window.location.search.substr(1).match(reg);
            var q = window.location.pathname.substr(1).match(reg_rewrite);
            if(r != null){
                return unescape(r[2]);
            }else if(q != null){
                return unescape(q[2]);
            }else{
                return null;
            }
        }

        function ajax(obj){
            var ajaxObj = null;
            if (window.XMLHttpRequest) {
                ajaxObj = new XMLHttpRequest();
            }else{
                ajaxObj = new ActiveObject("Microsoft.XMLHTTP");
            }
            ajaxObj.onreadystatechange = function(){
                if (ajaxObj.readyState == 4) {
                    if (ajaxObj.status >= 200 && ajaxObj.status < 300 || ajaxObj.status == 304) {
                        if (obj.success) {
                         obj.success(JSON.parse(ajaxObj.responseText));
                        }else{
                            alert("您忘记了 success 函数");
                        }    
                    }else{
                        if (obj.error) {
                            obj.error(ajaxObj.status);
                        }else{
                            alert("您忘记了 error 函数");
                        }
                    }
                }
            }
            var type = obj.type || "get";
            type = type.toLowerCase();
            var params = "";
            if (obj.data) {
                for(var key in obj.data){
                    params += (key + "=" + obj.data[key] + "&");
                }
                params = params.slice(0,params.length-1);
            }
            if (type == "get") {
                ajaxObj.open(type,obj.url+"?"+params,true);
                ajaxObj.send(null);
            }else{
                ajaxObj.open(type,obj.url,true);
                ajaxObj.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
                ajaxObj.send(params);
            }
        }
        
    </script>
</html>