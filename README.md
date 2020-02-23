麦达汇app

https://galactic-trinity-5094.postman.co/collections/6261349-a746c0a4-ad90-47a6-8a23-5dc72b62ce16?workspace=22696d0e-bf1a-4345-86e7-d6760bd58388#introduction

定时任务

拼团有效期结束，未拼成的单退款任务。用户待结算收入结算。商家订单结算
* * * * * php /home/wwwroot/zhuhao/maidahui/artisan schedule:run >> /dev/null 2>&1
