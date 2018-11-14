<?php
namespace app\index\controller;

use think\Config;
use think\Request;
use think\Controller;
use think\Db;
use app\common\controller\Index as commonIndex;

class Index extends Controller
{
  //主页：积分榜
  public function index()
  {
    $college = Db::table('college')
    ->order('col_integral DESC')
    ->select();

    $this->assign('rank',$college);
    return $this->fetch('');
  }

  public function api_colrank()
  {
    $college = Db::table('college')
    ->order('col_integral DESC')
    ->select();

    return json($college);
  }


}
