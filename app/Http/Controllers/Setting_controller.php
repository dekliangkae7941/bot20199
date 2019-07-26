<?php

namespace App\Http\Controllers;


use Log;
use DB;
use App\Model\M_tar_subscribers;
use App\Model\M_tar_site;
use App\Model\M_tar_tracking;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as Controller;

class Setting_controller extends Controller
{
    public function setting_show(){

        $data_tracking  = $this->get_all_tracking();
        $data           = array('rs_tracking' => $data_tracking);

        return view('setting.v_setting' , $data);
    }


    private function get_all_tracking(){

        $result_tracking   =   \DB::table('tar_tracking')->get();
        return $result_tracking;
    }



    public function get_status_notify(Request $request){
        
        $get_status =   $request->all();
       
        if(!empty($get_status)){
        
            $user_id    =   $get_status['user_id'];
            $result     =   \DB::table('tar_subscribers')->where('tar_userId', $user_id)->get();
            $tracking   =   explode(",",$result[0]->tar_selected); // get รายการจาก DB แปลงให้อยู่ในรูปแบบ array โดย split "," ออก
            
            foreach($result as $key => $value){

                $this_result['tar_user_id']     = $value->tar_userId; 
                $this_result['tar_status']      = $value->tar_status;
                $this_result['tar_time']        = $value->tar_time;
                $this_result['tar_time_login']  = $value->tar_time_login;       
                $this_result['tar_hr']          = $value->tar_hr;       
                $this_result['tar_ums']          = $value->tar_ums;       
        
            }

            $data = array( 
                
                'this_result'   => $this_result,
                'this_tracking' => $tracking
        
                );
        }

        return $data;
    }

    public function update_notify_time(Request $request){

        $get_time = $request->all();
        

        if(!empty($get_time)){

            $notify_time     = $get_time['set_time'];
            $user_id         = $get_time['user_id'];
            \DB::table('tar_subscribers')->where('tar_userId', $user_id)->update(['tar_time' => $notify_time]);

        }
    }

    public function update_notify_status(Request $request){

        $get_notify = $request->all();

        if(!empty($get_notify)){

            $notify_status      = $get_notify['notify_status'];
            $user_id            = $get_notify['user_id'];
            \DB::table('tar_subscribers')->where('tar_userId', $user_id)->update(['tar_status' => $notify_status]);

        }
    }

    public function update_tracking(Request $request){

        $get_tracking   =   $request->all();

        if(!empty($get_tracking)){

            $checked    = $get_tracking['value_checked'];
            $user_id    = $get_tracking['user_id'];
            \DB::table('tar_subscribers')->where('tar_userId', $user_id)->update(['tar_selected' => implode(",",$checked)]);

        }
    }
    public function update_time_login(Request $request){

        $get_time_login = $request->all();

        if(!empty($get_time_login)){

            $time_login =   $get_time_login['time_login'];
            $user_id    =   $get_time_login['user_id'];
            \DB::table('tar_subscribers')->where('tar_userId', $user_id)->update(['tar_time_login' => $time_login]);
        }
    }

    public function get_url_dropdownUMS(Request $request){

        $get_data   = $request->all();
        
        if(!empty($get_data)){
            
            $user_id    = $get_data['user_id'];
            $result     =   \DB::table('tar_subscribers')->join('tar_site','tar_subscribers.tar_id','=','tar_site.sit_tar_id')
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
     public function get_url_dropdownHR(Request $request){

        $get_data   = $request->all();
        
        if(!empty($get_data)){
            
            $user_id    = $get_data['user_id'];
            $result     =   \DB::table('tar_subscribers')->join('tar_site','tar_subscribers.tar_id','=','tar_site.sit_tar_id')
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

    public function update_hr_url(Request $request){

        $get_data = $request->all();

        if(!empty($get_data)){
            
            $user_id    =   $get_data['user_id'];
            $set_url    =   $get_data['set_url'];
            \DB::table('tar_subscribers')->where('tar_userId', $user_id)->update(['tar_hr' => $set_url]);
        }
    }
    public function update_ums_url(Request $request){

        $get_data = $request->all();

        if(!empty($get_data)){
            
            $user_id    =   $get_data['user_id'];
            $set_url    =   $get_data['set_url'];
            \DB::table('tar_subscribers')->where('tar_userId', $user_id)->update(['tar_ums' => $set_url]);
        }
    }
}
