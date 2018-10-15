<?php

namespace App\Http\Controllers;
use App\Bookingsupport;
use App\Http\Controllers\BookingController;
use App\Location;
use App\Room;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Pixelpeter\Woocommerce\Facades\Woocommerce;
use Carbon\Carbon;
use App\Enumerations\DateFormat;
use App\Price;
use App\Booking;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;

use Illuminate\Http\Request;

class ApiWooCommerce extends Controller
{
    public function index($string)
    {

        $arr = explode("2+!&A9", $string, 2);

        $email = base64_decode($arr[0]);
        $resource = base64_decode($arr[1]);

        $user = DB::table('users')->where('email', '=', $email)->get();

        $rooms = Room::all();
        $sedi = Location::all();

        if ($user[0]->name == 'guest'){

            $computer = $this->ip_macaddress();

            $random = $this->generateRandomString();

          //  return $random;

            Auth::loginUsingId($user[0]->id);

            return view('dashboard.booking-management-guest',compact('rooms','sedi','resource','computer','random'));

        } else {

            $computer = $this->ip_macaddress();

            $array = explode( ' - ',$computer);

            $ip = $array[0];
            $mac_address = $array[1];

            $response = $this->controller($ip,$mac_address);

            if($response == null){

                Auth::loginUsingId($user[0]->id);

                return view('dashboard.booking-management-register',compact('rooms','sedi','resource'));

            } else {

                Auth::loginUsingId($user[0]->id);

                $user = Auth::user();

                $roomId =  $response[0]['roomId'];
                $roomName =  $response[0]['roomName'];

                $bookingTime = $response[0]['bookingTime'];

                $time = explode(' - ', $bookingTime);

                $start = Carbon::createFromFormat(DateFormat::DATE_RANGE_PICKER, $time[0])
                    ->toDateTimeString();
                $end = Carbon::createFromFormat(DateFormat::DATE_RANGE_PICKER, $time[1])
                    ->toDateTimeString();

                $start_hour = explode(" ", $start);
                $end_hour = explode(" ", $end);

                $diff_sec = strtotime($end_hour[1]) - strtotime($start_hour[1]);

                $diff_day = (strtotime($end_hour[0]) - strtotime($start_hour[0])) / 86400; // prenotazione su più giorni
                $duration = $diff_sec / 3600;

                // store
                if ($diff_day == 0) {

                    if ($duration > 4) {
                        $duration = 8;
                        $price = Price::where('duration', '=', $duration)->where('price_id', '=', $roomId)->get();

                        $booking = Booking::create([
                            'room_id' => $roomId,
                            'booked_by' => Auth::user()->id,
                            'booked_name' => User::find(Auth::user()->id)->name,
                            'start_date' => $start,
                            'end_date' => $end,
                            'location_id' => 1,
                            'location' => 'Eur',
                            'price' => $price[0]->price
                        ]);

                        $controller = Bookingsupport::where('ip','=',$ip)
                            ->where('mac_address','=', $mac_address)
                            ->delete();



                        return Redirect::to('http://142.93.49.84/mio-account/')->with('message', 'Complimenti!'); //is this actually OK

                       /* return response()->json([
                            'message' => __('Room :name is successfully booked!', ['name' => $roomName])
                        ]); */


                    } else {
                        $price = Price::where('duration', '=', $duration)->where('price_id', '=', $roomId)->get();

                        $booking = Booking::create([
                            'room_id' => $roomId,
                            'booked_by' => Auth::user()->id,
                            'booked_name' => User::find(Auth::user()->id)->name,
                            'start_date' => $start,
                            'end_date' => $end,
                            'location_id' => 1,
                            'location' => 'Eur',
                            'price' => $price[0]->price
                        ]);

                        $controller = Bookingsupport::where('ip','=',$ip)
                            ->where('mac_address','=', $mac_address)
                            ->delete();

                        return Redirect::to('http://142.93.49.84/mio-account/')->with('message', 'Complimenti!'); //is this actually OK

                    /*  return response()->json([
                            'message' => __('Room :name is successfully booked!', $roomName)
                        ]); */
                    }

                } else {
                    $diff_day = $diff_day + 1;

                    $duration = 8;

                    $price = Price::where('duration', '=', $duration)->where('price_id', '=', $roomId)->get();

                    $price_final = ($price[0]->price) * $diff_day;


                    $booking = Booking::create([
                        'room_id' => $roomId,
                        'booked_by' => Auth::user()->id,
                        'booked_name' => User::find(Auth::user()->id)->name,
                        'start_date' => $start,
                        'end_date' => $end,
                        'location_id' => 1,
                        'location' => 'Eur',
                        'price' => $price_final
                    ]);

                    $controller = Bookingsupport::where('ip','=',$ip)
                        ->where('mac_address','=', $mac_address)
                        ->delete();

                    return Redirect::to('http://142.93.49.84/mio-account/')->with('message', 'Complimenti!'); //is this actually OK

                  /*  return response()->json([
                        'message' => __('Room :name is successfully booked!', ['name' => $roomName])
                    ]); */
                }

                $duration = $diff_sec / 3600;

                if ($duration > 5) {

                    $duration = 8;

                    $price = DB::table('prices')->where('duration', '=', $duration)->where('price_id', '=', $roomId)->get();

                } else {

                    $price = DB::table('prices')->where('duration', '=', $duration)->where('price_id', '=', $roomId)->get();
                }

                Booking::create([
                    'room_id' => $data['roomId'],
                    'booked_by' => Auth::user()->id,
                    'booked_name' => User::find(Auth::user()->id)->name,
                    'start_date' => $start,
                    'end_date' => $end,
                    'location_id' => $room->location_id,
                    'location' => $room->location,
                    'price' => $price[0]->price
                ]);


            }
        }
    }

