<?php

namespace App\Http\Controllers;

use App\User;
use App\Wordpress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WordpressController extends Controller
{

    public function index()
    {
        $db = DB::connection('mysql2');

        $db2 = DB::connection('mysql');

        $users_wp = $db->table('wp_users')
            ->select('user_email','user_login')
                ->get();

        $num = $users_wp->count();

            for($i=0; $i<$num; $i++){
                $users = User::where('email','=',$users_wp[$i]->user_email)->get();
                if($users->isEmpty()){
                    $new_user = new User();
                    $new_user->name = $users_wp[$i]->user_login;
                    $new_user->email = $users_wp[$i]->user_email;
                    $new_user->password = bcrypt('pickcenter');
                    $new_user->ragione_sociale = 'pickcenter';
                    $new_user->status = 1;
                    $new_user->save();

                    $pivot = $db2->table('role_user')->insertGetId(
                        ['role_id' => 3 ,'user_id' => $new_user->id, 'user_type' => 'App\user']
                    );

                } else{
                    echo '<p>'.$users_wp[$i]->user_email.' presente</p>';
                }
            }


    }

    public function myIp()
    {

        $ip = request()->ip();
        $mac=shell_exec("arp -a ".$ip);
        $mac_string = shell_exec("arp -a $ip");

        $mac_array = explode(" ",$mac_string);
        $mac = $mac_array[3];

        if(empty($mac)) {
            // for mac e linux
            $mac = shell_exec("arp -a ".escapeshellarg($_SERVER['REMOTE_ADDR'])." | grep -o -E '(:xdigit:{1,2}:){5}:xdigit:{1,2}'");
            $mac_string = shell_exec("arp -a $ip");

            $mac_array = explode(" ",$mac_string);
            $mac = $mac_array[3];
            $computer = $ip." - ".$mac;
        } else {
            // for windows
            $computer = $ip." - ".$mac;
        }

        return $computer;
    }

}
