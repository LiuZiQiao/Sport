<?php
namespace app\index\controller;

use think\Config;
use think\Request;
use think\Controller;
use think\Db;
use app\common\controller\Index as commonIndex;

class Query extends Controller
{

    //查询页
    public function index()
    {
      return $this->fetch('query/index');
    }


    //查询页ajax：按姓名/编号查询
    public function ajax_name($search='')
    {
      $search = addslashes($search);

      $res = Db::table(['athletes'])
      ->join('college','athletes.ath_college = college.col_id')
      ->where(['ath_id'=>$search])
      ->field(['ath_name','col_name','ath_integral','ath_match'])
      ->select();

      if($res==null){
        $res = Db::table('athletes')
        ->join('college','athletes.ath_college = college.col_id')
        ->where('ath_name','like','%'.$search.'%')
        ->field(['ath_name','col_name','ath_integral','ath_match'])
        ->select();
      }

      if($res!=null){
        for($i=0;$i<count($res);$i++){
          if($res[$i]['ath_integral']!='0'&&$res[$i]['ath_integral']!=''){
            $mnuarr = explode(',',$res[$i]['ath_integral']);
            $sum = 0;
            for($j=0;$j<count($mnuarr);$j++){
              if($mnuarr[$j]!=''&&$mnuarr[$j]!=null){
                preg_match("/:.+/", $mnuarr[$j], $mres);
                if($mres!=null&&$mres!=''){
                  $mnuarr[$j] = mb_substr($mres[0],1,strlen($mres[0])-1);
                  $sum += (float)$mnuarr[$j];
                }
              }
            }
            $res[$i]['ath_integral'] = $sum;
          }
        }
      }

      return json($res);
    }


    //查询页ajax：按比赛项目查询
    public function ajax_class($class='',$item='')
    {
      $class = addslashes($class);
      $item = addslashes($item);

      $tab = $class=='1'?'athletes':'team';
      $col = $class=='1'?'ath_college':'team_college';
      $queid = $class=='1'?'ath_id':'team_id';
      $sel = $class=='1'?'ath_name,col_name,ath_score':'col_name,team_score';
      $scor = $class=='1'?'ath_score':'team_score';
      $match_aths = null;

      $match_rank = Db::table('finals')
      ->where(['fin_id'=>$item])
      ->field('fin_rank')
      ->select();

      if(isset($match_rank[0]['fin_rank'])){

        if($match_rank[0]['fin_rank']!=''){

          //如果是团队项目  编号等于学院编号加项目编号
          if($class=='2'){
            $rankarr[0]['fin_rank'] = explode(',',$match_rank[0]['fin_rank']);
            for($i=0;$i<count($rankarr[0]['fin_rank']);$i++)  $rankarr[0]['fin_rank'][$i] .= $item;
            $match_rank[0]['fin_rank'] = implode(',',$rankarr[0]['fin_rank']);
          }

          $match_aths = Db::query("select ".$sel." from ".$tab.",college"." where ".$tab.".".$col."=college.col_id and ".$queid." IN (".$match_rank[0]['fin_rank'].") order by field (".$queid.",".$match_rank[0]['fin_rank'].")");

          if($match_aths!=null){

            for($i=0;$i<count($match_aths);$i++){
              $mtsarr = explode(',',$match_aths[$i][$scor]);
              for($j=0;$j<count($mtsarr);$j++){
                if($mtsarr[$j]!=''&&$mtsarr[$j]!=null){
                  if(preg_match("/$item/",$mtsarr[$j])){
                    preg_match("/:.+/", $mtsarr[$j], $mres);
                    if($mres!=null&&$mres!=''){
                      $match_aths[$i][$scor] = mb_substr($mres[0],1,strlen($mres[0])-1);
                      break;
                    }
                  }
                }
              }
            }
          }

        }

      }

      return json($match_aths);
    }



    //查询页ajax：按学院查询
    public function ajax_col($coll='')
    {

      $coll = addslashes($coll);

      $res = Db::table(['athletes'])
      ->where(['ath_college'=>$coll])
      ->field(['ath_name','ath_integral'])
      ->select();

      if($res!=null){

        for($i=0;$i<count($res);$i++){
          if($res[$i]['ath_integral']!='0'&&$res[$i]['ath_integral']!=null){

            $mnuarr = explode(',',$res[$i]['ath_integral']);
            $sum = 0;
            if($mnuarr!=''&&$mnuarr!=null){
              for($j=0;$j<count($mnuarr);$j++){
                if($mnuarr[$j]!=''&&$mnuarr[$j]!=null){
                  preg_match("/:.+/", $mnuarr[$j], $mres);
                  if($mres!=''&&$mres!=null){
                    $mnuarr[$j] = mb_substr($mres[0],1,strlen($mres[0])-1);
                    $sum += (int)$mnuarr[$j];
                  }
                }
              }
            }

            $res[$i]['ath_integral'] = $sum;

          }

        }

        for($i=0;$i<count($res);$i++){
          for($j=$i;$j<count($res);$j++){
            if($res[$i]['ath_integral']<$res[$j]['ath_integral']){
              $tem = $res[$i];
              $res[$i] = $res[$j];
              $res[$j] = $tem;
            }
          }
        }

      }else{
        $res = null;
      }

      return json($res);
    }


}