    public function wordpress_user(){

        $users = DB::connection('mysql2')->getPdo();

        return $users;

    }

    //function for get Ip and MacAdress
    public function ip_macaddress(){

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

    public function controller($ip,$mac_address){

        $controller = Bookingsupport::where('ip','=',$ip)
            ->where('mac_address','=', $mac_address)
            ->get();

        if($controller->count() == 0){

        } else{
            return $controller;
        }

    }



    // Prenotazione per utente guest che prenota
    // dopo la registrazione
    // il record viene cancellato dalla tabella di supporto
    // e spostato nella tabella booking
    public function booking_lastUser($string)
    {
        $email = base64_decode($string);
        $user = DB::table('users')->where('email', '=', $email)->get();
		
		// return $user;
		
        $user_id = $user[0]->id;
        $userName = $user[0]->name;

        $db = DB::connection('mysql2');

        $computer = $this->ip_macaddress();

        $array = explode( ' - ',$computer);

     //   return $array;

        $ip = $array[0];
        $mac_address = $array[1];

        $response = $this->controller($ip,$mac_address);

        $roomId = $response[0]['roomId'];
        $roomName = $response[0]['roomName'];

   //     return $roomId. ' - ' . $roomName;


        $bookingTime = $response[0]['bookingTime'];

       // return $bookingTime;

        $time = explode(' - ', $bookingTime);

        $start = Carbon::createFromFormat(DateFormat::DATE_RANGE_PICKER, $time[0])
            ->toDateTimeString();
        $end = Carbon::createFromFormat(DateFormat::DATE_RANGE_PICKER, $time[1])
            ->toDateTimeString();

        $start_hour = explode(" ", $start);
        $end_hour = explode(" ", $end);

        $diff_sec = strtotime($end_hour[1]) - strtotime($start_hour[1]);

        $diff_day = (strtotime($end_hour[0]) - strtotime($start_hour[0])) / 86400; // prenotazione su più giorni
        $duration = $diff_sec / 3600;

        if ($diff_day == 0) {

            if ($duration > 4) {
                $duration = 8;
                $price = Price::where('duration', '=', $duration)->where('price_id', '=', $roomId)->get();

                $booking = Booking::create([
                    'room_id' => $roomId,
                    'booked_by' => $user_id,
                    'booked_name' => $userName,
                    'start_date' => $start,
                    'end_date' => $end,
                    'location_id' => 1,
                    'location' => 'Eur',
                    'price' => $price[0]->price
                ]);

                $controller = Bookingsupport::where('ip','=',$ip)
                    ->where('mac_address','=', $mac_address)
                    ->delete();


                // START insert order in woocommerce

                // dati necessari da ricavare sul db di woocommerce
                // ed inserire l'ordine nel carrello

               // return 'ciao';
                $wp_users = $db->table('wp_users')->where('user_email','=',$email)->get();

                $wp_posts = $db->table('wp_posts')->orderBy('id','DESC')->get();


                $wp_woocommerce_order_items = $db->table('wp_woocommerce_order_items')->orderBy('order_item_id','DESC')->get();

                $id_wp_user = $wp_users[0]->ID;

               // return $id_wp_user;


                $billing_first_name = $db->table('wp_usermeta')->where('user_id','=',$id_wp_user)->get();

                $first_name = $billing_first_name[0]->meta_value;

                $last_name = $billing_first_name[1]->meta_value;

                $address_1 = $billing_first_name[2]->meta_value;


                $id_wp_posts = $wp_posts[0]->ID+1;


                $id_order_item = $wp_woocommerce_order_items[0]->order_item_id;


                $ip = request()->ip();

                $agent= request()->header('User-Agent');


                $this->woo_insert_post($db,$id_wp_posts,$id_wp_user);


                $this->woo_insert_postmeta($db,$id_wp_posts,$id_wp_user,$ip,$agent,$price[0]->price,$first_name,$last_name,$address_1);

                $this->insert_woocommerce_order_items($db,$id_order_item,$id_wp_posts,$roomName,$start,$end);

                $this->insert_woocommerce_order_itemmeta($db,$price[0]->price);


                // END


                return Redirect::to('http://142.93.49.84/mio-account/')->with('message', 'Complimenti!'); //is this actually OK

                /* return response()->json([
                     'message' => __('Room :name is successfully booked!', ['name' => $roomName])
                 ]); */

            } else {
                $price = Price::where('duration', '=', $duration)->where('price_id', '=', $roomId)->get();

                $booking = Booking::create([
                    'room_id' => $roomId,
                    'booked_by' => $user_id,
                    'booked_name' => $userName,
                    'start_date' => $start,
                    'end_date' => $end,
                    'location_id' => 1,
                    'location' => 'Eur',
                    'price' => $price[0]->price
                ]);

                $controller = Bookingsupport::where('ip','=',$ip)
                    ->where('mac_address','=', $mac_address)
                    ->delete();

                return Redirect::to('http://142.93.49.84/mio-account/')->with('message', 'Complimenti!'); //is this actually OK

                /*  return response()->json([
                        'message' => __('Room :name is successfully booked!', $roomName)
                    ]); */
            }

        } else {
            $diff_day = $diff_day + 1;

            $duration = 8;

            $price = Price::where('duration', '=', $duration)->where('price_id', '=', $roomId)->get();

            $price_final = ($price[0]->price) * $diff_day;


            $booking = Booking::create([
                'room_id' => $roomId,
                'booked_by' => $user_id,
                'booked_name' => $userName,
                'start_date' => $start,
                'end_date' => $end,
                'location_id' => 1,
                'location' => 'Eur',
                'price' => $price_final
            ]);

            $controller = Bookingsupport::where('ip','=',$ip)
                ->where('mac_address','=', $mac_address)
                ->delete();

            return Redirect::to('http://142.93.49.84/mio-account/')->with('message', 'Complimenti!'); //is this actually OK

            /*  return response()->json([
                  'message' => __('Room :name is successfully booked!', ['name' => $roomName])
              ]); */
        }

        $duration = $diff_sec / 3600;

        if ($duration > 5) {

            $duration = 8;

            $price = DB::table('prices')->where('duration', '=', $duration)->where('price_id', '=', $roomId)->get();

        } else {

            $price = DB::table('prices')->where('duration', '=', $duration)->where('price_id', '=', $roomId)->get();
        }

        Booking::create([
            'room_id' => $data['roomId'],
            'booked_by' => $user_id,
            'booked_name' => $userName,
            'start_date' => $start,
            'end_date' => $end,
            'location_id' => $room->location_id,
            'location' => $room->location,
            'price' => $price[0]->price
        ]);


    }

    // function for insert order in woocommerce

    // inser in wp_posts
    public function woo_insert_post($db,$post,$user)
    {

        $mytime = Carbon::now();

        $db->table('wp_posts')->insert(
            [
                'id' => $post,
                'post_author' => 1,
                'post_date' => $mytime->toDateTimeString(),
                'post_date_gmt' =>  $mytime->toDateTimeString(),
                'post_content' => '',
                'post_title' => 'Order &ndash; settembre 14, 2018 @ 10:02 AM',
                'post_excerpt' => '',
                'post_status' => 'wc-completed',
                'comment_status' => 'closed',
                'ping_status' => 'closed',
                'post_password' => 'order',
                'post_name' => 'ordine',
                'to_ping' => '',
                'pinged' => '',
                'post_modified' => $mytime->toDateTimeString(),
                'post_modified_gmt' => $mytime->toDateTimeString(),
                'post_content_filtered' => '',
                'post_parent' => 0,
                'guid' => 'http://142.93.49.84//?post_type=shop_order&#0'.$user.';p='.$post,
                'menu_order' => 0,
                'post_type' => 'shop_order',
                'post_mime_type' => '',
                'comment_count' => 2,
            ]
        );

    }

    // insert in wp_postmeta
    public function woo_insert_postmeta($db,$post,$user,$ip,$agent,$price,$first_name,$last_name,$address_1)
    {

        $mytime = Carbon::now();

        $wp_users = $db->table('wp_users')->where('id','=',$user)->get();

        $wp_usermeta = $db->table('wp_usermeta')->where('user_id','=',$user)->get();

        //  return $wp_usermeta;


        $db->table('wp_postmeta')->insert([
            ['post_id' => $post, 'meta_key'=>'_order_key','meta_value' => 'wc_order_5b9b86aee2351'],
            ['post_id' => $post, 'meta_key'=>'_customer_user','meta_value' => $user],
            ['post_id' => $post, 'meta_key'=>'_payment_method','meta_value' => 'cod'],
            ['post_id' => $post, 'meta_key'=>'_payment_method_title','meta_value' => 'Pagamento alla consegna'],
            ['post_id' => $post, 'meta_key'=>'_transaction_id','meta_value' => ''],
            ['post_id' => $post, 'meta_key'=>'_customer_ip_address','meta_value' => $ip],
            ['post_id' => $post, 'meta_key'=>'_customer_user_agent','meta_value' => $agent],
            ['post_id' => $post, 'meta_key'=>'_created_via','meta_value' => 'checkout'],
            ['post_id' => $post, 'meta_key'=>'_date_completed','meta_value' => date_timestamp_get(date_create())],
            ['post_id' => $post, 'meta_key'=>'_completed_date','meta_value' => $mytime->toDateTimeString()],
            ['post_id' => $post, 'meta_key'=>'_date_paid','meta_value' => date_timestamp_get(date_create())],
            ['post_id' => $post, 'meta_key'=>'_paid_date','meta_value' => 'wc_pickcenter'],
            ['post_id' => $post, 'meta_key'=>'_cart_hash','meta_value' => 'wc_pickcenter'],


            ['post_id' => $post, 'meta_key'=>'_billing_first_name','meta_value' => $first_name],
            ['post_id' => $post, 'meta_key'=>'_billing_last_name','meta_value' => $last_name],
            ['post_id' => $post, 'meta_key'=>'_billing_company','meta_value' => null],
            ['post_id' => $post, 'meta_key'=>'_billing_address_1','meta_value' => $address_1],
            ['post_id' => $post, 'meta_key'=>'_billing_address_2','meta_value' => 'null'],
            ['post_id' => $post, 'meta_key'=>'_billing_city','meta_value' => 'null'],
            ['post_id' => $post, 'meta_key'=>'_billing_state','meta_value' => 'null'],
            ['post_id' => $post, 'meta_key'=>'_billing_postcode','meta_value' => 'null'],
            ['post_id' => $post, 'meta_key'=>'_billing_country','meta_value' => 'null'],
            ['post_id' => $post, 'meta_key'=>'_billing_email','meta_value' => $wp_users[0]->user_email],
            ['post_id' => $post, 'meta_key'=>'_billing_phone','meta_value' => 'null'],

            ['post_id' => $post, 'meta_key'=>'_shipping_first_name','meta_value' =>  $wp_users[0]->user_login],
            ['post_id' => $post, 'meta_key'=>'_shipping_last_name','meta_value' =>  $wp_users[0]->user_login],
            ['post_id' => $post, 'meta_key'=>'_shipping_company','meta_value' => 'null'],
            ['post_id' => $post, 'meta_key'=>'_shipping_address_1','meta_value' => 'null'],
            ['post_id' => $post, 'meta_key'=>'_shipping_address_2','meta_value' => 'null'],
            ['post_id' => $post, 'meta_key'=>'_shipping_city','meta_value' => 'null'],
            ['post_id' => $post, 'meta_key'=>'_shipping_state','meta_value' => 'null'],
            ['post_id' => $post, 'meta_key'=>'_shipping_postcode','meta_value' => 'null'],
            ['post_id' => $post, 'meta_key'=>'_shipping_country','meta_value' => 'null'],
            ['post_id' => $post, 'meta_key'=>'_order_currency','meta_value' => 'EUR'],
            ['post_id' => $post, 'meta_key'=>'_cart_discount','meta_value' => '0'],
            ['post_id' => $post, 'meta_key'=>'_cart_discount_tax','meta_value' => '0'],
            ['post_id' => $post, 'meta_key'=>'_order_shipping','meta_value' => '0'],
            ['post_id' => $post, 'meta_key'=>'_order_shipping_tax','meta_value' => '0'],
            ['post_id' => $post, 'meta_key'=>'_order_tax','meta_value' => '0'],
            ['post_id' => $post, 'meta_key'=>'_order_total','meta_value' => $price],
            ['post_id' => $post, 'meta_key'=>'_order_version','meta_value' => '3.4.5'],
            ['post_id' => $post, 'meta_key'=>'_prices_include_tax','meta_value' => '0'],
            ['post_id' => $post, 'meta_key'=>'_billing_address_index','meta_value' => 'null'],
            ['post_id' => $post, 'meta_key'=>'_shipping_address_index','meta_value' => 'null'],

            ['post_id' => $post, 'meta_key'=>'_download_permissions_granted','meta_value' => 'yes'],
            ['post_id' => $post, 'meta_key'=>'_recorded_sales','meta_value' => 'yes'],
            ['post_id' => $post, 'meta_key'=>'_recorded_coupon_usage_counts','meta_value' => 'yes'],
            ['post_id' => $post, 'meta_key'=>'_order_stock_reduced','meta_value' => 'yes'],
            ['post_id' => $post, 'meta_key'=>'_edit_lock','meta_value' => 'wc_order_5b9b86aee2351'],
            ['post_id' => $post, 'meta_key'=>'_edit_last','meta_value' => '1'],
        ]);

    }

    // inser in wp_woocommerce_order_items
    public function insert_woocommerce_order_items($db,$order_item_id,$order_id,$roomName,$start,$end){


        $order_item_id = $order_item_id+1;

        $db->table('wp_woocommerce_order_items')->insert([
            ['order_item_name'=>$roomName.' dal '. $start . ' al ' . $end,   'order_item_type' => 'line_item',   'order_id'=>$order_id],
            ['order_item_name'=>'Spedizione gratuita',   'order_item_type' => 'shipping',   'order_id'=>$order_id],
        ]);

    }

    public function insert_woocommerce_order_itemmeta($db,$price)
    {
        // return $order_item . ' - ' . $order_item1;

        $wp_woocommerce_order_itemmeta =  $db->table('wp_woocommerce_order_items')->orderBy('order_item_id','DESC')->get();

        $order_item_id1 =  $wp_woocommerce_order_itemmeta[0]->order_item_id; //maggiore

        $order_item_id =  $wp_woocommerce_order_itemmeta[1]->order_item_id; //minore


        $db->table('wp_woocommerce_order_itemmeta')->insert([
            ['order_item_id' => $order_item_id, 'meta_key'=>'','meta_value' => ''],
            ['order_item_id' => $order_item_id, 'meta_key'=>'_variation_id','meta_value' => ''],
            ['order_item_id' => $order_item_id, 'meta_key'=>'_qty','meta_value' => ''],
            ['order_item_id' => $order_item_id, 'meta_key'=>'_tax_class','meta_value' => ''],
            ['order_item_id' => $order_item_id, 'meta_key'=>'_line_subtotal','meta_value' => '80'],
            ['order_item_id' => $order_item_id, 'meta_key'=>'_line_subtotal_tax','meta_value' => '0'],
            ['order_item_id' => $order_item_id, 'meta_key'=>'_line_total','meta_value' => $price],
            ['order_item_id' => $order_item_id, 'meta_key'=>'_line_tax','meta_value' => '0'],
            // ['order_item_id' => $order_item_id, 'meta_key'=>'_line_tax_data','meta_value' => 'a:2:{s:5:\"total\";a:0:{}s:8:\"subtotal\";a:0:{}}'],
            ['order_item_id' => $order_item_id1, 'meta_key'=>'method_id','meta_value' => 'flat_rate'],
            ['order_item_id' => $order_item_id1, 'meta_key'=>'instance_id','meta_value' => '1'],
            ['order_item_id' => $order_item_id1, 'meta_key'=>'cost','meta_value' => '10.00'],
            ['order_item_id' => $order_item_id1, 'meta_key'=>'total_tax','meta_value' => '0'],
            ['order_item_id' => $order_item_id1, 'meta_key'=>'taxes','meta_value' => 'a:1:{s:5:\"total\";a:0:{}}'],
            ['order_item_id' => $order_item_id1, 'meta_key'=>'Prodotti','meta_value' => 'Maglietta Batman']
        ]);

    }

    function generateRandomString($length = 20) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }


}
