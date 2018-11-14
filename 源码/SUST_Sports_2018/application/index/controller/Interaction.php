<?php
namespace app\index\controller;

use think\Config;
use think\Request;
use think\Controller;
use think\Db;
use app\common\controller\Index as commonIndex;

class Interaction extends Controller
{

    // 互动页
    public function index()
    {

      return $this->fetch('interaction/index');

    }


}
