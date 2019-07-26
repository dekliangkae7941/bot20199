<?php

namespace App\Http\Controllers;

use Log;
use DB;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Model\M_tar_subscribers;
use App\Model\M_tar_tracking;
use App\Model\M_tar_site;

class Tracking_linewebhook_controller extends BaseController
{
    /*
    * post_service_receive
    * ส่ง Request ไปที่ Line dev เพื่อขอการใช้ API
    * @input	Request
    * @output	status 200
    * @author	Aphisit Sipalat 58160161
    * @Create Date 2562-01-11
    */

   public function post_service_receive(Request $request){
          
        $data_body      = $request->all();
        $data_header    = $request->header();
        $this->isEvents = true;

        if(!empty($data_body)){
           
            try{
                $this->event_hadler($data_body);
                return response()->json(['status' => 'ok'], 200);

            }catch(Exception $e) {

                \Log::debug('Error '.$e);
                return response()->json(['status' => 'ok'], 200);

            }
        }             
   }

   /*
    * event_hadler
    * ฟังก์ชันจัดการ event จากไลน์ โดยรับข้อมูลจาก Tracking_linewebhook_controller
    * @input	$arr_request
    * @author	Aphisit Sipalat 58160161
    * @Create Date 2562-01-14
    */
   public function event_hadler($data_body){

        if(!empty($data_body)){
          
            foreach ($data_body['events'] as $event ) {

                if (array_key_exists('message', $event)) 
                {
                    $message        = (object) $event['message'];
                }

                $reply_message  = '';
                $source         = (object) $event['source'];
                $reply_token    = $event['replyToken'];
                $timestamp      = $event['timestamp'];
                $event_type     = $event['type'];
                $line_userid    = $event['source']['userId'];
               

                if($event_type === 'message'){
                   
                    $this->event_message($event['message'], $line_userid);
     
                }elseif($event_type =='postback'){

                    $text = $event['postback']['data'];
                    $this->event_postback($text, $line_userid);
                   
                }
            } 

        }  
    
    }

   /*
    * event_message
    * ฟังก์ชันสำหรับรับข้อความ หรือ event ต่าง ๆ สำหรับผู้ใช้งานครั้งแรก โดยจะส่งค่าไปยังฟังก์ชันเก็บ user id
    * @input	$message,$user_id
    * @author	Aphisit Sipalat 58160161
    * @Create Date 2562-01-14
    */
    private function event_message($message, $user_id){

        $message_type = $message['type'];

        if($message_type == 'text'){

            $text = $message['text'];
            $this->tracking_input_userid($user_id);

        }

    }
    
   /*
    * event_post
    * ฟังก์ชันสำหรับรับข้อความจากการกดปุ่ม Flexmessage หรือ event เป็น postback    
    * @input	$message,$user_id
    * @author	Aphisit Sipalat 58160161
    * @Create Date 2562-01-14
    */
    private function event_postback($message, $user_id){

        if($message == 'UMS Detail'){
            
            $this->get_detail_ums($user_id);

        }elseif($message =='HR Detail'){

            $this->get_detail_hr($user_id);
        }

    }
    

    /*
    * tracking_input_userid
    * ฟังก์ชันสำหรับเก็บ user id ของผู้ที่ทำการ Subcriber LINE application และมีการตอบกลับเมื่อทำการบันทึกข้อมูลเรียบร้อยแล้ว
    * @input	$message,$user_id
    * @author	Aphisit Sipalat 58160161
    * @Create Date 2562-01-18
    */
    private function tracking_input_userid($user_id){

        $str_trk         = "";
        $userId          = $user_id;
        $count_result    =  \DB::table('tar_subscribers')->where('tar_userId','=',$userId)->count();
        $tracking_result =  \DB::table('tar_tracking')->get();
        
        if($count_result == 0){

            foreach($tracking_result as $key => $value){
                
                if(sizeof($tracking_result)==$key+1){

                    $str_trk    =  $str_trk . $value->trk_data;

                }else{

                    $str_trk    =$str_trk . $value->trk_data . ',' ;

                }
            }
            \DB::table('tar_subscribers')->insert(['tar_userId' => $userId,'tar_time' => 'week','tar_hr'=>'HR site on BCNS','tar_time_login'=>7 ,'tar_ums'=>'UMS login','tar_status'=>1 , 'tar_selected' => $str_trk]);
            $this->add_api_defalte($userId);

        }
    }

