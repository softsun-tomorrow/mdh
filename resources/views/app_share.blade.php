<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>麦达汇</title>
    <link href="{{asset(_CSS_.'/common.css')}}" type="text/css" rel="stylesheet">
    <link rel="stylesheet" href="/js/layui/css/layui.css">
    <script src="/js/layui/layui.js"></script>
</head>
<body>
<div class="wrap">
    <div class="head">麦达汇</div>
    <img src="{{asset(_IMG_.'/logo.png')}}" class="logo">
    <div class="button">
        <a href="#" id="android">下载Android版</a>
        <a href="#" id="iphone">下载iphone版</a>
    </div>
</div>
</body>
<script type="text/javascript">
    function detectVersion() {
        let isAndroid,isIOS,isIOS9,version,
            u = navigator.userAgent,
            ua = u.toLowerCase();

        if (u.indexOf('Android') > -1 || u.indexOf('Linux') > -1) {   //android终端或者uc浏览器
            //Android系统
            isAndroid = true
        }

        if(ua.indexOf("like mac os x") > 0){
            //ios
            var regStr_saf = /os [\d._]*/gi ;
            var verinfo = ua.match(regStr_saf) ;
            version = (verinfo+"").replace(/[^0-9|_.]/ig,"").replace(/_/ig,".");
        }
        var version_str = version+"";
        if(version_str != "undefined" && version_str.length >0){
            version = parseInt(version)
            if(version>=8){
                // ios9以上
                isIOS9 = true
                // alert('ios9');
            }
            else{
                // alert('ios8一下');
                isIOS = true
            }
        }
        return {isAndroid,isIOS,isIOS9}
    }

    // 判断手机上是否安装了app，如果安装直接打开url，如果没安装，执行callback
    function openApp(type,id,callback) {
        let {isAndroid,isIOS,isIOS9} = detectVersion()
        var url;
        if(isAndroid){
            if(type == 1){
                url = "palmos://goods?type="+type+"&id="+id;
            }else{
                url = "palmos://maidahui?type="+type+"&id="+id;
            }
        }else{
            url = "palmos://maidahui?type="+type+"&id="+id;
        }
        if(isAndroid || isIOS){
            var timeout, t = 4000, hasApp = true;
            var openScript = setTimeout(function () {
                if (!hasApp) {
                    callback && callback()
                }
                document.body.removeChild(ifr);
            }, 5000)

            var t1 = Date.now();
            var ifr = document.createElement("iframe");
            ifr.setAttribute('src', url);
            ifr.setAttribute('style', 'display:none');
            document.body.appendChild(ifr);

            timeout = setTimeout(function () {
                var t2 = Date.now();
                if (t2 - t1 < t + 100) {
                    hasApp = false;
                }
            }, t);
        }

        if(isIOS9){
            location.href = url;
            setTimeout(function() {
                callback && callback()
            }, 250);
            setTimeout(function() {
                location.reload();
            }, 1000);
        }
    }

    //跳h5
    function goConfirmAddr(){
        // window.location.href = '/web/index/download'
    }
    window.onload = function(){
        layui.use('layer', function(){
            var layer = layui.layer;

            layer.msg('请点击右上角，选择自带浏览器打开链接');
        });

        var type= getQueryString("type") || "";
        var id= getQueryString("id") || "";
        // openApp("palmos://maidahui?type="+type+"&id="+id,goConfirmAddr)
        openApp(type,id,goConfirmAddr)
    }
</script>
<script>
    var url = 'http://'+window.location.host;

    var user_id= getQueryString("user_id") || "";
    ajax({
        url:url+"/api/index/appDownload", //请求地址
        type:'POST',   //请求方式
        dataType:"json",     // 返回值类型的设定
        async:false,   //是否异步
        success:function (response,xml) {
            if(response.code=="1"){
                document.getElementById("android").setAttribute("href",response.data.android)
                document.getElementById("iphone").setAttribute("href",response.data.ios)
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