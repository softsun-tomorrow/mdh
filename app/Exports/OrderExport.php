<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;    // 导出集合
use Maatwebsite\Excel\Concerns\WithEvents;     // 自动注册事件监听器
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;    // 导出 0 原样显示，不为 null
use Maatwebsite\Excel\Concerns\WithTitle;    // 设置工作䈬名称
use Maatwebsite\Excel\Events\AfterSheet;    // 在工作表流程结束时会引发事件

class BillExport implements FromCollection, WithTitle, WithEvents, WithStrictNullComparison
{
    public $data;

    public $dateTime;

    public $payways;

    public function __construct(array $data, $dateTime, $payways)
    {
        $this->data = $data;
        $this->dateTime = $dateTime;
        $this->payways = $payways;
    }

    /**
     * registerEvents    freeze the first row with headings
     * @return array
     * @author   liuml  <liumenglei0211@163.com>
     * @DateTime 2018/11/1  11:19
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // 合并单元格
                $event->sheet->getDelegate()->setMergeCells(['A1:O1', 'A2:C2', 'D2:O2']);
                // 冻结窗格
                $event->sheet->getDelegate()->freezePane('A4');
                // 设置单元格内容居中
                $event->sheet->getDelegate()->getStyle('A1:A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                // 定义列宽度
                $widths = ['A' => 10, 'B' => 15, 'C' => 25];
                   foreach ($widths as $k => $v) {
                       // 设置列宽度
                       $event->sheet->getDelegate()->getColumnDimension($k)->setWidth($v);
                   }
                   // 其他样式需求（设置边框，背景色等）处理扩展中给出的宏，也可以自定义宏来实现，详情见官网说明

               },
        ];
    }

    /**
     * 需要导出的数据统一在这个方法里面处理 这个方法里面也可以直接用 Model取数据
     * 我这里的数据是 Controller 传过来的，至于怎么传的看下面给出的 Controller 里面的代码就知道了
     * 里面数据处理太长了，多余的我都用 ... 表示，大家明白就行
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $i = 1;
        $total_amount = 0;    // 交易金额总计
        if (empty($this->data)) {
            foreach ($this->data as $key => $vo) {


                $data[$key]['num'] = $i;    // 编号
                $data[$key]['consume_type'] = $vo['consume_type'];    // 项目类型


                $total_amount += $data[$key]['total_amount '];    // 交易金额总计
                $i++;
            }
        }

        $total = ['ID', '订单号', '活动类型','支付时间','支付状态','订单状态','发货状态','订单总价'];

           $data[] = $total;
           $title = [$this->payways . '对账单'];
           $headings = ['ID', '订单号', '活动类型','支付时间','支付状态','订单状态','发货状态','订单总价'];
           $date = ['下载时间：' . date('Y-m-d H:i:s'), '', '', '数据时间范围：' . $this->dateTime];
           array_unshift($data, $title, $date, $headings);
		// 此处数据需要数组转集合
           return collect($data);
       }

    public function title(): string
    {
        // 设置工作䈬的名称
        return $this->payways . '账单明细';
    }
}
