<?php 
use App\Models\User;
use App\Models\UserOperation;
use App\Models\BasketItem;

// Начисление бонуса и назначение статуса и создание операции
if (!function_exists('set_b_s_o')){
    function set_b_s_o($u_id, $bonus, $status_id, $operation_type_id){
        $currentUser = User::find($u_id);
        $currentUser->main_wallet += $bonus;
        if($status_id > $currentUser->maximal_status_id){
            $currentUser->maximal_status_id = $status_id;
        }
        $currentUser->current_status_id = $status_id;
        $currentUser->save();

        if($bonus > 0){
            $n_op = new UserOperation();
            $n_op->operation_type_id = $operation_type_id;
            $n_op->amount = $bonus;
            $n_op->author_id = 1;
            $n_op->recipient_id = $u_id;
            $n_op->comment = null;
            $n_op->save();
        }
    }
}


// Личный оборот и количество приглашении
if (!function_exists('get_pt_ic')){
    function get_pt_ic($u_id, &$personal_turnover, &$invite_count){

        $from = date("Y-m-01 00:00:00", strtotime('-1 month'));
        $to = date("Y-m-t 23:59:00", strtotime('-1 month'));

        $total_sales = BasketItem::leftJoin('user_orders','basket_items.order_id','=','user_orders.id')
        ->select(DB::raw('SUM(basket_items.product_mark * basket_items.product_count) as total'))
        ->whereBetween('basket_items.created_at',[$from, $to])
        ->where('user_orders.status', 3)
        ->where('user_orders.buyer_id', $u_id)
        ->first();

        $personal_turnover += $total_sales['total'];

        $first_line_childs = User::where('sponsor_id', '=', $u_id)
        ->get();

        foreach($first_line_childs as $line_child){
            if($line_child->current_status_id == 1){
                $client_sales = BasketItem::leftJoin('user_orders','basket_items.order_id','=','user_orders.id')
                ->select(DB::raw('SUM(basket_items.product_mark * basket_items.product_count) as total'))
                ->whereBetween('basket_items.created_at',[$from, $to])
                ->where('user_orders.status', 3)
                ->where('user_orders.buyer_id', $line_child->id)
                ->first();
                if($client_sales['total'] > 0){
                    $personal_turnover += $client_sales['total'];
                }
            }
            if($line_child->current_status_id > 1){
                $invite_count++;
            }
        }
    }
}

// Групповой оборот
if (!function_exists('g_t')){
    function g_t($u_id, &$group_turnover) {
        $childs = User::where('sponsor_id', '=', $u_id)
        ->where('current_status_id', '>', 1)
        ->get();
        if(count($childs) > 0){
            foreach ($childs as $child) {
                $child_p_t= 0;
                $child_i_c = 0;
                get_pt_ic($child->id, $child_p_t, $child_i_c);
                $group_turnover += $child_p_t;
                g_t($child->id, $group_turnover);
            }
        }
    }
}

// Объём личной группы
if (!function_exists('p_g_v')){
    function p_g_v($u_id, &$pgv) {
        $childs = User::where('sponsor_id', '=', $u_id)
        ->whereBetween('current_status_id',[2, 7])
        ->get();
        if(count($childs) > 0){
            foreach ($childs as $child) {
                $child_p_t = 0;
                $child_i_c = 0;
                get_pt_ic($child->id, $child_p_t, $child_i_c);
                $pgv += $child_p_t;
                p_g_v($child->id, $pgv);
            }
        }
    }
}

// Подсчет розничной премии
if (!function_exists('recursiveRetailTree')){
    function recursiveRetailTree($retail_options, $u_id, &$child_turnover) {

        $childs = User::where('sponsor_id', '=', $u_id)
        ->where('current_status_id', '>', 1)
        ->get();

        foreach ($childs as $child) {
            $child_p_t= 0;
            $child_i_c = 0;
            get_pt_ic($child->id, $child_p_t, $child_i_c);

            if($child_p_t < $retail_options->child_cumulative){
                $child_turnover += $child_p_t;
                recursiveRetailTree($retail_options, $child->id, $child_turnover);
            }
        }
    }
}

