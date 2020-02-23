<?php

namespace App\Tenancy\Extensions;

use Encore\Admin\Admin;

class SendSms
{
    protected $id;
    protected $mobile;

    public function __construct($id,$mobile)
    {
        $this->id = $id;
        $this->mobile = $mobile;
    }

    protected function script()
    {
        return <<<SCRIPT

$('.grid-check-row').on('click', function () {

    var mobile = $(this).data('mobile');
    var url = '/api/sms';
    var data = {phone:mobile};
    $.post(url,data,function(ret){
        if(ret.code){
            layer.msg('发送成功');
        }else{
            layer.msg('发送失败');
        }
    });

});

SCRIPT;
    }

    protected function render()
    {
        Admin::script($this->script());

        return "<a title='发送短信' class='btn btn-xs btn-success fa fa-check grid-check-row' data-id='{$this->id}' data-mobile='{$this->mobile}'></a>";
    }

    public function __toString()
    {
        return $this->render();
    }
}