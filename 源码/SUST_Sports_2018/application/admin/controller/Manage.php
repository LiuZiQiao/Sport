<?php
namespace app\admin\controller;

use think\Config;
use think\Request;
use think\Session;
use think\Controller;
use think\Db;
use app\common\controller\Index as commonIndex;

class Manage extends Controller
{

  public function index()
  {
    if(Session::get('adm_userid')!=null)  return $this->fetch('manage/index');
    else  return "登录异常！";
  }



  //异步更新晋级决赛名单:把每一个运动员的参赛状态改为3（决赛）
  public function ajax_pro($class='',$item='',$ranks='')
  {
    $class = addslashes($class);
    $item = addslashes($item);
    $ranks = addslashes($ranks);

    if(!($this->sql_ver())){
      return json('授权异常！');
    }

    $ranks = preg_replace('/,+/',',',$ranks);
    $ranks = mb_substr($ranks,0,strlen($ranks)-1);
    $ranarr = explode(',',$ranks);

    //如果是团队项目  编号等于学院编号加项目编号
    if($class=='2'){
      for($i=0;$i<count($ranarr);$i++) $ranarr[$i] .= $item;
    }

    $tabl = $class=='1'?'athletes':'team';
    $tab_id = $class=='1'?'ath_id':'team_id';
    $tab_status = $class=='1'?'ath_status':'team_status';
    $tab_timestamp = $class=='1'?'ath_timestamp':'team_timestamp';

    //查询每一个运动员/团队的状态
    $status = null;
    for($i=0;$i<count($ranarr);$i++){
      $status[$i] = Db::table($tabl)
      ->where($tab_id,$ranarr[$i])
      ->value($tab_status);

      $starr[$i] = explode(',',$status[$i]);

      //修改运动员该项目的参赛状态为3
      $sign = false;
      for($j=0;$j<count($starr[$i]);$j++){
        if(preg_match("/$item/",$starr[$i][$j])){
          $starr[$i][$j] = $item.':'.'3';
          $sign = true;
          break;
        }
      }

      //如果没有此项比赛，则追加一个状态
      if($sign!=true) $starr[$i][count($starr[$i])] = $item.':'.'3';
      $starr[$i] = implode(',',$starr[$i]);
      //处理字符串开头错误
      if(mb_substr($starr[$i],0,1)=='0')  $starr[$i] = mb_substr($starr[$i],1,strlen($starr[$i]));
      if(mb_substr($starr[$i],0,1)==',')  $starr[$i] = mb_substr($starr[$i],1,strlen($starr[$i]));
      //更新每一个运动员的参赛状态
      $res = Db::name($tabl)->where([$tab_id=>$ranarr[$i]])->update([
        $tab_status => $starr[$i],
        $tab_timestamp => time()
      ]);

      //记录日志
      $suc = $res==null?"fails ":"success ";
      $userid = Session::get('adm_userid').' ';
      $opera = 'updata ';
      $tab = $tabl.' ';
      $codt = $tab_id.':'.$ranarr[$i].' ';
      $dat1 = $tab_status.':'.$starr[$i].' ';
      $log = $userid.$opera.$suc.$tab.$codt.'-> '.$dat1.' '.date("Y-m-d H:i:s")."\r\n";
      file_put_contents("log.txt", $log, FILE_APPEND);
    }

    return json($res==1?'success!':'fails!');
  }



