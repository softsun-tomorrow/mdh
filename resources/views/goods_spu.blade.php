<!doctype html>
<html lang="zh_CN">
<head>
    <meta charset="UTF-8">
    <title>商品参数</title>
    <style>
        table,table tr th, table tr td { border:1px solid grey; }
        table { width: 800px; min-height: 25px; line-height: 25px; text-align: center; border-collapse: collapse; padding:2px;}
    </style>
    <script src="/js/layui/layui.js"></script>
    <script src="/js/jquery.min.js"></script>
</head>
<body>
<table class="parameter-table" id="J_ParameterTable">
    <tbody>
    @if ($data)
        @foreach ($data->chunk(3) as $chunk)
            <tr class="">
                @foreach ($chunk as $spu)
                    <td>{{ $spu->spu_name }}: {{ $spu->spu_value }}</td>

                @endforeach
            </tr>
        @endforeach
    @endif
    </tbody>
</table>
</body>
</html>