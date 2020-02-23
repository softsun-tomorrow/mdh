<?php

return [

    'common' => [
        'app_key' => '2cc0218c-fa39-4c02-8498-a34eb0980d3b', //AppKey
        'e_business_id' => '1390937', //商户ID
        'data_type' => '2', //默认值2 JSON
    ],

    'api' => [
//        http://api.kdniao.com/Ebusiness/EbusinessOrderHandle.aspx
        //即时查询API
        'track' => [
            'url' => 'http://api.kdniao.com/Ebusiness/EbusinessOrderHandle.aspx',
            'type' => '1002',
        ],

        //物流跟踪API
        'follow' => [
            'url' => 'http://api.kdniao.com/api/dist',
            'type' => '1008',
        ],


    ],
];