// Поиск тимлидов в глубину и компрессия
if (!function_exists('search_depth')){
    function search_depth($u_id, $level, &$depths) {
        $childs = User::where('sponsor_id', '=', $u_id)
        ->whereIn('current_status_id', [2,8,9,10,11,12,13,14,15,16,17,18])
        ->get();

        if(count($childs) > 0){
            foreach ($childs as $child) {

                if($child->current_status_id == 2){
                    $child_personal_turnover = 0;
                    get_pt_ic($child->id, $child_personal_turnover, $invite_count);

                    if ($child_personal_turnover == 0) {
                        search_depth($child->id, $level, $depths);
                    }
                }

                if($child->current_status_id >= 8){
                    array_push($depths[$level], $child->id);
                }  
            }
        }
    }
}

// Личный оборот и количество приглашении (для лидерской премии)
if (!function_exists('get_pt_ic_ls')){
    function get_pt_ic_ls($u_id, &$personal_turnover, &$invite_count, $prev_month_from, $prev_month_to){

        $total_sales = BasketItem::leftJoin('user_orders','basket_items.order_id','=','user_orders.id')
        ->select(DB::raw('SUM(basket_items.product_mark * basket_items.product_count) as total'))
        ->whereBetween('basket_items.created_at',[$prev_month_from, $prev_month_to])
        ->where('user_orders.status', 3)
        ->where('user_orders.buyer_id', $u_id)
        ->first();

        $personal_turnover += $total_sales['total'];

        $first_line_childs = User::where('sponsor_id', '=', $u_id)
        ->get();

        foreach($first_line_childs as $line_child){
            if($line_child->current_status_id == 1){
                $client_sales = BasketItem::leftJoin('user_orders','basket_items.order_id','=','user_orders.id')
                ->select(DB::raw('SUM(basket_items.product_mark * basket_items.product_count) as total'))
                ->whereBetween('basket_items.created_at',[$prev_month_from, $prev_month_to])
                ->where('user_orders.status', 3)
                ->where('user_orders.buyer_id', $line_child->id)
                ->first();
                if($client_sales['total'] > 0){
                    $personal_turnover += $client_sales['total'];
                }
            }
            if($line_child->current_status_id > 1){
                $invite_count++;
            }
        }
    }
}

// Групповой оборот (для лидерской премии)
if (!function_exists('g_t_ls')){
    function g_t_ls($u_id, &$group_turnover, $prev_month_from, $prev_month_to) {
        $childs = User::where('sponsor_id', '=', $u_id)
        ->where('current_status_id', '>', 1)
        ->get();

        foreach ($childs as $child) {
            $child_p_t= 0;
            $child_i_c = 0;
            get_pt_ic_ls($child->id, $child_p_t, $child_i_c, $prev_month_from, $prev_month_to);
            $group_turnover += $child_p_t;
            g_t_ls($child->id, $group_turnover, $prev_month_from, $prev_month_to);
        }
    }
}

// Объём личной группы (для лидерской премии)
if (!function_exists('p_g_v_ls')){
    function p_g_v_ls($u_id, &$pgv, $prev_month_from, $prev_month_to) {
        $childs = User::where('sponsor_id', '=', $u_id)
        ->whereBetween('current_status_id',[2, 7])
        ->get();
        if(count($childs) > 0){
            foreach ($childs as $child) {
                $child_p_t = 0;
                $child_i_c = 0;
                get_pt_ic_ls($child->id, $child_p_t, $child_i_c, $prev_month_from, $prev_month_to);
                $pgv += $child_p_t;
                p_g_v_ls($child->id, $pgv, $prev_month_from, $prev_month_to);
            }
        }
    }
}
?>