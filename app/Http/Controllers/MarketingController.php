<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserOperation;
use App\Models\Currency;
use App\Models\UserOrder;
use App\Models\BasketItem;
use App\Models\StatusType;
use App\Models\Recruiting;
use App\Models\StructureBuilding;
use App\Models\Retail;
use App\Models\Mentor;
use App\Models\LeaderShip;
use App\Models\Independent;
use Illuminate\Support\Facades\Auth;
use DB;

class MarketingController extends Controller
{

    public function __construct(Request $request) {
        app()->setLocale($request->header('Lang'));
    }

    public function get_status_types(Request $request)
    {
        $statuses = StatusType::get();
        return response()->json($statuses, 200);
    }

    public function recruiting_data(Request $request)
    {
        $recruiting = Recruiting::find(1);
        $currency = Currency::find(1);

        $result['price'] = $recruiting['price'];
        $result['currency_amount'] = $currency['amount'];
        $result['currency_symbol'] = $currency['currency_symbol'];
        return response()->json($result, 200);
    }

    public function recruiting_pay(Request $request)
    {
        $recruiting = Recruiting::find(1);

        $current_user_id = Auth::user()->id;
        $payment_sum = $recruiting['price'];
        $send_money = $recruiting['send_money'];
        $max_iteration = $recruiting['max_iteration'];
        $excess = $payment_sum;
        $admin_id = $recruiting['admin_id'];
        if(isset($current_user_id)){
            if(Auth::user()->current_status_id < 2){
                switch ($request->pt_id) {
                    case 1:
                    if(Auth::user()->main_wallet >= $payment_sum){
                        $currentUser = User::find($current_user_id);
                        $currentUser->main_wallet -= $payment_sum;
                        $currentUser->current_status_id = 2;
                        $currentUser->maximal_status_id = 2;
                        $currentUser->save();
                        $is_paid = true;
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

                if ($is_paid === true) {

                    $n_op = new UserOperation();
                    $n_op->operation_type_id = 2;
                    $n_op->amount = $payment_sum;
                    $n_op->author_id = Auth::user()->id;
                    $n_op->recipient_id = null;
                    $n_op->comment = null;
                    $n_op->save();

                    $my_sponsor_id = Auth::user()->sponsor_id;
                    $next_sponsor_id = array();

                    for ($i=1; $i <= $max_iteration; $i++) { 
                        if($i === 1){
                            $sponsor = User::find($my_sponsor_id);
                        }
                        else{
                            $sponsor_parent_id = $next_sponsor_id[$i-2];
                            $sponsor = User::find($sponsor_parent_id);
                        }

                        if($sponsor->id == $admin_id){
                            $sponsor->main_wallet += $excess;
                            $sponsor->save();

                            $n_op = new UserOperation();
                            $n_op->operation_type_id = 3;
                            $n_op->amount = $excess;
                            $n_op->author_id = Auth::user()->id;
                            $n_op->recipient_id = $sponsor->id;
                            $n_op->comment = null;
                            $n_op->save();

                            $result['status'] = 1;
                            return response()->json($result, 200);
                            die();
                        }
                        else{
                            $sponsor->main_wallet += $send_money;
                            $sponsor->save();
                            $excess -= $send_money;
                            array_push($next_sponsor_id, $sponsor->sponsor_id);

                            $n_op = new UserOperation();
                            $n_op->operation_type_id = 3;
                            $n_op->amount = $send_money;
                            $n_op->author_id = Auth::user()->id;
                            $n_op->recipient_id = $sponsor->id;
                            $n_op->comment = null;
                            $n_op->save();
                        }
                        
                        if($i === $max_iteration && $sponsor->id != $admin_id){
                         $admin = User::find($admin_id);
                         $admin->main_wallet += $excess;
                         $admin->save();

                         $n_op = new UserOperation();
                         $n_op->operation_type_id = 3;
                         $n_op->amount = $excess;
                         $n_op->author_id = Auth::user()->id;
                         $n_op->recipient_id = $admin_id;
                         $n_op->comment = null;
                         $n_op->save();
                     }
                 }
             }

             $result['status'] = 1;
             return response()->json($result, 200);
         }
     }
 }

 public function independent(Request $request){
    $independent_options = Independent::find(1);
    $total_result = array();

    $users = User::get()->where('current_status_id','>', 1);

    foreach ($users as $user) {
        $personal_turnover = 0;
        $pgv = 0;
        $award_amount = 0;

        get_pt_ic($user->id, $personal_turnover, $invite_count);

        if($personal_turnover > 0){
            p_g_v($user->id, $pgv);
            $pgv = $pgv + $personal_turnover;
            if($pgv >= $independent_options->each_pv){
                $award_amount = floor($pgv / $independent_options->each_pv) * (($independent_options->each_pv / 100) * $independent_options->kickback);
                set_b_s_o($user->id, $award_amount, $user->current_status_id, 7);
            }
        }
        
        array_push($total_result, array(
            'id' => $user->id,
            'name' => $user->name,
            'sponsor_id' => $user->sponsor_id,
            'personal_group_volume' => $pgv,
            'award_amount' => $award_amount
        ));
    }
    return response()->json($total_result, 200);
}

public function retail(Request $request){
    $retail_options = Retail::find(1);
    $total_result = array();

    $users = User::get()->where('current_status_id','>', 1);

    foreach ($users as $user) {
        $personal_turnover = 0;
        $child_turnover = 0;
        $award_amount = 0;

        // ЛО и кол-во приглашении
        get_pt_ic($user->id, $personal_turnover, $invite_count);

        if($personal_turnover >= $retail_options->sponsor_cumulative){
            recursiveRetailTree($retail_options, $user->id, $child_turnover);
            $all_turnover = $personal_turnover + $child_turnover;
            $award_amount = ($all_turnover / 100) * $retail_options['kickback'];
            set_b_s_o($user->id, $award_amount, $user->current_status_id, 6);
        }

        array_push($total_result, array(
            'id' => $user->id,
            'name' => $user->name,
            'sponsor_id' => $user->sponsor_id,
            'personal_turnover' => $personal_turnover,
            'child_turnover' => $child_turnover,
            'award' => $award_amount,
        ));
    }
    return response()->json($total_result, 200);
}

public function structure_building(Request $request){
    $total_result = array();

    $users = User::get()->where('current_status_id','>', 1);

    foreach ($users as $user) {
        $personal_turnover = 0;
        $group_turnover = 0;
        $invite_count = 0;
        $award_amount = 0;

        // ЛО и кол-во приглашении
        get_pt_ic($user->id, $personal_turnover, $invite_count);

        // ГО
        g_t($user->id, $group_turnover);

        $group_turnover = $group_turnover + $personal_turnover;

        //Найти в таблице присваиваемый статус исходя из полученных данных
        $findCondition = StructureBuilding::where('group_turnover', '<=', $group_turnover)
        ->where('invite_count', '<=', $invite_count)
        ->where('personal_turnover', '<=', $personal_turnover)
        ->orderBy('status_id', 'desc')
        ->first();

        if(isset($findCondition->status_id)){
            $new_status_id = $findCondition->status_id;
            $award_amount += (($group_turnover * $findCondition->kickback) / 100);
            set_b_s_o($user->id, $award_amount, $new_status_id, 5);
        }
        else{
            $new_status_id = 2;
        }

        $old_status = StatusType::find($user->current_status_id);
        $new_status = StatusType::find($new_status_id);

        array_push($total_result, array(
            'id' => $user->id,
            'name' => $user->name,
            'sponsor_id' => $user->sponsor_id,
            'personal_turnover' => $personal_turnover,
            'group_turnover' => $group_turnover,
            'invite_count' => $invite_count,
            'old_status' => $old_status->name,
            'new_status' => $new_status->name,
            'award' => $award_amount
        ));
    }
    return response()->json($total_result, 200);
}

public function mentor(Request $request){
    $total_result = array();

    $max_level = Mentor::max('tl_in_depth');
    $min_status_id = StructureBuilding::max('status_id');

    $users = User::get()->where('current_status_id','>=', $min_status_id);

    foreach ($users as $user) {
        $personal_turnover = 0;
        $p_g_v = 0;
        $all_tl_p_g_v = 0;
        $invite_count = 0;
        $count_depth = 0;
        $award_amount = 0;
        $depths = array();

            // ЛО
        get_pt_ic($user->id, $personal_turnover, $invite_count);

            // ОЛГ
        p_g_v($user->id, $p_g_v);
        $p_g_v = $p_g_v + $personal_turnover;

            // Создание ветвей
        for ($l = 0; $l < $max_level; $l++) {
            array_push($depths, array());
            if($l === 0){
                search_depth($user->id, $l, $depths);
            }
            else{
                foreach ($depths[$l-1] as $key => $teamlead) {
                    search_depth($depths[$l-1][$key], $l, $depths);
                }
            }
        }

        for ($j=0; $j < count($depths); $j++) { 
            if(count($depths[$j])){
                $count_depth++;
            }
        }

        $findCondition = Mentor::where('personal_turnover', '<=', $personal_turnover)
        ->where('personal_group_volume', '<=', $p_g_v)
        ->where('invite_count', '<=', $invite_count)
        ->where('count_teamlead_in_the_first_line', '<=', count($depths[0]))
        ->where('tl_in_depth', '<=', $count_depth)
        ->orderBy('status_id', 'desc')
        ->first();

        if(isset($findCondition->status_id)){

            for ($c=0; $c < $findCondition->tl_in_depth; $c++) { 
                foreach ($depths[$c] as $key => $teamlead) {
                    $tl_p_t = 0;
                    $tl_i_c = 0;
                    $tl_p_g_v = 0;
                    get_pt_ic($teamlead, $tl_p_t, $tl_i_c);
                    p_g_v($teamlead, $tl_p_g_v);
                    $all_tl_p_g_v += ($tl_p_t + $tl_p_g_v);
                }
            }

            $award_amount = ($all_tl_p_g_v / 100) * $findCondition->kickback;
            $new_status_id = $findCondition->status_id;
            set_b_s_o($user->id, $award_amount, $new_status_id, 8);
        }
        else{
            $new_status_id = $user->current_status_id;
        }

        $old_status = StatusType::find($user->current_status_id);
        $new_status = StatusType::find($new_status_id);


        array_push($total_result, array(
            'id' => $user->id,
            'fio' => $user->last_name,
            'sponsor_id' => $user->sponsor_id,
            'invite_count' => $invite_count,
            'personal_turnover' => $personal_turnover,
            'personal_group_volume' => $p_g_v,
            'all_tl_p_g_v' => $all_tl_p_g_v,
            'old_status' => $old_status->name,
            'new_status' => $new_status->name,
            'award_amount' => $award_amount,
            'depths' => $depths
        ));
    }
    return response()->json($total_result, 200);
}


public function leader_ship(Request $request){
    $total_result = array();
    $min_status_id = StructureBuilding::max('status_id');
    $l_s_min_status_id = LeaderShip::min('status_id');
    $max_level = LeaderShip::max('count_month');

    function f_l($user, $prev_month_from, $prev_month_to, $level, &$tl_depth, &$dd_depth, &$statuses) {

        $personal_turnover = 0;
        $p_g_v = 0;
        $invite_count = 0;

            // ЛО (для лидерской премии)
        get_pt_ic_ls($user->id, $personal_turnover, $invite_count, $prev_month_from, $prev_month_to);

            // ОЛГ (для лидерской премии)
        p_g_v_ls($user->id, $p_g_v, $prev_month_from, $prev_month_to);
        $p_g_v = $p_g_v + $personal_turnover;

        $childs = User::where('sponsor_id', '=', $user->id)
        ->where('current_status_id', '>', 1)
        ->get();

        if(count($childs) > 0){
            foreach ($childs as $child) {
                $child_p_t = 0;
                $child_g_t = 0;
                $child_i_c = 0;

                // ЛО и кол-во приглашении потомка (для лидерской премии)
                get_pt_ic_ls($child->id, $child_p_t, $child_i_c, $prev_month_from, $prev_month_to);

                // ГО потомка (для лидерской премии)
                g_t_ls($child->id, $child_g_t, $prev_month_from, $prev_month_to);

                $child_g_t = $child_g_t + $child_p_t;

                //Найти в таблице присваиваемый статус исходя из полученных данных
                $findCondition = StructureBuilding::where('group_turnover', '<=', $child_g_t)
                ->where('invite_count', '<=', $child_i_c)
                ->where('personal_turnover', '<=', $child_p_t)
                ->orderBy('status_id', 'desc')
                ->first();

                if(isset($findCondition->status_id)){
                    if($findCondition->status_id >= 8 && $findCondition->status_id <= 12){
                        array_push($tl_depth[$level], $child->id);
                    }  
                    elseif($findCondition->status_id == 13){
                        array_push($dd_depth[$level], $child->id);
                    }
                }
            }
        }

        $findCondition_ls_tl = LeaderShip::where('personal_turnover', '<=', $personal_turnover)
        ->where('personal_group_turnover', '<=', $p_g_v)
        ->where('count_tl_f_l', '<=', count($tl_depth[$level]))
        ->where('count_dd_f_l', 0)
        ->orderBy('status_id', 'desc')
        ->first();

        $findCondition_ls_dd = LeaderShip::where('personal_turnover', '<=', $personal_turnover)
        ->where('personal_group_turnover', '<=', $p_g_v)
        ->where('count_dd_f_l', '<=', count($dd_depth[$level]))
        ->where('count_tl_f_l', 0)
        ->orderBy('status_id', 'desc')
        ->first();

        $new_status_id = $user->maximal_status_id;

        if(isset($findCondition_ls_tl->status_id)){
            $new_status_id = $findCondition_ls_tl->status_id;
        }

        if(isset($findCondition_ls_dd->status_id)){
            $new_status_id = $findCondition_ls_dd->status_id;
        }

        array_push($statuses, $new_status_id);
        
    }

    $users = User::get()->where('current_status_id','>=', $min_status_id);

    foreach ($users as $user) {
        $count_depth = 0;
        $tl_depth = array();
        $dd_depth = array();
        $statuses = array();
        $maximal_status_id = $user->current_status_id;

            // Создание ветвей месяцев
        for ($l = 0; $l < $max_level; $l++) {
            array_push($tl_depth, array());
            array_push($dd_depth, array());
            $prev_month = $l + 2;
            $prev_month_from = date("Y-m-01 00:00:00", strtotime('-'.$prev_month.' month'));
            $prev_month_to = date("Y-m-t 23:59:00", strtotime('-'.$prev_month.' month'));
            f_l($user, $prev_month_from, $prev_month_to, $l, $tl_depth, $dd_depth, $statuses);
        }

        if($statuses[0] >= $l_s_min_status_id){
            $findCondition = LeaderShip::where('status_id', $statuses[0])->first();
            $maximal_status_id = $findCondition->status_id;

            for ($s = 0; $s < $findCondition->count_month; $s++) {
                $maximal_status_id = $statuses[$s];
            }
        }

        if($maximal_status_id >= $l_s_min_status_id){
            $save_status = User::find($user->id);
            $save_status->current_status_id = $maximal_status_id;
            if($maximal_status_id > $user->maximal_status_id){
                $save_status->maximal_status_id = $maximal_status_id;
            }
            $save_status->save();
        }
    }
    

    $leaders = User::get()->where('maximal_status_id','>=', $l_s_min_status_id);

    function child_g_t($leader_id, &$group_turnover, $maximal_status_id){
        $childs = User::where('sponsor_id', '=', $leader_id)
        ->whereBetween('maximal_status_id',[2, $maximal_status_id])
        ->get();

        foreach ($childs as $child) {
            $child_p_t = 0;
            $child_i_c = 0;
            get_pt_ic($child->id, $child_p_t, $child_i_c);
            $group_turnover += $child_p_t;
            child_g_t($child->id, $group_turnover, $maximal_status_id);
        }
    }

    foreach ($leaders as $leader) {
        $personal_turnover = 0;
        $group_turnover = 0;
        $invite_count = 0;
        $award_amount = 0;

        // ЛО и кол-во приглашении
        get_pt_ic($leader->id, $personal_turnover, $invite_count);

        // ГО
        child_g_t($leader->id, $group_turnover, $leader->maximal_status_id - 1);

        $group_turnover = $group_turnover + $personal_turnover;

        $findCondition = LeaderShip::where('status_id', $leader->maximal_status_id)->first();

        $award_amount += (($group_turnover * $findCondition->kickback) / 100);

      // set_b_s_o($leader->id, $award_amount, $user->current_status_id, 6);

        array_push($total_result, array(
            'id' => $leader->id,
            'name' => $leader->name,
            'sponsor_id' => $leader->sponsor_id,
            'status' => $leader->maximal_status_id,
            'award_amount' => $award_amount
        ));
    }
    return response()->json($total_result, 200);
}
}