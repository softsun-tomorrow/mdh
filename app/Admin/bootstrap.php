<?php

use App\Admin\Extensions\WangEditor;
use Encore\Admin\Form;
use Encore\Admin\Grid;

/**
 * Laravel-admin - admin builder based on Laravel.
 * @author z-song <https://github.com/z-song>
 *
 * Bootstraper for Admin.
 *
 * Here you can remove builtin form field:
 * Encore\Admin\Form::forget(['map', 'editor']);
 *
 * Or extend custom form field:
 * Encore\Admin\Form::extend('php', PHPEditor::class);
 *
 * Or require js and css assets:
 * Admin::css('/packages/prettydocs/css/styles.css');
 * Admin::js('/packages/prettydocs/js/main.js');
 *
 */

Form::forget(['map','editor']);
Form::extend('editor', WangEditor::class);

Admin::js('/js/layer/layer.js');


Grid::init(function (Grid $grid) {

    $grid->disableRowSelector();

//    $grid->disableTools();

    $grid->disableExport();

    $grid->actions(function (Grid\Displayers\Actions $actions) {
        $actions->disableView();
//        $actions->disableEdit();
//        $actions->disableDelete();
    });

    Form::init(function (Form $form) {

        $form->disableEditingCheck();

        $form->disableCreatingCheck();

        $form->disableViewCheck();


        $form->tools(function (Form\Tools $tools) {
            $tools->disableDelete();
            $tools->disableView();
//            $tools->disableList();
        });


        $form->footer(function ($footer) {

            // 去掉`查看`checkbox
            $footer->disableViewCheck();

            // 去掉`继续编辑`checkbox
            $footer->disableEditingCheck();

            // 去掉`继续创建`checkbox
            $footer->disableCreatingCheck();

        });
    });
});
