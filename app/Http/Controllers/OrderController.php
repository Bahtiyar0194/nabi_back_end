<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Currency;
use App\Models\User;
use App\Models\Product;
use App\Models\UserOperation;
use App\Models\UserOrder;
use App\Models\BasketItem;
use App\Models\PaymentType;
use App\Models\DeliveryType;
use App\Models\Country;
use App\Models\Region;
use App\Models\City;
use App\Models\Delivery;
use App\Models\Pickup;
use App\Models\Warehouse;
use App\Models\Buyerinfo;
use Illuminate\Support\Facades\Auth;
use Validator;
use DB;

class OrderController extends Controller
{
    public function __construct(Request $request) {
        app()->setLocale($request->header('Lang'));
    }

    public function get_orders(Request $request)
    {
        $current_user_id = Auth::user()->id;
        $openOrder = UserOrder::where('buyer_id', $current_user_id)
        ->where('status',1)
        ->first();

        if(isset($openOrder)){
            $orders = BasketItem::leftJoin('products','basket_items.product_id','=','products.id')
            ->leftJoin('user_orders','basket_items.order_id','=','user_orders.id')
            ->select(
                'products.id AS product_id',
                'products.name AS product_name',
                'products.image AS image',
                'products.amount AS product_amount',
                'products.client_amount_perc',
                'products.dist_amount_perc',
                'basket_items.product_count AS product_count'
            )
            ->where('user_orders.status', 1)
            ->where('user_orders.buyer_id', $current_user_id)
            ->get();

            $total_orders = array();
            $total_order_sum = 0;
            $total_marks = 0;
            $total_product_count = 0;
            if(count($orders) > 0){
                foreach ($orders as $order) {
                    if(isset(Auth::user()->id)){
                        $current_user_status_id = Auth::user()->current_status_id;
                        if($current_user_status_id < 3){
                            $product_amount = $order['product_amount'] - (($order['product_amount'] / 100) * $order['client_amount_perc']);
                        }
                        elseif($current_user_status_id >= 3){
                            $client_amount = $order['product_amount'] - (($order['product_amount'] / 100) * $order['client_amount_perc']);
                            $product_amount = $client_amount - (($client_amount / 100) * $order['dist_amount_perc']);
                        }
                        $marks = round((($product_amount / Currency::find(1)->amount) * Currency::find(2)->amount) * $order['product_count'], 2);
                        $total_marks += $marks;
                    }
                    else{
                        $product_amount = $order['product_amount'];
                        $marks = 0;
                    }
                    array_push($total_orders, array(
                        'product_id' => $order['product_id'],
                        'product_name' => $order['product_name'],
                        'image' => $order['image'],
                        'product_amount' => $product_amount,
                        'product_mark' => $marks,
                        'product_count' => $order['product_count']
                    ));
                    $total_order_sum += ($product_amount * $order['product_count']);

                    $total_product_count += $order['product_count'];
                }
            }
            $result['order_id'] = $openOrder->id;
            $result['orders'] = $total_orders;
            $result['total_order_sum'] = $total_order_sum;
            $result['total_marks'] = $total_marks;
            $result['total_product_count'] = $total_product_count;

            return response()->json($result, 200);
        }
    }

    public function plus_product_basket(Request $request)
    {
        $current_user_id = Auth::user()->id;
        $product = Product::find($request->product_id);
        $amount = ($product->amount - ($product->amount * $product->client_amount_perc) / 100);

        $openOrder = UserOrder::where('buyer_id', $current_user_id)
        ->where('status',1)
        ->first();

        if(isset($openOrder)){
            $openOrderId = $openOrder->id;
        }
        else{
            $new_order = new UserOrder();
            $new_order->buyer_id = $current_user_id;
            $new_order->save();
            $openOrderId = $new_order->id;            
        }

        $basket_item = BasketItem::where('order_id', $openOrderId)
        ->where('product_id', $product->id)
        ->first();

        if(isset($basket_item)){
            $basket_item->product_count++;
            $basket_item->save();
        }
        else{
            $new_basket_item = new BasketItem();
            $new_basket_item->order_id = $openOrderId;
            $new_basket_item->product_id = $product->id;
            $new_basket_item->save();
        }

        $result['status'] = 1;
        return response()->json($result, 200);
    }

