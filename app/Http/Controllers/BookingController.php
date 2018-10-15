<?php

namespace App\Http\Controllers;

use App\Booking;
use App\BookingOptional;
use App\Enumerations\BookingStatus;
use App\Enumerations\DateFormat;
use App\Http\Requests\StoreBooking;
use App\Optional;
use App\Price;
use App\Room;
use App\Rules\Duration;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Location;
use Illuminate\Support\Facades\Response;


/**
 * Class BookingController per il booking per utenti registrati
 *
 */
class BookingController extends Controller
{
    private $data;

    /**
     * BookingController constructor.
     *
     */
    public function __construct()
    {
        $this->middleware('permission:create-booking|read-booking|update-booking|delete-booking');

        $this->data = [
            'pageTitle' => __('Booking Management'),
            'pageHeader' => __('Booking Management'),
            'pageSubHeader' => __('Manage your bookings here')
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $this->location['sedi'] = Location::groupBy('id')->orderBy('id')->get(['sede']);
        $this->data['rooms'] = Room::groupBy('pax')->orderBy('pax')->get(['pax']);

        return view('dashboard.booking-management', $this->data, $this->location);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreBooking $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */

    public function store(StoreBooking $request)
    {

        $db = DB::connection('mysql2');

        $user = Auth::user();

        $data = $request->all();

        $roomId = $data['roomId'];

        $roomName = $data['roomName'];

        $room = Room::find($roomId);

        $time = explode(' - ', $data['bookingTime']);

        $user_name = User::find( Auth::user()->id);


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
                    'room_id' => $data['roomId'],
                    'booked_by' => Auth::user()->id,
                    'booked_name' => User::find(Auth::user()->id)->name,
                    'start_date' => $start,
                    'end_date' => $end,
                    'location_id' => $room->location_id,
                    'location' => $room->location,
                    'price' => $price[0]->price
                ]);
			
				


           // START insert order in woocommerce

                // dati necessari da ricavare sul db di woocommerce
                // ed inserire l'ordine nel carrello

                $wp_users = $db->table('wp_users')->where('user_email','=',$user->email)->get();
				// return $wp_users;

                $wp_posts = $db->table('wp_posts')->orderBy('id','DESC')->get();
				// return $wp_posts;

                $wp_woocommerce_order_items = $db->table('wp_woocommerce_order_items')->orderBy('order_item_id','DESC')->get();

                $id_wp_user = $wp_users[0]->ID;

                $billing_first_name = $db->table('wp_usermeta')->where('user_id','=',$id_wp_user)->get();

             //   return $billing_first_name[1]->meta_value;


               // $billing_last_name = $db->table('wp_usermeta')->where('user_id','=',$id_wp_user)->where('meta_key','=','billing_last_name')->get();
               // $billing_address_1 = $db->table('wp_usermeta')->where('user_id','=',$id_wp_user)->where('meta_key','=','billing_address_1')->get();


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

                return response()->json([
                    'message' => __('Room :name is successfully booked!', ['name' => $data['roomName']])
                ]);


            } else {
                $price = Price::where('duration', '=', $duration)->where('price_id', '=', $roomId)->get();


                $booking = Booking::create([
                    'room_id' => $data['roomId'],
                    'booked_by' => Auth::user()->id,
                    'booked_name' => User::find(Auth::user()->id)->name,
                    'start_date' => $start,
                    'end_date' => $end,
                    'location_id' => $room->location_id,
                    'location' => $room->location,
                    'price' => $price[0]->price
                ]);

                // START insert order in woocommerce

                // dati necessari da ricavare sul db di woocommerce
                // ed inserire l'ordine nel carrello

                $wp_users = $db->table('wp_users')->where('user_email','=',$user->email)->get();

                $wp_posts = $db->table('wp_posts')->orderBy('id','DESC')->get();

                $wp_woocommerce_order_items = $db->table('wp_woocommerce_order_items')->orderBy('order_item_id','DESC')->get();

                $id_wp_user = $wp_users[0]->ID;

                $billing_first_name = $db->table('wp_usermeta')->where('user_id','=',$id_wp_user)->where('meta_key','=','billing_first_name')->get();
                $billing_last_name = $db->table('wp_usermeta')->where('user_id','=',$id_wp_user)->where('meta_key','=','billing_last_name')->get();
                $billing_address_1 = $db->table('wp_usermeta')->where('user_id','=',$id_wp_user)->where('meta_key','=','billing_address_1')->get();


                $first_name = $billing_first_name[0]->meta_value;
                $last_name = $billing_last_name[0]->meta_value;
                $address_1 = $billing_address_1[0]->meta_value;


                $id_wp_posts = $wp_posts[0]->ID+1;

                $id_order_item = $wp_woocommerce_order_items[0]->order_item_id;


                $ip = request()->ip();

                $agent= request()->header('User-Agent');


                $this->woo_insert_post($db,$id_wp_posts,$id_wp_user);


                $this->woo_insert_postmeta($db,$id_wp_posts,$id_wp_user,$ip,$agent,$price[0]->price,$first_name,$last_name,$address_1);

                $this->insert_woocommerce_order_items($db,$id_order_item,$id_wp_posts,$roomName,$start,$end);

                $this->insert_woocommerce_order_itemmeta($db,$price[0]->price);


                // END

                //     return $optional;

                return response()->json([
                    'message' => __('Room :name is successfully booked!', ['name' => $data['roomName']])
                ]);
            }

        } else {
            $diff_day = $diff_day + 1;

            $duration = 8;

            $price = Price::where('duration', '=', $duration)->where('price_id', '=', $roomId)->get();

            $price_final = ($price[0]->price) * $diff_day;


            $booking = Booking::create([
                'room_id' => $data['roomId'],
                'booked_by' => Auth::user()->id,
                'booked_name' => User::find(Auth::user()->id)->name,
                'start_date' => $start,
                'end_date' => $end,
                'location_id' => $room->location_id,
                'location' => $room->location,
                'price' => $price_final
            ]);

            // START insert order in woocommerce

            // dati necessari da ricavare sul db di woocommerce
            // ed inserire l'ordine nel carrello

            $wp_users = $db->table('wp_users')->where('user_email','=',$user->email)->get();

            $wp_posts = $db->table('wp_posts')->orderBy('id','DESC')->get();

            $wp_woocommerce_order_items = $db->table('wp_woocommerce_order_items')->orderBy('order_item_id','DESC')->get();

            $id_wp_user = $wp_users[0]->ID;

            $billing_first_name = $db->table('wp_usermeta')->where('user_id','=',$id_wp_user)->where('meta_key','=','billing_first_name')->get();
            $billing_last_name = $db->table('wp_usermeta')->where('user_id','=',$id_wp_user)->where('meta_key','=','billing_last_name')->get();
            $billing_address_1 = $db->table('wp_usermeta')->where('user_id','=',$id_wp_user)->where('meta_key','=','billing_address_1')->get();


            $first_name = $billing_first_name[0]->meta_value;
            $last_name = $billing_last_name[0]->meta_value;
            $address_1 = $billing_address_1[0]->meta_value;


            $id_wp_posts = $wp_posts[0]->ID+1;

            $id_order_item = $wp_woocommerce_order_items[0]->order_item_id;


            $ip = request()->ip();

            $agent= request()->header('User-Agent');


            $this->woo_insert_post($db,$id_wp_posts,$id_wp_user);


            $this->woo_insert_postmeta($db,$id_wp_posts,$id_wp_user,$ip,$agent,$price[0]->price,$first_name,$last_name,$address_1);

            $this->insert_woocommerce_order_items($db,$id_order_item,$id_wp_posts,$roomName,$start,$end);

            $this->insert_woocommerce_order_itemmeta($db,$price[0]->price);


            // END


            return response()->json([
                'message' => __('Room :name is successfully booked!', ['name' => $data['roomName']])
            ]);
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

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        $booking = Booking::with('room')->findOrFail($id);

        return $booking;

        //  abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */

    public function edit($id)
    {
        $user_id = Auth::id();

        $pageTitle = 'edit booking';

        $pageHeader = 'Edit Booking';

        $booking = Booking::find($id);

        $optionals = BookingOptional::find($id);

        $a='0';

        if($optionals == null){
            return view('dashboard.edit_booking', compact('booking', 'pageTitle','pageHeader','a'));
        } else {

            $a='1';

            $price_tot_optional =   $optionals->coffee_break+
                $optionals->quick_lunch+
                $optionals->videoproiettore+
                $optionals->permenent_coffee+
                $optionals->wifi+
                $optionals->videoconferenza +
                $optionals->webconference +
                $optionals->lavagna_foglimobili +
                $optionals->stampante +
                $optionals-> permenent_coffeeplus +
                $optionals->connessione_viacavo +
                $optionals->integrazione_permanentcoffee+
                $optionals->upgrade_banda10mb +
                $optionals->upgrade_banda8mb +
                $optionals->upgrade_banda20mb +
                $optionals->wirless_4mb20accessi +
                $optionals->wirless_8mb35accessi +
                $optionals->wirless_10mb50accessi +
                $optionals->videoregistrazione +
                $optionals->fattorino +
                $optionals->lavagna_interattiva;

            $total_price = $booking->price + $price_tot_optional ;

            return view('dashboard.edit_booking', compact('booking', 'price_tot_optional', 'total_price' ,'pageTitle','pageHeader','a','optionals'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        $booking = Booking::find($id);
        $user = User::find($booking->booked_by);

        $booking->room_setup = $request->room_setup;
        // se è un utente registrato ad effetuare il booking
        if(is_null($request->booked_name))
            $booking->booked_name = $user->name;
         else
        // nel caso venga inserita da parte dell'admin
            $booking->booked_name = $request->booked_name;

        $booking->status = $request->status;

        $booking->update();

        return redirect('dashboard/bookings');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->canDeleteBooking()) {
            return response()->json([
                'message' => __('You have no authorization to perform this action.')
            ], 403);
        }

        $booking = Booking::findOrFail($id);

        if ($booking->delete()) {
            $booking->status = BookingStatus::CANCELLED;
            $booking->save();
        }

        return response()->json([
            'message' => __('Booking on room :name is successfully cancelled!', ['name' => $booking->room->name])
        ]);
    }

    public function search(Request $request)
    {
		
		//return $request->all();

      //  return $request->all();

       // return $request->bookingTimeUno;

        if (!auth()->user()->canCreateBooking()) {
            return response()->json([
                'errors' => [
                    'message' => __('You have no authorization to perform this action.')
                ]
            ], 403);
        }

        $data = $request->validate([
            'bookingTimeUno' => [
                'bail',
                'required'
            ],
            'bookingTimeDue' => [
                'bail',
                'required'
            ],
            'pax' => 'required|integer|min:1',
            'location' => 'string'
        ]);

        $time = $request['bookingTimeUno'] . ' - ' . $request['bookingTimeDue'];

        $bookingTime = explode(' - ', $time);

       // return $bookingTime;

        $start = Carbon::createFromFormat(DateFormat::DATE_RANGE_PICKER, $bookingTime[0]);
        $end = Carbon::createFromFormat(DateFormat::DATE_RANGE_PICKER, $bookingTime[1]);

        $start_hour = explode(" ", $start);
        $end_hour = explode(" ", $end);

        $diff_sec = strtotime($end_hour[1]) - strtotime($start_hour[1]);

        $diff_sec = strtotime($end_hour[1]) - strtotime($start_hour[1]);

        // Todo in case of duration is <> 4 end >8 //

        $duration = (integer)$diff_sec / 3600;

        $diff_day = (strtotime($end_hour[0]) - strtotime($start_hour[0])) / 86400;

         // differenza tra date in giorni

        if ($diff_day == 0) {
            if ($duration > 4) {
                $duration = 8;
                // Query ricerca disponibilita //
                $rooms = Room::Available($start, $end)
                    ->join('prices', function ($join) use ($duration) {
                        $join->on('prices.price_id', '=', 'rooms.id')
                            ->where('prices.duration', '=', $duration);
                    })
                    ->where('pax', '=', $data['pax'])
                    ->where('location', '=', $data['location']) // Search in base alla sede
                    ->get(['name', 'pax', 'id', 'location', 'type', 'price']);

                // Create book button
                $rooms = $rooms->each(function ($room) {
                    $bookUrl = route('bookings.store');
                    $bookBtn = '<button class="btn btn-xs btn-primary btn-book"';
                    $bookBtn .= 'data-remote="' . $bookUrl . '" data-name="' . $room->name . '" data-id="' . $room->id . '">';
                    $bookBtn .= '<span class="glyphicon glyphicon-edit"></span> ';
                    $bookBtn .= __('Book');
                    $bookBtn .= '</button>';

                    $room->action = $bookBtn;
                });

                $result = [];

                foreach ($rooms as $key => $value) {
                    $result[] = $value;
                }

                return response()->make($result);
            } else {

                // Query ricerca disponibilita //
                $rooms = Room::Available($start, $end)
                    ->join('prices', function ($join) use ($duration) {
                        $join->on('prices.price_id', '=', 'rooms.id')
                            ->where('prices.duration', '=', $duration);
                    })
                    ->where('pax', '=', $data['pax'])
                    ->where('location', '=', $data['location'])// Search in base alla sede
                    ->get(['name', 'pax', 'id', 'location', 'type', 'price']);

                // Create book button
                $rooms = $rooms->each(function ($room) {
                    $bookUrl = route('bookings.store');
                    $bookBtn = '<button class="btn btn-xs btn-primary btn-book"';
                    $bookBtn .= 'data-remote="' . $bookUrl . '" data-name="' . $room->name . '" data-id="' . $room->id . '">';
                    $bookBtn .= '<span class="glyphicon glyphicon-edit"></span> ';
                    $bookBtn .= __('Book');
                    $bookBtn .= '</button>';

                    $room->action = $bookBtn;
                });

                $result = [];

                foreach ($rooms as $key => $value) {
                    $result[] = $value;
                }

                return response()->make($result);
            }

        } else {

            $diff_day = $diff_day + 1;

            $duration = (integer)$diff_sec / 3600;

        //   return $diff_day.' - '.$duration;

            if ($duration > 4) {

                $duration = 8;
                // Query ricerca disponibilita //
                $rooms = Room::Available($start, $end,$diff_day)
                    ->join('prices', function ($join) use ($duration) {
                        $join->on('prices.price_id', '=', 'rooms.id')
                            ->where('prices.duration', '=', $duration);
                    })
                    ->where('pax', '=', $data['pax'])
                    ->where('location', '=', $data['location'])// Search in base alla sede
                    ->get(['name', 'pax', 'id', 'location', 'type', 'price']);

                // Create book button
                $rooms = $rooms->each(function ($room) {
                    $bookUrl = route('bookings.store');
                    $bookBtn = '<button class="btn btn-xs btn-primary btn-book"';
                    $bookBtn .= 'data-remote="' . $bookUrl . '" data-name="' . $room->name . '" data-id="' . $room->id . '">';
                    $bookBtn .= '<span class="glyphicon glyphicon-edit"></span> ';
                    $bookBtn .= __('Book');
                    $bookBtn .= '</button>';

                    $room->action = $bookBtn;
                });

                $result = [];

                foreach ($rooms as $key => $value) {
                    $result[] = $value;
                }

                return response()->make($result);
            } else {


                // Query ricerca disponibilita //
                $rooms = Room::Available($start, $end)
                    ->join('prices', function ($join) use ($duration) {
                        $join->on('prices.price_id', '=', 'rooms.id')
                            ->where('prices.duration', '=', $duration);
                    })
                    ->where('pax', '=', $data['pax'])
                    ->where('location', '=', $data['location'])// Search in base alla sede
                    ->get(['name', 'pax', 'id', 'location', 'type', 'price']);


                foreach ($rooms as $room) {
                    $room->price = $room->price * $diff_day;
                }


                // Create book button
                $rooms = $rooms->each(function ($room) {
                    $bookUrl = route('bookings.store');
                    $bookBtn = '<button class="btn btn-xs btn-primary btn-book"';
                    $bookBtn .= 'data-remote="' . $bookUrl . '" data-name="' . $room->name . '" data-id="' . $room->id . '">';
                    $bookBtn .= '<span class="glyphicon glyphicon-edit"></span> ';
                    $bookBtn .= __('Book');
                    $bookBtn .= '</button>';

                    $room->action = $bookBtn;
                });

                $result = [];

                foreach ($rooms as $key => $value) {
                    $result[] = $value;
                }

                return response()->make($result);
            }

        }
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


       /* $db->table('wp_postmeta')->insert([
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
            ['post_id' => $post, 'meta_key'=>'_billing_first_name','meta_value' => $wp_users[0]->user_login],
            ['post_id' => $post, 'meta_key'=>'_billing_last_name','meta_value' => $wp_users[0]->user_login],
            ['post_id' => $post, 'meta_key'=>'_billing_company','meta_value' => 'null'],
            ['post_id' => $post, 'meta_key'=>'_billing_address_1','meta_value' => 'null'],
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
        ]); */


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



}

