<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\UserRole;
use Validator;


class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct(Request $request) {
        $this->middleware('auth:api', ['except' => ['login', 'register', 'get_sponsor']]);
        app()->setLocale($request->header('Lang'));
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request){
    	$validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (!$token = auth()->attempt($validator->validated())) {
            return response()->json(['error' => trans('auth.failed')], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'last_name' => 'required|string|between:2,100',
            'sponsor_id' => 'numeric',
            'email' => 'required|string|email|max:100|unique:users',
            'phone' => 'required|string|max:20|unique:users',
            'password' => 'required|string|confirmed|min:6',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 422);
        }

        $user = User::create(array_merge(
            $validator->validated(),
            ['password' => bcrypt($request->password)]
        ));

        $role = new UserRole();
        $role->user_id = $user->id;
        $role->save();

        return response()->json([
            'message' => 'success',
        ], 201);
    }

    public function get_sponsor(Request $request) {

        $sponsor = User::where('id','=', $request->sponsor_id)->first();

        if(!$sponsor){
            return response()->json([
                'status' => 0,
                'message' => trans('auth.sponsor_not_found')
            ], 200);
        }
        elseif($sponsor['current_status_id'] <= 1){
            return response()->json([
                'status' => 0,
                'message' => trans('auth.sponsor_not_partner')
            ], 200);
        }
        else{
            return response()->json([
                'status' => 1,
                'message' => trans('auth.your_sponsor').' '.$sponsor['last_name'].' '.$sponsor['name']
            ], 200);
        }
    }


    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout() {
        auth()->logout();

        return response()->json(['message' => 'success']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile() {
        return response()->json(auth()->user());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL()
        ]);
    }

}