  //异步更新决赛成绩
  public function ajax_fin($class='',$item='',$ranks='',$scores='',$integrals='',$newcor='')
  {
    if(!($this->sql_ver())){
      return json('授权异常');
    }

    $class = addslashes($class);
    $item = addslashes($item);
    $ranks = addslashes($ranks);
    $scores = addslashes($scores);
    $integrals = addslashes($integrals);
    $newcor = addslashes($newcor);

    $ranks = preg_replace('/,+/',',',$ranks);
    $scores = preg_replace('/,+/',',',$scores);
    $integrals = preg_replace('/,+/',',',$integrals);
    // $newcor = preg_replace('/,+/',',',$newcor);
    if(preg_match('/,$/',$ranks)){
      $ranks = mb_substr($ranks,0,strlen($ranks)-1);
      $scores = mb_substr($scores,0,strlen($scores)-1);
      $integrals = mb_substr($integrals,0,strlen($integrals)-1);
    }
    // $newcor = mb_substr($newcor,0,strlen($newcor)-1);
    $ranarr = explode(',',$ranks);
    $scoarr = explode(',',$scores);
    $ingarr = explode(',',$integrals);
    $newarr = explode(',',$newcor);

    //如果是团队项目  编号等于学院编号加项目编号
    if($class=='2'){
      for($i=0;$i<count($ranarr);$i++) $ranarr[$i] .= $item;
    }

    $tabl = $class=='1'?'athletes':'team';
    $tab_id = $class=='1'?'ath_id':'team_id';
    $tab_score = $class=='1'?'ath_score':'team_score';
    $tab_integral = $class=='1'?'ath_integral':'team_integral';
    $tab_timestamp = $class=='1'?'ath_timestamp':'team_timestamp';

    $repa = Db::table('finals')
    ->where('fin_id',$item)
    ->value('fin_score');
    if($repa!=null) return json('失败：重复提交！');

    // 更新决赛表排名和成绩
    $newcord = '';
    for($i=0;$i<count($ranarr);$i++){
      if($newarr[$i]=='1'){
        $newcord .= $ranarr[$i].':'.$scoarr[$i];
      }
    }

    $res1 = Db::name('finals')->where(['fin_id'=>$item])->update([
      'fin_rank' => $ranks,
      'fin_score' => $scores,
      'fin_newcord' => $newcord,
      'fin_timestamp' => time()
    ]);

    //记录日志
    $suc = $res1==null?"fails ":"success ";
    $userid = Session::get('adm_userid').' ';
    $opera = 'updata ';
    $tab = 'finals ';
    $codt = 'fin_id:'.$item.' ';
    $dat1 = 'fin_rank:'.$ranks.' ';
    $dat2 = 'fin_score:'.$scores.' ';
    $dat3 = 'fin_newcord:'.$newcord.' ';
    $log = $userid.$opera.$suc.$tab.$codt.'-> '.$dat1.'/ '.$dat2.'/ '.$dat3.' '.date("Y-m-d H:i:s")."\r\n";
    file_put_contents("log.txt", $log, FILE_APPEND);

    //更新个人/团队表决赛成绩
    for($i=0;$i<count($ranarr);$i++){

      $asco[$i] = Db::table($tabl)
      ->where($tab_id,$ranarr[$i])
      ->value($tab_score);

      $aing[$i] = Db::table($tabl)
      ->where($tab_id,$ranarr[$i])
      ->value($tab_integral);

      $ascorr[$i] = explode(',',$asco[$i]);
      $aingrr[$i] = explode(',',$aing[$i]);

      $sign1 = false;
      for($j=0;$j<count($ascorr[$i]);$j++){
        if(preg_match("/$item/",$ascorr[$i][$j])){
          $ascorr[$i][$j] = $item.':'.$scoarr[$i];
          $sign = true;
          break;
        }
      }

      $sign2 = false;
      for($k=0;$k<count($aingrr[$i]);$k++){
        if(preg_match("/$item/",$aingrr[$i][$k])){
          $ascorr[$i][$k] = $item.':'.$ingarr[$i];
          $sign2 = true;
          break;
        }
      }

      if($sign1!=true) $ascorr[$i][count($ascorr[$i])] = $item.':'.$scoarr[$i];
      $asco[$i] = implode(',',$ascorr[$i]);
      if($sign2!=true) $aingrr[$i][count($aingrr[$i])] = $item.':'.$ingarr[$i];
      $aing[$i] = implode(',',$aingrr[$i]);

      if(mb_substr($asco[$i],0,1)=='0')  $asco[$i] = mb_substr($asco[$i],1,strlen($asco[$i]));
      if(mb_substr($aing[$i],0,1)=='0')  $aing[$i] = mb_substr($aing[$i],1,strlen($aing[$i]));
      if(mb_substr($asco[$i],0,1)==',')  $asco[$i] = mb_substr($asco[$i],1,strlen($asco[$i]));
      if(mb_substr($aing[$i],0,1)==',')  $aing[$i] = mb_substr($aing[$i],1,strlen($aing[$i]));

      $res2 = Db::name($tabl)->where([$tab_id=>$ranarr[$i]])->update([
        $tab_score => $asco[$i],
        $tab_integral => $aing[$i],
        $tab_timestamp => time()
      ]);

      //记录日志
      $suc = $res2==null?"fails ":"success ";
      $userid = Session::get('adm_userid').' ';
      $opera = 'updata ';
      $tab = $tabl.' ';
      $codt = $tab_id.':'.$ranarr[$i].' ';
      $dat1 = $tab_score.':'.$asco[$i].' ';
      $dat2 = $tab_integral.':'.$aing[$i].' ';
      $log = $userid.$opera.$suc.$tab.$codt.'-> '.$dat1.'/ '.$dat2.' '.date("Y-m-d H:i:s")."\r\n";
      file_put_contents("log.txt", $log, FILE_APPEND);

    }




    //更新学院表积分和奖牌
    for($i=0;$i<count($ranarr);$i++){

      $rancol[$i] = mb_substr($ranarr[$i],0,2);

      $colsco = Db::table('college')
      ->where('col_id',$rancol[$i])
      ->field(['col_integral','col_gold','col_silver','col_copper'])
      ->select();

      $colsco[0]['col_integral'] = $colsco[0]['col_integral']+(float)$ingarr[$i];

      if($i==0)  $colsco[0]['col_gold']++;
      if($i==1)  $colsco[0]['col_silver']++;
      if($i==2)  $colsco[0]['col_copper']++;

      $res3 = Db::name('college')->where(['col_id'=>$rancol[$i]])->update([
        'col_integral' => $colsco[0]['col_integral'],
        'col_gold' => $colsco[0]['col_gold'],
        'col_silver' => $colsco[0]['col_silver'],
        'col_copper' => $colsco[0]['col_copper'],
        'col_timestamp' => time()
      ]);

      //记录日志
      $suc = $res3==null?"fails ":"success ";
      $userid = Session::get('adm_userid').' ';
      $opera = 'updata ';
      $tab = 'college ';
      $codt = 'col_id:'.$rancol[$i].' ';
      $dat1 = 'col_integral:'.$colsco[0]['col_integral'].' ';
      $dat2 = 'col_gold:'.$colsco[0]['col_gold'].' ';
      $dat3 = 'col_silver:'.$colsco[0]['col_silver'].' ';
      $dat4 = 'col_copper:'.$colsco[0]['col_copper'].' ';
      $log = $userid.$opera.$suc.$tab.$codt.'-> '.$dat1.'/ '.$dat2.'/ '.$dat3.'/ '.$dat4.' '.date("Y-m-d H:i:s")."\r\n";
      file_put_contents("log.txt", $log, FILE_APPEND);

    }

    return json($res1.$res2.$res3);
  }




