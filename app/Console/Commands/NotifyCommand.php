<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Log;

class NotifyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:notify {time?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'crontab notification of tracking data';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $time           = $this->argument('time');
        $num_time       = substr($time,5);
     
        // log::info('Time :'. $time);
        // log::info('numTime : '.$num_time); //substring word "time=" 
        // log::info('Time : '.\Carbon\Carbon::now());

        $subscribers    =   \DB::table('tar_subscribers')->join('tar_site','tar_subscribers.tar_id','=','tar_site.sit_tar_id')
                            ->where('tar_status','=',1)->get();

        $result_site    =   \DB::table('tar_subscribers')->join('tar_site','tar_subscribers.tar_id','=','tar_site.sit_tar_id')
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
            $topics     = explode(",",$value->tar_selected);

            foreach($topics as $key => $topic){

                if($site_topic[$topic] == 'UMS'){
              
                    if($value->tar_time_login == $num_time && $value->sit_name == $value->tar_ums){
                        $respond = $this->get__ums_api($value->sit_url);
                        $this->send_data_ums($respond,$user_id,$value->tar_ums);
                    }
                   
                }

                if($site_topic[$topic] == 'HR'){
                    
                    if($value->tar_time == $num_time && $value->sit_name == $value->tar_hr){
                        $time = $value->tar_time;
                        $respond = $this->get_hr_api($value->sit_url,$time);
                        $this->send_data_hr($respond,$user_id,$value->tar_hr);
            
                    }
                 
                }

            }
            
        }
    }
    /*
    * send_data_ums
    * ฟังก์ชันสำหรับส่ง Flex message เข้า LINE Application
    * @input	$respond, $user_id
    * @output	ข้อความใน LINE Application
    * @author	Aphisit Sipalat 58160161
    */
    private function send_data_ums($respond ,$user_id, $sit_name){

        $data_respond   = json_decode($respond,true); 

        $subscribers    = \DB::table('tar_subscribers')
                            ->join('tar_site','tar_subscribers.tar_id','=','tar_site.sit_tar_id')
                            ->where('tar_userId', $user_id)
                            ->where('sit_name', $sit_name)->get();
        $tar_id = '';
        $sit_id = '';

        $date_array = getdate (time()); 
        $numdays    = $date_array["wday"];
        $time       = date("Y-m-d");
        $startdate  = date("Y-m-d", time() - ($numdays * 24*60*60)); 
        $enddate    = date("Y-m-d", time() + ((7 - $numdays) * 24*60*60)); 
 
        foreach($subscribers as $key=>$value){

            $tar_id = $value->tar_id;  //get tar_id จาก tar_subscribers เพื่อนำไป where หาในตาราง tar_ums_detail
            
            $sit_id = '';$sit_id = $value->sit_id;


        }
        
        //count data ในตาราง tar_ums_detail ว่ามีข้อมูลเท่ากับ 0 หรือไม่ หากเท่ากับ 0 ให้ insert ลง DB
        $count_result    =  \DB::table('tar_ums_detail')
                            ->where('ums_start_week','=',$startdate)
                            ->where('ums_end_week','=',$enddate)
                            ->where('ums_tar_id','=',$tar_id)
                            ->where('ums_sit_id','=',$sit_id) //
                            ->count();

        if($count_result == 0 ){

            foreach($data_respond['data'] as $value){
                foreach($value as $key=>$val){
                   \DB::table('tar_ums_detail')->insert(['ums_user_id' => $val['user_id'] ,'ums_name'=>$val['fullname'],'ums_last_date'=>$val['last_date'],'ums_last_time'=>$val['last_time'],'ums_wg_name'=>$val['WgNameT'],'ums_start_week'=>$startdate,'ums_end_week'=>$enddate,'ums_tar_id'=>$tar_id,'ums_dp_name'=>$val['dpName'],'ums_sit_id'=>$sit_id]);
                }
            }
        }
        
        //Formate JSON จาก Web LINE DEV 
        if($data_respond['status_cod'] == 200){

            $data = '{"to":"'.  $user_id .'","messages":[{"type":"flex","altText":"ข้อความใหม่","contents":{
                "type": "bubble",
                "styles": {
                    "footer": {
                    "separator": true
                    }
                },
                "body": {
                    "type": "box",
                    "layout": "vertical",
                    "contents": [
                    {
                        "type": "text",
                        "text": "Trackign and Report",
                        "weight": "bold",
                        "color": "#1DB446",
                        "size": "sm"
                    },
                    {
                        "type": "box",
                        "layout": "baseline",
                        "margin": "md",
                        "contents": [
                        {
                            "type": "text",
                            "text": "UMS Login",
                            "weight": "bold",
                            "size": "xl"
                        },
                        {
                            "type": "icon",
                            "size": "xxl",
                            "url": "https://linebot.aos.in.th/log-in.png"
                        }
                        ]
                    },
                    {
                        "type": "text",
                        "text": "ผู้ที่ไม่เข้าใช้งานระบบประจำสัปดาห์",
                        "size": "xs",
                        "color": "#aaaaaa",
                        "wrap": true
                    },
                    {
                        "type": "separator",
                        "margin": "xxl"
                    },
                    {
                        "type": "box",
                        "layout": "vertical",
                        "margin": "xxl",
                        "spacing": "sm",
                        "contents": [
                        {
                            "type": "box",
                            "layout": "horizontal",
                            "contents": [
                            {
                                "type": "text",
                                "text": "Start date",
                                "size": "sm",
                                "weight": "bold",
                                "color": "#555555",
                                "flex": 0
                            },
                            {
                                "type": "text",
                                "text": "'.date("d/m/Y " ,strtotime($startdate)).'",
                                "size": "sm",
                                "color": "#111111",
                                "align": "end"
                            }
                            ]
                        },
                        {
                            "type": "box",
                            "layout": "horizontal",
                            "contents": [
                            {
                                "type": "text",
                                "text": "End date",
                                "weight": "bold",
                                "size": "sm",
                                "color": "#555555",
                                "flex": 0
                            },
                            {
                                "type": "text",
                                "text": "'.date("d/m/Y ",strtotime($enddate)).'",
                                "size": "sm",
                                "color": "#111111",
                                "align": "end"
                            }
                            ]
                        },
                        {
                            "type": "box",
                            "layout": "horizontal",
                            "contents": [
                            {
                                "type": "text",
                                "text": "Number none-active user",
                                "size": "sm",
                                "weight": "bold",
                                "color": "#555555",
                                "flex": 0
                            },
                            {
                                "type": "text",
                                "text": "'.$data_respond['rec_data'].' /'.$data_respond['all_user_data'].'",
                                "size": "sm",
                                "color": "#111111",
                                "align": "end"
                            }
                            ]
                        }
                        ]
                    },
                    {
                        "type": "separator",
                        "margin": "xxl"
                    },
                    {
                        "type": "box",
                        "layout": "vertical",
                        "contents": [
                        {
                            "type": "button",
                            "flex": 2,
                            "style": "primary",
                            "color": "#aaaaaa",
                            "action": {
                            "type": "postback",
                            "label": "Detail",
                            "displayText": "UMS Detail",
                            "data": "UMS Detail"
                            }
                        },{
                            "type": "button",
                            "style": "link",
                            "height": "sm",
                            "action": {
                            "type": "uri",
                            "label": "WEB VIEW",
                            "uri": "line://app/1636870579-8KMGXXg1"
                            }
                        }
                        ]
                    }
                    ]
                }
                }}]}';

        }else{
            
            $data = '{"to":"'.  $user_id .'","messages":[{"type":"flex","altText":"ข้อความใหม่","contents":{
                "type": "bubble",
                "styles": {
                    "footer": {
                    "separator": true
                    }
                },
                "body": {
                    "type": "box",
                    "layout": "vertical",
                    "contents": [
                    {
                        "type": "text",
                        "text": "Trackign and Report",
                        "weight": "bold",
                        "color": "#1DB446",
                        "size": "sm"
                    },
                    {
                        "type": "box",
                        "layout": "baseline",
                        "margin": "md",
                        "contents": [
                        {
                            "type": "text",
                            "text": "UMS Login",
                            "weight": "bold",
                            "size": "xl"
                        },
                        {
                            "type": "icon",
                            "size": "xxl",
                            "url": "https://linebot.aos.in.th/log-in.png"
                        }
                        ]
                    },
                    {
                        "type": "text",
                        "text": "รายงานข้อมูลบุคล                 วันที่ '.date("d/m/Y",strtotime($time)).'",
                        "size": "xs",
                        "color": "#aaaaaa",
                        "wrap": true
                    },
                    {
                        "type": "separator",
                        "margin": "xxl"
                    },
                    {
                        "type": "box",
                        "layout": "vertical",
                        "margin": "xxl",
                        "spacing": "sm",
                        "contents": [
                        {
                            "type": "box",
                            "layout": "horizontal",
                            "contents": [
                            {
                                "type": "text",
                                "text": "Status",
                                "weight": "bold",
                                "size": "sm",
                                "color": "#555555",
                                "flex": 0
                            },
                            {
                                "type": "text",
                                "text": "ไม่มีข้อมูล หรือมีข้อผิดพลาด",
                                "size": "sm",
                                "color": "#111111",
                                "align": "end"
                            }
                            ]
                        }]
                    },
                    {
                        "type": "separator",
                        "margin": "xxl"
                    }
                    ]
                }
                }}]}';
        }
        

            $this->send_reply_message('/push',$data);
    }

    private function send_data_hr($respond ,$user_id ,$sit_name){
        
        $data_respond   = json_decode($respond,true); 
        $subscribers    = \DB::table('tar_subscribers')
                            ->join('tar_site','tar_subscribers.tar_id','=','tar_site.sit_tar_id')
                            ->where('tar_userId', $user_id)
                            ->where('sit_name', $sit_name)->get();
        $countDate = 0;
        $tar_id = '';
        $sit_id = '';
        $date   = '';
        $i      = 0;
        $time   =  date("Y-m-d");

        foreach($subscribers as $value){
            $tar_id = $value->tar_id;
            $sit_id = $value->sit_id;

        }

        
        if($data_respond['status_code'] == 200){

            foreach($data_respond['data'] as $key=>$value){
                foreach($value as $val){

                     $count_result    =  \DB::table('tar_hr_detail')
                                        ->where('hr_name','=',$val['FullName'])
                                        ->where('hr_dept_name','=',$val['dept_name'])
                                        ->where('hr_date','=',$val['tio_date'])
                                        ->where('hr_status_name',$key)
                                        ->where('hr_tar_id','=',$tar_id)
                                        ->where('hr_sit_id','=',$sit_id)
                                        ->count();
                                        
                    if($count_result == 0){
                        \DB::table('tar_hr_detail')->insert(['hr_name' => $val['FullName'] ,'hr_dept_name'=>$val['dept_name'],'hr_date'=>$val['tio_date'],'hr_status_name'=>$key,'hr_tar_id'=>$tar_id,'hr_sit_id'=>$sit_id]);
                    }
                }
            }
        }

        foreach($data_respond['date_times'] as $val){
           
            if($countDate++ == 0){
                $date = date("d/m/Y",strtotime($val));
            }else{
                $date .= " ถึง ".date("d/m/Y",strtotime($val));
            }
        }
        
        if($data_respond['status_code'] == 200){

            $data = '{"to":"'.  $user_id .'","messages":[{"type":"flex","altText":"ข้อความใหม่","contents":{
                "type": "bubble",
                "styles": {
                    "footer": {
                    "separator": true
                    }
                },
                "body": {
                    "type": "box",
                    "layout": "vertical",
                    "contents": [
                    {
                        "type": "text",
                        "text": "Trackign and Report",
                        "weight": "bold",
                        "color": "#1DB446",
                        "size": "sm"
                    },
                    {
                        "type": "box",
                        "layout": "baseline",
                        "margin": "md",
                        "contents": [
                        {
                            "type": "text",
                            "text": "HR Data",
                            "weight": "bold",
                            "size": "xl"
                        },
                        {
                            "type": "icon",
                            "size": "xxl",
                            "url": "https://linebot.aos.in.th/hr.png"
                        }
                        ]
                    },{
                        "type": "text",
                        "text": "Data report of '.$data_respond['status_type'].'  รายงานข้อมูลบุคล",
                        "size": "xs",
                        "color": "#aaaaaa",
                        "wrap": true
                    },
                    {
                        "type": "text",
                        "text": "วันที่ '.$date.'",
                        "size": "xs",
                        "color": "#aaaaaa",
                        "wrap": true
                    },
                    {
                        "type": "separator",
                        "margin": "xxl"
                    },
                    {
                        "type": "box",
                        "layout": "vertical",
                        "margin": "xxl",
                        "spacing": "sm",
                        "contents": [';
                        foreach($data_respond['data'] as  $key=>$value){
                           
                            $data .= '{
                            "type": "box",
                            "layout": "horizontal",
                            "contents": [
                            {
                                "type": "text",
                                "text": "'.$key.'",
                                "weight": "bold",
                                "size": "sm",
                                "color": "#555555",
                                "flex": 0
                            },
                            {
                                "type": "text",
                                "text": "'.sizeof($value).'",
                                "size": "sm",
                                "color": "#111111",
                                "align": "end"
                            }
                            ]
                        }';
                          $i++;
                        if(sizeof($data_respond['data']) != $i) {
                            $data .= ',';
                        }
                        
                        }
                        
                       $data .=' ]
                    },
                    {
                        "type": "separator",
                        "margin": "xxl"
                    },
                    {
                        "type": "box",
                        "layout": "vertical",
                        "contents": [
                             {
                            "type": "button",
                            "flex": 2,
                            "style": "primary",
                            "color": "#aaaaaa",
                            "action": {
                            "type": "postback",
                            "label": "Detail",
                            "displayText": "HR Detail",
                            "data": "HR Detail"
                            }
                        },
                        {
                            "type": "button",
                            "style": "link",
                            "height": "sm",
                            "action": {
                            "type": "uri",
                            "label": "WEB VIEW",
                            "uri": "line://app/1636870579-L31kaaGN"
                            }
                        }
                        ]
                    }
                    ]
                }
                }}]}';
               

        }else{

             $data = '{"to":"'.  $user_id .'","messages":[{"type":"flex","altText":"ข้อความใหม่","contents":{
                "type": "bubble",
                "styles": {
                    "footer": {
                    "separator": true
                    }
                },
                "body": {
                    "type": "box",
                    "layout": "vertical",
                    "contents": [
                    {
                        "type": "text",
                        "text": "Trackign and Report",
                        "weight": "bold",
                        "color": "#1DB446",
                        "size": "sm"
                    },
                    {
                        "type": "box",
                        "layout": "baseline",
                        "margin": "md",
                        "contents": [
                        {
                            "type": "text",
                            "text": "HR Data",
                            "weight": "bold",
                            "size": "xl"
                        },
                        {
                            "type": "icon",
                            "size": "xxl",
                            "url": "https://linebot.aos.in.th/hr.png"
                        }
                        ]
                    },
                    {
                        "type": "text",
                        "text": "รายงานข้อมูลบุคล                 วันที่ '.date("d/m/Y",strtotime($time)).'",
                        "size": "xs",
                        "color": "#aaaaaa",
                        "wrap": true
                    },
                    {
                        "type": "separator",
                        "margin": "xxl"
                    },
                    {
                        "type": "box",
                        "layout": "vertical",
                        "margin": "xxl",
                        "spacing": "sm",
                        "contents": [
                        {
                            "type": "box",
                            "layout": "horizontal",
                            "contents": [
                            {
                                "type": "text",
                                "text": "Status",
                                "weight": "bold",
                                "size": "sm",
                                "color": "#555555",
                                "flex": 0
                            },
                            {
                                "type": "text",
                                "text": "ไม่มีข้อมูล หรือมีข้อผิดพลาด",
                                "size": "sm",
                                "color": "#111111",
                                "align": "end"
                            }
                            ]
                        }]
                    },
                    {
                        "type": "separator",
                        "margin": "xxl"
                    }
                    ]
                }
                }}]}';

        }
        $this->send_reply_message('/push',$data);
 
    }

    /*
    * get__ums_api
    * รับ url api ของแต่ละคนในการ get data respond จาก api
    * @input	$method, $post_body
    * @output	ข้อความใน LINE Application
    * @author	Aphisit Sipalat 58160161
    */
    private function get__ums_api($url){

        $API_URL        = $url;
        $POST_HEADER    = array('Content-Type: application/x-www-form-urlencoded');
       
        $date_array = getdate (time()); 
        $numdays    = $date_array["wday"];

        $startdate  = date("Y-m-d", time() - ($numdays * 24*60*60)); 
        $enddate    = date("Y-m-d", time() + ((7 - $numdays) * 24*60*60)); 

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

    /*
    * send_reply_message
    * ส่ง $method, $post_body ไปที่ Line 
    * @input	$method, $post_body
    * @output	ข้อความใน LINE Application
    * @author	Aphisit Sipalat 58160161
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
}