    private function add_api_defalte($user_id){
        
        //defalt for call API UMS and HR
            $user_tar_id = '';
            $ums_name   = 'UMS login';
            $ums_topic  = 'UMS';
            $ums_site   = 'https://thepd-nu.aos.in.th/index.php/Service_ums/week';
            $ums_detail = 'UMS Api for checked user login';

            $hr_name   = 'HR site on BCNS';
            $hr_topic  = 'HR';
            $hr_site   = 'https://nursing.iserl.org/bcns/index.php/hr/Service_miss_leave_late/';
            $hr_detail = 'HR Api for checked user data';

            $hr_name_two    = 'HR site on CKR';
            $hr_topic_two   = 'HR';
            $hr_site_two    = 'https://nursing.iserl.org/ckr/index.php/hr/Service_miss_leave_late/';
            $hr_detail_two  = 'HR Api for checked user data site two';    

            $subscriber = \DB::table('tar_subscribers')->where('tar_userId', $user_id)->get();
          
            //เพิ่ม url api สำหรับการตั้ง defalt ไว้ในตาราง tar_site
            foreach($subscriber as $val){
                
                $user_tar_id = $val->tar_id;

                \DB::table('tar_site')->insert(['sit_url' => $ums_site ,'sit_detail'=>$ums_detail,'sit_name'=>$ums_name,'sit_topic'=>$ums_topic,'sit_tar_id'=>$user_tar_id]);
                \DB::table('tar_site')->insert(['sit_url' => $hr_site ,'sit_detail'=>$hr_detail,'sit_name'=>$hr_name,'sit_topic'=>$hr_topic,'sit_tar_id'=>$user_tar_id]);
                \DB::table('tar_site')->insert(['sit_url' => $hr_site_two ,'sit_detail'=>$hr_detail_two,'sit_name'=>$hr_name_two,'sit_topic'=>$hr_topic_two,'sit_tar_id'=>$user_tar_id]);
        
            }              
            
            //push message to LINE
            $arr_postdata = array();
            $arr_postdata['to'] = $user_id;
            $arr_postdata['messages'][0]['type'] = "text";
            $arr_postdata['messages'][0]['text'] = "บันทึกข้อมูลสำเร็จ";
            $arr_postdata['messages'][1]['type'] ="sticker";
            $arr_postdata['messages'][1]['packageId'] = "2";
            $arr_postdata['messages'][1]['stickerId'] = "179";

            $data = json_encode($arr_postdata);
            $this->send_reply_message('/push',$data);
         

    }

