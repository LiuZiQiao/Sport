<?php
namespace app\index\controller;

use think\Config;
use think\Request;
use think\Controller;
use think\Db;
use app\common\controller\Index as commonIndex;
use app\index\model\Match;
class Event extends Controller
{

  //赛事页
    public function index()
    {
      $match=array();
      $result = Match::all();
      date_default_timezone_set("Asia/Shanghai");
      // $current_time=date("Y-m-d h:i:s",time());
      foreach($result as $key=>$value){
        if(!empty($value->mat_pretime)&&strtotime($value->mat_pretime)>time()){
            $matrem = array('mat_name' =>$value->mat_name,'mat_time'=> strtotime($value->mat_pretime),'mat_status'=>'预赛');
            array_push($match,$matrem);
        }
        if(!empty($value->mat_remtime)&&strtotime($value->mat_remtime)>time()){
            $matrem = array('mat_name' =>$value->mat_name,'mat_time'=> strtotime($value->mat_remtime),'mat_status'=>'复赛');
            array_push($match,$matrem);
        }
        if(!empty($value->mat_fintime)&&strtotime($value->mat_fintime)>time()){
            $matrem = array('mat_name' =>$value->mat_name,'mat_time'=> strtotime($value->mat_fintime),'mat_status'=>'决赛');
            array_push($match,$matrem);
        }
      }
      array_multisort(array_column($match,'mat_time'),SORT_ASC,$match);
      foreach ($match as $key => $value) {
          $match[$key]['mat_time']=date('Y-m-d H:i:s',$value['mat_time']);
          $match[$key]['mat_time'] = mb_substr($match[$key]['mat_time'],6,strlen($match[$key]['mat_time'])-9);
      }

      $mat = array();
      $i = 0;
      if(count($match)>=6){
        for($i=0;$i<6;$i++)   $mat[$i] = $match[$i];
      }else {
        foreach ($match as $key => $value){
          $mat[$i] = $value;
          $i++;
        }
      }

      $this->assign('matime',$mat);
      return $this->fetch('event/index');
    }



    //所有赛事
    public function ajax_mat(){

      $matpre = Db::table('match')
      ->field(['mat_name','mat_pretime as mat_time'])
      ->order('mat_pretime')
      ->select();
      for($i=0;$i<count($matpre);$i++){
        $matpre[$i]['mat_status'] = '预赛';
      }

      $matfin = Db::table('match')
      ->field(['mat_name','mat_fintime as mat_time'])
      ->order('mat_fintime')
      ->select();
      for($i=0;$i<count($matfin);$i++){
        $matfin[$i]['mat_status'] = '决赛';
      }

      //合并数组
      $matall = array_merge($matpre,$matfin);
      //排序
      for($i=0;$i<count($matall);$i++){
        for($j=$i;$j<count($matall);$j++){
          if(strtotime($matall[$i]['mat_time'])>strtotime($matall[$j]['mat_time'])){
            $tem = $matall[$i];
            $matall[$i] = $matall[$j];
            $matall[$j] = $tem;
          }
        }
      }

      $j = 0;
      $k = 0;
      $mat1 = array();
      $mat2 = array();
      for($i=0;$i<count($matall);$i++){
        if(preg_match('/4-25/',$matall[$i]['mat_time'])){
          $mat1[$j] = $matall[$i];
          $j++;
        }
        if(preg_match('/4-26/',$matall[$i]['mat_time'])){
          $mat2[$k] = $matall[$i];
          $k++;
        }
      }

      for($i=0;$i<count($mat1);$i++){
        $mat1[$i]['mat_time'] = mb_substr($mat1[$i]['mat_time'],10,strlen($mat1[$i]['mat_time'])-2);
      }

      for($i=0;$i<count($mat2);$i++){
        $mat2[$i]['mat_time'] = mb_substr($mat2[$i]['mat_time'],10,strlen($mat2[$i]['mat_time'])-2);
      }

      $matc['one'] = $mat1;
      $matc['two'] = $mat2;

      return json($matc);
    }


}
