<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\StatusType;
use Illuminate\Support\Facades\Auth;

class StructureController extends Controller
{
    public function __construct(Request $request) {
        app()->setLocale($request->header('Lang'));
    }

    public function index(Request $request)
    {
        $development = true;

        $users = array();
        $current_user = Auth::user();
        $current_status = StatusType::find($current_user->current_status_id);

        $i_c = 0;
        $p_t = 0;
        $g_t = 0;
        $p_g_v = 0;

        if($development === true){
            get_pt_ic($current_user->id, $p_t, $i_c);
            if($current_user->current_status_id > 1){
                g_t($current_user->id, $g_t);
                p_g_v($current_user->id, $p_g_v);    
            }
        }

        array_push($users, array(
            'nodeId' => $current_user->id, 
            'parentNodeId' => null, 
            'fio' => $current_user->name, 
            'status' => $current_status->name, 
            'p_t' => round($p_t, 2), 
            'g_t' => round($g_t + $p_t, 2), 
            'p_g_v' => round($p_g_v + $p_t, 2)
        ));

        function get_childs($parent_id, &$users, $development){

            $first_line_childs = User::where('sponsor_id', '=', $parent_id)
            ->get();

            if(count($first_line_childs) > 0){
                foreach($first_line_childs as $line_child){
                    $current_status = StatusType::find($line_child->current_status_id);

                    $i_c = 0;
                    $p_t = 0;
                    $g_t = 0;
                    $p_g_v = 0;

                    if($development === true){
                        get_pt_ic($line_child->id, $p_t, $i_c);
                        if($line_child->current_status_id > 1){
                            g_t($line_child->id, $g_t);
                            p_g_v($line_child->id, $p_g_v);    
                        }
                    }  

                    array_push($users, array(
                        'nodeId' => $line_child->id, 
                        'parentNodeId' => $parent_id, 
                        'fio' => $line_child->last_name, 
                        'status' => $current_status->name, 
                        'p_t' => round($p_t, 2), 
                        'g_t' => round($g_t + $p_t, 2), 
                        'p_g_v' => round($p_g_v + $p_t, 2)
                    ));
                    get_childs($line_child->id, $users, $development);
                }
            }
        }

        get_childs($current_user->id, $users, $development);

        return response()->json($users, 200);
    }
}
