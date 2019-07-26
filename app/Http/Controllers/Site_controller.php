<?php

namespace App\Http\Controllers;

use Log;
use DB;
use App\Model\M_tar_site;
use Illuminate\Http\Request;
use App\Model\M_tar_subscribers;
use Illuminate\Routing\Controller as Controller;

class Site_controller extends Controller
{
    public function site_show(){
        
        return view('site.v_site');
        
    }

    public function get_data_site(Request $request){
        
        $get_data   =   $request->all();

        if(!empty($get_data)){

            $user_id    =   $get_data['user_id'];
            $result     =   \DB::table('tar_subscribers')->join('tar_site','tar_subscribers.tar_id','=','tar_site.sit_tar_id')
                            ->where('tar_userId', $user_id)->get();

            $data = array( 
                
                'result' => $result
        
            );
        }
        // log::info(json_encode($data));
        echo json_encode($data);
    }

    public function insert_site(Request $request){

        $get_data   =   $request->all();

        if(!empty($get_data)){

            $user_id    =   $get_data['user_id'];
            $name       =   $get_data['name'];
            $url        =   $get_data['url'];
            $detail     =   $get_data['detail'];
            $topic      =   $get_data['topic'];

            $subscribers    =   \DB::table('tar_subscribers')->where('tar_userId', $user_id)->get();

            foreach($subscribers as $key => $value){

                $tar_id = $value->tar_id;

                $site_url   =   \DB::table('tar_site')->insert(['sit_url' => $url ,'sit_detail'=>$detail,'sit_name'=>$name,'sit_topic'=>$topic,'sit_tar_id'=>$tar_id]);
            }
        }
    }
    public function update_site(Request $request){

        $get_data   =   $request->all();

        if(!empty($get_data)){

            $sit_id     =   $get_data['upd_id'];
            $user_id    =   $get_data['user_id'];
            $name       =   $get_data['name'];
            $url        =   $get_data['url'];
            $detail     =   $get_data['detail'];
            $topic      =   $get_data['topic'];

            $subscribers    =   \DB::table('tar_subscribers')->where('tar_userId', $user_id)->get();

            foreach($subscribers as $key => $value){

                $tar_id = $value->tar_id;

                $site_url   =   \DB::table('tar_site')->where('sit_id',$sit_id)->update(['sit_url' => $url ,'sit_name'=>$name,'sit_detail'=>$detail,'sit_topic'=>$topic,'sit_tar_id'=>$tar_id]);
            }
        }
    }

    public function delete_site(Request $request){

        $get_data   =   $request->all();

        if(!empty($get_data)){
            
            $user_id    =   $get_data['user_id'];
            $del_id     =   $get_data['del_id'];
            
            $result     =   \DB::table('tar_site')->where('sit_id',$del_id)->delete();
        }
    }
}
