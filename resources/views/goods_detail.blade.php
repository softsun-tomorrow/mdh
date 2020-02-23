<!doctype html>
<html lang="zh_CN">
<head>
    <meta charset="UTF-8">
    <title>商品详情</title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            font-size:100px;
        }

        .wp {
            width: 800px;
        }

        @media screen and (min-width: 1000px) {

        }

        @media screen and (min-width: 640px) and (max-width: 999px) {
            .wp {
                width: 800px;
            }
        }

        @media screen and (max-width: 639px) {
            .wp {
                width: 800px;
            }
        }

        table, table tr th, table tr td {
            border: 1px solid grey;
        }

        table {
            width: auto;
            min-height: 25px;
            line-height: 25px;
            text-align: center;
            border-collapse: collapse;
            padding: 2px;
        }

        .content {
            width: auto
        }

        img{
            width: 100%;
        }
        
    </style>
    <link rel="stylesheet" href="/js/layui/css/layui.css">
    <script src="/js/layui/layui.js"></script>
</head>
<body>
<div class="layui-tab">
    <ul class="layui-tab-title " style="height: 1.2rem;width:90%;margin:auto">
        <li class="layui-this" style="width: 10%;height: 1.2rem;font-size:0.3rem;line-height: 1.2rem; align-content: center;" >商品详情</li>
        <li style="width: 10%;height: 1.2rem;font-size:0.3rem;line-height: 1.2rem; align-content: center;">商品参数</li>
        <li style="width: 10%;height: 1.2rem;font-size:0.3rem;line-height: 1.2rem; align-content: center;">购买需知</li>
        <li style="width: 10%;height: 1.2rem;font-size:0.3rem;line-height: 1.2rem; align-content: center;">拼团需知</li>
    </ul>
    <div class="layui-tab-content">
        <div class="layui-tab-item layui-show">
            <div><p style="margin-bottom:0.2rem; font-size: 0.24rem">{{ $notice }}</p></div>
            @if($detail)
                @foreach($detail as $img)
                    <li><img class="content" style="width: 100vw" src="/uploads/{{  $img }}"/></li>
                @endforeach
            @endif
        </div>
        <div class="layui-tab-item">
            <table class="parameter-table" id="J_ParameterTable" style="width: 90vw;margin:auto">
                <tbody>
                @if ($goods_spu)
                    @foreach ($goods_spu->chunk(3) as $chunk)
                        <tr style="height: 0.5rem;">
                            @foreach ($chunk as $spu)
                                <td style="width: 30vw;height:0.5rem;line-height:0.5rem;font-size: 0.3rem">{{ $spu->spu_name }}: {{ $spu->spu_value }}</td>

                            @endforeach
                        </tr>
                    @endforeach
                @endif
                </tbody>
            </table>
        </div>
        <div class="layui-tab-item">{!! $buy_notice !!}</div>
        <div class="layui-tab-item">{!! $team_notice !!}</div>
    </div>
</div>

<script>
    //注意：选项卡 依赖 element 模块，否则无法进行功能性操作
    layui.use('element', function () {
        var element = layui.element;

    });

    (function(win) {
        var doc = win.document;
        var docEl = doc.documentElement;
        var tid;

        function refreshRem() {
            var width = docEl.getBoundingClientRect().width;
            //alert(width);
            if (width > 650) { // 最大宽度
                // width = 540;
                docEl.style.fontSize=100+"px";
            }else{
                docEl.style.fontSize=40+"px";
            }
            //var rem = width / 10; // 将屏幕宽度分成10份， 1份为1rem
            //docEl.style.fontSize = rem + 'px';
        }

        win.addEventListener('resize', function() {
            clearTimeout(tid);
            tid = setTimeout(refreshRem, 300);
        }, false);
        win.addEventListener('pageshow', function(e) {
            if (e.persisted) {
                clearTimeout(tid);
                tid = setTimeout(refreshRem, 300);
            }
        }, false);

        refreshRem();

    })(window);
</script>
</body>
</html>