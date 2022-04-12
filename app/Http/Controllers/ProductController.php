<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Currency;
use App\Models\Product;
use App\Models\UserOrder;
use App\Models\BasketItem;
use Illuminate\Support\Facades\Auth;
use DB;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $products = Product::leftJoin('product_category','products.product_category_id','=','product_category.id')
        ->leftJoin('product_status','products.product_status_id','=','product_status.id')
        ->select(
         'products.id AS product_id',
         'products.name AS product_name',
         'products.mini_description_rus',
         'products.image AS image',
         'products.amount AS product_amount',
         'products.client_amount_perc',
         'products.dist_amount_perc',
         'product_category.name_rus AS product_category_name'
     )
        ->where('products.is_show', 1)
        ->orderBy('products.id')
        ->get();

        $total_products = array();
        if(count($products) > 0){
            foreach ($products as $product) {
                if(isset(Auth::user()->id)){
                    $current_user_status_id = Auth::user()->current_status_id;
                    if($current_user_status_id < 3){
                        $product_amount = $product['product_amount'] - (($product['product_amount'] / 100) * $product['client_amount_perc']);
                    }
                    elseif($current_user_status_id >= 3){
                        $client_amount = $product['product_amount'] - (($product['product_amount'] / 100) * $product['client_amount_perc']);
                        $product_amount = $client_amount - (($client_amount / 100) * $product['dist_amount_perc']);
                    }
                    $mark = round(($product_amount / Currency::find(1)->amount) * Currency::find(2)->amount);
                }
                else{
                    $mark = 0;
                    $product_amount = $product['product_amount'];
                }

                array_push($total_products, array(
                    'id' => $product['product_id'],
                    'name' => $product['product_name'],
                    'mini_description_rus' => $product['mini_description_rus'],
                    'image' => $product['image'],
                    'product_amount' => $product_amount,
                    'product_mark' => $mark,
                ));
            }
        }


//Проверка на наличие товаров в корзине пользователя
        @$current_user_id = Auth::user()->id;

        $openOrder = UserOrder::where('buyer_id', $current_user_id)
        ->where('status',1)
        ->first();

        if(isset($openOrder)){
            $products_in_basket = BasketItem::leftJoin('user_orders','basket_items.order_id','=','user_orders.id')
            ->select(
                'basket_items.product_id'
            )
            ->where('user_orders.status', 1)
            ->where('user_orders.buyer_id', $current_user_id)
            ->get();

            if(count($products_in_basket) > 0){
                $products = array();
                foreach ($products_in_basket as $product) {
                    array_push($products, $product['product_id']);
                }
                $result['products_in_basket'] = $products;
            }
            else{
                $result['products_in_basket'] = array();
            }
        }
        else{
            $result['products_in_basket'] = array();
        }

        $result['products'] = $total_products;
        return response()->json($result, 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
