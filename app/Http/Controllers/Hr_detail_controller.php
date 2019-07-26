<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Log;
use DB;
use App\Model\M_tar_subscribers;
use App\Model\M_tar_hr_detail;
use Illuminate\Routing\Controller as Controller;

class Hr_detail_controller extends Controller
{
    public function hr_detail_show(){

        return view('hr_detail.v_hr_detail');

    }
    public function get_data_detail(Request $request){

        $get_data   =  $request->all();
       
        if(!empty($get_data)){
        $user_id        =   $get_data['user_id'];
        $start          =   $get_data['start'];
        $end            =   $get_data['end'];
        $site           =   $get_data['site'];
       
       
       if($start == '' && $end == '' && $site == '' || $site == '1'){


            $result         =   \DB::table('tar_subscribers')->join('tar_hr_detail','tar_subscribers.tar_id','=','tar_hr_detail.hr_tar_id')
                                ->where('tar_userId','=',$user_id)
                                ->orderBy('hr_date', 'desc')
                                ->orderBy('hr_status_name', 'asc')
                                ->whereRaw('1 = 1')->get();

        }elseif($start == '' && $end == '' && $site != "" ){

                $result     =   \DB::table('tar_subscribers')->join('tar_hr_detail','tar_subscribers.tar_id','=','tar_hr_detail.hr_tar_id')
                                ->where('tar_userId','=',$user_id)
                                ->orderBy('hr_date', 'desc')
                                ->orderBy('hr_status_name', 'asc')
                                ->where('hr_sit_id','=',$site)->get();

        }else if($start != '' && $end != '' && $site != ""){

              $result       =   \DB::table('tar_subscribers')->join('tar_hr_detail','tar_subscribers.tar_id','=','tar_hr_detail.hr_tar_id')
                                ->where('tar_userId','=',$user_id)
                                ->where('hr_sit_id','=',$site)
                                ->whereBetween('hr_date',[$start,$end])
                                ->orderBy('hr_date', 'desc')
                                ->orderBy('hr_status_name', 'asc')
                                ->get();
                           

        }elseif($start != '' && $end != '' && $site == ""){
            $result         =   \DB::table('tar_subscribers')->join('tar_hr_detail','tar_subscribers.tar_id','=','tar_hr_detail.hr_tar_id')
                                ->where('tar_userId','=',$user_id)
                                ->whereRaw('1 = 1')
                                ->whereBetween('hr_date',[$start,$end])
                                ->orderBy('hr_date', 'desc')
                                ->orderBy('hr_status_name', 'asc')
                                ->get();
        }else{

            $result         =   \DB::table('tar_subscribers')->join('tar_hr_detail','tar_subscribers.tar_id','=','tar_hr_detail.hr_tar_id')
                                ->where('tar_userId','=',$user_id)
                                ->whereBetween('hr_date',[$start,$end])
                                ->orderBy('hr_date', 'desc')
                                ->orderBy('hr_status_name', 'asc')
                                ->get();
        }
        
 
         $data = array( 
                
                'result' => $result
        
            );
        }
        echo json_encode($data);
        
    }
    public function get_site(Request $request){
        
        $get_data   =  $request->all();

        if(!empty($get_data)){
            $user_id        =   $get_data['user_id'];
            $result         =   \DB::table('tar_subscribers')->join('tar_site','tar_subscribers.tar_id','=','tar_site.sit_tar_id')
                                ->where('tar_userId', $user_id)
                                ->where('sit_topic','HR')->get();

            $num = count($result);

            if($num == 0 ){

                $result = array(['sit_name'=>'Please add URL']);
                $data = array( 
                    'result' => $result   
                );
                
            }else{
               
                $data = array(                 
                    'result' => $result
                );
            }
        }
        echo json_encode($data);
    }
}