    public function minus_product_basket(Request $request)
    {
        $current_user_id = Auth::user()->id;
        $openOrder = UserOrder::where('buyer_id', $current_user_id)
        ->where('status',1)
        ->first();

        if (isset($openOrder)) {
            $deleteProduct = BasketItem::where('order_id', $openOrder->id)
            ->where('product_id', $request->product_id)
            ->first();

            if(isset($deleteProduct)){
                if($deleteProduct->product_count > 1){
                    $deleteProduct->product_count--;
                    $deleteProduct->save();
                    $result['status'] = 1;
                    return response()->json($result, 200);
                }
            }
        }
    }

    public function change_product_count_in_basket(Request $request)
    {
        $current_user_id = Auth::user()->id;
        $openOrder = UserOrder::where('buyer_id', $current_user_id)
        ->where('status',1)
        ->first();

        if (isset($openOrder)) {
            $changeProduct = BasketItem::where('order_id', $openOrder->id)
            ->where('product_id', $request->product_id)
            ->first();

            if(isset($changeProduct)){
                if(is_numeric($request->count) && $request->count > 0){
                    if($changeProduct->product_count >= 1){
                        $changeProduct->product_count = $request->count;
                        $changeProduct->save();
                        $result['status'] = 1;
                        return response()->json($result, 200);
                    }
                }
            }
        }
    }

    public function delete_product_basket(Request $request)
    {
        $current_user_id = Auth::user()->id;
        $openOrder = UserOrder::where('buyer_id', $current_user_id)
        ->where('status',1)
        ->first();

        if (isset($openOrder)) {
            $deleteProduct = BasketItem::where('order_id', $openOrder->id)
            ->where('product_id', $request->product_id)
            ->first();

            if(isset($deleteProduct)){
                $deleteProduct->delete();
                $result['status'] = 1;
                return response()->json($result, 200);
            }
        }
    }

    public function get_payment_and_delivery_types(Request $request)
    {
        if(isset(Auth::user()->id)){
            $payment_types = PaymentType::where('is_show', 1)
            ->get();
        }
        else{
            $payment_types = PaymentType::where('is_show', 1)
            ->where('show_on_auth', 0)
            ->get();
        };

        $delivery_types = DeliveryType::where('is_show', 1)
        ->get();

        $result['payment_types'] = $payment_types;
        $result['delivery_types'] = $delivery_types;

        return response()->json($result, 200);
    }

    public function get_countries_for_delivery_and_pickup(Request $request)
    {
        $delivery_countries = Country::where('is_show', 1)
        ->where('show_for_delivery', 1)
        ->get();

        $pickup_countries = Country::leftJoin('regions','regions.country_id','=','countries.id')
        ->leftJoin('cities','cities.region_id','=','regions.id')
        ->leftJoin('warehouse','warehouse.city_id','=','cities.id')
        ->select(
            'countries.id',
            'countries.name'
        )
        ->where('warehouse.is_show', 1)
        ->groupBy('countries.id')
        ->get();

        $result['delivery_countries'] = $delivery_countries;
        $result['pickup_countries'] = $pickup_countries;

        return response()->json($result, 200);
    }

    public function get_regions_for_delivery(Request $request)
    {
        $regions = Region::where('country_id', $request->c_id)
        ->where('is_show', 1)
        ->get();
        return response()->json($regions, 200);
    }

    public function get_cities_for_delivery(Request $request)
    {
        $cities = City::where('region_id', $request->r_id)
        ->where('is_show', 1)
        ->get();
        return response()->json($cities, 200);
    }

