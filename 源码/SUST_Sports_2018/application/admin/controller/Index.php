<?php
namespace app\admin\controller;

use think\Config;
use think\Request;
use think\Session;
use think\Controller;
use think\Db;
use app\common\controller\Index as commonIndex;

class Index extends Controller
{

  public function index()
  {
    return $this->fetch('login/index');
  }

  public function ajax_login($userid='',$password='')
  {
    $user = addslashes($userid);
    $password = addslashes($password);

    $userid = md5($user);
    $password = md5($password);

    // print_r($userid);
    // echo('<br/>');
    // print_r($password);

    $res = Db::table('admin')
    ->where([
      'adm_userid' => $userid,
      'adm_password' => $password
    ])
    ->update(['adm_timestamp' => time()]);

    if($res!=null){
      Session::set('adm_userid',$user);
      Session::set('adm_password',$password);
    }

    $che = $res==null?0:1;
    $opera = ' login ';
    $suc = $res==null?"fails ":"success ";
    $log = $user.$opera.$suc.' '.date("Y-m-d H:i:s")."\r\n";
    file_put_contents("log.txt", $log, FILE_APPEND);

    return json($che);
  }

}
