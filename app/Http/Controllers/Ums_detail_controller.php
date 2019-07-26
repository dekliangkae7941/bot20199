<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Log;
use DB;
use App\Model\M_tar_subscribers;
use App\Model\M_tar_ums_detail;
use Illuminate\Routing\Controller as Controller;

class Ums_detail_controller extends Controller
{
    public function ums_detail_show(){

        return view('ums_detail.v_ums_detail');
    }

    public function get_data_detail(Request $request){

        $get_data   =  $request->all();
        
        if(!empty($get_data)){
        $user_id        =   $get_data['user_id'];
        $start          =   $get_data['start'];
        $end            =   $get_data['end'];
        $site           =   $get_data['site'];

       
        if($start == '' && $end == '' && $site == '' || $site =='1'){

            $result         =   \DB::table('tar_subscribers')->join('tar_ums_detail','tar_subscribers.tar_id','=','tar_ums_detail.ums_tar_id')
                                ->where('tar_userId','=',$user_id)
                                ->orderBy('ums_last_date', 'desc')
                                ->orderBy('ums_wg_name', 'asc')
                                ->whereRaw('1 = 1')->get();
                              

        }elseif($start == '' && $end == '' && $site != "" ){

                $result     =   \DB::table('tar_subscribers')->join('tar_ums_detail','tar_subscribers.tar_id','=','tar_ums_detail.ums_tar_id')
                                ->where('tar_userId','=',$user_id)
                                ->orderBy('ums_last_date', 'desc')
                                ->orderBy('ums_wg_name', 'asc')
                                ->where('ums_sit_id','=',$site)->get();
                          

        }else if($start != '' && $end != '' && $site != ""){

              $result       =   \DB::table('tar_subscribers')->join('tar_ums_detail','tar_subscribers.tar_id','=','tar_ums_detail.ums_tar_id')
                                ->where('tar_userId','=',$user_id)
                                ->where('ums_sit_id','=',$site)
                                ->whereBetween('ums_last_date',[$start,$end])
                                ->orderBy('ums_last_date', 'desc')
                                ->orderBy('ums_wg_name', 'asc')
                                ->get();
                        

        }elseif($start != '' && $end != '' && $site == ""){
            $result         =   \DB::table('tar_subscribers')->join('tar_ums_detail','tar_subscribers.tar_id','=','tar_ums_detail.ums_tar_id')
                                ->where('tar_userId','=',$user_id)
                                ->whereRaw('1 = 1')
                                ->whereBetween('ums_last_date',[$start,$end])
                                ->orderBy('ums_last_date', 'desc')
                                ->orderBy('ums_wg_name', 'asc')
                                ->get();
         
        }else{

            $result         =   \DB::table('tar_subscribers')->join('tar_ums_detail','tar_subscribers.tar_id','=','tar_ums_detail.ums_tar_id')
                                ->where('tar_userId','=',$user_id)
                                ->whereBetween('ums_last_date',[$start,$end])
                                ->orderBy('ums_last_date', 'desc')
                                ->orderBy('ums_wg_name', 'asc')
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
                                ->where('sit_topic','UMS')->get();

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