    public function get_cities_for_pickup(Request $request)
    {
        $warehouses = Warehouse::leftJoin('cities','warehouse.city_id','=','cities.id')
        ->leftJoin('regions','cities.region_id','=','regions.id')
        ->leftJoin('countries','regions.country_id','=','countries.id')
        ->select(
            'cities.id',
            'cities.name AS city',
            'warehouse.address'
        )
        ->where('countries.id', $request->c_id)
        ->where('warehouse.is_show', 1)
        ->get();

        return response()->json($warehouses, 200);
    }


    public function place_an_order(Request $request)
    {
        $current_user_id = Auth::user()->id;
        if(isset($current_user_id)){
            $openOrder = UserOrder::where('buyer_id', $current_user_id)
            ->where('id', $request->o_id)
            ->where('status', 1)
            ->first();

            if(isset($openOrder)){
                $orders = BasketItem::leftJoin('products','basket_items.product_id','=','products.id')
                ->leftJoin('user_orders','basket_items.order_id','=','user_orders.id')
                ->select(
                    'products.id AS product_id',
                    'products.name AS product_name',
                    'products.image AS image',
                    'products.amount AS product_amount',
                    'products.client_amount_perc',
                    'products.dist_amount_perc',
                    'basket_items.id AS basket_item_id',
                    'basket_items.product_count AS product_count'
                )
                ->where('user_orders.status', 1)
                ->where('user_orders.buyer_id', $current_user_id)
                ->get();

                $total_order_sum = 0;
                $total_marks = 0;
                if(count($orders) > 0){
                    foreach ($orders as $order) {
                        if(isset(Auth::user()->id)){
                            $current_user_status_id = Auth::user()->current_status_id;
                            if($current_user_status_id < 3){
                                $product_amount = $order['product_amount'] - (($order['product_amount'] / 100) * $order['client_amount_perc']);
                            }
                            elseif($current_user_status_id >= 3){
                                $client_amount = $order['product_amount'] - (($order['product_amount'] / 100) * $order['client_amount_perc']);
                                $product_amount = $client_amount - (($client_amount / 100) * $order['dist_amount_perc']);
                            }
                            $marks = round((($product_amount / Currency::find(1)->amount) * Currency::find(2)->amount) * $order['product_count'], 2);
                            $total_marks += $marks;
                        }
                        else{
                            $product_amount = $order['product_amount'];
                            $marks = 0;
                        }
                        $total_order_sum += ($product_amount * $order['product_count']);
                    }
                }

                $validator = Validator::make($request->c_form_data, [
                    'name' => 'required|string|between:2,100',
                    'last_name' => 'required|string|between:2,100',
                    'email' => 'required|string|email|max:100',
                    'phone' => 'required|string|max:100',
                ]);
                if($validator->fails()){
                    $errors['c_errors'] = $validator->errors();
                    return response()->json($errors, 422);
                }


                switch ($request->d_id) {
                    case 1:
                    $validator = Validator::make($request->d_form_data, [
                        'country' => 'required',
                        'region' => 'required',
                        'city' => 'required',
                        'street' => 'required|string|between:2,200',
                        'house' => 'required',
                    ]);

                    if($validator->fails()){
                        $errors['d_errors'] = $validator->errors();
                        return response()->json($errors, 422);
                    }
                    break;

                    case 2:
                    $validator = Validator::make($request->pu_form_data, [
                        'country' => 'required',
                        'city' => 'required',
                    ]);

                    if($validator->fails()){
                        $errors['d_errors'] = $validator->errors();
                        return response()->json($errors, 422);
                    }
                    break;
                }

                switch ($request->pt_id) {
                    case 1:
                    if((Auth::user()->main_wallet * Currency::find(1)->amount) >= $total_order_sum){
                        $currentUser = User::find($current_user_id);
                        $currentUser->main_wallet -= ($total_order_sum / Currency::find(1)->amount);
                        $currentUser->save();
                        $is_paid = 1;
                    }
                    else{
                        $result['status'] = 2;
                        return response()->json($result, 200);
                    }
                    break;
                    
                    default:
                    $result['status'] = 3;
                    return response()->json($result, 200);
                    break;
                }


                if($is_paid === 1){
                    switch ($request->d_id) {
                        case 1:
                        $new_delivery = new Delivery();
                        $new_delivery->order_id = $openOrder->id;
                        $new_delivery->city_id = $request->d_form_data['city'];
                        $new_delivery->street = $request->d_form_data['street'];
                        $new_delivery->house = $request->d_form_data['house'];
                        $new_delivery->flat = $request->d_form_data['flat'];
                        $new_delivery->save();
                        break;

                        case 2:
                        $new_pickup = new Pickup();
                        $new_pickup->order_id = $openOrder->id;
                        $new_pickup->warehouse_id = $request->pu_form_data['city'];
                        $new_pickup->save();
                        break;
                    }

                    $new_buyer = new Buyerinfo();
                    $new_buyer->last_name = $request->c_form_data['last_name'];
                    $new_buyer->name = $request->c_form_data['name'];
                    $new_buyer->email = $request->c_form_data['email'];
                    $new_buyer->phone = $request->c_form_data['phone'];
                    $new_buyer->order_id = $openOrder->id;
                    $new_buyer->save();

                    $openOrder->status = 3;
                    $openOrder->payment_type_id = $request->pt_id;
                    $openOrder->save();

                    if(count($orders) > 0){
                        $direct_bonus = 0;
                        foreach ($orders as $order) {
                            if(isset(Auth::user()->id)){
                                $current_user_status_id = Auth::user()->current_status_id;
                                if($current_user_status_id < 3){
                                    $product_amount = $order['product_amount'] - (($order['product_amount'] / 100) * $order['client_amount_perc']);
                                }
                                elseif($current_user_status_id >= 3){
                                    $client_amount = $order['product_amount'] - (($order['product_amount'] / 100) * $order['client_amount_perc']);
                                    $product_amount = $client_amount - (($client_amount / 100) * $order['dist_amount_perc']);
                                }
                                $direct_bonus += ($order['product_amount'] - $product_amount);
                                $mark = round((($product_amount / Currency::find(1)->amount) * Currency::find(2)->amount), 2);
                            }
                            else{
                                $mark = 0;
                                $product_amount = $order['product_amount'];
                            }

                            $order_item = BasketItem::find($order['basket_item_id']);
                            $order_item->product_amount = $product_amount;
                            $order_item->product_mark = $mark;
                            $order_item->save();
                        }
                    }
                    
                    if(isset(Auth::user()->id)){
                        if(Auth::user()->current_status_id == 1){
                            $getSponsor = User::find(Auth::user()->sponsor_id);
                            if(isset($getSponsor->id)){
                               $getSponsor->main_wallet += ($direct_bonus / Currency::find(1)->amount);
                               $getSponsor->save();

                               $n_op = new UserOperation();
                               $n_op->operation_type_id = 4;
                               $n_op->amount = $direct_bonus / Currency::find(1)->amount;
                               $n_op->author_id = Auth::user()->id;
                               $n_op->recipient_id = $getSponsor->id;
                               $n_op->comment = null;
                               $n_op->save();
                           }
                       }

                       $n_op = new UserOperation();
                       $n_op->operation_type_id = 5;
                       $n_op->amount = $total_order_sum / Currency::find(1)->amount;
                       $n_op->author_id = Auth::user()->id;
                       $n_op->recipient_id = null;
                       $n_op->comment = null;
                       $n_op->save();
                   }

                   $result['status'] = 1;
                   return response()->json($result, 200);
               }
           }
       }
   }
}
