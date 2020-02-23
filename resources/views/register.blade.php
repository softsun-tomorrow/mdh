<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>麦达汇</title>
    <link href="{{asset('/css/common.css')}}" type="text/css" rel="stylesheet">
</head>
<body style="background:#f1f1f1">
    <div class="wrap">
        <div class="head">麦达汇</div>
        <img src="{{asset('/images/logo2.png')}}" class="logo">
        <div class="input_wrap">
            <label class="clearfix">
                <img src="{{asset('/images/username.png')}}" style="width:6%">
                <span>手机号</span>
                <input type="text" id="username" placeholder="请输入手机号" onfocus="this.placeholder=''" onblur="this.placeholder='请输入手机号'">
            </label>
            <label class="clearfix">
                <img src="{{asset('/images/password.png')}}">
                <span>验证码</span>
                <input type="text" id="password" placeholder="请输入验证码" onfocus="this.placeholder=''" onblur="this.placeholder='请输入验证码'">
                <button onclick="return getpwd(this)">获取验证码</button>
            </label>
            <button class="submit_" onclick="return register()">提交注册</button>
        </div>
        <a href="download.html" class="download">直接下载APP领取奖励</a>
    </div>
</body>
<script>
    var url = 'http://'+window.location.host;

    var countdown=60;
    function getpwd(val){
        var username = document.getElementById("username").value;
        var password = document.getElementById("password").value;
        if(!username){
            alertTips("提示","请输入手机号");
            return false;
        }else if(!(/^1[3|4|5|7|8][0-9]\d{8,11}$/.test(username))){
            alertTips("提示","请输入正确的手机号");
            return false;
        }else{
            postpwd(val)
        }
    }
   
    function postpwd(val){
        ajax({
            url:url+"/api/sms?phone="+document.getElementById("username").value, //请求地址
            type:'POST',   //请求方式
            dataType:"json",     // 返回值类型的设定
            async:false,   //是否异步
            success:function (response,xml) {
                if(response.code=="1"){
                    down(val)
                }else{
                    alertTips("提示","验证码发送失败，请稍候重试");
                }
            },
            error:function (status) {
                console.log('状态码为'+status);   // 此处为执行成功后的代码
            }
    
        });
    }

    function down(val) {  
        if (countdown == 0) {  
            val.removeAttribute("disabled");  
            val.innerText="获取验证码";  
            countdown = 60;  
            return false;  
        } else {  
            val.setAttribute("disabled", true);  
            val.innerText=countdown;  
            countdown--;  
        }  
        setTimeout(function() {  
            down(val);  
        },1000);  
    }  

    function register(){
        var username = document.getElementById("username").value;
        var password = document.getElementById("password").value;
        if(!username){
            alertTips("提示","请输入手机号");
            return false;
        }else if(!(/^1[3|4|5|7|8][0-9]\d{8,11}$/.test(username))){
            alertTips("提示","请输入正确的手机号");
            return false;
        }
        ajax({
            url:url+"/api/register", //请求地址
            type:'POST',   //请求方式
            dataType:"json",     // 返回值类型的设定
            async:false,   //是否异步
            data:{
                mobile:username,
                code:password,
                pcode:getQueryString("referral_code")
            },
            success:function (response,xml) {
                if(response.code==1){
                    // window.location.href="download.html?user_id="+getQueryString("user_id")
                    window.location.href="download";
                }else{
                    alertTips("提示",response.msg)
                }
            },
            error:function (status) {
                console.log('状态码为'+status);   // 此处为执行成功后的代码
            }

        });
    }


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

    window.alertTips=function(title,txt){
        var tipsLayer=document.createElement("div");
        tipsLayer.className="alertTips-layer";
        var alertBox=document.createElement("div");
        alertBox.className="alertTips-alert-box";
        var alertContent='<div class="alertTips-top-box"><span>'+title+'</span></div>';
        alertContent+='<div class="alertTips-center-box">'+txt+'</div>';
        alertContent+='<div class="alertTips-bottom-box"><button class="bg-blue" onclick="doOk();">确定</button></div>';
        alertBox.innerHTML=alertContent;
        document.body.appendChild(tipsLayer);
        document.body.appendChild(alertBox);
        this.doOk=function(){
            tipsLayer.parentNode.removeChild(alertBox);
            tipsLayer.parentNode.removeChild(tipsLayer);
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