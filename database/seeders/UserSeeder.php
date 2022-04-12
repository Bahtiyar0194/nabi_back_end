<?php

namespace Database\Seeders;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        function createUser($faker, $sponsor_id, $status_id){
            $rand_num = rand(1,100);

            $user = new User();
            $user->name = $faker->name;
            $user->last_name = $faker->lastName;
            $user->sponsor_id = $sponsor_id;
            $user->phone = $faker->phoneNumber;
            $user->current_status_id = $status_id;
            $user->maximal_status_id = $status_id;
            $user->email_verified_at = now();
            $user->email = $rand_num.$faker->email;
            $user->password = bcrypt('Aa12345');
            $user->remember_token = Str::random(10);
            $user->created_at = now();
            $user->updated_at = now();
            $user->save();

            $role = new UserRole();
            $role->user_id = $user->id;
            $role->role_id = 1;
            $role->save();
        }

        $j = 1000;
        for ($i=1; $i <= $j; $i++) { 
            if($i == 1){
                $sponsor_id = null;
                $status_id = 2;
                createUser($faker, $sponsor_id, $status_id);
            }
            else{
                $sponsor_id = rand(1, $i-1);

                if($i <= 3){
                    createUser($faker, $sponsor_id, 2);
                }
                else{
                    $sponsor = User::find($sponsor_id);
                    if(isset($sponsor)){
                        if($sponsor->current_status_id == 2){
                            $status_id = 2;
                            createUser($faker, $sponsor_id, $status_id);
                        }
                    }
                }
            }
        }
    }
}
