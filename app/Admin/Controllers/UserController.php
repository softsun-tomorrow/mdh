<?php

namespace App\Admin\Controllers;

use App\Logic\UserLogic;
use App\Models\User;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;

class UserController extends Controller
{
    use HasResourceActions;

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header('Index')
            ->description('description')
            ->body($this->grid());
    }

    /**
     * Show interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        return $content
            ->header('Detail')
            ->description('description')
            ->body($this->detail($id));
    }

    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->header('Edit')
            ->description('description')
            ->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->header('Create')
            ->description('description')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new User);
        $userLogic = new UserLogic();

        $grid->header(function($query){
            $normal = $query->where('level',0)->count();
            $agent = DB::table('users')->where('level','>',0)->count();

            return '普通用户数量：'. $normal . '代理用户数量：'. $agent;
        });

        $grid->id('Id');
        $grid->mobile(trans('user.mobile'));
        $grid->name(trans('user.name'));
        $grid->created_at(trans('user.created_at'));
//        $grid->avatar(trans('user.avatar'));
//        $grid->gender(trans('user.gender'))->using(User::getGenderArr());
        $grid->level(trans('user.level'))->using(User::LEVEL);
        $grid->first_leader(trans('user.first_leader'))->display(function($firstLeader){
            return optional(User::find($firstLeader))->mobile;
        });
        $grid->money(trans('user.money'));
        $grid->account(trans('user.account'));
        $grid->column('上月销售额')->display(function () use ($userLogic){
            $userLogic->setUserId($this->id);
            $arr = $userLogic->getAgentArchive();
            return $arr['lastMouthAmount'];
        });

        $grid->column('总销售额')->display(function () use ($userLogic){
            $userLogic->setUserId($this->id);
            $arr = $userLogic->getAgentArchive();
            return $arr['totalAmount'];
        });

        $grid->actions(function($actions){
            $actions->disableView();
            $actions->disableDelete();
        });
        $grid->filter(function($filter){
            $filter->equal('mobile',trans('user.mobile'));
        });
        $grid->model()->orderBy('id','desc');
        $grid->disableCreateButton();
        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(User::findOrFail($id));

        $show->id('Id');
        $show->mobile(trans('user.mobile'));
        $show->name('Name');
        $show->email(trans('user.email'));
        $show->password('Password');
        $show->remember_token('Remember token');
        $show->created_at('Created at');
        $show->updated_at('Updated at');
        $show->real_name('Real name');
        $show->contact_mobile(trans('user.contact_mobile'));
        $show->idcard_front('Idcard front');
        $show->idcard_back(trans('user.idcard_back'));
        $show->idcard_num(trans('user.idcard_num'));
        $show->payment_password('Payment password');
        $show->avatar(trans('user.avatar'));
        $show->gender('Gender');
        $show->sign(trans('user.sign'));
        $show->home(trans('user.home'));
        $show->birthday('Birthday');
        $show->num('Num');
        $show->account(trans('user.account'));
        $show->free_tyfon('Free tyfon');
        $show->change_nums('Change nums');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new User);

        $form->mobile('mobile', trans('user.mobile'))->readOnly();
        $form->text('name',trans('user.name'))->readOnly();
        $form->select('first_leader',trans('user.first_leader'))->options('/admin/api/user');
//        $form->email('email', trans('user.email'))->readOnly();
        $form->text('real_name',trans('user.real_name'))->readOnly();
//        $form->text('contact_mobile', trans('user.contact_mobile'))->readOnly();
        $form->text('idcard_front',trans('user.idcard_front'))->readOnly();
        $form->text('idcard_back', trans('user.idcard_back'))->readOnly();
        $form->text('idcard_num', trans('user.idcard_num'))->readOnly();
        $form->image('avatar', trans('user.avatar'))->readOnly();
        $form->radio('gender',trans('user.gender'))->options(User::getGenderArr())->readOnly();
//        $form->text('sign', trans('user.sign'))->readOnly();
//        $form->text('home', trans('user.home'))->readOnly();
//        $form->date('birthday',trans('user.birthday'))->default(date('Y-m-d'))->readOnly();
        $form->number('account', trans('user.account'));
        $form->number('money', trans('user.money'));
        $form->switch('is_lock', trans('user.is_lock'));
        $form->ignore(['mobile','name','email','real_name','contact_mobile','idcard_front','idcard_back','idcard_num','avatar','gender','sign','home','birthday']);
        $form->footer(function ($footer) {
            // 去掉`查看`checkbox
            $footer->disableViewCheck();
            // 去掉`继续编辑`checkbox
            $footer->disableEditingCheck();
            // 去掉`继续创建`checkbox
            $footer->disableCreatingCheck();
        });
        $form->tools(function (Form\Tools $tools) {
            $tools->disableDelete();
            $tools->disableView();
        });

        $form->saving(function (Form $form) {

            $user = User::find(request()->route()->parameter('user'));

            if($user['first_leader']){
                //已经有上级
                if($form->first_leader){
                    $error = new MessageBag([
                        'title'   => '错误提示',
                        'message' => '已经有上级的用户， 不可再更改其上级',
                    ]);

                    return back()->with(compact('error'));
                }
            }
        });


        return $form;
    }
}