    /*
    * send_reply_message
    * ฟังก์ชันสำหรับส่งข้อมูล หรือ event ไปยัง LINE API เพื่อนำไปแสดงผลใน LINE application
    * @input	$method,$post_body
    * @author	Aphisit Sipalat 58160161
    * @Create Date 2562-01-14
    */
    private function send_reply_message($method, $post_body){

        $API_URL        = env('LINE_API_URL');  // URL API_Messgae LINE
        $ACCESS_TOKEN   = env('LINE_TOKEN');    // TOKEN of Account LINE DEV
        $CHANNELSECRET  = env('LINE_SECRET');   // CHANNEL of Account LINE DEV
        $POST_HEADER    = array('Content-Type: application/json', 'Authorization: Bearer ' . $ACCESS_TOKEN);


        $ch = curl_init($API_URL.$method);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $POST_HEADER);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_body);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
        
    }

     /*
    * get__ums_api
    * ฟังก์ชันสำหรับคิวรี่ URL
    * @input	$$message, $user_id
    * @author	Aphisit Sipalat 58160161
    * @Create Date 2562-02-21
    */
    private function get_detail_ums($user_id){

        $subscribers    =   \DB::table('tar_subscribers')->join('tar_site','tar_subscribers.tar_id','=','tar_site.sit_tar_id')
                            ->where('tar_subscribers.tar_status','=',1)
                            ->where('tar_subscribers.tar_userId',$user_id)->get();

        $result_site    =   \DB::table('tar_subscribers')->join('tar_site','tar_subscribers.tar_id','=','tar_site.sit_tar_id')
                            ->where('tar_subscribers.tar_userId',$user_id)
                            ->get();

        $site_id    = array();
        $sit_name   = array();
        $site_topic = array();
        $site_url   = array();

        foreach($result_site as $site){
        
            $site_id[$site->sit_topic]      =   $site->sit_id;
            $site_name[$site->sit_topic]    =   $site->sit_name;
            $site_topic[$site->sit_topic]   =   $site->sit_topic;
            $site_url[$site->sit_topic]     =   $site->sit_url;

        }
        $data = '';

        foreach($subscribers as $value){
            
            $user_id    = $value->tar_userId;

            if(strstr($value->tar_selected,',')){

                $topics     = explode(",",$value->tar_selected);

                foreach($topics as $key => $topic){

                    if($site_topic[$topic] == 'UMS'){
                        
                        if($value->tar_time_login == '7' && $value->sit_name == $value->tar_ums){

                            $respond = $this->get__ums_api($value->sit_url); //ส่ง url ไป get respond
                            $this->send_data_ums($respond,$user_id); // ส่งเข้าฟังก์ชันส่งข้อความเข้าสู่ไลน์
                        }
                    }

                }

            }else {

                    if($value->tar_selected == 'UMS'){
                        
                        if($value->tar_time_login == '7' && $value->sit_name == $value->tar_ums){

                            $respond = $this->get__ums_api($value->sit_url); //ส่ง url ไป get respond
                            $this->send_data_ums($respond,$user_id); // ส่งเข้าฟังก์ชันส่งข้อความเข้าสู่ไลน์
                        }
                    }

            }
            
            
        }

    }

    public function get_detail_hr($user_id){
       
        $subscribers    =   \DB::table('tar_subscribers')->join('tar_site','tar_subscribers.tar_id','=','tar_site.sit_tar_id')
                            ->where('tar_subscribers.tar_status','=',1)
                            ->where('tar_subscribers.tar_userId',$user_id)->get();

        $result_site    =   \DB::table('tar_subscribers')->join('tar_site','tar_subscribers.tar_id','=','tar_site.sit_tar_id')
                            ->where('tar_subscribers.tar_userId',$user_id)
                            ->get();

        $site_id    = array();
        $sit_name   = array();
        $site_topic = array();
        $site_url   = array();

        foreach($result_site as $site){
            
            $site_id[$site->sit_topic]      =   $site->sit_id;
            $site_name[$site->sit_topic]    =   $site->sit_name;
            $site_topic[$site->sit_topic]   =   $site->sit_topic;
            $site_url[$site->sit_topic]     =   $site->sit_url;

        }
        $data = '';
        foreach($subscribers as $value){
            
            $user_id    = $value->tar_userId;

            if(strstr($value->tar_selected,',')){ //เช็คว่า ข้อมูลที่ถูกเก็บ มี , อยู่หรือเปล่า เช้น UMS.HR หรือ HR เพียงแค่ตัวเดียว

                $topics     = explode(",",$value->tar_selected);

                foreach($topics as $key => $topic){

                    if($site_topic[$topic] == 'HR'){
                    
                        if($value->tar_time == 'day' && $value->sit_name == $value->tar_hr){

                            $respond = $this->get_hr_api($value->sit_url,$value->tar_time); //ส่ง url ไป get respond
                            $this->send_data_hr($respond,$user_id); // ส่งเข้าฟังก์ชันส่งข้อความเข้าสู่ไลน์


                        }
                        else if($value->tar_time == 'week' && $value->sit_name == $value->tar_hr){

                            $respond = $this->get_hr_api($value->sit_url,$value->tar_time); //ส่ง url ไป get respond
                            $this->send_data_hr($respond,$user_id); // ส่งเข้าฟังก์ชันส่งข้อความเข้าสู่ไลน์
                    

                        }
                        else if($value->tar_time == 'month' && $value->sit_name == $value->tar_hr){

                            $respond = $this->get_hr_api($value->sit_url,$value->tar_time); //ส่ง url ไป get respond
                            $this->send_data_hr($respond,$user_id); // ส่งเข้าฟังก์ชันส่งข้อความเข้าสู่ไลน์

                        }
                    }

                }
                
            }else{

                if($value->tar_selected == 'HR'){
                    
                        if($value->tar_time == 'day' && $value->sit_name == $value->tar_hr){

                            $respond = $this->get_hr_api($value->sit_url,$value->tar_time); //ส่ง url ไป get respond
                            $this->send_data_hr($respond,$user_id); // ส่งเข้าฟังก์ชันส่งข้อความเข้าสู่ไลน์


                        }
                        else if($value->tar_time == 'week' && $value->sit_name == $value->tar_hr){

                            $respond = $this->get_hr_api($value->sit_url,$value->tar_time); //ส่ง url ไป get respond
                            $this->send_data_hr($respond,$user_id); // ส่งเข้าฟังก์ชันส่งข้อความเข้าสู่ไลน์
                    

                        }
                        else if($value->tar_time == 'month' && $value->sit_name == $value->tar_hr){

                            $respond = $this->get_hr_api($value->sit_url,$value->tar_time); //ส่ง url ไป get respond
                            $this->send_data_hr($respond,$user_id); // ส่งเข้าฟังก์ชันส่งข้อความเข้าสู่ไลน์

                        }
                    }
             
            }
            
            
        }
    }
    
    /*
    * send_data_ums
    * ฟังก์ชันสำหรับส่ง detail UMS API เข้าไลน์
    * @input	$url
    * @author	Aphisit Sipalat 58160161
    * @Create Date 2562-02-21
    */
    private function send_data_ums($respond,$user_id){
       
        $data_respond = json_decode($respond,true);
        $text   = '';
        $num    = 20;
        $count  = 0;
        $arr    = array();
        $index  = 0;
        $i      = 1;

        foreach($data_respond['data'] as $value){
            foreach($value as $key=>$val){
                $round = sizeof($value);
                
                if($index <= $round){
                if($index <= $num){
                   
                    $text .= $key+1 ." ".' '.$val['fullname']."\xA".'        Last Date : '.date("d/m/Y", strtotime($val['last_date']))."\xA";
                    $index++;
                }
                if($index == $num){
                    $num+=20;
                    $arr[$count] = $text;
                    $count++;   
                    $text ="";
                  
                }
                if($index == $round){ 
                    $arr[$count] = $text;

                }
            }
                
            }
        }
           $round = sizeof($arr);
            for($i=0;$i< $round;$i++){
            $arr_postdata = array();
            $arr_postdata['to'] = $user_id;
            $arr_postdata['messages'][0]['type'] = "text";
            $arr_postdata['messages'][0]['text'] = $arr[$i];

            $data = json_encode($arr_postdata);
           
            $this->send_reply_message('/push',$data);
        }
       
        //ฟังก์ชันสำหรับการส่ง Text ข้อความรายชื่อผู้ที่ไม่เข้าสู่ระบบสัปดาห์นั้น และวันที่เข้าใช้งานล่าสุด
        //โดยส่ง Text เป็นทีละชุด ชุดละ 20 ข้อความ $text
        
    }

    private function send_data_hr($respond,$user_id){
       
        $data_respond = json_decode($respond,true);
        $text   = '';
        $num    = 15;
        $count  = 0;
        $arr    = array();
        $index  = 0;
        $i      = 1;
        $j      = 1;
        $number_of_key = 0;

        foreach($data_respond['data'] as $key=>$value){
               
                $round = sizeof($value);
                $number_of_key = sizeof($data_respond['data']);
                $text .= "----------------------------------------"."\xA".$key."\xA"."----------------------------------------"."\xA";
            foreach($value as $key=>$val){

               
                if((int)$key <= $round){
                if((int)$index <= $num){
                        $text .= $val['FullName']."\t"."  วันที่ ".$val['tio_date']."\xA";
                        $index++;
                        $j++;
             
                }
                if((int)$index == $num){
                    $num+=15;
                    $arr[$count] = $text;
                    $count++;   
                    $text ="";
                
                }
                if((int)$index == $round){ 
                    $arr[$count] = $text;
                    $text ="";
                }

            }
            
                
            }
        }
           $round = sizeof($arr);
            for($i=0;$i< $round;$i++){
            $arr_postdata = array();
            $arr_postdata['to'] = $user_id;
            $arr_postdata['messages'][0]['type'] = "text";
            $arr_postdata['messages'][0]['text'] = $arr[$i];

            $data = json_encode($arr_postdata);
            $this->send_reply_message('/push',$data);
        }
       
        //ฟังก์ชันสำหรับการส่ง Text ข้อความรายชื่อผู้ที่ไม่เข้าสู่ระบบสัปดาห์นั้น และวันที่เข้าใช้งานล่าสุด
        //โดยส่ง Text เป็นทีละชุด ชุดละ 20 ข้อความ $text
        
    }

    /*
    * get__ums_api
    * ฟังก์ชันสำหรับรับข้อมูล และรายละเอียก API UMS
    * @input	$url
    * @author	Aphisit Sipalat 58160161
    * @Create Date 2562-02-21
    */
    private function get__ums_api($url){

        $API_URL        = $url; //รับ URL API
        $POST_HEADER    = array('Content-Type: application/x-www-form-urlencoded');
       
        $date_array = getdate (time()); 
        $numdays    = $date_array["wday"];

        $startdate  = date("Y-m-d", time() - ($numdays * 24*60*60)); //วันที่เริ่มต้นของแต่ละสัปดาห์
        $enddate    = date("Y-m-d", time() + ((7 - $numdays) * 24*60*60)); //วันที่สิ้นสุดของแต่ละสัปดาห์
       
        $values = array(
            'start' => $startdate,
            'end' => $enddate
        );

        $params  = http_build_query($values);

        $ch = curl_init($API_URL);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $POST_HEADER);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    private function get_hr_api($url,$time){
        
        $API_URL        = $url;
        $POST_HEADER    = array('Content-Type: application/x-www-form-urlencoded');

        $values = array();

        if($time == 'day'){

            $API_URL    = $url.'day';
            $day        =  date("Y-m-d");
            $values = array(
                'day' => $day,
            );

        }else if($time == 'week'){

            $API_URL    = $url.'week';
            $date_array = getdate (time()); 
            $numdays    = $date_array["wday"];
            $startdate  = date("Y-m-d", time() - ($numdays * 24*60*60)); 
            $enddate    = date("Y-m-d", time() + ((7 - $numdays) * 24*60*60)); 
            $values = array(
                'start' => $startdate,
                'end' => $enddate
            );

        }else if($time == 'month'){

            $API_URL                = $url.'month';
            $first_day_this_month   = date('Y-m-01'); // hard-coded '01' for first day
            $last_day_this_month    = date('Y-m-t');
            $values = array(
                'start' => $first_day_this_month,
                'end' => $last_day_this_month
            );
           
        
        }

        $params  = http_build_query($values);

        $ch = curl_init($API_URL);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $POST_HEADER);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
        

    }

}
