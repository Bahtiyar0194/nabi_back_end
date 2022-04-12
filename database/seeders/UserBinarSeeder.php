<?php

namespace Database\Seeders;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class UserBinarSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        function createUser($faker, $sponsor_id, $level){
            $childs_count = 15;
            $rand_num = rand(1,100);

            $user = new User();
            $user->name = $faker->name;
            $user->last_name = $faker->lastName;
            $user->sponsor_id = $sponsor_id;
            $user->phone = $faker->phoneNumber;
            $user->current_status_id = 2;
            $user->maximal_status_id = 2;
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

            $level - 1;
            
            if($level > 1){
                for ($i=0; $i < $childs_count; $i++) { 
                    createUser($faker, $user->id, $level);
                }
            }
        }

        $level = 3;
        $childs_count = 20;
        $rand_num = rand(1,100);

        $user = new User();
        $user->name = $faker->name;
        $user->last_name = $faker->lastName;
        $user->sponsor_id = null;
        $user->phone = $faker->phoneNumber;
        $user->current_status_id = 2;
        $user->maximal_status_id = 2;
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

        for ($i=0; $i < $childs_count; $i++) { 
            createUser($faker, $user->id, $level);
        }      
    }
}