  //异步校验
  public function ajax_check($class='',$item='',$cheid='')
  {

    $class = addslashes($class);
    $item = addslashes($item);
    $cheid = addslashes($cheid);

    $cheid = $class=='1'?$cheid:$cheid.$item;

    $tabl = $class=='1'?'athletes':'team';
    $tab_id = $class=='1'?'ath_id':'team_id';
    $tab_name = $class=='1'?'ath_name':'team_college';
    $tab_status = $class=='1'?'ath_status':'team_status';
    $effe = null;
    $data = null;

    $effe = Db::table($tabl)
    ->where($tab_id,$cheid)
    ->field([$tab_status,$tab_name])
    ->select();

    if($effe!=null){
      $part = preg_match("/$item/",$effe[0][$tab_status]);
      $data['part'] = $part;
    }

    if($effe!=null&&$class!='1'){
      $name = Db::table('college')
      ->where('col_id',$effe[0][$tab_name])
      ->field('col_name')
      ->select();
      $effe[0]['ath_name'] = $name[0]['col_name'];
    }

    $data['effe'] = $effe;

    return json($data);
  }




  //数据库关键操作验证
  public function sql_ver()
  {
    $userid = md5(Session::get('adm_userid'));
    $password = Session::get('adm_password');

    $res = Db::table('admin')
    ->where([
      'adm_userid' => $userid,
      'adm_password' => $password
    ])
    ->select();

    $che = $res==null?false:true;

    return $che;
  }




}
