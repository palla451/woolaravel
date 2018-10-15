<?php

namespace App;

use App\Enumerations\BookingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use SoftDeletes;

    const DATE_FORMAT = 'j M Y, H:i';

    protected $dates = [
        'start_date',
        'end_date',
        'deleted_at'
    ];

    protected $fillable = [
        'booked_by',
        'booked_name',
        'room_id',
        'start_date',
        'end_date',
        'status',
        'location',
        'location_id',
        'price',
        'optional_id',
        'room_setup'
    ];

    /**
     * Get room details which related to this booking
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get user who related to this booking
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'booked_by');
    }

    /**
     * Get duration for current booking
     *
     * @return mixed
     */
    public function getDuration()
    {
        $year = '';
        $month = '';
        $day = '';
        $hour = '';
        $minute = '';

        if (isset($this->start_date) && isset($this->end_date)) {
            $duration = $this->end_date->diff($this->start_date);

            $tempArray = [];

            if (!empty($duration->y)) {
                $tempArray[] = $duration->y . ($duration->y > 1 ? ' years' : ' year');
            }

            if (!empty($duration->m)) {
                $tempArray[] = $duration->m . ($duration->m > 1 ? ' months' : ' month');
            }

            if (!empty($duration->d)) {
                $tempArray[] = $duration->d . ($duration->d > 1 ? ' days' : ' day');
            }

            if (!empty($duration->h)) {
                $tempArray[] = $duration->h . ($duration->h > 1 ? ' hours' : ' hour');
            }

            if (!empty($duration->i)) {
                $tempArray[] = $duration->i . ($duration->i > 1 ? ' minutes' : ' minute');
            }

            $last = array_pop($tempArray);
            return count($tempArray) ? implode(', ', $tempArray) . ' &amp; ' . $last : $last;
        }
    }

    /**
     * Get Status in text
     *
     * @return string
     */

    public function getStatusTextualRepresentation()
    {
        $text = '';
        switch ($this->status) {
            case BookingStatus::CONFIRMED:
                $text = 'Confirmed';
                break;
            case BookingStatus::OPTION:
                $text = 'Option';
                break;
            case BookingStatus::CANCELLED:
                $text = 'Cancelled';
                break;
            default:
                break;
        }

        return $text;
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
	
	// inser in wp_woocommerce_order_items
    public function insert_woocommerce_order_items($db,$order_item_id,$order_id,$roomName,$start,$end){


        $order_item_id = $order_item_id+1;

        $db->table('wp_woocommerce_order_items')->insert([
            ['order_item_name'=>$roomName.' dal '. $start . ' al ' . $end,   'order_item_type' => 'line_item',   'order_id'=>$order_id],
            ['order_item_name'=>'Spedizione gratuita',   'order_item_type' => 'shipping',   'order_id'=>$order_id],
        ]);

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
	
}
