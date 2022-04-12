<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserOrder;
use App\Models\BasketItem;
use Illuminate\Database\Seeder;

class PurchaseSeeder extends Seeder
{
    /**
     * Seed the application's purchase.
     *
     * @return void
     */
    public function run()
    {
        $level = 7;
        $users = User::get();

        foreach ($users as $user) {

            $product_count = 100;
            $product_amount = 10000;

            for ($l = $level; $l >= 1; $l--) {
                $month = $l - 1;
                $prev_month_from = date("Y-m-01 00:00:00", strtotime('-'.$month.' month'));

                $order = new UserOrder();
                $order->buyer_id = $user->id;
                $order->payment_type_id = 1;
                $order->status = 3;
                $order->created_at = $prev_month_from;
                $order->updated_at = $prev_month_from;
                $order->save();

                $basket_item = new BasketItem();
                $basket_item->order_id = $order->id;
                $basket_item->product_id = 3;
                $basket_item->product_amount = $product_amount;
                $basket_item->product_mark = (($product_amount / 432) * 0.65);
                $basket_item->product_count = $product_count;
                $basket_item->created_at = $prev_month_from;
                $basket_item->updated_at = $prev_month_from;
                $basket_item->save();
            }
        }
    }
}
